<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import Modal from '../../Shared/Overlay/Modal.vue'
import OrderCard from './OrderCard.vue'
import { useOrderHistory } from '../../../composables/useOrderHistory.js'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close', 'reorder-success'])

const {
    orders,
    activeOrders,
    completedOrders,
    loading,
    error,
    fetchOrders,
    cancelOrder,
    reorderItems,
    getStatusInfo,
    formatPrice,
    formatDate
} = useOrderHistory()

const activeTab = ref('active')
const cancellingOrderId = ref(null)
const showCancelConfirm = ref(false)
const orderToCancel = ref(null)

const displayedOrders = computed(() => {
    return activeTab.value === 'active' ? activeOrders.value : completedOrders.value
})

watch(() => props.show, (newVal) => {
    if (newVal) {
        fetchOrders()
    }
})

onMounted(() => {
    if (props.show) {
        fetchOrders()
    }
})

const handleClose = () => {
    emit('close')
}

const handleCancelClick = (order) => {
    orderToCancel.value = order
    showCancelConfirm.value = true
}

const confirmCancel = async () => {
    if (!orderToCancel.value) return
    
    cancellingOrderId.value = orderToCancel.value.id
    const result = await cancelOrder(orderToCancel.value.id)
    cancellingOrderId.value = null
    showCancelConfirm.value = false
    orderToCancel.value = null
    
    if (!result.success) {
        alert(result.error)
    }
}

const handleReorder = (order) => {
    const result = reorderItems(order)
    
    if (result.success) {
        emit('reorder-success', result.itemCount)
        handleClose()
    } else {
        alert(result.error)
    }
}
</script>

<template>
    <Modal :show="show" @close="handleClose" max-width="2xl">
        <div class="bg-white dark:bg-surface-900 rounded-xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="p-4 sm:p-6 border-b border-surface-200 dark:border-surface-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-surface-900 dark:text-white">My Orders</h2>
                        <p class="text-sm text-surface-500 dark:text-surface-400 mt-1">View and manage your orders</p>
                    </div>
                    <button
                        @click="handleClose"
                        class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500 transition-colors"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="flex gap-2 mt-4">
                    <button
                        @click="activeTab = 'active'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                            activeTab === 'active'
                                ? 'bg-primary text-white'
                                : 'bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-400 hover:bg-surface-200 dark:hover:bg-surface-700'
                        ]"
                    >
                        Active
                        <span v-if="activeOrders.length > 0" class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full bg-white/20">
                            {{ activeOrders.length }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'history'"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                            activeTab === 'history'
                                ? 'bg-primary text-white'
                                : 'bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-400 hover:bg-surface-200 dark:hover:bg-surface-700'
                        ]"
                    >
                        History
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                <div v-if="loading" class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>

                <div v-else-if="error" class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
                    </div>
                    <p class="text-surface-600 dark:text-surface-400">{{ error }}</p>
                    <button
                        @click="fetchOrders"
                        class="mt-4 px-4 py-2 text-sm text-primary hover:underline"
                    >
                        Try again
                    </button>
                </div>

                <div v-else-if="displayedOrders.length === 0" class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-surface-100 dark:bg-surface-800 flex items-center justify-center">
                        <i class="fas fa-receipt text-2xl text-surface-400"></i>
                    </div>
                    <p class="text-surface-600 dark:text-surface-400">
                        {{ activeTab === 'active' ? 'No active orders' : 'No order history' }}
                    </p>
                    <p class="text-sm text-surface-500 dark:text-surface-500 mt-1">
                        {{ activeTab === 'active' ? 'Your active orders will appear here' : 'Your past orders will appear here' }}
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <OrderCard
                        v-for="order in displayedOrders"
                        :key="order.id"
                        :order="order"
                        :is-cancelling="cancellingOrderId === order.id"
                        @cancel="handleCancelClick"
                        @reorder="handleReorder"
                    />
                </div>
            </div>
        </div>
    </Modal>

    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showCancelConfirm" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="showCancelConfirm = false"></div>
                <div class="relative bg-white dark:bg-surface-800 rounded-xl p-6 max-w-sm w-full shadow-xl">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-xl text-red-500"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-surface-900 dark:text-white mb-2">Cancel Order?</h3>
                        <p class="text-sm text-surface-600 dark:text-surface-400 mb-6">
                            Are you sure you want to cancel this order? This action cannot be undone.
                        </p>
                        <div class="flex gap-3">
                            <button
                                @click="showCancelConfirm = false"
                                class="flex-1 px-4 py-2.5 rounded-lg border border-surface-300 dark:border-surface-600 text-surface-700 dark:text-surface-300 font-medium hover:bg-surface-50 dark:hover:bg-surface-700 transition-colors"
                            >
                                Keep Order
                            </button>
                            <button
                                @click="confirmCancel"
                                :disabled="cancellingOrderId !== null"
                                class="flex-1 px-4 py-2.5 rounded-lg bg-red-500 text-white font-medium hover:bg-red-600 transition-colors disabled:opacity-50"
                            >
                                <span v-if="cancellingOrderId">Cancelling...</span>
                                <span v-else>Yes, Cancel</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
