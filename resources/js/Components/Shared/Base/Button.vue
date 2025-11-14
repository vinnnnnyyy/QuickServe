<template>
    <component 
        :is="tag"
        :href="href"
        :type="type"
        :disabled="disabled"
        :class="[
            baseClasses,
            variantClasses,
            sizeClasses,
            roundedClasses,
            {
                'opacity-50 cursor-not-allowed': disabled,
                'w-full': fullWidth,
                'shadow-lg': shadow
            }
        ]"
        v-bind="$attrs"
    >
        <i v-if="iconLeft" :class="['mr-2', iconLeft]"></i>
        <slot />
        <i v-if="iconRight" :class="['ml-2', iconRight]"></i>
        
        <!-- Badge/Counter -->
        <span 
            v-if="badge"
            :class="[
                'ml-2 px-2 py-0.5 rounded-full text-xs font-medium',
                badgeClass || defaultBadgeClass
            ]"
        >
            {{ badge }}
        </span>
    </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    // Component type
    tag: {
        type: String,
        default: 'button',
        validator: (value) => ['button', 'a', 'router-link'].includes(value)
    },
    
    // Button attributes
    type: {
        type: String,
        default: 'button'
    },
    href: String,
    disabled: Boolean,
    
    // Styling
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'outline', 'ghost', 'navigation', 'navigation-active'].includes(value)
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    rounded: {
        type: String,
        default: 'xl',
        validator: (value) => ['sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(value)
    },
    
    // Layout
    fullWidth: Boolean,
    shadow: Boolean,
    
    // Icons and badges
    iconLeft: String,
    iconRight: String,
    badge: [String, Number],
    badgeClass: String
})

const baseClasses = 'inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none text-button'

const variantClasses = computed(() => {
    const variants = {
        primary: 'bg-primary-500 text-white hover:bg-primary-600 hover:shadow-lg shadow-primary-500/20 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
        secondary: 'bg-surface-100 text-surface-700 hover:bg-surface-200 focus:ring-2 focus:ring-surface-300',
        outline: 'border border-surface-200 text-surface-600 hover:bg-surface-50 hover:text-surface-800 focus:ring-2 focus:ring-surface-300',
        ghost: 'text-surface-600 hover:bg-surface-50 hover:text-surface-800 focus:ring-2 focus:ring-surface-300',
        navigation: 'text-surface-600 hover:bg-surface-50 hover:text-surface-800 focus:ring-2 focus:ring-primary-500/30 focus:ring-offset-2',
        'navigation-active': 'bg-primary-500 text-white shadow-lg shadow-primary-500/20 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2'
    }
    return variants[props.variant] || variants.primary
})

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'px-2 py-1.5 text-xs',
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-3 text-sm',
        lg: 'px-6 py-3 text-base',
        xl: 'px-8 py-4 text-lg'
    }
    return sizes[props.size] || sizes.md
})

const roundedClasses = computed(() => {
    const rounded = {
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        xl: 'rounded-xl',
        '2xl': 'rounded-2xl',
        full: 'rounded-full'
    }
    return rounded[props.rounded] || rounded.xl
})

const defaultBadgeClass = computed(() => {
    if (props.variant === 'navigation-active' || props.variant === 'primary') {
        return 'bg-primary-400 text-white'
    }
    return 'bg-surface-100 text-surface-600'
})
</script>