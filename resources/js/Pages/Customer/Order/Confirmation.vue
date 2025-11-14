<script setup>
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useCheckout } from '../../../composables/useCheckout.js'
import { useCart } from '../../../composables/useCart.js'
import Button from '../../../Components/Shared/Base/Button.vue'

const props = defineProps({
    orderId: {
        type: String,
        required: true
    }
})

// Composables
const { getOrder, formatCurrency } = useCheckout()
const { clearCart } = useCart()

// Component state
const order = ref(null)
const loading = ref(true)

// Methods
const loadOrder = () => {
    order.value = getOrder(props.orderId)
    if (!order.value) {
        router.visit('/')
        return
    }
    
    // Clear cart after successful order
    clearCart()
    loading.value = false
}


const continueOrdering = () => {
    router.visit('/')
}

const downloadReceipt = () => {
    // In a real app, this would generate and download a PDF receipt
    const receiptData = {
        orderId: order.value.id,
        customer: order.value.customer,
        items: order.value.items,
        total: order.value.total,
        paymentMethod: order.value.paymentMethod,
        timestamp: order.value.createdAt
    }
    
    console.log('Receipt data:', receiptData)
    alert('Receipt download functionality would be implemented here')
}

// Get payment method display name
const getPaymentMethodDisplay = (method) => {
    const methods = {
        gcash: 'GCash',
        cash: 'Pay Cash',
        card: 'Credit/Debit Card',
        paymaya: 'PayMaya'
    }
    return methods[method] || method
}

// Format date
const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Lifecycle
onMounted(() => {
    loadOrder()
})
</script>

<template>
    <Head title="Order Confirmation" />

    <div class="min-h-screen bg-gray-50 py-8 px-4">
        <div class="max-w-2xl mx-auto">
            <!-- Loading State -->
            <div v-if="loading" class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
                <p class="text-gray-600">Loading order details...</p>
            </div>

            <!-- Order Confirmation -->
            <div v-else class="space-y-6">
                <!-- Success Header -->
                <div class="text-center bg-white rounded-lg p-8 shadow-sm">
                    <div class="text-green-600 text-6xl mb-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Confirmed!</h1>
                    <p class="text-gray-600 text-lg">Thank you for your order. We're preparing your delicious items!</p>
                </div>


                <!-- Order Details Card -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Order Info -->
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Order ID</label>
                                <p class="text-lg font-mono text-gray-900">{{ order.id }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Reference Number</label>
                                <p class="text-lg font-mono text-gray-900">{{ order.referenceNumber }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Order Date</label>
                                <p class="text-gray-900">{{ formatDate(order.createdAt) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Status</label>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i>
                                    Confirmed
                                </span>
                            </div>
                        </div>

                        <!-- Order Info -->
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Table Number</label>
                                <p class="text-gray-900">{{ order.tableNumber || 'Table 1' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Payment Method</label>
                                <p class="text-gray-900">{{ getPaymentMethodDisplay(order.paymentMethod) }}</p>
                            </div>
                            <div v-if="order.customer.nickname">
                                <label class="text-sm font-medium text-gray-500">Preferred Name</label>
                                <p class="text-gray-900">{{ order.customer.nickname }}</p>
                            </div>
                        </div>
                    </div>


                    <!-- Special Instructions -->
                    <div v-if="order.customer.notes" class="mb-6">
                        <label class="text-sm font-medium text-gray-500">Special Instructions</label>
                        <p class="text-gray-900 bg-gray-50 rounded-lg p-3 mt-1">{{ order.customer.notes }}</p>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Items</h2>
                    
                    <div class="space-y-3">
                        <div 
                            v-for="(item, index) in order.items"
                            :key="`confirmed-${item.id}-${index}`"
                            class="flex justify-between items-start py-3 border-b border-gray-200 last:border-b-0"
                        >
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900">{{ item.name }}</h3>
                                <div class="text-sm text-gray-600 space-y-1">
                                    <div>Quantity: {{ item.quantity }}</div>
                                    <div v-if="item.isCustomized" class="text-primary-600">
                                        <i class="fas fa-cog mr-1"></i>
                                        Customized Order
                                    </div>
                                    <div class="text-gray-500">
                                        {{ formatCurrency(item.displayPrice || item.finalPrice || item.price) }} each
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-gray-900">
                                    {{ formatCurrency((item.displayPrice || item.finalPrice || item.price) * item.quantity) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Total -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <div class="flex justify-between text-lg font-bold text-gray-900">
                            <span>Total Paid:</span>
                            <span class="text-primary-600">{{ formatCurrency(order.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div v-if="order.paymentDetails" class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Transaction ID</label>
                            <p class="text-gray-900 font-mono">{{ order.paymentDetails.transactionId }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Payment Status</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i>
                                Paid
                            </span>
                        </div>
                        <div v-if="order.paymentDetails.gcashReferenceNumber">
                            <label class="text-sm font-medium text-gray-500">GCash Reference</label>
                            <p class="text-gray-900 font-mono">{{ order.paymentDetails.gcashReferenceNumber }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Payment Date</label>
                            <p class="text-gray-900">{{ formatDate(order.paymentDetails.timestamp) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">What's Next?</h3>
                    <ul class="text-blue-800 space-y-2">
                        <li v-if="currentStatus === 'confirmed'" class="flex items-start">
                            <i class="fas fa-check-circle mt-1 mr-2 text-blue-600"></i>
                            <span>Order confirmed! Moving to processing queue...</span>
                        </li>
                        <li v-if="currentStatus === 'received'" class="flex items-start">
                            <i class="fas fa-receipt mt-1 mr-2 text-blue-600"></i>
                            <span>Your order is being processed (estimated time: 15-30 minutes)</span>
                        </li>
                        <li v-if="currentStatus === 'queued'" class="flex items-start">
                            <i class="fas fa-clock mt-1 mr-2 text-blue-600"></i>
                            <span>Your order is in the barista queue, waiting to be prepared</span>
                        </li>
                        <li v-if="currentStatus === 'preparing'" class="flex items-start">
                            <i class="fas fa-fire mt-1 mr-2 text-blue-600"></i>
                            <span>Your order is being prepared by our barista</span>
                        </li>
                        <li v-if="currentStatus === 'ready'" class="flex items-start">
                            <i class="fas fa-check-circle mt-1 mr-2 text-blue-600"></i>
                            <span>Your order is ready! We'll bring it to your table shortly.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-utensils mt-1 mr-2 text-blue-600"></i>
                            <span>Your order will be served directly to {{ order.tableNumber || 'your table' }}</span>
                        </li>
                        <li v-if="currentStatus !== 'ready'" class="flex items-start">
                            <i class="fas fa-bell mt-1 mr-2 text-blue-600"></i>
                            <span>This page updates automatically as your order progresses</span>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <Button 
                        variant="primary" 
                        @click="continueOrdering"
                        class="flex-1"
                    >
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Continue Ordering
                    </Button>
                    <Button 
                        variant="secondary" 
                        @click="downloadReceipt"
                        class="flex-1"
                    >
                        <i class="fas fa-download mr-2"></i>
                        Download Receipt
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
