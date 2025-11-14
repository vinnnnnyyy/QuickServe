<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import SidebarLogo from '../Sidebar/SidebarLogo.vue';
import SidebarStats from '../Sidebar/SidebarStats.vue';
import SidebarQuickActions from '../Sidebar/SidebarQuickActions.vue';
import HeaderNavigation from './HeaderNavigation.vue';
const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    categories: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'category-selected']);

const sidebarRef = ref(null);
const isVisible = ref(false);

// Handle backdrop click
const handleBackdropClick = (e) => {
    if (e.target === e.currentTarget) {
        emit('close');
    }
};

// Handle escape key
const handleEscape = (e) => {
    if (e.key === 'Escape' && props.isOpen) {
        emit('close');
    }
};

// Watch for isOpen changes and handle animations
watch(() => props.isOpen, (newValue) => {
    if (newValue) {
        isVisible.value = true;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', handleEscape);
        // Trigger slide-in animation
        setTimeout(() => {
            if (sidebarRef.value) {
                sidebarRef.value.style.transform = 'translateX(0)';
            }
        }, 10);
    } else {
        // Trigger slide-out animation
        if (sidebarRef.value) {
            sidebarRef.value.style.transform = 'translateX(-100%)';
        }
        document.body.style.overflow = '';
        document.removeEventListener('keydown', handleEscape);
        // Hide after animation
        setTimeout(() => {
            isVisible.value = false;
        }, 300);
    }
});

onUnmounted(() => {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <!-- Use Teleport to render directly in body -->
    <Teleport to="body">
        <div v-if="isVisible"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity duration-300 lg:hidden"
            style="z-index: 99999;" @click="handleBackdropClick">

            <!-- Mobile Sidebar -->
            <aside ref="sidebarRef"
                class="fixed left-0 top-0 h-full w-80 max-w-[85vw] bg-white shadow-2xl transform transition-transform duration-300 ease-out overflow-y-auto"
                style="transform: translateX(-100%); z-index: 100000;" @click.stop>

                <!-- Header with close button -->
                <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">Menu</h2>
                    <button @click="$emit('close')" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-xl text-gray-600"></i>
                    </button>
                </div>

                <!-- Sidebar Content -->
                <div class="p-6 space-y-6">
                    <!-- Mobile Search -->
                    <div class="relative">
                        <input type="search" placeholder="Search menu..."
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <SidebarLogo cafe-name="Café Delight" tagline="Best Coffee in Town" />

                    <SidebarStats />
                    <!-- Quick Actions Section -->
                    <SidebarQuickActions />
                </div>
            </aside>
        </div>
    </Teleport>
    <HeaderNavigation 
        :categories="props.categories"
        @category-selected="(c) => emit('category-selected', c)"
    />
</template>