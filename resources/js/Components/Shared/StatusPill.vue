<script setup>
import { computed } from 'vue'
import { useOrderWorkflow } from '../../composables/useOrderWorkflow.js'

const props = defineProps({
    status: {
        type: String,
        required: true
    },
    size: {
        type: String,
        default: 'sm',
        validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
    }
})

const { getStatusInfo, getStatusClasses } = useOrderWorkflow()

const statusInfo = computed(() => getStatusInfo(props.status))
const statusClasses = computed(() => getStatusClasses(props.status))

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'px-2 py-1 text-xs',
        sm: 'px-2.5 py-1 text-xs',
        md: 'px-3 py-1.5 text-sm',
        lg: 'px-4 py-2 text-base font-semibold'
    }
    return sizes[props.size]
})
</script>

<template>
    <span 
        class="inline-flex items-center rounded-full font-medium border"
        :class="[statusClasses, sizeClasses]"
    >
        <div 
            class="w-1.5 h-1.5 rounded-full mr-1.5"
            :class="statusInfo.color === 'green' ? 'bg-green-500' : 
                   statusInfo.color === 'blue' ? 'bg-blue-500' :
                   statusInfo.color === 'yellow' ? 'bg-yellow-500' :
                   statusInfo.color === 'purple' ? 'bg-purple-500' :
                   statusInfo.color === 'orange' ? 'bg-orange-500' :
                   statusInfo.color === 'indigo' ? 'bg-indigo-500' :
                   statusInfo.color === 'red' ? 'bg-red-500' : 'bg-gray-500'"
        ></div>
        {{ statusInfo.label }}
    </span>
</template>
