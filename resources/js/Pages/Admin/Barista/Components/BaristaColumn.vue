<script setup>
defineProps({
    title: {
        type: String,
        required: true
    },
    count: {
        type: Number,
        default: 0
    },
    icon: {
        type: String,
        default: ''
    },
    countColor: {
        type: String,
        default: 'bg-gray-200 text-gray-700'
    },
    isGrid: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <div class="flex flex-col h-full bg-gray-50/50 dark:bg-gray-900/50 rounded-2xl p-2 md:p-4 border border-gray-100 dark:border-gray-800">
        <!-- Column Header -->
        <div class="flex items-center justify-between mb-4 px-2">
            <h2 class="flex items-center gap-2 font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wide text-sm">
                <span v-if="icon" class="material-symbols-outlined text-lg opacity-70">{{ icon }}</span>
                {{ title }}
            </h2>
            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold', countColor]">
                {{ count }}
            </span>
        </div>

        <!-- Scrollable Content Area -->
        <div class="flex-1 overflow-y-auto min-h-[300px] scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700 px-1 pb-2">
            
            <TransitionGroup 
                name="list" 
                tag="div" 
                :class="isGrid ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4' : 'space-y-4'"
            >
                <slot ></slot>
            </TransitionGroup>

            <!-- Empty State -->
            <div 
                v-if="count === 0" 
                class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-600 border-2 border-dashed border-gray-200 dark:border-gray-700/50 rounded-xl mt-2"
            >
                <span class="material-symbols-outlined text-4xl mb-2 opacity-50">inbox</span>
                <span class="text-sm font-medium">No orders</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.list-move, 
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(30px);
}

.list-leave-active {
  position: absolute;
  width: 100%; /* Important for list mode, might need adjustment for grid */
}
</style>
