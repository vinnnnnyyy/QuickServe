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
    }
})

const emit = defineEmits(['close', 'proceed-to-payment'])

// Cart data
const { cartItems, cartTotal, formatPrice, isEmpty } = useCart()

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
        items: cartItems.value,
        total: cartTotal.value
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
    const subtotal = cartTotal.value
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
</script>

<template>
    <Modal :show="show" @close="handleModalClose" max-width="4xl">
        <div class="bg-white">
             <!-- Modal Header -->
             <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-primary-50 to-blue-50">
                 <div class="flex items-center">
                     <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center mr-4">
                         <i class="fas fa-shopping-cart text-primary-600 text-xl"></i>
                     </div>
                     <div>
                         <h2 class="text-2xl font-bold text-gray-900">Checkout</h2>
                         <p class="text-sm text-gray-600">Review your order and complete payment</p>
                     </div>
                 </div>
                 <button
                     @click="handleModalClose"
                     class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-lg hover:bg-white/50"
                 >
                     <i class="fas fa-times text-xl"></i>
                 </button>
             </div>

            <!-- Modal Body -->
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Column: Customer Info & Payment -->
                    <div class="space-y-6">
                         <!-- Table Information -->
                         <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5 shadow-sm">
                             <div class="flex items-center justify-between">
                                 <div class="flex items-center">
                                     <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                                         <i class="fas fa-utensils text-blue-600"></i>
                                     </div>
                                     <div>
                                         <span class="font-bold text-blue-900 text-lg">{{ currentTable }}</span>
                                         <p class="text-xs text-blue-700">Dine-in service</p>
                                     </div>
                                 </div>
                                 <i class="fas fa-map-marker-alt text-blue-500"></i>
                             </div>
                         </div>

                         <!-- Customer Information -->
                         <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 shadow-sm">
                             <h3 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
                                 <i class="fas fa-user text-primary-600"></i>
                                 Customer Details
                             </h3>
                             
                             <div class="space-y-5">
                                 <!-- Nickname -->
                                 <div>
                                     <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                         <i class="fas fa-id-badge text-sm"></i>
                                         Nickname <span class="text-xs text-gray-500 ml-1">(Optional)</span>
                                     </label>
                                     <input 
                                         v-model="customerForm.nickname"
                                         type="text"
                                         class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white transition-all shadow-sm"
                                         placeholder="How should we call you?"
                                     />
                                 </div>

                                 <!-- Notes -->
                                 <div>
                                     <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                         <i class="fas fa-sticky-note text-sm"></i>
                                         Special Instructions
                                     </label>
                                     <textarea 
                                         v-model="customerForm.notes"
                                         rows="3"
                                         class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white transition-all shadow-sm resize-none"
                                         placeholder="Any special requests or notes..."
                                     ></textarea>
                                 </div>
                             </div>
                         </div>

                         <!-- Payment Method Selection -->
                         <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 shadow-sm">
                             <h3 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
                                 <i class="fas fa-credit-card text-primary-600"></i>
                                 Payment Method
                             </h3>
                             <PaymentMethodSelector v-model="selectedPaymentMethod" />
                         </div>
                    </div>

                    <!-- Right Column: Order Details -->
                    <div class="lg:border-l lg:border-gray-200 lg:pl-8">
                        <div class="max-h-[70vh] overflow-y-auto">
                            <OrderDetailsPanel
                                :order="orderDetails"
                                :items="cartItems"
                                :totals="orderTotals"
                                :status="orderStatus"
                                :payment="paymentStatus"
                                :show-order-info="false"
                                theme="light"
                            />
                        </div>
                    </div>
                </div>
            </div>

             <!-- Modal Footer -->
             <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                 <div class="flex flex-col sm:flex-row gap-3 justify-between items-center">
                     <div class="flex items-center gap-2 text-sm text-gray-600">
                         <i class="fas fa-info-circle text-sm"></i>
                         <span>Your order will be prepared after payment confirmation</span>
                     </div>
                     <div class="flex gap-3">
                         <Button variant="secondary" @click="handleModalClose" class="flex items-center gap-2">
                             <i class="fas fa-times text-sm"></i>
                             Cancel
                         </Button>
                         <Button 
                             variant="primary" 
                             @click="handleProceedToPayment"
                             :disabled="!isFormValid"
                             class="flex items-center gap-2 shadow-lg"
                         >
                             <i class="fas fa-credit-card text-sm"></i>
                             Proceed to Payment
                         </Button>
                     </div>
                 </div>
             </div>
        </div>
    </Modal>
</template>
