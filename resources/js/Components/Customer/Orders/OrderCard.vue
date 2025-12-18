<script setup>
import { computed } from 'vue'
import { useOrderHistory } from '../../../composables/useOrderHistory.js'

const props = defineProps({
    order: {
        type: Object,
        required: true
    },
    isCancelling: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['cancel', 'reorder'])

const { getStatusInfo, formatPrice, formatDate } = useOrderHistory()

const statusInfo = computed(() => getStatusInfo(props.order.status))

const canReorder = computed(() => {
    return ['completed', 'cancelled'].includes(props.order.status)
})

const itemsSummary = computed(() => {
    const items = props.order.items || []
    const totalItems = items.reduce((sum, item) => sum + (item.quantity || 1), 0)
    const itemNames = items.slice(0, 2).map(item => item.name).join(', ')
    const remaining = items.length - 2
    
    if (remaining > 0) {
        return `${itemNames} +${remaining} more`
    }
    return itemNames
})

const statusColorClasses = computed(() => {
    const colorMap = {
        blue: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        indigo: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        purple: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        orange: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        green: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        teal: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
        gray: 'bg-surface-100 text-surface-600 dark:bg-surface-800 dark:text-surface-400',
        red: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
    }
    return colorMap[statusInfo.value.color] || colorMap.gray
})

const handleCancel = () => {
    emit('cancel', props.order)
}

const handleReorder = () => {
    emit('reorder', props.order)
}
</script>

<template>
    <div class="bg-white dark:bg-surface-800 rounded-xl border border-surface-200 dark:border-surface-700 overflow-hidden hover:border-surface-300 dark:hover:border-surface-600 transition-colors">
        <div class="p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-semibold text-surface-900 dark:text-white">
                            {{ order.order_number }}
                        </span>
                        <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', statusColorClasses]">
                            <i :class="[statusInfo.icon, 'mr-1']"></i>
                            {{ statusInfo.text }}
                        </span>
                    </div>
                    <p class="text-sm text-surface-500 dark:text-surface-400 truncate">
                        {{ itemsSummary }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-surface-900 dark:text-white">
                        {{ formatPrice(order.total_formatted) }}
                    </p>
                    <p class="text-xs text-surface-500 dark:text-surface-400">
                        {{ formatDate(order.created_at) }}
                    </p>
                </div>
            </div>

            <div class="mt-3 pt-3 border-t border-surface-100 dark:border-surface-700">
                <div class="flex flex-wrap gap-2">
                    <div class="flex items-center gap-1.5 text-xs text-surface-500 dark:text-surface-400">
                        <i class="fas fa-utensils"></i>
                        <span>{{ order.table_number || 'Table 1' }}</span>
                    </div>
                    <div v-if="order.payment_method" class="flex items-center gap-1.5 text-xs text-surface-500 dark:text-surface-400">
                        <i class="fas fa-credit-card"></i>
                        <span class="capitalize">{{ order.payment_method }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-surface-500 dark:text-surface-400">
                        <i class="fas fa-box"></i>
                        <span>{{ order.items?.length || 0 }} items</span>
                    </div>
                </div>
            </div>

            <div v-if="order.items && order.items.length > 0" class="mt-3 pt-3 border-t border-surface-100 dark:border-surface-700">
                <div class="space-y-1.5">
                    <div 
                        v-for="(item, index) in order.items.slice(0, 3)" 
                        :key="index"
                        class="flex items-center justify-between text-sm"
                    >
                        <span class="text-surface-600 dark:text-surface-400">
                            {{ item.quantity }}x {{ item.name }}
                        </span>
                        <span class="text-surface-500 dark:text-surface-500">
                            {{ formatPrice(item.price * item.quantity) }}
                        </span>
                    </div>
                    <div v-if="order.items.length > 3" class="text-xs text-surface-400 dark:text-surface-500">
                        +{{ order.items.length - 3 }} more items
                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 py-3 bg-surface-50 dark:bg-surface-800/50 border-t border-surface-100 dark:border-surface-700 flex gap-2">
            <button
                v-if="order.can_cancel"
                @click="handleCancel"
                :disabled="isCancelling"
                class="flex-1 px-3 py-2 rounded-lg border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-50"
            >
                <i class="fas fa-times mr-1.5"></i>
                <span v-if="isCancelling">Cancelling...</span>
                <span v-else>Cancel Order</span>
            </button>
            <button
                v-if="canReorder"
                @click="handleReorder"
                class="flex-1 px-3 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary-600 transition-colors"
            >
                <i class="fas fa-redo mr-1.5"></i>
                Order Again
            </button>
            <button
                v-if="!order.can_cancel && !canReorder"
                class="flex-1 px-3 py-2 rounded-lg bg-surface-100 dark:bg-surface-700 text-surface-600 dark:text-surface-400 text-sm font-medium cursor-default"
            >
                <i :class="[statusInfo.icon, 'mr-1.5']"></i>
                {{ statusInfo.text }}
            </button>
        </div>
    </div>
</template>
