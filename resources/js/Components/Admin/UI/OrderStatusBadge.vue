<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true
    }
});

const statusConfig = computed(() => {
    switch (props.status) {
        case 'received':
            return { color: 'bg-gray-100 text-gray-700 border-gray-200', icon: 'fas fa-inbox', label: 'Received' };
        case 'confirmed':
            return { color: 'bg-blue-100 text-blue-700 border-blue-200', icon: 'fas fa-check', label: 'Confirmed' };
        case 'queued':
            return { color: 'bg-yellow-100 text-yellow-700 border-yellow-200', icon: 'fas fa-clock', label: 'Queued' };
        case 'preparing': // Kitchen
            return { color: 'bg-orange-100 text-orange-700 border-orange-200', icon: 'fas fa-fire', label: 'Preparing' };
        case 'ready':
            return { color: 'bg-green-100 text-green-700 border-green-200', icon: 'fas fa-bell', label: 'Ready' };
        case 'served':
            return { color: 'bg-teal-100 text-teal-700 border-teal-200', icon: 'fas fa-utensils', label: 'Served' };
        case 'completed':
            return { color: 'bg-purple-100 text-purple-700 border-purple-200', icon: 'fas fa-check-double', label: 'Completed' };
        case 'cancelled':
            return { color: 'bg-red-100 text-red-700 border-red-200', icon: 'fas fa-times', label: 'Cancelled' };
        default:
            return { color: 'bg-gray-100 text-gray-500 border-gray-200', icon: 'fas fa-circle', label: props.status };
    }
});
</script>

<template>
    <span :class="['px-3 py-1 rounded-full text-xs font-semibold border flex items-center gap-2 w-fit', statusConfig.color]">
        <i :class="[statusConfig.icon, 'text-[10px]']"></i>
        {{ statusConfig.label }}
    </span>
</template>
