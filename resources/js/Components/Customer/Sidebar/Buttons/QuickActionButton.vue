<template>
    <Button
        :tag="href ? 'a' : 'button'"
        :href="href"
        variant="ghost"
        size="md"
        rounded="xl"
        full-width
        class="gap-3 text-left justify-start"
    >
        <i :class="['text-xl', iconClass]"></i>
        <div class="flex-1">
            <span class="text-button font-semibold block">{{ title }}</span>
            <p class="text-surface-400 text-xs">{{ subtitle }}</p>
        </div>
    </Button>
</template>

<script setup>
import Button from '../../../Shared/Base/Button.vue'

defineProps({
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
        required: true
    },
    href: {
        type: String,
        default: null
    }
})
</script>