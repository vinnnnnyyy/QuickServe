<template>
    <Button
        :tag="tag"
        :href="href"
        :variant="isActive ? 'navigation-active' : 'navigation'"
        size="md"
        rounded="xl"
        full-width
        :badge="badge"
        :badge-class="badgeClass"
        class="gap-3 text-left justify-start group relative"
        :aria-current="isActive ? 'page' : null"
        role="link"
        @click="handleClick"
    >
        <span aria-hidden="true"
              class="absolute left-0 top-1/2 -translate-y-1/2 h-6 w-1 rounded-full transition-colors"
              :class="isActive ? 'bg-white/90' : 'bg-transparent group-hover:bg-surface-300'"></span>
        <i :class="['text-xl transition-colors', isActive ? 'text-white' : 'text-surface-500 group-hover:text-surface-700', iconClass]"></i>
        <div class="flex-1">
            <span class="text-button font-semibold block">{{ title }}</span>
            <p v-if="!isMobile" :class="subtitleClasses">{{ subtitle }}</p>
        </div>
    </Button>
</template>

<script setup>
import { computed } from 'vue'
import Button from '../../../Shared/Base/Button.vue'

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
    subtitle: {
        type: String,
    },
    isActive: {
        type: Boolean,
        default: false
    },
    badge: {
        type: [String, Number],
        default: null
    },
    badgeClass: {
        type: String,
        default: null
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

const subtitleClasses = computed(() => {
    return props.isActive 
        ? 'text-xs text-white/90' 
        : 'text-xs text-surface-500'
})

const handleClick = () => {
    emit('category-selected', props.category)
}
</script>
