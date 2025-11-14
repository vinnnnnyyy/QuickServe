<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  icon: {
    type: String,
    default: 'info'
  },
  maxWidth: {
    type: String,
    default: '4xl' // xs, sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl
  },
  closeable: {
    type: Boolean,
    default: true
  },
  showHeader: {
    type: Boolean,
    default: true
  },
  showFooter: {
    type: Boolean,
    default: true
  },
  animationType: {
    type: String,
    default: 'scale', // scale, slide, fade
    validator: (value) => ['scale', 'slide', 'fade'].includes(value)
  }
});

const emit = defineEmits(['close', 'confirm']);

const maxWidthClasses = {
  'xs': 'max-w-xs',
  'sm': 'max-w-sm', 
  'md': 'max-w-md',
  'lg': 'max-w-lg',
  'xl': 'max-w-xl',
  '2xl': 'max-w-2xl',
  '3xl': 'max-w-3xl',
  '4xl': 'max-w-4xl',
  '5xl': 'max-w-5xl',
  '6xl': 'max-w-6xl',
  '7xl': 'max-w-7xl'
};

// Animation states
const isAnimating = ref(false);
const contentVisible = ref(false);

const closeModal = () => {
  if (props.closeable && !isAnimating.value) {
    isAnimating.value = true;
    contentVisible.value = false;
    emit('close');
  }
};

const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    closeModal();
  }
};

// Enhanced animation handling
watch(() => props.show, async (newShow) => {
  if (newShow) {
    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = `${window.innerWidth - document.documentElement.clientWidth}px`;
    isAnimating.value = true;
    
    // Stagger content animation
    await nextTick();
    setTimeout(() => {
      contentVisible.value = true;
      isAnimating.value = false;
    }, 50);
  } else {
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    contentVisible.value = false;
    
    // Wait for animation to complete
    setTimeout(() => {
      isAnimating.value = false;
    }, 300);
  }
});

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
  document.body.style.paddingRight = '';
});
</script>

<template>
  <Teleport to="body">
    <!-- Backdrop Transition -->
    <Transition
      name="modal-backdrop"
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="show"
        class="modal-backdrop fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
      >
        <!-- Modal Container Transition -->
        <Transition
          :name="`modal-${animationType}`"
          enter-active-class="transition-all duration-300 ease-out"
          :enter-from-class="animationType === 'scale' ? 'opacity-0 scale-95 translate-y-4' : 
                           animationType === 'slide' ? 'opacity-0 translate-y-full' : 
                           'opacity-0'"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          :leave-from-class="animationType === 'scale' ? 'opacity-100 scale-100 translate-y-0' : 
                            animationType === 'slide' ? 'opacity-100 translate-y-0' : 
                            'opacity-100'"
          :leave-to-class="animationType === 'scale' ? 'opacity-0 scale-95 translate-y-4' : 
                           animationType === 'slide' ? 'opacity-0 translate-y-full' : 
                           'opacity-0'"
        >
          <div 
            v-if="show"
            :class="maxWidthClasses[maxWidth]"
            class="modal-content bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-2xl w-full max-h-[90vh] overflow-hidden shadow-2xl"
          >
            <!-- Header Slot with Animation -->
            <Transition
              name="modal-header"
              enter-active-class="transition-all duration-400 ease-out delay-100"
              enter-from-class="opacity-0 translate-y-4"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 translate-y-4"
            >
              <div 
                v-if="showHeader && contentVisible"
                class="modal-header sticky top-0 bg-background-light dark:bg-background-dark border-b border-gray-200 dark:border-gray-700 p-6 rounded-t-2xl"
              >
                <slot name="header">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="modal-icon p-2 rounded-lg bg-primary/10 dark:bg-primary/20 transform transition-all duration-300 hover:scale-110">
                        <span class="material-symbols-outlined text-primary text-xl">{{ icon }}</span>
                      </div>
                      <div class="modal-title-container">
                        <h3 class="text-xl font-bold text-black dark:text-white transition-colors duration-200">{{ title }}</h3>
                        <p v-if="subtitle" class="text-sm text-black/60 dark:text-white/60 transition-colors duration-200">{{ subtitle }}</p>
                      </div>
                    </div>
                    <button
                      v-if="closeable"
                      @click="closeModal"
                      class="modal-close-btn p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 hover:rotate-90 transition-all duration-200 hover:scale-110"
                    >
                      <span class="material-symbols-outlined text-gray-500 dark:text-gray-400">close</span>
                    </button>
                  </div>
                </slot>
              </div>
            </Transition>

            <!-- Scrollable Content Container -->
            <div class="modal-scroll-container max-h-[calc(90vh-140px)] overflow-y-auto">
              <!-- Content Slot with Staggered Animation -->
              <Transition
                name="modal-content"
                enter-active-class="transition-all duration-500 ease-out delay-150"
                enter-from-class="opacity-0 translate-y-6"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-6"
              >
                <div v-if="contentVisible" class="modal-body p-6">
                  <slot />
                </div>
              </Transition>
            </div>

            <!-- Footer Slot with Animation -->
            <Transition
              name="modal-footer"
              enter-active-class="transition-all duration-400 ease-out delay-200"
              enter-from-class="opacity-0 translate-y-4"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 translate-y-4"
            >
              <div 
                v-if="showFooter && contentVisible"
                class="modal-footer sticky bottom-0 bg-background-light dark:bg-background-dark border-t border-gray-200 dark:border-gray-700 p-6 rounded-b-2xl"
              >
                <slot name="footer">
                  <div class="flex items-center justify-end gap-3">
                    <button
                      @click="closeModal"
                      class="px-6 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:scale-105 transition-all duration-200"
                    >
                      Close
                    </button>
                  </div>
                </slot>
              </div>
            </Transition>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Modal Backdrop Animations */
