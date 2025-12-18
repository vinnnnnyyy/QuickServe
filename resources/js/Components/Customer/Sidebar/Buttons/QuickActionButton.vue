<template>
    <component
        :is="href ? 'a' : 'button'"
        :href="href"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-left w-full text-surface-600 hover:bg-surface-50 transition-colors"
    >
        <i :class="['text-base w-5 text-center text-surface-400', iconClass]"></i>
        <span class="text-sm font-medium flex-1">{{ title }}</span>
        <span 
            v-if="badge" 
            class="px-1.5 py-0.5 text-xs font-semibold rounded-full bg-primary text-white min-w-[20px] text-center"
        >
            {{ badge }}
        </span>
    </component>
</template>

<script setup>
defineProps({
    iconClass: {
        type: String,
        required: true
    },
    title: {
        type: String,
        required: true
    },
    href: {
        type: String,
        default: null
    },
    badge: {
        type: [Number, String],
        default: null
    }
})
</script>