<script setup>
import { ref, onMounted, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useCheckout } from '../../../composables/useCheckout.js'
import Button from '../../../Components/Shared/Base/Button.vue'

const props = defineProps({
    orderId: {
        type: String,
        required: true
    }
})

// Checkout composable
const { getOrder, simulateGCashPayment, formatCurrency } = useCheckout()

// Component state
const order = ref(null)
const loading = ref(true)
const processing = ref(false)
const paymentStatus = ref('pending') // pending, processing, success, failed
const errorMessage = ref('')
const countdown = ref(600) // 10 minutes in seconds
const qrCodeUrl = ref('')

// Timer for payment timeout
let countdownInterval = null

// Computed properties
const formattedCountdown = computed(() => {
    const minutes = Math.floor(countdown.value / 60)
    const seconds = countdown.value % 60
    return `${minutes}:${seconds.toString().padStart(2, '0')}`
})

const isExpired = computed(() => {
    return countdown.value <= 0
})

// Methods
const loadOrder = () => {
    order.value = getOrder(props.orderId)
    if (!order.value) {
        router.visit('/menu')
        return
    }
    
    // Generate QR code URL (placeholder)
    qrCodeUrl.value = generateQRCode()
    loading.value = false
    startCountdown()
}

const generateQRCode = () => {
    // In a real app, this would generate an actual QR code
    // For now, we'll use a placeholder QR code image
    const qrData = `gcash://pay?amount=${order.value.total}&reference=${order.value.referenceNumber}`
    return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrData)}`
}

const startCountdown = () => {
    countdownInterval = setInterval(() => {
        countdown.value--
        if (countdown.value <= 0) {
            clearInterval(countdownInterval)
            paymentStatus.value = 'expired'
        }
    }, 1000)
}

const simulatePaymentSuccess = async () => {
    if (processing.value || isExpired.value) return
    
    processing.value = true
    paymentStatus.value = 'processing'
    
    try {
        await simulateGCashPayment(props.orderId, 'success')
        paymentStatus.value = 'success'
        clearInterval(countdownInterval)
        
        // Redirect to success page after a short delay
        setTimeout(() => {
            // The composable already handles navigation to confirmation page
        }, 2000)
    } catch (error) {
        console.error('Payment simulation failed:', error)
        paymentStatus.value = 'failed'
        errorMessage.value = error.message || 'Payment processing failed'
        processing.value = false
    }
}

const simulatePaymentFailure = async () => {
    if (processing.value || isExpired.value) return
    
    processing.value = true
    paymentStatus.value = 'processing'
    
    try {
        await simulateGCashPayment(props.orderId, 'failure')
        paymentStatus.value = 'failed'
        errorMessage.value = 'Payment was declined. Please try again or use a different payment method.'
    } catch (error) {
        console.error('Payment simulation failed:', error)
        paymentStatus.value = 'failed'
        errorMessage.value = error.message || 'Payment processing failed'
    }
    
    processing.value = false
}

const retryPayment = () => {
    paymentStatus.value = 'pending'
    errorMessage.value = ''
    countdown.value = 600
    startCountdown()
}

const goBack = () => {
    router.visit('/menu')
}

// Lifecycle
onMounted(() => {
    loadOrder()
})

// Cleanup interval on unmount
onMounted(() => {
    return () => {
        if (countdownInterval) {
            clearInterval(countdownInterval)
        }
    }
})
</script>

<template>
    <Head title="GCash Payment" />

    <div class="min-h-screen bg-blue-600 flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            <!-- Loading State -->
            <div v-if="loading" class="bg-white rounded-2xl p-8 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <p class="text-gray-600">Loading payment details...</p>
            </div>

            <!-- Payment Interface -->
            <div v-else class="bg-white rounded-2xl overflow-hidden shadow-xl">
                <!-- Header -->
                <div class="bg-blue-600 text-white p-6 text-center">
                    <div class="flex items-center justify-center mb-2">
                        <i class="fas fa-mobile-alt text-3xl mr-3"></i>
                        <h1 class="text-2xl font-bold">GCash Payment</h1>
                    </div>
                    <p class="text-blue-100">Secure Digital Payment</p>
                </div>

                <!-- Payment Content -->
                <div class="p-6">
                    <!-- Order Details -->
                    <div class="mb-6">
                        <h2 class="font-semibold text-gray-900 mb-3">Order Details</h2>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Order ID:</span>
                                <span class="font-medium">{{ order.id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Reference:</span>
                                <span class="font-medium">{{ order.referenceNumber }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-2">
                                <span>Amount to Pay:</span>
                                <span class="text-blue-600">{{ formatCurrency(order.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Status -->
                    <div class="mb-6 text-center">
                        <!-- Pending State -->
                        <div v-if="paymentStatus === 'pending'" class="space-y-4">
                            <!-- QR Code -->
                            <div class="bg-gray-100 p-6 rounded-lg">
                                <img 
                                    :src="qrCodeUrl" 
                                    alt="GCash QR Code" 
                                    class="w-48 h-48 mx-auto mb-4"
                                />
                                <p class="text-sm text-gray-600">Scan QR code with your GCash app</p>
                            </div>

                            <!-- Timer -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center justify-center text-yellow-800">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span class="font-medium">Time remaining: {{ formattedCountdown }}</span>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div class="text-left bg-blue-50 rounded-lg p-4">
                                <h3 class="font-semibold text-blue-900 mb-2">Payment Instructions:</h3>
                                <ol class="text-sm text-blue-800 space-y-1">
                                    <li>1. Open your GCash app</li>
                                    <li>2. Tap "Pay QR"</li>
                                    <li>3. Scan the QR code above</li>
                                    <li>4. Confirm the payment amount</li>
                                    <li>5. Enter your PIN to complete</li>
                                </ol>
                            </div>
                        </div>

                        <!-- Processing State -->
                        <div v-else-if="paymentStatus === 'processing'" class="space-y-4">
                            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto"></div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900">Processing Payment...</h3>
                                <p class="text-gray-600">Please wait while we verify your payment</p>
                            </div>
                        </div>

                        <!-- Success State -->
                        <div v-else-if="paymentStatus === 'success'" class="space-y-4">
                            <div class="text-green-600 text-6xl">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-green-800">Payment Successful!</h3>
                                <p class="text-gray-600">Redirecting to order confirmation...</p>
                            </div>
                        </div>

                        <!-- Failed State -->
                        <div v-else-if="paymentStatus === 'failed'" class="space-y-4">
                            <div class="text-red-600 text-6xl">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-red-800">Payment Failed</h3>
                                <p class="text-gray-600">{{ errorMessage }}</p>
                            </div>
                        </div>

                        <!-- Expired State -->
                        <div v-else-if="paymentStatus === 'expired'" class="space-y-4">
                            <div class="text-yellow-600 text-6xl">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-yellow-800">Payment Expired</h3>
                                <p class="text-gray-600">The payment session has expired. Please try again.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <!-- Test Buttons (for demonstration purposes) -->
                        <div v-if="paymentStatus === 'pending'" class="space-y-2">
                            <p class="text-xs text-gray-500 text-center mb-3">Testing Buttons (Demo Only)</p>
                            <div class="grid grid-cols-2 gap-2">
                                <Button 
                                    variant="success" 
                                    size="sm" 
                                    @click="simulatePaymentSuccess"
                                    :disabled="processing"
                                    class="text-xs"
                                >
                                    <i class="fas fa-check mr-1"></i>
                                    Simulate Success
                                </Button>
                                <Button 
                                    variant="danger" 
                                    size="sm" 
                                    @click="simulatePaymentFailure"
                                    :disabled="processing"
                                    class="text-xs"
                                >
                                    <i class="fas fa-times mr-1"></i>
                                    Simulate Failure
                                </Button>
                            </div>
                        </div>

                        <!-- Retry/Back buttons -->
                        <div v-if="paymentStatus === 'failed' || paymentStatus === 'expired'">
                            <Button 
                                variant="primary" 
                                @click="retryPayment"
                                class="w-full mb-2"
                            >
                                <i class="fas fa-redo mr-2"></i>
                                Try Again
                            </Button>
                        </div>

                        <Button 
                            variant="secondary" 
                            @click="goBack"
                            class="w-full"
                        >
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Menu
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
