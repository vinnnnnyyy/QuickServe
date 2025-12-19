<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useCheckout } from '../../../composables/useCheckout.js'
import { useCart } from '../../../composables/useCart.js'

const props = defineProps({
    orderId: {
        type: String,
        required: true
    }
})

const { getOrder, formatCurrency } = useCheckout()
const { clearCart } = useCart()

const order = ref(null)
const loading = ref(true)

const loadOrder = () => {
    order.value = getOrder(props.orderId)
    if (!order.value) {
        router.visit('/')
        return
    }
    clearCart()
    loading.value = false
}

const continueOrdering = () => {
    const token = sessionStorage.getItem('authTableToken')
    if (token) {
        router.visit(`/table/${token}`)
        return
    }

    if (order.value?.tableId) {
        router.visit('/')
    } else {
        router.visit('/')
    }
}

const getPaymentMethodDisplay = (method) => {
    const methods = {
        gcash: 'GCash',
        cash: 'Cash Payment',
        card: 'Credit/Debit Card',
        paymaya: 'PayMaya'
    }
    return methods[method] || method
}

const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const itemsCount = computed(() => {
    if (!order.value?.items) return 0
    return order.value.items.reduce((sum, item) => sum + item.quantity, 0)
})

onMounted(() => {
    loadOrder()
})
</script>

<template>
    <Head title="Order Confirmation" />

    <div class="min-h-screen bg-gradient-to-b from-primary-50 to-white">
        <div v-if="loading" class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="w-16 h-16 border-4 border-primary-200 border-t-primary-500 rounded-full animate-spin mx-auto"></div>
                <p class="mt-4 text-surface-500 font-medium">Loading order...</p>
            </div>
        </div>

        <div v-else class="max-w-lg mx-auto px-4 py-8">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-8 text-center">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check text-3xl text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-white mb-1">Order Received!</h1>
                    <p class="text-primary-100 text-sm">We're preparing your order</p>
                </div>

                <div class="px-6 py-5 border-b border-surface-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-surface-400 uppercase tracking-wide font-medium">Order Number</p>
                            <p class="text-lg font-bold text-surface-900 font-mono">{{ order.referenceNumber }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-surface-400 uppercase tracking-wide font-medium">Table</p>
                            <p class="text-lg font-bold text-surface-900">{{ order.tableNumber || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-surface-50 border-b border-surface-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-clock text-primary-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-surface-900">Awaiting Confirmation</p>
                            <p class="text-xs text-surface-500">Staff will confirm your order shortly</p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-surface-900">Order Summary</h2>
                        <span class="text-xs text-surface-500">{{ itemsCount }} item(s)</span>
                    </div>
                    
                    <div class="space-y-3">
                        <div 
                            v-for="(item, index) in order.items"
                            :key="`item-${item.id}-${index}`"
                            class="flex items-start justify-between"
                        >
                            <div class="flex items-start gap-3">
                                <span class="w-6 h-6 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ item.quantity }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-surface-900">{{ item.name }}</p>
                                    <p v-if="item.isCustomized" class="text-xs text-primary-600 mt-0.5">
                                        <i class="fas fa-sliders-h mr-1"></i>Customized
                                    </p>
                                </div>
                            </div>
                            <p class="text-sm font-medium text-surface-700">
                                {{ formatCurrency((item.displayPrice || item.finalPrice || item.price) * item.quantity) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-dashed border-surface-200">
                        <div class="flex items-center justify-between">
                            <span class="text-base font-bold text-surface-900">Total</span>
                            <span class="text-xl font-bold text-primary-600">{{ formatCurrency(order.total) }}</span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-surface-50 border-t border-surface-100">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <p class="text-xs text-surface-400 mb-1">Payment</p>
                            <p class="text-sm font-semibold text-surface-900">{{ getPaymentMethodDisplay(order.paymentMethod) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-surface-400 mb-1">Date</p>
                            <p class="text-sm font-semibold text-surface-900">{{ formatDate(order.createdAt) }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="order.customer?.nickname || order.customer?.notes" class="px-6 py-4 border-t border-surface-100">
                    <div v-if="order.customer.nickname" class="mb-3">
                        <p class="text-xs text-surface-400 mb-1">Name</p>
                        <p class="text-sm font-medium text-surface-900">{{ order.customer.nickname }}</p>
                    </div>
                    <div v-if="order.customer.notes">
                        <p class="text-xs text-surface-400 mb-1">Special Instructions</p>
                        <p class="text-sm text-surface-700 bg-surface-50 rounded-lg p-3">{{ order.customer.notes }}</p>
                    </div>
                </div>

                <div class="px-6 py-5 border-t border-surface-100">
                    <button 
                        @click="continueOrdering"
                        class="w-full bg-primary-500 hover:bg-primary-600 text-white font-semibold py-3.5 px-6 rounded-xl transition-colors duration-200 flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-utensils"></i>
                        Order More
                    </button>
                </div>
            </div>

            <p class="text-center text-xs text-surface-400 mt-6">
                Order ID: {{ order.id }}
            </p>
        </div>
    </div>
</template>
