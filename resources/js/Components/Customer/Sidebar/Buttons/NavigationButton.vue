<template>
    <button
        :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-left w-full transition-colors',
            isActive 
                ? 'bg-primary text-white' 
                : 'text-surface-600 hover:bg-surface-50'
        ]"
        :aria-current="isActive ? 'page' : null"
        @click="handleClick"
    >
        <i :class="['text-base w-5 text-center', iconClass]"></i>
        <span class="text-sm font-medium">{{ title }}</span>
    </button>
</template>

<script setup>
const props = defineProps({
    tag: {
        type: String,
        default: 'button'
    },
    href: {
        type: String,
        default: null
    },
    iconClass: {
        type: String,
        required: true
    },
    title: {
        type: String,
        required: true
    },
    isActive: {
        type: Boolean,
        default: false
    },
    category: {
        type: Object,
        required: true
    },
    isMobile: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['category-selected'])

const handleClick = () => {
    emit('category-selected', props.category)
}
</script>
