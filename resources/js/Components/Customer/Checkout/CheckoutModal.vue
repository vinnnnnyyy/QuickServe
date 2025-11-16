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
    <Modal :show="show" @close="handleModalClose" max-width="3xl">
        <div class="bg-white rounded-xl shadow-xl overflow-hidden max-h-[80vh] flex flex-col">
             <!-- Modal Header -->
             <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white">
                 <div class="flex items-center gap-2.5">
                     <div class="w-9 h-9 rounded-full bg-primary-100 flex items-center justify-center">
                         <i class="fas fa-shopping-cart text-primary-600 text-base"></i>
                     </div>
                     <div>
                         <h2 class="text-lg font-semibold text-gray-900">Checkout</h2>
                         <p class="text-xs text-gray-500">Review and confirm your order</p>
                     </div>
                 </div>
                 <button
                     @click="handleModalClose"
                     class="text-gray-400 hover:text-gray-700 transition-colors p-1.5 rounded-full hover:bg-gray-100"
                 >
                     <i class="fas fa-times text-base"></i>
                 </button>
             </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 overflow-y-auto flex-1">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
                    <!-- Left Column: Customer Info & Payment -->
                    <div class="lg:col-span-3 space-y-4">
                         <!-- Table Information -->
                         <div class="bg-blue-50 border border-blue-100 rounded-lg p-3.5">
                             <div class="flex items-center justify-between">
                                 <div class="flex items-center gap-2.5">
                                     <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                         <i class="fas fa-utensils text-blue-600 text-sm"></i>
                                     </div>
                                     <div>
                                         <span class="font-semibold text-blue-900 text-sm">{{ currentTable }}</span>
                                         <p class="text-xs text-blue-600">Dine-in service</p>
                                     </div>
                                 </div>
                                 <i class="fas fa-map-marker-alt text-blue-400 text-xs"></i>
                             </div>
                         </div>

                         <!-- Customer Information -->
                         <div class="bg-white rounded-lg p-4 border border-gray-200">
                             <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                 <i class="fas fa-user text-primary-600 text-xs"></i>
                                 Customer Details
                             </h3>
                             
                             <div class="space-y-3">
                                 <!-- Nickname -->
                                 <div>
                                     <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                         Nickname <span class="text-xs text-gray-400 font-normal">(Optional)</span>
                                     </label>
                                     <input 
                                         v-model="customerForm.nickname"
                                         type="text"
                                         class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white transition-all text-sm"
                                         placeholder="How should we call you?"
                                     />
                                 </div>

                                 <!-- Notes -->
                                 <div>
                                     <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                         Special Instructions
                                     </label>
                                     <textarea 
                                         v-model="customerForm.notes"
                                         rows="2"
                                         class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white transition-all resize-none text-sm"
                                         placeholder="Any special requests or notes..."
                                     ></textarea>
                                 </div>
                             </div>
                         </div>

                         <!-- Payment Method Selection -->
                         <div class="bg-white rounded-lg p-4 border border-gray-200">
                             <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                 <i class="fas fa-credit-card text-primary-600 text-xs"></i>
                                 Payment Method
                             </h3>
                             <PaymentMethodSelector v-model="selectedPaymentMethod" />
                         </div>
                    </div>

                    <!-- Right Column: Order Details -->
                    <div class="lg:col-span-2 lg:border-l lg:border-gray-100 lg:pl-5">
                        <div class="max-h-[50vh] overflow-y-auto pr-2 custom-scrollbar">
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
             <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                 <div class="flex flex-col sm:flex-row gap-2.5 justify-between items-center">
                     <div class="flex items-center gap-1.5 text-xs text-gray-500">
                         <i class="fas fa-info-circle text-xs"></i>
                         <span>Order will be prepared after payment confirmation</span>
                     </div>
                     <div class="flex gap-2">
                         <Button variant="secondary" @click="handleModalClose" class="flex items-center gap-1.5 px-4 py-2 text-sm">
                             <i class="fas fa-times text-xs"></i>
                             Cancel
                         </Button>
                         <Button 
                             variant="primary" 
                             @click="handleProceedToPayment"
                             :disabled="!isFormValid"
                             class="flex items-center gap-1.5 px-4 py-2 text-sm shadow-sm"
                         >
                             <i class="fas fa-credit-card text-xs"></i>
                             Proceed to Payment
                         </Button>
                     </div>
                 </div>
             </div>
        </div>
    </Modal>
</template>
