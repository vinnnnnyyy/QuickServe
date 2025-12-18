import { ref, computed, onMounted } from 'vue'
import { useCart } from './useCart.js'

const orders = ref([])
const loading = ref(false)
const error = ref(null)
const initialized = ref(false)

export function useOrderHistory() {
    const { addToCart, clearCart } = useCart()

    const activeOrders = computed(() => {
        return orders.value.filter(order =>
            !['completed', 'cancelled'].includes(order.status)
        )
    })

    const activeOrderCount = computed(() => activeOrders.value.length)

    const completedOrders = computed(() => {
        return orders.value.filter(order =>
            ['completed', 'cancelled'].includes(order.status)
        )
    })

    const fetchOrders = async () => {
        loading.value = true
        error.value = null

        try {
            const response = await window.axios.get('/api/orders/my-orders')
            orders.value = response.data
            initialized.value = true
        } catch (err) {
            console.error('Error fetching orders:', err)
            error.value = err?.response?.data?.message || 'Failed to load orders'
            orders.value = []
        } finally {
            loading.value = false
        }
    }

    const initializeOrders = () => {
        if (!initialized.value && !loading.value) {
            fetchOrders()
        }
    }

    const cancelOrder = async (orderId) => {
        try {
            const response = await window.axios.post(`/api/orders/${orderId}/cancel`)

            const index = orders.value.findIndex(o => o.id === orderId)
            if (index !== -1) {
                orders.value[index] = {
                    ...orders.value[index],
                    status: 'cancelled',
                    can_cancel: false
                }
            }

            return { success: true, order: response.data.order }
        } catch (err) {
            console.error('Error cancelling order:', err)
            return {
                success: false,
                error: err?.response?.data?.message || 'Failed to cancel order'
            }
        }
    }

    const reorderItems = (order) => {
        if (!order?.items || order.items.length === 0) {
            return { success: false, error: 'No items to reorder' }
        }

        clearCart()

        order.items.forEach(item => {
            addToCart({
                id: item.id,
                name: item.name,
                price: item.price,
                quantity: item.quantity,
                isCustomized: item.isCustomized || false
            })
        })

        return { success: true, itemCount: order.items.length }
    }

    const getStatusInfo = (status) => {
        const statusMap = {
            received: { text: 'Order Received', color: 'blue', icon: 'fas fa-inbox' },
            confirmed: { text: 'Confirmed', color: 'indigo', icon: 'fas fa-check-circle' },
            queued: { text: 'In Queue', color: 'purple', icon: 'fas fa-clock' },
            preparing: { text: 'Preparing', color: 'orange', icon: 'fas fa-fire' },
            ready: { text: 'Ready', color: 'green', icon: 'fas fa-bell' },
            served: { text: 'Served', color: 'teal', icon: 'fas fa-utensils' },
            completed: { text: 'Completed', color: 'gray', icon: 'fas fa-check-double' },
            cancelled: { text: 'Cancelled', color: 'red', icon: 'fas fa-times-circle' }
        }
        return statusMap[status] || { text: status, color: 'gray', icon: 'fas fa-question' }
    }

    const formatPrice = (price) => {
        const numPrice = typeof price === 'number' ? price : parseFloat(price) || 0
        return `₱${numPrice.toFixed(2)}`
    }

    const formatDate = (dateString) => {
        if (!dateString) return ''
        const date = new Date(dateString)
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
    }

    return {
        orders: computed(() => orders.value),
        activeOrders,
        activeOrderCount,
        completedOrders,
        loading: computed(() => loading.value),
        error: computed(() => error.value),
        fetchOrders,
        initializeOrders,
        cancelOrder,
        reorderItems,
        getStatusInfo,
        formatPrice,
        formatDate
    }
}
