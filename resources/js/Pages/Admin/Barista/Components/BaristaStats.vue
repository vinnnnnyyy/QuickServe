<script setup>
import { computed } from 'vue';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
        queued: 0,
        preparing: 0,
        ready: 0,
        overdue: 0
    })
  }
});

const cards = computed(() => [
  {
    label: 'To Prep',
    value: props.stats.queued,
    icon: 'assignment', // receipt_long
    color: 'text-gray-600 dark:text-gray-300',
    bg: 'bg-white dark:bg-gray-800',
    border: 'border-gray-200 dark:border-gray-700'
  },
  {
    label: 'In Progress',
    value: props.stats.preparing,
    icon: 'blender', 
    color: 'text-primary-600 dark:text-primary-400',
    bg: 'bg-primary-50 dark:bg-primary-900/10',
    border: 'border-primary-200 dark:border-primary-800'
  },
  {
    label: 'Ready',
    value: props.stats.ready,
    icon: 'task_alt',
    color: 'text-green-600 dark:text-green-400',
    bg: 'bg-green-50 dark:bg-green-900/10',
    border: 'border-green-200 dark:border-green-800'
  },
  {
    label: 'Overdue',
    value: props.stats.overdue,
    icon: 'warning',
    color: 'text-red-600 dark:text-red-400',
    bg: 'bg-red-50 dark:bg-red-900/10',
    border: 'border-red-200 dark:border-red-800',
    showIfZero: false
  }
]);
</script>

<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <template v-for="(card, index) in cards" :key="index">
        <div 
            v-if="card.value > 0 || card.showIfZero !== false"
            :class="[
                'flex items-center gap-4 p-4 rounded-xl border shadow-sm transition-all duration-300',
                card.bg,
                card.border
            ]"
        >
            <div :class="['p-3 rounded-full bg-white dark:bg-gray-800 shadow-sm', card.color]">
                <span class="material-symbols-outlined text-2xl block">{{ card.icon }}</span>
            </div>
            <div>
                <div :class="['text-2xl font-bold font-sans leading-none', card.color]">{{ card.value }}</div>
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mt-1">
                    {{ card.label }}
                </div>
            </div>
        </div>
    </template>
  </div>
</template>
