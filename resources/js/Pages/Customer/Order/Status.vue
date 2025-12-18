<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import CustomerLayout from '../../../Layouts/CustomerLayout.vue'
import { useCheckout } from '../../../composables/useCheckout.js'
import { useOrderWorkflow } from '../../../composables/useOrderWorkflow.js'

const { currentOrder, formatCurrency, clearCurrentOrder } = useCheckout()
const { estimateEta, formatEta, getStatusInfo } = useOrderWorkflow()

const loading = ref(false)
const lastUpdated = ref(null)
const pollingError = ref(false)
const liveStatus = ref(null)

const POLLING_INTERVAL = 10000
let pollingTimer = null

const hasCurrentOrder = computed(() => currentOrder.value !== null)
const orderStatus = computed(() => liveStatus.value || currentOrder.value?.status || 'received')
const statusInfo = computed(() => getStatusInfo(orderStatus.value))
const estimatedEta = computed(() => {
    if (!currentOrder.value) return null
    return estimateEta(orderStatus.value, currentOrder.value.createdAt)
})
const formattedEta = computed(() => {
    if (!estimatedEta.value) return ''
    return formatEta(estimatedEta.value)
})

const steps = [
    { id: 'received', label: 'Order Received', icon: 'fa-receipt', description: 'Waiting for confirmation' },
    { id: 'confirmed', label: 'Confirmed', icon: 'fa-check', description: 'Order confirmed by staff' },
    { id: 'queued', label: 'Queued', icon: 'fa-list-ol', description: 'Your order is in the queue' },
    { id: 'processing', label: 'Preparing', icon: 'fa-fire-burner', description: 'Barista is making your order' },
    { id: 'ready', label: 'Ready', icon: 'fa-bell', description: 'Your order is ready for pickup' },
]

const currentStepIndex = computed(() => {
    const statusMap = {
        'received': 0, 'pending': 0,
        'confirmed': 1,
        'queued': 2,
        'in_progress': 3, 'processing': 3, 'preparing': 3,
        'ready': 4, 'served': 4, 'completed': 4
    }
    return statusMap[orderStatus.value] ?? 0
})

const goToMenu = () => {
    if (currentOrder.value?.tableId) {
        router.visit(`/table/${currentOrder.value.tableId}`)
    } else {
        router.visit('/')
    }
}
const goToConfirmation = () => {
    if (currentOrder.value?.id) {
        router.visit(`/order/confirmation/${currentOrder.value.id}`)
    }
}
const clearOrder = () => {
    const tableId = currentOrder.value?.tableId
    clearCurrentOrder()
    if (tableId) {
        router.visit(`/table/${tableId}`)
    } else {
        router.visit('/')
    }
}

const retryPolling = () => {
    pollingError.value = false
    pollOrderStatus()
}

