<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import CustomerLayout from '../../../Layouts/CustomerLayout.vue'
import OrderCard from '../../../Components/Customer/Orders/OrderCard.vue'
import { useOrderHistory } from '../../../composables/useOrderHistory.js'
import { useCheckout } from '../../../composables/useCheckout.js'

const {
    orders,
    activeOrders,
    completedOrders,
    loading,
    error,
    fetchOrders,
    cancelOrder,
    reorderItems
} = useOrderHistory()

const { clearCurrentOrder } = useCheckout()

const activeTab = ref('active')
const cancellingOrderId = ref(null)
const showCancelConfirm = ref(false)
const orderToCancel = ref(null)

const displayedOrders = computed(() => {
    return activeTab.value === 'active' ? activeOrders.value : completedOrders.value
})

onMounted(() => {
    fetchOrders()
})

const goBack = () => router.visit('/')

const handleCancelClick = (order) => {
    orderToCancel.value = order
    showCancelConfirm.value = true
}

const closeCancelConfirm = () => {
    showCancelConfirm.value = false
    orderToCancel.value = null
}

const confirmCancel = async () => {
    if (!orderToCancel.value) return
    
    cancellingOrderId.value = orderToCancel.value.id
    const result = await cancelOrder(orderToCancel.value.id)
    cancellingOrderId.value = null
    showCancelConfirm.value = false
    orderToCancel.value = null
    
    if (result.success) {
        clearCurrentOrder()
    } else {
        alert(result.error)
    }
}

const handleReorder = (order) => {
    const result = reorderItems(order)
    
    if (result.success) {
        router.visit('/')
    } else {
        alert(result.error)
    }
}
</script>

<template>
    <Head title="My Orders" />

    <CustomerLayout :show-header="false">
        <div class="min-h-screen bg-white">
            <!-- Header -->
            <div class="bg-white border-b border-gray-100">
                <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button @click="goBack" class="p-2 -ml-2 rounded-full hover:bg-gray-100 transition-colors">
                            <i class="fas fa-arrow-left text-lg text-gray-700"></i>
                        </button>
                        <div>
                            <h1 class="text-heading text-xl sm:text-2xl text-gray-900">My Orders</h1>
                            <p class="text-gray-500 text-sm">View and manage your orders</p>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex gap-2 mt-4">
                        <button
                            @click="activeTab = 'active'"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                activeTab === 'active'
                                    ? 'bg-primary text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            Active
                            <span v-if="activeOrders.length > 0" class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full" :class="activeTab === 'active' ? 'bg-white/20' : 'bg-primary/10 text-primary'">
                                {{ activeOrders.length }}
                            </span>
                        </button>
                        <button
                            @click="activeTab = 'history'"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                activeTab === 'history'
                                    ? 'bg-primary text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]"
                        >
                            History
                            <span v-if="completedOrders.length > 0" class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full" :class="activeTab === 'history' ? 'bg-white/20' : 'bg-gray-200'">
                                {{ completedOrders.length }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
                <!-- Loading State -->
                <div v-if="loading" class="flex items-center justify-center py-16">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary mx-auto mb-4"></div>
                        <p class="text-gray-500">Loading your orders...</p>
                    </div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="text-center py-16">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 sm:p-12 max-w-md mx-auto">
                        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
                        </div>
                        <h2 class="text-heading text-xl text-gray-900 mb-2">Something went wrong</h2>
                        <p class="text-gray-500 mb-6">{{ error }}</p>
                        <button 
                            @click="fetchOrders"
                            class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-medium hover:bg-primary-600 transition-colors"
                        >
                            <i class="fas fa-redo"></i>
                            Try Again
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="displayedOrders.length === 0" class="text-center py-16">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 sm:p-12 max-w-md mx-auto">
                        <div class="w-20 h-20 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-receipt text-3xl text-primary"></i>
                        </div>
                        <h2 class="text-heading text-xl text-gray-900 mb-2">
                            {{ activeTab === 'active' ? 'No Active Orders' : 'No Order History' }}
                        </h2>
                        <p class="text-gray-500 mb-8">
                            {{ activeTab === 'active' ? 'Your active orders will appear here' : 'Your past orders will appear here' }}
                        </p>
                        <button 
                            @click="goBack"
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-primary to-primary-600 text-white px-6 py-3 rounded-xl font-medium hover:shadow-lg hover:shadow-primary/25 transition-all"
                        >
                            <i class="fas fa-utensils"></i>
                            Browse Menu
                        </button>
                    </div>
                </div>

                <!-- Orders List -->
                <div v-else class="space-y-4 pb-6">
                    <OrderCard
                        v-for="order in displayedOrders"
                        :key="order.id"
                        :order="order"
                        :is-cancelling="cancellingOrderId === order.id"
                        @cancel="handleCancelClick"
                        @reorder="handleReorder"
                    />
                </div>
            </div>
        </div>

        <!-- Cancel Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showCancelConfirm" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50" @click="closeCancelConfirm"></div>
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="showCancelConfirm" class="relative bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl">
                            <div class="text-center">
                                <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                                    <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Cancel Order?</h3>
                                <p class="text-sm text-gray-600 mb-6">
                                    Are you sure you want to cancel order <span class="font-medium">{{ orderToCancel?.order_number }}</span>? This action cannot be undone.
                                </p>
                                <div class="flex gap-3">
                                    <button
                                        @click="closeCancelConfirm"
                                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
                                    >
                                        Keep Order
                                    </button>
                                    <button
                                        @click="confirmCancel"
                                        :disabled="cancellingOrderId !== null"
                                        class="flex-1 px-4 py-2.5 rounded-xl bg-red-500 text-white font-medium hover:bg-red-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="cancellingOrderId" class="flex items-center justify-center gap-2">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            Cancelling...
                                        </span>
                                        <span v-else>Yes, Cancel</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </CustomerLayout>
</template>
