import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Global checkout state
const currentOrder = ref(null)
const orderHistory = ref([])

// Load current order from localStorage on initialization
const loadCurrentOrderFromStorage = () => {
    try {
        const stored = localStorage.getItem('currentOrder')
        if (stored) {
            currentOrder.value = JSON.parse(stored)
        }
    } catch (e) {
        console.warn('Failed to load current order from localStorage:', e)
        currentOrder.value = null
    }
}

// Save current order to localStorage
const saveCurrentOrderToStorage = (order) => {
    try {
        if (order) {
            localStorage.setItem('currentOrder', JSON.stringify(order))
        } else {
            localStorage.removeItem('currentOrder')
        }
    } catch (e) {
        console.warn('Failed to save current order to localStorage:', e)
    }
}

// Initialize current order from storage
loadCurrentOrderFromStorage()

export function useCheckout() {
    // Generate unique order ID
    const generateOrderId = () => {
        const timestamp = Date.now()
        const random = Math.random().toString(36).substr(2, 9)
        return `ORD-${timestamp}-${random}`.toUpperCase()
    }

    // Generate payment reference number
    const generateReferenceNumber = () => {
        const timestamp = Date.now()
        const random = Math.random().toString(36).substr(2, 6)
        return `REF${timestamp}${random}`.toUpperCase()
    }

    // Create order from checkout data
    const createOrder = (orderData) => {
        const orderId = generateOrderId()
        const referenceNumber = generateReferenceNumber()
        
        const order = {
            id: orderId,
            referenceNumber: referenceNumber,
            customer: orderData.customer,
            items: [...orderData.items],
            subtotal: orderData.total,
            total: orderData.total, // No delivery fee for table orders
            paymentMethod: orderData.paymentMethod,
            tableNumber: orderData.customer.tableNumber || 'Table 1', // Add table number
            status: 'received', // Orders start as received
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        }

        currentOrder.value = order
        saveCurrentOrderToStorage(order)
        return order
    }

    // Process payment (simulation)
    const processPayment = async (order, paymentData = {}) => {
        return new Promise((resolve) => {
            // Simulate payment processing delay
            setTimeout(() => {
                const isSuccess = Math.random() > 0.1 // 90% success rate for testing

                const paymentResult = {
                    success: isSuccess,
                    transactionId: generateReferenceNumber(),
                    paymentMethod: order.paymentMethod,
                    amount: order.total,
                    timestamp: new Date().toISOString(),
                    ...paymentData
                }

                if (isSuccess) {
                    // Update order status
                    order.status = 'paid'
                    order.paymentDetails = paymentResult
                    order.updatedAt = new Date().toISOString()
                    
                    // Add to order history (including backendId if available)
                    orderHistory.value.unshift({ 
                        ...order, 
                        backendId: order.backendId 
                    })
                } else {
                    order.status = 'payment_failed'
                    order.paymentDetails = {
                        ...paymentResult,
                        error: 'Payment processing failed. Please try again.'
                    }
                    order.updatedAt = new Date().toISOString()
                }

                resolve(paymentResult)
            }, 2000) // 2 second delay to simulate processing
        })
    }

    // Navigate to payment page based on method
    const navigateToPayment = (order) => {
        switch (order.paymentMethod) {
            case 'gcash':
                router.visit(`/payment/gcash/${order.id}`)
                break
            case 'cash':
                // For cash payment, we can directly process the order
                processPaymentAndComplete(order, { method: 'cash', note: 'Pay Cash at Table' })
                break
            case 'card':
                // Would navigate to card payment page
                router.visit(`/payment/card/${order.id}`)
                break
            default:
                console.error('Unknown payment method:', order.paymentMethod)
        }
    }

    // Save order to backend API
    const saveOrderToAPI = async (order) => {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            
            const response = await fetch('/api/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify(order)
            })

            if (!response.ok) {
                const errorText = await response.text()
                throw new Error(`Failed to save order: ${response.status} - ${errorText}`)
            }

            const savedOrder = await response.json()
            
            // Persist backend ID for polling
            if (savedOrder?.id != null) {
                currentOrder.value.backendId = savedOrder.id
                saveCurrentOrderToStorage(currentOrder.value)
                // Store fallback mapping in localStorage
                try {
                    localStorage.setItem(`orderBackendId:${order.id}`, String(savedOrder.id))
                } catch (e) {
                    console.warn('Failed to store backend ID mapping in localStorage:', e)
                }
            }
            
            return savedOrder
        } catch (error) {
            console.error('Error saving order to API:', error)
            // Don't block the checkout flow if API fails
            return null
        }
    }

    // Process payment and navigate to confirmation
    const processPaymentAndComplete = async (order, paymentData = {}) => {
        const result = await processPayment(order, paymentData)
        
        if (result.success) {
            // Save order to admin system
            await saveOrderToAPI(order)
            
            router.visit(`/order/confirmation/${order.id}`)
        } else {
            // Handle payment failure
            console.error('Payment failed:', result.error)
            return result
        }
    }

    // Simulate GCash payment
    const simulateGCashPayment = async (orderId, action = 'success') => {
        const order = currentOrder.value
        if (!order || order.id !== orderId) {
            throw new Error('Order not found')
        }

        const paymentData = {
            method: 'gcash',
            gcashReferenceNumber: generateReferenceNumber(),
            mobileNumber: '09XX XXX XXXX'
        }

        if (action === 'success') {
            return await processPaymentAndComplete(order, paymentData)
        } else {
            // Simulate failure
            const result = await processPayment(order, { ...paymentData, forceFailure: true })
            return result
        }
    }

    // Get order by ID
    const getOrder = (orderId) => {
        if (currentOrder.value && currentOrder.value.id === orderId) {
            return currentOrder.value
        }
        return orderHistory.value.find(order => order.id === orderId)
    }

    // Clear current order (after completion)
    const clearCurrentOrder = () => {
        currentOrder.value = null
        saveCurrentOrderToStorage(null)
    }

    // Format currency
    const formatCurrency = (amount) => {
        return `₱${Number(amount || 0).toFixed(2)}`
    }

    return {
        // State
        currentOrder,
        orderHistory,
        
        // Methods
        createOrder,
        processPayment,
        navigateToPayment,
        processPaymentAndComplete,
        simulateGCashPayment,
        getOrder,
        clearCurrentOrder,
        
        // Utilities
        generateOrderId,
        generateReferenceNumber,
        formatCurrency
    }
}
