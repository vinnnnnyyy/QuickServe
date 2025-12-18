<script setup>
import { ref, computed } from 'vue'
import Modal from '../../Shared/Overlay/Modal.vue'
import Button from '../../Shared/Base/Button.vue'
import PaymentMethodSelector from './PaymentMethodSelector.vue'
import OrderDetailsPanel from '../../Shared/Order/OrderDetailsPanel.vue'
import { useCart } from '../../../composables/useCart.js'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    paymentMode: {
        type: String,
        default: 'host'
    }
})

const emit = defineEmits(['close', 'proceed-to-payment'])

// Cart data
const { cartItems, cartTotal, formatPrice, isEmpty, myCartItems, myCartTotal } = useCart()

const displayItems = computed(() => props.paymentMode === 'split' ? myCartItems.value : cartItems.value)
const displayTotal = computed(() => props.paymentMode === 'split' ? myCartTotal.value : cartTotal.value)

// Customer form data
const customerForm = ref({
    nickname: '',
    notes: '',
    tableNumber: 'Table 1' // Add table number to customer form
})

// Table information (this could come from props or global state)
const currentTable = ref('Table 1') // This should be dynamic based on actual table

// Payment method
const selectedPaymentMethod = ref('')

// Form validation
const isFormValid = computed(() => {
    return selectedPaymentMethod.value !== '' && !isEmpty.value
})

// Methods
const handleClose = () => {
    emit('close')
}

const handleProceedToPayment = () => {
    if (!isFormValid.value) return
    
    const orderData = {
        customer: {
            ...customerForm.value,
            tableNumber: currentTable.value // Ensure table number is passed
        },
        paymentMethod: selectedPaymentMethod.value,
        paymentMethod: selectedPaymentMethod.value,
        items: displayItems.value,
        total: displayTotal.value
    }
    
    emit('proceed-to-payment', orderData)
}

const resetForm = () => {
    customerForm.value = {
        nickname: '',
        notes: '',
        tableNumber: 'Table 1'
    }
    selectedPaymentMethod.value = ''
}

// Reset form when modal closes
const handleModalClose = () => {
    resetForm()
    handleClose()
}

// Adapt cart data for OrderDetailsPanel
const orderDetails = computed(() => ({
    id: `ORDER-${Date.now()}`, // Generate temporary order ID
    sessionId: `#${Math.floor(Math.random() * 10000)}`,
    tableType: currentTable.value,
    orderType: 'Dine In',
    time: new Date().toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit' 
    })
}))

const orderStatus = computed(() => ({
    text: 'Pending',
    color: 'yellow'
}))

const paymentStatus = computed(() => ({
    status: selectedPaymentMethod.value ? `Selected: ${getPaymentMethodName(selectedPaymentMethod.value)}` : 'Not Selected',
    method: selectedPaymentMethod.value,
    color: selectedPaymentMethod.value ? 'blue' : 'red'
}))

const orderTotals = computed(() => {
    const subtotal = displayTotal.value
    const tax = subtotal * 0.12 // 12% VAT
    const fees = 0 // No service fees for now
    
    return {
        subtotal: subtotal,
        tax: tax,
        fees: fees,
        total: subtotal + tax + fees
    }
})

// Helper to get payment method display name
const getPaymentMethodName = (method) => {
    const methods = {
        gcash: 'GCash',
        cash: 'Cash',
        card: 'Card',
        paymaya: 'PayMaya'
    }
    return methods[method] || method
}

const checkoutButtonText = computed(() => {
    if (selectedPaymentMethod.value === 'cash') {
        return 'Place Order & Pay Cash'
    }
    return 'Proceed to Payment'
})

const checkoutMessage = computed(() => {
    if (selectedPaymentMethod.value === 'cash') {
        return 'Our staff will assist you with the payment at your table.'
    }
    return 'Order will be prepared after payment confirmation'
})
</script>

