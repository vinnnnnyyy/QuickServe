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
        card: 'bg-gradient-to-br from-primary-50 to-primary-100 border border-primary-200/50',
        icon: 'color: rgb(234 88 12)', // primary-600
        label: 'color: rgb(194 65 12)', // primary-700
        value: 'color: rgb(154 52 18)' // primary-800
    },
    green: {
        card: 'bg-gradient-to-br from-green-50 to-green-100 border border-green-200/50',
        icon: 'color: rgb(22 163 74)', // green-600
        label: 'color: rgb(21 128 61)', // green-700
        value: 'color: rgb(22 101 52)' // green-800
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
