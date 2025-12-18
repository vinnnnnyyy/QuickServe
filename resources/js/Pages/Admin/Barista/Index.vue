<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminModal from '@/Components/Admin/UI/AdminModal.vue';
import Button from '@/Components/Shared/Base/Button.vue';
import LoadingSpinner from '@/Components/Shared/Base/LoadingSpinner.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useOrderWorkflow } from '../../../composables/useOrderWorkflow.js';
import BaristaStats from './Components/BaristaStats.vue';
import BaristaColumn from './Components/BaristaColumn.vue';
import BaristaOrderCard from './Components/BaristaOrderCard.vue';

// Workflow composable
const { 
    getStatusInfo, 
    getTimeInStatus,
    isOverdue,
    updateOrderStatus 
} = useOrderWorkflow()

// Component state
const orders = ref([])
const loading = ref(false)
const error = ref(null)
const lastRefresh = ref(null)
const selectedView = ref('all') // 'all', 'queued', 'preparing', 'ready'

// Auto-refresh
const autoRefreshEnabled = ref(true)
const autoRefreshInterval = ref(null)

// Filter orders by status for barista workflow
const queuedOrders = computed(() => {
    return orders.value.filter(order => order.status === 'queued')
})

const preparingOrders = computed(() => {
    return orders.value.filter(order => order.status === 'preparing')
})

const readyOrders = computed(() => {
    return orders.value.filter(order => order.status === 'ready')
})

// Statistics for barista interface
const queueStats = computed(() => ({
    queued: queuedOrders.value.length,
    preparing: preparingOrders.value.length,
    ready: readyOrders.value.length,
    overdue: orders.value.filter(order => 
        ['queued', 'preparing'].includes(order.status) && 
        isOverdue(order.status, order.updatedAt)
    ).length
}))

// Fetch orders from API (barista-specific orders)
const fetchOrders = async () => {
    // Only show loading on initial load to avoid flickering during auto-refresh
    if (orders.value.length === 0) {
        loading.value = true
    }
    error.value = null
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        
        const response = await fetch('/api/orders', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            }
        })
        
        if (!response.ok) {
            throw new Error(`Failed to fetch orders: ${response.status}`)
        }
        
        const data = await response.json()
        
        // Transform and filter orders for barista view (only queued, preparing, ready)
        const transformedOrders = data
            .filter(order => ['queued', 'preparing', 'ready'].includes(order.status))
            .map(order => ({
                id: order.id,
                orderNumber: order.order_number || order.id,
                tableNumber: order.table_number || 'Table 1',
                customerName: order.customer_name || 'Customer',
                items: order.items || [],
                total: parseFloat(order.total) || 0,
                status: order.status,
                notes: order.notes,
                paymentMethod: order.payment_method,
                createdAt: order.created_at,
                updatedAt: order.updated_at,
                originalData: order.original_data
            }))
        
        orders.value = transformedOrders
        lastRefresh.value = new Date()
        
    } catch (err) {
        console.error('Error fetching barista orders:', err)
        error.value = err.message
    } finally {
        loading.value = false
    }
}

// Handle status transitions for barista workflow
const handleStatusChange = async (orderId, newStatus) => {
    // Optimistic update
    const orderIndex = orders.value.findIndex(o => o.id === orderId)
    if (orderIndex === -1) return

    const previousStatus = orders.value[orderIndex].status
    
    // Update local state immediately for UI responsiveness
    orders.value[orderIndex].status = newStatus
    orders.value[orderIndex].updatedAt = new Date().toISOString()

    try {
        await updateOrderStatus(orderId, newStatus)
        // Success - no further action needed
    } catch (error) {
        // Revert on failure
        orders.value[orderIndex].status = previousStatus
        alert('Failed to update order status: ' + error.message)
        fetchOrders() // Re-sync to be safe
    }
}

// Card Actions
const handleCardAction = (order, action) => {
    if (action === 'view') {
        openOrderDetails(order.id)
    } else if (action === 'advance') {
        if (order.status === 'queued') {
            handleStatusChange(order.id, 'preparing')
        } else if (order.status === 'preparing') {
             handleStatusChange(order.id, 'ready')
        }
    }
}

// Auto-refresh functionality
const startAutoRefresh = () => {
    if (autoRefreshInterval.value) clearInterval(autoRefreshInterval.value)
    if (autoRefreshEnabled.value) {
        autoRefreshInterval.value = setInterval(fetchOrders, 10000) // 10 seconds
    }
}

const stopAutoRefresh = () => {
    if (autoRefreshInterval.value) {
        clearInterval(autoRefreshInterval.value)
        autoRefreshInterval.value = null
    }
}

const toggleAutoRefresh = () => {
    autoRefreshEnabled.value = !autoRefreshEnabled.value
    if (autoRefreshEnabled.value) startAutoRefresh()
    else stopAutoRefresh()
}

// Utility functions
const formatPrice = (price) => `₱${Number(price || 0).toFixed(2)}`

// Modal state
const showOrderModal = ref(false)
const selectedOrder = ref(null)