<template>
    <Modal :show="show" @close="handleModalClose" max-width="7xl">
        <div class="flex flex-col w-full bg-white dark:bg-surface-900 text-surface-900 dark:text-white max-h-[95vh] sm:max-h-[90vh] overflow-hidden sm:rounded-xl">
            <button
                @click="handleModalClose"
                class="absolute top-3 right-3 z-10 w-9 h-9 flex items-center justify-center rounded-full bg-white/95 dark:bg-surface-800/95 backdrop-blur-sm text-surface-600 dark:text-surface-300 hover:bg-white dark:hover:bg-surface-700 transition-all shadow-lg md:w-10 md:h-10"
            >
                <i class="fas fa-times text-sm md:text-base"></i>
            </button>

            <div class="flex flex-col lg:flex-row flex-1 overflow-y-auto lg:overflow-hidden">
                <div class="w-full lg:w-3/5 flex flex-col lg:overflow-hidden">
                    <div class="flex-1 lg:overflow-y-auto p-4 sm:p-5 md:p-6 space-y-4 sm:space-y-5">
                        <div>
                            <h2 class="text-heading text-xl sm:text-2xl text-surface-900 dark:text-white mb-2">Checkout</h2>
                            <p class="text-sm text-surface-600 dark:text-surface-400 leading-relaxed">Review and confirm your order</p>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg p-3.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-800 flex items-center justify-center">
                                        <i class="fas fa-utensils text-blue-600 dark:text-blue-300 text-sm"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-blue-900 dark:text-blue-100 text-sm">{{ currentTable }}</span>
                                        <p class="text-xs text-blue-600 dark:text-blue-400">Dine-in service</p>
                                    </div>
                                </div>
                                <i class="fas fa-map-marker-alt text-blue-400 dark:text-blue-500 text-xs"></i>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-surface-800 rounded-lg p-4 border-2 border-surface-200 dark:border-surface-700">
                            <h3 class="text-subheading text-base font-semibold text-surface-900 dark:text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-user text-primary text-sm"></i>
                                Customer Details
                            </h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-surface-700 dark:text-surface-300 mb-1.5">
                                        Nickname <span class="text-xs text-surface-400 font-normal">(Optional)</span>
                                    </label>
                                    <input 
                                        v-model="customerForm.nickname"
                                        type="text"
                                        class="w-full px-3 py-2.5 border-2 border-surface-300 dark:border-surface-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white dark:bg-surface-900 text-surface-900 dark:text-white transition-all text-sm"
                                        placeholder="How should we call you?"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-surface-700 dark:text-surface-300 mb-1.5">
                                        Special Instructions
                                    </label>
                                    <textarea 
                                        v-model="customerForm.notes"
                                        rows="3"
                                        class="w-full px-3 py-2.5 border-2 border-surface-300 dark:border-surface-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary bg-white dark:bg-surface-900 text-surface-900 dark:text-white transition-all resize-none text-sm"
                                        placeholder="Any special requests or notes..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-surface-800 rounded-lg p-4 border-2 border-surface-200 dark:border-surface-700">
                            <h3 class="text-subheading text-base font-semibold text-surface-900 dark:text-white mb-3 flex items-center gap-2">
                                <i class="fas fa-credit-card text-primary text-sm"></i>
                                Payment Method
                            </h3>
                            <PaymentMethodSelector v-model="selectedPaymentMethod" />
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-2/5 bg-surface-50 dark:bg-surface-800/50 border-t lg:border-t-0 lg:border-l border-surface-200 dark:border-surface-700 flex flex-col">
                    <div class="flex-1 p-4 sm:p-5 md:p-6 space-y-4 overflow-y-auto">
                        <h3 class="text-subheading text-base font-semibold text-surface-900 dark:text-white">Order Summary</h3>
                        
                        <OrderDetailsPanel
                            :order="orderDetails"
                            :items="displayItems"
                            :totals="orderTotals"
                            :status="orderStatus"
                            :payment="paymentStatus"
                            :show-order-info="false"
                            theme="light"
                        />
                    </div>

                    <div class="p-4 sm:p-5 md:p-6 pt-0 space-y-3">
                        <div class="flex items-center gap-2 text-xs text-surface-600 dark:text-surface-400 bg-surface-100 dark:bg-surface-800 p-3 rounded-lg">
                            <i class="fas fa-info-circle text-sm" :class="selectedPaymentMethod === 'cash' ? 'text-emerald-500' : ''"></i>
                            <span>{{ checkoutMessage }}</span>
                        </div>
                        <button
                            @click="handleProceedToPayment"
                            :disabled="!isFormValid"
                            type="button"
                            class="w-full bg-primary text-white text-button font-semibold py-3.5 px-6 rounded-lg hover:bg-primary-600 active:bg-primary-700 transition-all shadow-lg shadow-primary/20 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i :class="selectedPaymentMethod === 'cash' ? 'fas fa-check-circle' : 'fas fa-credit-card'" class="text-sm mr-2"></i>
                            {{ checkoutButtonText }}
                        </button>
                        <button
                            @click="handleModalClose"
                            type="button"
                            class="w-full border-2 border-surface-200 dark:border-surface-700 text-surface-700 dark:text-surface-300 text-button font-medium py-3 px-6 rounded-lg hover:bg-surface-100 dark:hover:bg-surface-800 transition-all"
                        >
                            <i class="fas fa-times text-sm mr-2"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>
