<script setup>
const props = defineProps({
    width: {
        type: String,
        default: 'full'
    },
    height: {
        type: String,
        default: '4'
    },
    rounded: {
        type: String,
        default: 'md'
    },
    lines: {
        type: Number,
        default: 1
    },
    spacing: {
        type: String,
        default: '2'
    }
})

const widthClass = `w-${props.width}`
const heightClass = `h-${props.height}`
const roundedClass = `rounded-${props.rounded}`
const spacingClass = `space-y-${props.spacing}`
</script>

<template>
    <div v-if="lines === 1" class="animate-pulse">
        <div 
            class="bg-gray-200 dark:bg-gray-700"
            :class="[widthClass, heightClass, roundedClass]"
        ></div>
    </div>
    
    <div v-else class="animate-pulse" :class="spacingClass">
        <div 
            v-for="line in lines"
            :key="line"
            class="bg-gray-200 dark:bg-gray-700"
            :class="[
                line === lines && lines > 1 ? 'w-3/4' : widthClass,
                heightClass, 
                roundedClass
            ]"
        ></div>
    </div>
</template>