const openOrderDetails = (orderId) => {
    const order = orders.value.find(o => o.id === orderId)
    if (order) {
        selectedOrder.value = order
        showOrderModal.value = true
    }
}

const closeOrderModal = () => {
    showOrderModal.value = false
    selectedOrder.value = null
}

// Lifecycle hooks
onMounted(() => {
    fetchOrders()
    startAutoRefresh()
})

onUnmounted(() => {
    stopAutoRefresh()
})
</script>

<template>
  <AdminLayout
    title="Barista KDS"
    :show-header="false" 
  >
    <div class="h-[calc(100vh-64px)] flex flex-col p-4 md:p-6 max-w-full mx-auto bg-gray-50/30 dark:bg-black/20">
        
        <!-- Top Bar -->
        <div class="flex items-center justify-between mb-6 shrink-0">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="material-symbols-outlined text-4xl text-primary-500">coffee_maker</span>
                    Barista KDS
                </h1>
                <p class="text-gray-500 text-sm mt-1 ml-1">Kitchen Display System</p>
            </div>

            <!-- View Filter (Centered) -->
            <div class="hidden md:flex bg-gray-200 dark:bg-gray-800 p-1 rounded-lg border border-gray-300 dark:border-gray-700">
                <button 
                    v-for="view in ['all', 'queued', 'preparing', 'ready']" 
                    :key="view"
                    @click="selectedView = view"
                    class="px-4 py-1.5 rounded-md text-sm font-medium transition-all capitalize"
                    :class="selectedView === view 
                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' 
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'"
                >
                    {{ view === 'all' ? 'All Views' : view.replace('queued', 'To Prep').replace('preparing', 'In Progress') }}
                </button>
            </div>
            
            <div class="flex items-center gap-4">
                 <div class="flex items-center gap-2 bg-white dark:bg-gray-800 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="text-xs text-gray-500 font-medium uppercase tracking-wide">Auto-Refresh</div>
                    <div 
                        @click="toggleAutoRefresh"
                        class="relative w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full cursor-pointer transition-colors"
                        :class="{'bg-green-500 dark:bg-green-600': autoRefreshEnabled}"
                    >
                        <div 
                            class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                            :class="{'translate-x-5': autoRefreshEnabled}"
                        ></div>
                    </div>
                 </div>

                <div class="text-right hidden sm:block">
                    <div class="text-xs text-gray-400 font-medium">Last Updated</div>
                    <div class="text-sm font-bold text-gray-700 dark:text-gray-300 font-mono">
                        {{ lastRefresh ? lastRefresh.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}) : '--:--' }}
                    </div>
                </div>

                <button 
                    @click="fetchOrders" 
                    :disabled="loading"
                    class="p-2.5 text-gray-500 hover:text-primary-600 bg-white dark:bg-gray-800 rounded-full shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all active:scale-95"
                    title="Refresh Now"
                >
                    <span class="material-symbols-outlined text-xl" :class="{ 'animate-spin': loading }">refresh</span>
                </button>
            </div>
        </div>

        <!-- Stats Bar -->
        <BaristaStats :stats="queueStats" class="shrink-0" />

        <!-- Mobile Filter (Visible only on small screens) -->
        <div class="md:hidden mb-4 overflow-x-auto pb-2">
            <div class="flex gap-2">
                 <button 
                    v-for="view in ['all', 'queued', 'preparing', 'ready']" 
                    :key="view"
                    @click="selectedView = view"
                    class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap border"
                    :class="selectedView === view 
                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-transparent' 
                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700'"
                >
                    {{ view === 'all' ? 'Show All' : view.replace('queued', 'To Prep').replace('preparing', 'In Progress') }}
                </button>
            </div>
        </div>

        <!-- Error State -->
        <div v-if="error" class="flex-1 flex flex-col items-center justify-center text-center p-12">
            <div class="bg-red-50 dark:bg-red-900/20 p-6 rounded-full mb-4">
                <span class="material-symbols-outlined text-4xl text-red-500">error_outline</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Connection Issue</h3>
            <p class="text-gray-500 mb-6">{{ error }}</p>
            <Button @click="fetchOrders">Try Again</Button>
        </div>

        <!-- Main KDS Board (Board Layout) -->
        <div 
            v-else 
            class="flex-1 min-h-0 grid gap-4 lg:gap-6 pb-2"
            :class="selectedView === 'all' ? 'grid-cols-1 md:grid-cols-3' : 'grid-cols-1'"
        >
            
            <!-- Column 1: Queued -->
            <BaristaColumn 
                v-if="selectedView === 'all' || selectedView === 'queued'"
                title="To Prep" 
                :count="queuedOrders.length" 
                icon="receipt_long"
                count-color="bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                :is-grid="selectedView !== 'all'"
            >
                <BaristaOrderCard 
                    v-for="order in queuedOrders" 
                    :key="order.id"
                    :order="order"
                    :is-overdue="isOverdue(order.status, order.updatedAt)"
                    :time-in-status="getTimeInStatus(order.updatedAt)"
                    @action="(action) => handleCardAction(order, action)"
                    @click="openOrderDetails(order.id)"
                />
            </BaristaColumn>

            <!-- Column 2: Preparing -->
            <BaristaColumn 
                v-if="selectedView === 'all' || selectedView === 'preparing'"
                title="In Progress" 
                :count="preparingOrders.length" 
                icon="blender"
                count-color="bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300"
                :is-grid="selectedView !== 'all'"
            >
                <BaristaOrderCard 
                    v-for="order in preparingOrders" 
                    :key="order.id"
                    :order="order"
                    :is-overdue="isOverdue(order.status, order.updatedAt)"
                    :time-in-status="getTimeInStatus(order.updatedAt)"
                    @action="(action) => handleCardAction(order, action)"
                    @click="openOrderDetails(order.id)"
                />
            </BaristaColumn>

            <!-- Column 3: Ready -->
            <BaristaColumn 
                v-if="selectedView === 'all' || selectedView === 'ready'"
                title="Ready for Pickup" 
                :count="readyOrders.length" 
                icon="check_circle"
                count-color="bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300"
                :is-grid="selectedView !== 'all'"
            >
                <BaristaOrderCard 
                    v-for="order in readyOrders" 
                    :key="order.id"
                    :order="order"
                    :time-in-status="getTimeInStatus(order.updatedAt)"
                    @action="(action) => handleCardAction(order, action)"
                    @click="openOrderDetails(order.id)"
                />
            </BaristaColumn>

        </div>
    </div>
    
    <!-- Order Details Modal -->
    <AdminModal
      :show="showOrderModal"
      :title="selectedOrder ? `#${selectedOrder.orderNumber}` : ''"
      :show-close="true"
      max-width="2xl"
      @close="closeOrderModal"
    >
      <div v-if="selectedOrder" class="p-1">
         <!-- Modal Header Context -->
         <div class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
            <div>
                <div class="text-sm text-gray-500">Customer</div>
                <div class="font-bold text-lg text-gray-900 dark:text-white">{{ selectedOrder.customerName }}</div>
            </div>
             <div class="text-right">
                <div class="text-sm text-gray-500">Table</div>
                <div class="font-bold text-lg text-gray-900 dark:text-white">{{ selectedOrder.tableNumber }}</div>
            </div>
         </div>

         <!-- Items List -->
         <div class="space-y-4 mb-8 max-h-[40vh] overflow-y-auto pr-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">Order Items</h4>
            <div v-for="item in selectedOrder.items" :key="item.id" class="flex justify-between items-start py-3 border-b border-dashed border-gray-100 dark:border-gray-800 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded px-2 transition-colors">
                <div class="flex gap-4">
                    <div class="font-extrabold text-xl text-primary-600 dark:text-primary-400 w-8 text-center bg-primary-50 dark:bg-primary-900/20 rounded h-8 flex items-center justify-center">
                        {{ item.quantity }}
                    </div>
                    <div>
                        <div class="text-gray-900 dark:text-white font-bold text-lg leading-tight">{{ item.name }}</div>
                        <div v-if="item.isCustomized" class="inline-flex items-center gap-1 text-xs font-bold text-orange-600 bg-orange-50 dark:bg-orange-900/20 px-2 py-0.5 rounded mt-1">
                            <span class="material-symbols-outlined text-[10px]">tune</span> Customized
                        </div>
                    </div>
                </div>
            </div>
         </div>

         <!-- Notes Block -->
         <div v-if="selectedOrder.notes" class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded-r-lg mb-8">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-yellow-600">comment</span>
                <div>
                     <div class="font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wide">Special Instructions</div>
                     <div class="text-gray-700 dark:text-gray-300 mt-1">{{ selectedOrder.notes }}</div>
                </div>
            </div>
         </div>

         <!-- Footer Actions -->
         <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
            <Button variant="ghost" @click="closeOrderModal">Close</Button>
            
            <Button 
                v-if="selectedOrder.status === 'queued'" 
                variant="primary"
                size="lg" 
                class="bg-primary-600 hover:bg-primary-700"
                @click="handleStatusChange(selectedOrder.id, 'preparing'); closeOrderModal()"
            >
                <span class="material-symbols-outlined mr-2">play_arrow</span>
                Start Making
            </Button>
            
            <Button 
                v-if="selectedOrder.status === 'preparing'" 
                variant="success" 
                size="lg"
                class="bg-green-600 hover:bg-green-700"
                @click="handleStatusChange(selectedOrder.id, 'ready'); closeOrderModal()"
            >
                <span class="material-symbols-outlined mr-2">check_circle</span>
                Mark Ready
            </Button>

             <Button 
                v-if="selectedOrder.status === 'preparing'" 
                variant="outline" 
                @click="handleStatusChange(selectedOrder.id, 'queued'); closeOrderModal()"
                class="text-gray-500 hover:text-red-500"
            >
                Return to Queue
            </Button>
         </div>
      </div>
    </AdminModal>
  </AdminLayout>
</template>