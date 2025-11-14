<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import CustomerLayout from '../../../Layouts/CustomerLayout.vue'
import OrderStatusTracker from '../../../Components/Shared/DataDisplay/OrderStatusTracker.vue'
import StatusPill from '../../../Components/Shared/StatusPill.vue'
import SkeletonBlock from '../../../Components/Shared/SkeletonBlock.vue'
import StatCard from '../../../Components/Shared/DataDisplay/StatCard.vue'
import Button from '../../../Components/Shared/Base/Button.vue'
import { useCheckout } from '../../../composables/useCheckout.js'
import { useOrderWorkflow } from '../../../composables/useOrderWorkflow.js'

// Composables
const { currentOrder, formatCurrency, clearCurrentOrder } = useCheckout()
const { estimateEta, formatEta } = useOrderWorkflow()

// Component state
const loading = ref(false)
const lastUpdated = ref(null)
const pollingError = ref(null)
const liveStatus = ref(null)

// Polling configuration
const POLLING_INTERVAL = 10000 // 10 seconds
let pollingTimer = null

// Computed properties
const hasCurrentOrder = computed(() => currentOrder.value !== null)
const orderStatus = computed(() => liveStatus.value || currentOrder.value?.status || 'received')
const estimatedEta = computed(() => {
    if (!currentOrder.value) return null
    return estimateEta(orderStatus.value, currentOrder.value.createdAt)
})
const formattedEta = computed(() => {
    if (!estimatedEta.value) return ''
    return formatEta(estimatedEta.value)
})

// Methods
const goToMenu = () => {
    router.visit('/')
}

const goToConfirmation = () => {
    if (currentOrder.value?.id) {
        router.visit(`/order/confirmation/${currentOrder.value.id}`)
    }
}

const clearOrder = () => {
    clearCurrentOrder()
    router.visit('/')
}

// Retry polling on error
const retryPolling = () => {
    pollingError.value = null
    pollOrderStatus()
}

