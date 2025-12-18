<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'add-to-cart'])

const isListening = ref(false)
const voiceTranscript = ref('')
const voiceProcessing = ref(false)

watch(() => props.show, (newVal) => {
  if (!newVal) {
    isListening.value = false
    voiceTranscript.value = ''
    voiceProcessing.value = false
  }
})

const closeModal = () => {
  emit('close')
}

const toggleListening = () => {
  isListening.value = !isListening.value
  if (isListening.value) {
    voiceTranscript.value = ''
  }
}

const handleAddToCart = () => {
  if (voiceTranscript.value) {
    emit('add-to-cart', voiceTranscript.value)
    closeModal()
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-300"
      leave-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
        
        <Transition
          enter-active-class="transition-all duration-300"
          leave-active-class="transition-all duration-200"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          leave-to-class="opacity-0 scale-95 translate-y-4"
        >
          <div v-if="show" class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-primary to-primary/80 px-6 py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-lg font-bold text-white">Voice Order</h3>
                    <p class="text-sm text-white/80">Speak your order</p>
                  </div>
                </div>
                <button @click="closeModal" class="text-white/80 hover:text-white transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <div class="p-6">
              <div class="flex flex-col items-center mb-6">
                <button
                  @click="toggleListening"
                  :class="[
                    'w-24 h-24 rounded-full flex items-center justify-center transition-all duration-300 transform',
                    isListening 
                      ? 'bg-red-500 hover:bg-red-600 scale-110 animate-pulse' 
                      : 'bg-primary hover:bg-primary/90 hover:scale-105'
                  ]"
                >
                  <svg v-if="!isListening" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                  </svg>
                </button>

                <p class="mt-4 text-sm font-medium" :class="isListening ? 'text-red-500' : 'text-gray-500 dark:text-gray-400'">
                  {{ isListening ? 'Listening... Tap to stop' : 'Tap to start speaking' }}
                </p>

                <div v-if="isListening" class="flex items-center gap-1 mt-3">
                  <span v-for="i in 5" :key="i" class="w-1 bg-red-500 rounded-full animate-pulse" :style="{ height: `${12 + Math.random() * 16}px`, animationDelay: `${i * 0.1}s` }"></span>
                </div>
              </div>

              <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 min-h-[100px]">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Your order:</p>
                <p v-if="voiceTranscript" class="text-gray-900 dark:text-white">{{ voiceTranscript }}</p>
                <p v-else class="text-gray-400 dark:text-gray-500 italic">
                  {{ isListening ? 'Speak now...' : 'Your spoken order will appear here' }}
                </p>
              </div>

              <div class="mt-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Try saying:</p>
                <div class="flex flex-wrap gap-2">
                  <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-3 py-1 rounded-full">"One iced latte please"</span>
                  <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-3 py-1 rounded-full">"Add a croissant"</span>
                  <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-3 py-1 rounded-full">"Two cappuccinos"</span>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex gap-3">
              <button
                @click="closeModal"
                class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors font-medium"
              >
                Cancel
              </button>
              <button
                @click="handleAddToCart"
                :disabled="!voiceTranscript"
                :class="[
                  'flex-1 px-4 py-2.5 rounded-xl font-medium transition-colors',
                  voiceTranscript 
                    ? 'bg-primary text-white hover:bg-primary/90' 
                    : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                ]"
              >
                Add to Order
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
