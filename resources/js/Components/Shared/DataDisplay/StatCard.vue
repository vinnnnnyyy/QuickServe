<!-- StatCard.vue -->

<script setup>
import { computed } from 'vue'

const props = defineProps({
    icon: {
        type: String,
        required: true
    },
    label: {
        type: String,
        required: true
    },
    value: {
        type: String,
        required: true
    },
    theme: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'green', 'blue', 'purple'].includes(value)
    }
})

const themes = {
    primary: {
        card: 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm',
        icon: 'color: #3B82F6', // blue-500
        label: 'color: #6B7280', // gray-500
        value: 'color: #111827' // gray-900
    },
    green: {
        card: 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm',
        icon: 'color: #10B981', // green-500
        label: 'color: #6B7280',
        value: 'color: #111827'
    },
    blue: {
        card: 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm',
        icon: 'color: #3B82F6',
        label: 'color: #6B7280',
        value: 'color: #111827'
    },
    purple: {
        card: 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm',
        icon: 'color: #8B5CF6', // purple-500
        label: 'color: #6B7280',
        value: 'color: #111827'
    }
}

const cardClasses = computed(() =>
    `rounded-2xl p-4 ${themes[props.theme].card}`
)

const iconStyle = computed(() => themes[props.theme].icon)
const labelStyle = computed(() => themes[props.theme].label)
const valueStyle = computed(() => themes[props.theme].value)


</script>
<template>
    <div :class="cardClasses">
        <div class="flex items-center gap-2 mb-2">
            <i :class="`${icon} text-lg`" :style="iconStyle"></i>
            <span class="text-label text-xs" :style="labelStyle">{{ label }}</span>
        </div>
        <p class="text-sm font-bold" :style="valueStyle">{{ value }}</p>
    </div>
</template>