// Poll order status from backend
const pollOrderStatus = async () => {
    if (!currentOrder.value?.backendId) {
        console.log('No backend ID available for polling')
        return
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        
        const response = await fetch(`/api/orders/${currentOrder.value.backendId}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            }
        })
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
        }
        
        const data = await response.json()
        console.log('Polling response:', data)
        
        const newStatus = data.status
        if (newStatus && newStatus !== liveStatus.value) {
            console.log(`Status updated from ${liveStatus.value} to ${newStatus}`)
            liveStatus.value = newStatus
            lastUpdated.value = new Date()
            
            // Update localStorage order with new status
            if (currentOrder.value) {
                currentOrder.value.status = newStatus
                currentOrder.value.updatedAt = new Date().toISOString()
                // Save to localStorage
                try {
                    localStorage.setItem('currentOrder', JSON.stringify(currentOrder.value))
                } catch (e) {
                    console.warn('Failed to update localStorage:', e)
                }
            }
        }
        
        // Clear error on success
        pollingError.value = null
        
    } catch (error) {
        console.error('Error polling order status:', error)
        pollingError.value = 'Unable to fetch live updates. Retrying...'
    }
}

// Start polling
const startPolling = () => {
    if (pollingTimer) return
    
    // Poll immediately
    pollOrderStatus()
    
    // Then poll at interval
    pollingTimer = setInterval(pollOrderStatus, POLLING_INTERVAL)
}

// Stop polling
const stopPolling = () => {
    if (pollingTimer) {
        clearInterval(pollingTimer)
        pollingTimer = null
    }
}

// Format date helper
const formatDate = (dateString) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Format relative time for last updated
const formatRelativeTime = (date) => {
    if (!date) return ''
    
    const now = new Date()
    const diff = now - date
    const seconds = Math.floor(diff / 1000)
    
    if (seconds < 10) return 'just now'
    if (seconds < 60) return `${seconds}s ago`
    
    const minutes = Math.floor(seconds / 60)
    if (minutes < 60) return `${minutes}m ago`
    
    const hours = Math.floor(minutes / 60)
    return `${hours}h ago`
}

// Lifecycle
onMounted(() => {
    if (hasCurrentOrder.value && currentOrder.value?.backendId) {
        startPolling()
    }
})

onUnmounted(() => {
    stopPolling()
})
</script>

<template>
    <Head title="Track Order" />

    <CustomerLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 lg:px-8">
            <div class="max-w-7xl mx-auto">

                <!-- Loading State -->
                <div v-if="loading" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-center mb-6">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        </div>
                        <div class="space-y-4">
                            <SkeletonBlock height="6" />
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <SkeletonBlock height="20" />
                                <SkeletonBlock height="20" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Order State -->
                <div v-else-if="!hasCurrentOrder" class="text-center">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 mb-6">
                        <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-clipboard-list text-gray-400 dark:text-gray-500 text-4xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">No Active Order</h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
                            You don't have any active orders to track right now. Start by browsing our menu and placing an order.
                        </p>
                        <Button 
                            @click="goToMenu"
                            variant="primary"
                            size="lg"
                        >
                            <i class="fas fa-utensils mr-2"></i>
                            Browse Menu
                        </Button>
                    </div>
                </div>

                <!-- Order Status -->
                <div v-else>
                    <!-- Desktop Layout -->
                    <div class="hidden lg:block">
                        <div class="grid grid-cols-12 gap-8 h-full">
                            <!-- Left Column - Order Info & Items -->
                            <div class="col-span-5 space-y-6">
                                <!-- Order Header Card -->
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                                    <div class="mb-6">
                                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ currentOrder.referenceNumber }}</h1>
                                        <p class="text-gray-500 dark:text-gray-400">{{ formatDate(currentOrder.createdAt) }}</p>
                                    </div>

                                    <div class="flex items-center justify-between mb-4">
                                        <StatusPill :status="orderStatus" size="lg" />
                                        <div v-if="formattedEta" class="text-right">
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Estimated Ready</p>
                                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formattedEta }}</p>
                                        </div>
                                    </div>

                                    <!-- Live Status Indicator -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2" aria-live="polite">
                                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Live Updates</span>
                                            <span v-if="lastUpdated" class="text-xs text-gray-400 dark:text-gray-500">
                                                • {{ formatRelativeTime(lastUpdated) }}
                                            </span>
                                        </div>
                                        <button
                                            v-if="pollingError"
                                            @click="retryPolling"
                                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium"
                                        >
                                            <i class="fas fa-refresh mr-1"></i>
                                            Retry
                                        </button>
                                    </div>

                                    <!-- Error banner -->
                                    <div 
                                        v-if="pollingError"
                                        class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-sm text-yellow-800 dark:text-yellow-200"
                                    >
                                        <div class="flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            {{ pollingError }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Summary Cards -->
                                <div class="grid grid-cols-1 gap-4">
                                    <StatCard
                                        icon="fas fa-peso-sign"
                                        label="Total Amount"
                                        :value="formatCurrency(currentOrder.total)"
                                        theme="green"
                                    />
                                    <StatCard
                                        icon="fas fa-chair"
                                        label="Table Number"
                                        :value="currentOrder.tableNumber"
                                        theme="primary"
                                    />
                                    <StatCard
                                        icon="fas fa-credit-card"
                                        label="Payment Method"
                                        :value="currentOrder.paymentMethod.charAt(0).toUpperCase() + currentOrder.paymentMethod.slice(1)"
                                        theme="primary"
                                    />
                                </div>

                                <!-- Order Items (if available) -->
                                <div v-if="currentOrder.items && currentOrder.items.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Order Items</h3>
                                    <div class="space-y-3 max-h-96 overflow-y-auto">
                                        <div 
                                            v-for="item in currentOrder.items" 
                                            :key="item.id"
                                            class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                                        >
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                                                <p v-if="item.customizations && item.customizations.length > 0" class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ item.customizations.map(c => c.name).join(', ') }}
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">×{{ item.quantity }}</span>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(item.price * item.quantity) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Progress Tracker & Actions -->
                            <div class="col-span-7 space-y-6">
                                <!-- Order Progress Tracker -->
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8 h-full">
                                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-8">Order Progress</h2>
                                    <div class="flex items-center justify-center min-h-[400px]">
                                        <div class="w-full">
                                            <OrderStatusTracker :status="orderStatus" />
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons at bottom -->
                                    <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
                                        <div class="flex justify-center gap-4">
                                            <Button 
                                                @click="goToConfirmation"
                                                variant="outline"
                                                size="lg"
                                            >
                                                <i class="fas fa-receipt mr-2"></i>
                                                View Order Details
                                            </Button>
                                            <Button 
                                                @click="goToMenu"
                                                variant="primary"
                                                size="lg"
                                            >
                                                <i class="fas fa-plus mr-2"></i>
                                                Place Another Order
                                            </Button>
                                            <Button 
                                                @click="clearOrder"
                                                variant="secondary"
                                                size="lg"
                                            >
                                                <i class="fas fa-times mr-2"></i>
                                                Clear Order
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile/Tablet Layout -->
                    <div class="lg:hidden space-y-6">
                        <!-- Order Header Card -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ currentOrder.referenceNumber }}</h1>
                                    <p class="text-gray-500 dark:text-gray-400">{{ formatDate(currentOrder.createdAt) }}</p>
                                </div>
                                <div class="flex flex-col sm:items-end gap-3">
                                    <StatusPill :status="orderStatus" size="md" />
                                    <div v-if="formattedEta" class="text-sm text-gray-500 dark:text-gray-400">
                                        ETA: {{ formattedEta }}
                                    </div>
                                </div>
                            </div>

                            <!-- Live Status Indicator -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2" aria-live="polite">
                                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Live Updates</span>
                                    <span v-if="lastUpdated" class="text-xs text-gray-400 dark:text-gray-500">
                                        • {{ formatRelativeTime(lastUpdated) }}
                                    </span>
                                </div>
                                <button
                                    v-if="pollingError"
                                    @click="retryPolling"
                                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium"
                                >
                                    <i class="fas fa-refresh mr-1"></i>
                                    Retry
                                </button>
                            </div>

                            <!-- Error banner -->
                            <div 
                                v-if="pollingError"
                                class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-sm text-yellow-800 dark:text-yellow-200"
                            >
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    {{ pollingError }}
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <StatCard
                                icon="fas fa-peso-sign"
                                label="Total Amount"
                                :value="formatCurrency(currentOrder.total)"
                                theme="green"
                            />
                            <StatCard
                                icon="fas fa-chair"
                                label="Table Number"
                                :value="currentOrder.tableNumber"
                                theme="primary"
                            />
                            <StatCard
                                icon="fas fa-credit-card"
                                label="Payment Method"
                                :value="currentOrder.paymentMethod.charAt(0).toUpperCase() + currentOrder.paymentMethod.slice(1)"
                                theme="primary"
                            />
                        </div>

                        <!-- Order Progress Tracker -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Order Progress</h2>
                            <OrderStatusTracker :status="orderStatus" />
                        </div>

                        <!-- Order Items (if available) -->
                        <div v-if="currentOrder.items && currentOrder.items.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Order Items</h3>
                            <div class="space-y-3">
                                <div 
                                    v-for="item in currentOrder.items" 
                                    :key="item.id"
                                    class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                                >
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                                        <p v-if="item.customizations && item.customizations.length > 0" class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ item.customizations.map(c => c.name).join(', ') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">×{{ item.quantity }}</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(item.price * item.quantity) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Button 
                                @click="goToConfirmation"
                                variant="outline"
                                size="lg"
                            >
                                <i class="fas fa-receipt mr-2"></i>
                                View Order Details
                            </Button>
                            <Button 
                                @click="goToMenu"
                                variant="primary"
                                size="lg"
                            >
                                <i class="fas fa-plus mr-2"></i>
                                Place Another Order
                            </Button>
                            <Button 
                                @click="clearOrder"
                                variant="secondary"
                                size="lg"
                            >
                                <i class="fas fa-times mr-2"></i>
                                Clear Order
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