.modal-backdrop {
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(8px);
  animation: backdrop-pulse 0.3s ease-out;
}

@keyframes backdrop-pulse {
  0% {
    backdrop-filter: blur(0px);
    background: rgba(0, 0, 0, 0);
  }
  100% {
    backdrop-filter: blur(8px);
    background: rgba(0, 0, 0, 0.5);
  }
}

/* Modal Content Animations */
.modal-content {
  transform-origin: center center;
  animation: modal-entrance 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modal-entrance {
  0% {
    transform: scale(0.9) translateY(20px);
    opacity: 0;
  }
  50% {
    transform: scale(1.02) translateY(-5px);
    opacity: 0.8;
  }
  100% {
    transform: scale(1) translateY(0);
    opacity: 1;
  }
}

/* Hover Effects */
.modal-icon:hover {
  transform: scale(1.1) rotate(5deg);
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-close-btn:hover {
  transform: scale(1.1) rotate(90deg);
  transition: all 0.2s ease-out;
}

/* Smooth Scrolling */
.modal-scroll-container {
  scrollbar-width: thin;
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.modal-scroll-container::-webkit-scrollbar {
  width: 6px;
}

.modal-scroll-container::-webkit-scrollbar-track {
  background: transparent;
}

.modal-scroll-container::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}

.modal-scroll-container::-webkit-scrollbar-thumb:hover {
  background: rgba(156, 163, 175, 0.7);
}

/* Content Stagger Animations */
.modal-body > * {
  animation: content-slide-up 0.4s ease-out forwards;
  opacity: 0;
  transform: translateY(20px);
}

.modal-body > *:nth-child(1) { animation-delay: 0.1s; }
.modal-body > *:nth-child(2) { animation-delay: 0.15s; }
.modal-body > *:nth-child(3) { animation-delay: 0.2s; }
.modal-body > *:nth-child(4) { animation-delay: 0.25s; }
.modal-body > *:nth-child(5) { animation-delay: 0.3s; }

@keyframes content-slide-up {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Button Hover Animations */
.modal-footer button {
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.modal-footer button:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.modal-footer button:active {
  transform: translateY(0);
  transition: all 0.1s ease;
}

/* Loading State Animation */
.modal-content.loading {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}

/* Responsive Animations */
@media (max-width: 640px) {
  .modal-content {
    margin: 1rem;
    max-height: calc(100vh - 2rem);
  }
  
  .modal-scroll-container {
    max-height: calc(100vh - 200px);
  }
  
  /* Simplified animations for mobile */
  .modal-body > * {
    animation-delay: 0s !important;
  }
}

/* Dark mode specific enhancements */
@media (prefers-color-scheme: dark) {
  .modal-backdrop {
    background: rgba(0, 0, 0, 0.7);
  }
  
  .modal-content {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .modal-content,
  .modal-icon,
  .modal-close-btn,
  .modal-body > *,
  .modal-footer button {
    animation: none !important;
    transition: none !important;
  }
  
  .modal-backdrop {
    backdrop-filter: none;
  }
}

/* Focus management */
.modal-content:focus-within {
  outline: 2px solid rgb(236 120 19); /* Use your primary color */
  outline-offset: 2px;
}

/* Glass morphism effect - respects theme colors */
.modal-header,
.modal-footer {
  backdrop-filter: blur(10px);
  /* Use theme colors instead of fixed colors */
}
</style>
