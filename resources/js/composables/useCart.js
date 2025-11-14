import { ref, computed } from 'vue'

// Global cart state
const cartItems = ref([])

export function useCart() {
    // Computed properties
    const cartItemCount = computed(() => {
        return cartItems.value.reduce((total, item) => total + item.quantity, 0)
    })

    const cartTotal = computed(() => {
        return cartItems.value.reduce((total, item) => {
            const itemPrice = item.finalPrice || item.price || 0
            return total + (itemPrice * item.quantity)
        }, 0)
    })

    const isEmpty = computed(() => {
        return cartItems.value.length === 0
    })

    // Actions
    const addToCart = (product) => {
        // Check if product already exists in cart
        const existingItem = cartItems.value.find(item => {
            // For customized products, compare by unique combination of id and customizations
            if (product.isCustomized && item.isCustomized) {
                return item.id === product.id && 
                       JSON.stringify(item.customizations) === JSON.stringify(product.customizations)
            }
            // For regular products, just compare by id
            return item.id === product.id && !item.isCustomized
        })
        
        if (existingItem) {
            // If exists, increase quantity
            existingItem.quantity += product.quantity || 1
        } else {
            // If not exists, add new item
            cartItems.value.push({
                ...product,
                quantity: product.quantity || 1,
                // Use finalPrice if available (for customized items), otherwise use base price
                displayPrice: product.finalPrice || product.price || 0
            })
        }
        
        console.log('Added to cart:', product)
        console.log('Current cart:', cartItems.value)
    }

    const removeFromCart = (itemIndex) => {
        if (itemIndex >= 0 && itemIndex < cartItems.value.length) {
            cartItems.value.splice(itemIndex, 1)
        }
    }

    const updateQuantity = (itemIndex, newQuantity) => {
        if (itemIndex >= 0 && itemIndex < cartItems.value.length) {
            if (newQuantity <= 0) {
                removeFromCart(itemIndex)
            } else {
                cartItems.value[itemIndex].quantity = newQuantity
            }
        }
    }

    const clearCart = () => {
        cartItems.value = []
    }

    const getCartItem = (index) => {
        return cartItems.value[index] || null
    }

    // Utility functions
    const formatPrice = (price) => {
        return `₱${Number(price || 0).toFixed(2)}`
    }

    return {
        // State
        cartItems: computed(() => cartItems.value),
        
        // Computed properties
        cartItemCount,
        cartTotal,
        isEmpty,
        
        // Actions
        addToCart,
        removeFromCart,
        updateQuantity,
        clearCart,
        getCartItem,
        
        // Utilities
        formatPrice
    }
}