const pollOrderStatus = async () => {
    if (!currentOrder.value?.backendId) return
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const response = await fetch(`/api/orders/${currentOrder.value.backendId}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' }
        })
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        
        const data = await response.json()
        const newStatus = data.status
        if (newStatus && newStatus !== liveStatus.value) {
            liveStatus.value = newStatus
            lastUpdated.value = new Date()
            if (currentOrder.value) {
                currentOrder.value.status = newStatus
                currentOrder.value.updatedAt = new Date().toISOString()
                try { localStorage.setItem('currentOrder', JSON.stringify(currentOrder.value)) } catch (e) {}
            }
        }
        pollingError.value = false
    } catch (error) {
        pollingError.value = true
    }
}

const startPolling = () => {
    if (pollingTimer) return
    pollOrderStatus()
    pollingTimer = setInterval(pollOrderStatus, POLLING_INTERVAL)
}

const stopPolling = () => {
    if (pollingTimer) {
        clearInterval(pollingTimer)
        pollingTimer = null
    }
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    })
}

const formatRelativeTime = (date) => {
    if (!date) return ''
    const seconds = Math.floor((new Date() - date) / 1000)
    if (seconds < 10) return 'just now'
    if (seconds < 60) return `${seconds}s ago`
    const minutes = Math.floor(seconds / 60)
    return `${minutes}m ago`
}

onMounted(() => {
    if (hasCurrentOrder.value && currentOrder.value?.backendId) startPolling()
})

onUnmounted(() => stopPolling())
</script>

<template>
    <Head title="Track Order" />

    <CustomerLayout :show-header="false">
        <div class="min-h-screen bg-white">
            <!-- Header -->
            <div class="bg-white border-b border-gray-100">
                <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button @click="goToMenu" class="p-2 -ml-2 rounded-full hover:bg-gray-100 transition-colors">
                            <i class="fas fa-arrow-left text-lg text-gray-700"></i>
                        </button>
                        <div>
                            <h1 class="text-heading text-xl sm:text-2xl text-gray-900">Track Your Order</h1>
                            <p class="text-gray-500 text-sm">Real-time order status</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
                <!-- No Order State -->
                <div v-if="!hasCurrentOrder" class="text-center py-16">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 sm:p-12 max-w-md mx-auto">
                        <div class="w-20 h-20 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-receipt text-3xl text-primary"></i>
                        </div>
                        <h2 class="text-heading text-xl text-gray-900 mb-2">No Active Order</h2>
                        <p class="text-body text-gray-500 mb-8">You don't have any orders to track right now.</p>
                        <button 
                            @click="goToMenu"
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-primary to-primary-600 text-white px-6 py-3 rounded-xl font-medium hover:shadow-lg hover:shadow-primary/25 transition-all"
                        >
                            <i class="fas fa-utensils"></i>
                            Browse Menu
                        </button>
                    </div>
                </div>

                <!-- Order Tracking -->
                <div v-else class="space-y-4">
                    <!-- Status Card -->
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Order Info Header -->
                        <div class="p-5 sm:p-6 border-b border-gray-100">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-heading text-lg text-gray-900">{{ currentOrder.referenceNumber }}</span>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-orange-50 text-orange-700': currentStepIndex === 0,
                                                'bg-blue-50 text-blue-700': currentStepIndex === 1,
                                                'bg-indigo-50 text-indigo-700': currentStepIndex === 2,
                                                'bg-yellow-50 text-yellow-700': currentStepIndex === 3,
                                                'bg-green-50 text-green-700': currentStepIndex === 4
                                            }">
                                            <span class="w-1.5 h-1.5 rounded-full"
                                                :class="{
                                                    'bg-orange-500 animate-pulse': currentStepIndex === 0,
                                                    'bg-blue-500': currentStepIndex === 1,
                                                    'bg-indigo-500 animate-pulse': currentStepIndex === 2,
                                                    'bg-yellow-500 animate-pulse': currentStepIndex === 3,
                                                    'bg-green-500': currentStepIndex === 4
                                                }"></span>
                                            {{ statusInfo.label }}
                                        </span>
                                    </div>
                                    <p class="text-caption text-gray-500">{{ formatDate(currentOrder.createdAt) }}</p>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="flex items-center gap-1.5 text-green-600">
                                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                        Live
                                    </span>
                                    <span v-if="lastUpdated" class="text-gray-400">{{ formatRelativeTime(lastUpdated) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Tracker -->
                        <div class="p-5 sm:p-6">
                            <!-- Desktop Progress -->
                            <div class="hidden sm:block">
                                <div class="relative flex justify-between">
                                    <!-- Progress Line Background -->
                                    <div class="absolute top-5 left-0 right-0 h-1 bg-gray-100 rounded-full mx-12"></div>
                                    <!-- Progress Line Active -->
                                    <div class="absolute top-5 left-0 h-1 bg-gradient-to-r from-primary to-primary-500 rounded-full mx-12 transition-all duration-500"
                                        :style="{ width: `calc(${(currentStepIndex / (steps.length - 1)) * 100}% - 6rem)` }"></div>
                                    
                                    <div v-for="(step, index) in steps" :key="step.id" class="relative flex flex-col items-center flex-1">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center z-10 transition-all duration-300"
                                            :class="{
                                                'bg-gradient-to-br from-primary to-primary-600 text-white shadow-lg shadow-primary/30': index <= currentStepIndex,
                                                'bg-white border-2 border-gray-200 text-gray-400': index > currentStepIndex
                                            }">
                                            <i v-if="index < currentStepIndex" class="fas fa-check text-sm"></i>
                                            <i v-else class="fas text-sm" :class="step.icon"></i>
                                        </div>
                                        <p class="mt-3 text-sm font-medium text-center"
                                            :class="index <= currentStepIndex ? 'text-gray-900' : 'text-gray-400'">
                                            {{ step.label }}
                                        </p>
                                        <p class="text-xs text-gray-500 text-center mt-0.5">{{ step.description }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Progress -->
                            <div class="sm:hidden space-y-4">
                                <div v-for="(step, index) in steps" :key="step.id" class="flex items-start gap-4">
                                    <div class="relative flex flex-col items-center">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center z-10 transition-all"
                                            :class="{
                                                'bg-gradient-to-br from-primary to-primary-600 text-white shadow-lg shadow-primary/30': index <= currentStepIndex,
                                                'bg-white border-2 border-gray-200 text-gray-400': index > currentStepIndex
                                            }">
                                            <i v-if="index < currentStepIndex" class="fas fa-check text-sm"></i>
                                            <i v-else class="fas text-sm" :class="step.icon"></i>
                                        </div>
                                        <div v-if="index < steps.length - 1" class="w-0.5 h-8 mt-2"
                                            :class="index < currentStepIndex ? 'bg-primary' : 'bg-gray-200'"></div>
                                    </div>
                                    <div class="flex-1 pb-4">
                                        <p class="font-medium" :class="index <= currentStepIndex ? 'text-gray-900' : 'text-gray-400'">
                                            {{ step.label }}
                                        </p>
                                        <p class="text-sm text-gray-500">{{ step.description }}</p>
                                        <p v-if="index === currentStepIndex" class="text-xs text-primary font-medium mt-1">
                                            <i class="fas fa-clock mr-1"></i> In progress...
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ETA Banner -->
                        <div v-if="formattedEta && currentStepIndex < 4" class="px-5 sm:px-6 pb-5 sm:pb-6">
                            <div class="bg-gray-50 rounded-2xl p-4 flex items-center gap-4">
                                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-sm">
                                    <i class="fas fa-clock text-primary text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Estimated Ready Time</p>
                                    <p class="text-heading text-xl text-gray-900">{{ formattedEta }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Ready Banner -->
                        <div v-if="currentStepIndex === 4" class="px-5 sm:px-6 pb-5 sm:pb-6">
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-4 flex items-center gap-4">
                                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center shadow-sm">
                                    <i class="fas fa-check text-white text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-heading text-lg text-green-700">Your order is ready!</p>
                                    <p class="text-sm text-green-600">Please pick up at the counter</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Details Card -->
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 sm:p-6">
                        <h3 class="text-subheading text-gray-900 mb-4">Order Details</h3>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fas fa-peso-sign text-primary"></i>
                                    <span class="text-caption text-gray-500 text-xs uppercase">Total</span>
                                </div>
                                <p class="text-price text-lg text-gray-900">{{ formatCurrency(currentOrder.total) }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fas fa-chair text-primary"></i>
                                    <span class="text-caption text-gray-500 text-xs uppercase">Table</span>
                                </div>
                                <p class="text-price text-lg text-gray-900">{{ currentOrder.tableNumber || 'N/A' }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 col-span-2 sm:col-span-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fas fa-credit-card text-primary"></i>
                                    <span class="text-caption text-gray-500 text-xs uppercase">Payment</span>
                                </div>
                                <p class="text-price text-lg text-gray-900 capitalize">{{ currentOrder.paymentMethod }}</p>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div v-if="currentOrder.items && currentOrder.items.length > 0">
                            <p class="text-caption text-gray-500 text-xs uppercase mb-3">Items ({{ currentOrder.items.length }})</p>
                            <div class="space-y-3">
                                <div v-for="item in currentOrder.items" :key="item.id" 
                                    class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-mug-hot text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ item.name }}</p>
                                            <p v-if="item.customizations?.length" class="text-xs text-gray-500">
                                                {{ item.customizations.map(c => c.name).join(', ') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">{{ formatCurrency(item.price * item.quantity) }}</p>
                                        <p class="text-xs text-gray-500">x{{ item.quantity }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pb-6">
                        <button @click="goToConfirmation"
                            class="flex-1 flex items-center justify-center gap-2 bg-white border border-gray-200 text-gray-700 px-6 py-3.5 rounded-xl font-medium hover:bg-gray-50 transition-colors">
                            <i class="fas fa-receipt"></i>
                            View Receipt
                        </button>
                        <button v-if="currentStepIndex === 4" @click="clearOrder"
                            class="flex-1 flex items-center justify-center gap-2 bg-gradient-to-r from-primary to-primary-600 text-white px-6 py-3.5 rounded-xl font-medium hover:shadow-lg hover:shadow-primary/25 transition-all">
                            <i class="fas fa-plus"></i>
                            Order Again
                        </button>
                    </div>

                    <!-- Error Toast -->
                    <div v-if="pollingError" 
                        class="fixed bottom-20 left-4 right-4 sm:left-auto sm:right-6 sm:w-80 bg-white border border-yellow-200 rounded-xl p-4 shadow-lg z-40">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-yellow-50 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Connection issue</p>
                                <p class="text-xs text-gray-500">Unable to fetch updates</p>
                            </div>
                            <button @click="retryPolling" class="text-primary font-medium text-sm hover:underline">
                                Retry
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
