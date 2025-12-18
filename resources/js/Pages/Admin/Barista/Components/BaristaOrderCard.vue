<script setup>
import { computed } from 'vue';
import Button from '@/Components/Shared/Base/Button.vue';

const props = defineProps({
  order: {
    type: Object,
    required: true
  },
  isOverdue: {
    type: Boolean,
    default: false
  },
  timeInStatus: {
    type: String,
    default: 'Just now'
  }
});

const emit = defineEmits(['click', 'action']);

// Helper to determine styling based on status
const statusStyles = computed(() => {
  switch (props.order.status) {
    case 'queued':
      return {
        // System CardWrapper style for border
        border: props.isOverdue ? 'border-red-300 dark:border-red-800' : 'border-[#ec7813]/20 dark:border-[#ec7813]/30', 
        bg: props.isOverdue ? 'bg-red-50 dark:bg-red-900/10' : 'bg-[#f8f7f6] dark:bg-[#221810]',
        accent: 'text-gray-600',
        bar: 'bg-gray-400'
      };
    case 'preparing':
      return {
        border: props.isOverdue ? 'border-orange-300 dark:border-orange-800' : 'border-[#ec7813]/40 dark:border-[#ec7813]/50', // Slightly stronger for active
        bg: props.isOverdue ? 'bg-orange-50 dark:bg-orange-900/10' : 'bg-[#fff7ed] dark:bg-[#2c1d12]/20', 
        accent: 'text-primary-600 dark:text-primary-400',
        bar: 'bg-primary-500'
      };
    case 'ready':
      return {
        border: 'border-green-200 dark:border-green-800', 
        bg: 'bg-green-50 dark:bg-green-900/10',
        accent: 'text-green-600 dark:text-green-400',
        bar: 'bg-green-500'
      };
    default:
      return { border: '', bg: '', accent: '', bar: '' };
  }
});

const primaryActionLabel = computed(() => {
    switch (props.order.status) {
        case 'queued': return 'Start Prep';
        case 'preparing': return 'Ready';
        case 'ready': return 'Complete'; // Or View
        default: return 'View';
    }
});

const primaryActionIcon = computed(() => {
     switch (props.order.status) {
        case 'queued': return 'play_arrow';
        case 'preparing': return 'check';
        case 'ready': return 'done_all';
        default: return 'visibility';
    }
});
</script>

<template>
  <div 
    @click="$emit('click')"
    class="relative flex flex-col w-full rounded-xl border shadow-sm transition-all duration-200 hover:shadow-md cursor-pointer group overflow-hidden"
    :class="[statusStyles.bg, statusStyles.border]"
  >
    <!-- Header Section -->
    <div class="px-5 pt-5 pb-3 flex justify-between items-start">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-base font-medium text-gray-900 dark:text-white leading-tight tracking-tight">
                    #{{ order.orderNumber }}
                </h3>
                <span v-if="isOverdue" class="animate-pulse text-red-600 dark:text-red-400" title="Order is overdue">
                    <span class="material-symbols-outlined text-xl">warning</span>
                </span>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-0.5">
                {{ order.tableNumber }} 
                <span class="text-xs font-normal opacity-75">• {{ order.customerName }}</span>
            </p>
        </div>
        <div class="text-right">
            <div class="text-xs font-bold uppercase tracking-wide mb-1 opacity-90" :class="statusStyles.accent">
                {{ order.status }}
            </div>
            <div class="flex items-center justify-end gap-1 text-xs text-gray-400 dark:text-gray-500 font-medium">
                <span class="material-symbols-outlined text-[14px]">schedule</span>
                {{ timeInStatus }}
            </div>
        </div>
    </div>

    <!-- Divider -->
    <div class="h-px w-full bg-gray-100 dark:bg-gray-700 my-1"></div>

    <!-- Items Section -->
    <div class="px-5 py-3 flex-1 space-y-2.5">
        <div 
            v-for="(item, idx) in order.items.slice(0, 4)" 
            :key="item.id"
            class="flex gap-3 items-start"
        >
            <div class="font-bold text-base text-gray-700 dark:text-gray-200 w-6 text-right shrink-0">
                {{ item.quantity }}<span class="text-xs font-normal opacity-60">x</span>
            </div>
            <div class="flex-1">
                <span class="text-sm font-medium text-gray-800 dark:text-gray-100 block leading-snug">
                    {{ item.name }}
                </span>
                <!-- Customizations -->
                <div v-if="item.isCustomized" class="text-[10px] font-medium text-orange-600 dark:text-orange-400 mt-0.5 uppercase tracking-wide">
                    Customized
                </div>
            </div>
        </div>
        
        <!-- Overflow -->
        <div v-if="order.items.length > 4" class="text-xs text-center text-gray-400 py-1.5 font-medium bg-gray-50 dark:bg-gray-800/50 rounded">
            + {{ order.items.length - 4 }} more items
        </div>

        <!-- Notes -->
        <div v-if="order.notes" class="mt-3 text-xs bg-yellow-50 dark:bg-yellow-900/10 text-yellow-800 dark:text-yellow-200 p-2.5 rounded border border-yellow-100 dark:border-yellow-800/30">
            <span class="font-bold block mb-0.5 text-[10px] uppercase tracking-wide opacity-80">Note:</span> 
            <span class="leading-relaxed">{{ order.notes }}</span>
        </div>
    </div>

    <!-- Actions Footer (Visible on Hover or always on specific states) -->
    <div class="p-3 mt-auto border-t border-gray-100 dark:border-gray-700/50 flex gap-2" >
        <Button 
            variant="ghost" 
            size="sm" 
            class="px-2 text-gray-400 hover:text-gray-600"
            @click.stop="$emit('action', 'view')"
        >
            <span class="material-symbols-outlined text-xl">visibility</span>
        </Button>
        <Button 
            v-if="order.status !== 'ready'"
            variant="primary" 
            size="sm" 
            class="flex-1 font-semibold tracking-wide text-sm"
            :class="{'bg-green-600 hover:bg-green-700 border-green-600 shadow-green-100': order.status === 'preparing'}"
            @click.stop="$emit('action', 'advance')"
        >
            <span class="material-symbols-outlined mr-2 text-lg">{{ primaryActionIcon }}</span>
            {{ primaryActionLabel }}
        </Button>
         <Button 
            v-else
            variant="outline" 
            size="sm" 
            class="flex-1 font-medium"
            title="Customer picked up"
             @click.stop="$emit('click')"
        >
            Details
        </Button>
    </div>

  </div>
</template>
