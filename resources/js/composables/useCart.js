import { ref, computed } from 'vue'

// Global cart state
const cartItems = ref([])
const cartAnimationTrigger = ref(0)
const lastAddedItem = ref(null)
const cartMode = ref('local') // 'local' or 'table'

export function useCart() {
    // Computed properties
    const cartItemCount = computed(() => {
        return cartItems.value.reduce((total, item) => total + item.quantity, 0)
    })

    const cartTotal = computed(() => {
        return cartItems.value.reduce((total, item) => {
            const itemPrice = item.price || 0
            return total + (itemPrice * item.quantity)
        }, 0)
    })

    const isEmpty = computed(() => {
        return cartItems.value.length === 0
    })

    // Filter items added by the current device
    const myCartItems = computed(() => {
        return cartItems.value.filter(item => item.added_by_me)
    })

    const myCartTotal = computed(() => {
        return myCartItems.value.reduce((total, item) => {
            const itemPrice = item.price || 0
            return total + (itemPrice * item.quantity)
        }, 0)
    })

    const setMode = (mode) => {
        cartMode.value = mode
        if (mode === 'table') {
            refreshCart()
        } else {
            // Load local storage if needed, or clear
            // For now, let's assume switching modes clears view or reloads
            cartItems.value = []
        }
    }

    const refreshCart = async () => {
        if (cartMode.value !== 'table') return
        try {
            const response = await window.axios.get('/api/table-cart')
            cartItems.value = response.data
        } catch (err) {
            console.error('Failed to fetch table cart:', err)
        }
    }

    // Actions
    const addToCart = async (product) => {
        if (cartMode.value === 'table') {
            try {
                await window.axios.post('/api/table-cart/add', {
                    id: product.id,
                    quantity: product.quantity || 1,
                    options: product.customizations || [],
                    notes: product.notes
                })
                await refreshCart()
                triggerAnimation(product)
            } catch (err) {
                console.error('Failed to add to table cart:', err)
            }
            return
        }

        // Local Logic
        const existingItem = cartItems.value.find(item => {
            if (product.isCustomized && item.isCustomized) {
                return item.id === product.id &&
                    JSON.stringify(item.customizations) === JSON.stringify(product.customizations)
            }
            return item.id === product.id && !item.isCustomized
        })

        if (existingItem) {
            existingItem.quantity += product.quantity || 1
        } else {
            cartItems.value.push({
                ...product,
                quantity: product.quantity || 1,
                displayPrice: product.finalPrice || product.price || 0
            })
        }

        triggerAnimation(product)
    }

    const triggerAnimation = (product) => {
        lastAddedItem.value = product
        cartAnimationTrigger.value++
    }

    const removeFromCart = async (itemIndexOrId) => {
        if (cartMode.value === 'table') {
            // Expecting ID for table mode
            const item = cartItems.value[itemIndexOrId] // itemIndexOrId is likely index here from UI loop
            if (!item) return // Or pass ID directly from UI

            // Actually, UI usually passes index for local. 
            // Let's assume UI passes item object or we find it.
            // For now, assume simple index if local, but we need ID for server.
            // We'll fix UI to pass ID or handle finding it.
            const idToDelete = cartItems.value[itemIndexOrId]?.id
            if (idToDelete) {
                try {
                    await window.axios.delete(`/api/table-cart/${idToDelete}`)
                    await refreshCart()
                } catch (err) {
                    console.error('Failed to delete:', err)
                }
            }
            return
        }

        if (itemIndexOrId >= 0 && itemIndexOrId < cartItems.value.length) {
            cartItems.value.splice(itemIndexOrId, 1)
        }
    }

    const updateQuantity = (itemIndex, newQuantity) => {
        // Table cart quantity update not yet API supported in this simple version
        // Would be a PUT /api/table-cart/{id}
        if (cartMode.value === 'table') return

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
        cartAnimationTrigger: computed(() => cartAnimationTrigger.value),
        lastAddedItem: computed(() => lastAddedItem.value),
        cartMode: computed(() => cartMode.value),

        // Computed properties
        cartItemCount,
        cartTotal,
        isEmpty,

        // Actions
        setMode,
        refreshCart,
        addToCart,
        removeFromCart, // Note: updated to optionally take ID
        updateQuantity,
        clearCart,
        getCartItem,

        // Split Bill Helpers
        myCartItems,
        myCartTotal,

        // Utilities
        formatPrice
    }
}
