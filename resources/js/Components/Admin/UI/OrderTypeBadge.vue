<script setup>
import { computed } from 'vue';

const props = defineProps({
    isGroup: {
        type: Boolean,
        default: false
    },
    tableNumber: {
        type: [String, Number],
        default: ''
    }
});

const badgeConfig = computed(() => {
    if (props.isGroup) {
        return {
            color: 'bg-indigo-50 text-indigo-700 border-indigo-200',
            icon: 'fas fa-users',
            label: 'One Bill'
        };
    }
    return {
        color: 'bg-slate-50 text-slate-600 border-slate-200',
        icon: 'fas fa-user',
        label: 'Individual'
    };
});
</script>

<template>
    <div class="flex items-center gap-2">
        <span class="font-bold text-gray-800">{{ tableNumber }}</span>
        <span :class="['px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider', badgeConfig.color]">
            <i :class="[badgeConfig.icon, 'mr-1']"></i>
            {{ badgeConfig.label }}
        </span>
    </div>
</template>
