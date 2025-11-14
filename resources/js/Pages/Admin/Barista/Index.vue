<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminModal from '@/Components/Admin/UI/AdminModal.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import Button from '@/Components/Shared/Base/Button.vue';
import LoadingSpinner from '@/Components/Shared/Base/LoadingSpinner.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useOrderWorkflow } from '../../../composables/useOrderWorkflow.js';

// Workflow composable
const { 
    getStatusInfo, 
    getStatusClasses, 
    getStatusDotClasses, 
    getActionButtons, 
    getTimeInStatus,
    isOverdue,
    updateOrderStatus 
} = useOrderWorkflow()

// Component state
const orders = ref([])
const loading = ref(false)
const error = ref(null)
const lastRefresh = ref(null)

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
    loading.value = true
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
    try {
        await updateOrderStatus(orderId, newStatus)
        
        // Update local order status
        const order = orders.value.find(o => o.id === orderId)
        if (order) {
            order.status = newStatus
            order.updatedAt = new Date().toISOString()
        }
        
    } catch (error) {
        alert('Failed to update order status: ' + error.message)
    }
}

// Barista workflow methods
const startPreparation = (orderId) => {
    handleStatusChange(orderId, 'preparing')
}

const markReady = (orderId) => {
    handleStatusChange(orderId, 'ready')
}

const moveToQueue = (orderId) => {
    handleStatusChange(orderId, 'queued')
}

// Auto-refresh functionality (faster for barista - 15 seconds)
const startAutoRefresh = () => {
    if (autoRefreshInterval.value) {
        clearInterval(autoRefreshInterval.value)
    }
    
    if (autoRefreshEnabled.value) {
        autoRefreshInterval.value = setInterval(fetchOrders, 15000) // 15 seconds
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
    if (autoRefreshEnabled.value) {
        startAutoRefresh()
    } else {
        stopAutoRefresh()
    }
}

// Utility functions
const formatPrice = (price) => {
    return `₱${Number(price || 0).toFixed(2)}`
}

const formatTimeAgo = (timestamp) => {
    if (!timestamp) return 'Unknown'
    
    try {
        const date = new Date(timestamp)
        const now = new Date()
        const diff = now - date
        
        const minutes = Math.floor(diff / 60000)
        const hours = Math.floor(minutes / 60)
        
        if (hours > 0) return `${hours}h ${minutes % 60}m ago`
        if (minutes > 0) return `${minutes}m ago`
        return 'Just now'
    } catch (e) {
        return 'Unknown'
    }
}

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
    title="Barista Queue"
    page-title="Barista Queue"
    page-subtitle="Manage orders and track brewing progress"
  >
    <div class="barista-typography">
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <div class="text-xs text-gray-500">
          <span v-if="lastRefresh">
            Last updated: {{ lastRefresh.toLocaleTimeString() }}
          </span>
        </div>
        <button
          @click="fetchOrders"
          :disabled="loading"
          class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50"
        >
          <span class="material-symbols-outlined" :class="{ 'animate-spin': loading }">refresh</span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <LoadingSpinner 
      v-if="loading"
      message="Loading barista queue..."
      subtitle="Please wait while we fetch the latest orders"
    />

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-12 mb-6">
        <div class="text-red-500 mb-4">
            <span class="material-symbols-outlined text-6xl">error</span>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Error Loading Queue</h3>
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <Button variant="primary" @click="fetchOrders">
            <span class="material-symbols-outlined mr-2">refresh</span>
            Try Again
        </Button>
    </div>

    <!-- Queue Stats -->
    <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <CardWrapper padding="sm">
        <div class="flex items-center justify-between mb-2">
          <div class="p-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/20">
            <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-400">queue</span>
          </div>
          <span v-if="queueStats.queued > 5" class="text-sm text-red-600 font-medium">High</span>
        </div>
        <p class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ queueStats.queued }}</p>
        <p class="text-sm leading-snug text-gray-700 dark:text-gray-400">In Queue</p>
      </CardWrapper>
      
      <CardWrapper padding="sm">
        <div class="flex items-center justify-between mb-2">
          <div class="p-2 rounded-lg bg-yellow-100 dark:bg-yellow-900/20">
            <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400">local_cafe</span>
          </div>
          <span class="text-sm text-yellow-600 dark:text-yellow-400 font-medium">Active</span>
        </div>
        <p class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ queueStats.preparing }}</p>
        <p class="text-sm leading-snug text-gray-700 dark:text-gray-400">Preparing</p>
      </CardWrapper>
      
      <CardWrapper padding="sm">
        <div class="flex items-center justify-between mb-2">
          <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/20">
            <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">notifications_active</span>
          </div>
          <span class="text-sm text-purple-600 dark:text-purple-400 font-medium">Ready</span>
        </div>
        <p class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ queueStats.ready }}</p>
        <p class="text-sm leading-snug text-gray-700 dark:text-gray-400">Ready for Pickup</p>
      </CardWrapper>
      
      <CardWrapper padding="sm">
        <div class="flex items-center justify-between mb-2">
          <div class="p-2 rounded-lg bg-red-100 dark:bg-red-900/20">
            <span class="material-symbols-outlined text-red-600 dark:text-red-400">schedule</span>
          </div>
          <span class="text-sm text-red-600 dark:text-red-400 font-medium">Urgent</span>
        </div>
        <p class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ queueStats.overdue }}</p>
        <p class="text-sm leading-snug text-gray-700 dark:text-gray-400">Overdue</p>
      </CardWrapper>
    </div>


    <!-- Queue Sections -->
    <div class="space-y-6">
      <!-- Queued Orders -->
      <div v-if="queuedOrders.length > 0">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
            <span class="material-symbols-outlined text-indigo-600">queue</span>
            Queue - Ready to Start
            <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-sm">
              {{ queuedOrders.length }}
            </span>
          </h3>
          <div class="text-sm text-gray-500 leading-snug flex items-center gap-2">
            <i class="fas fa-info-circle"></i>
            Click any order card to view full preparation details
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
          <div 
            v-for="order in queuedOrders" 
            :key="order.id"
            :class="[
              'bg-white dark:bg-gray-900 border-2 rounded-xl p-4 transition-all cursor-pointer hover:shadow-lg',
              isOverdue(order.status, order.updatedAt) 
                ? 'border-red-300 bg-red-50 dark:bg-red-900/10' 
                : 'border-indigo-200 hover:border-indigo-300'
            ]"
            @click="openOrderDetails(order.id)"
          >
            <!-- Order Header -->
            <div class="flex items-center justify-between mb-3">
              <div>
                <h4 class="font-bold text-gray-900 dark:text-white">{{ order.orderNumber }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.tableNumber }}</p>
              </div>
              <div class="text-right">
                <div class="flex items-center gap-1 mb-1">
                  <span 
                    :class="[
                      'w-2 h-2 rounded-full', 
                      getStatusDotClasses(order.status),
                      isOverdue(order.status, order.updatedAt) ? 'animate-pulse' : ''
                    ]"
                  ></span>
                  <span class="text-sm font-medium text-indigo-600">
                    {{ getStatusInfo(order.status).label }}
                  </span>
                </div>
                <p class="text-xs text-gray-500">
                  {{ formatTimeAgo(order.updatedAt) }}
                </p>
              </div>
            </div>

            <!-- Items -->
            <div class="mb-4">
              <div class="space-y-2">
                <div 
                  v-for="item in order.items.slice(0, 2)" 
                  :key="`queued-${order.id}-${item.id}`"
                  class="bg-gray-50 dark:bg-gray-800 p-2 rounded-lg"
                >
                  <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2">
                      <span class="bg-indigo-500 text-white px-2 py-1 rounded-full text-xs font-bold min-w-[24px] text-center">
                        {{ item.quantity }}
                      </span>
                      <span class="font-medium">{{ item.name }}</span>
                      <span v-if="item.isCustomized" class="bg-orange-100 text-orange-700 px-1 py-0.5 rounded text-xs">
                        <i class="fas fa-cog text-xs"></i> Custom
                      </span>
                    </span>
                  </div>
                </div>
                <div v-if="order.items.length > 2" class="text-xs text-gray-500 text-center py-1">
                  +{{ order.items.length - 2 }} more items... (click to view all)
                </div>
              </div>
              <div v-if="order.notes" class="mt-2 text-xs text-blue-600 bg-blue-50 p-2 rounded border border-blue-200">
                <i class="fas fa-exclamation-circle mr-1"></i>
                <strong>Special:</strong> {{ order.notes }}
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
              <Button 
                @click.stop="openOrderDetails(order.id)"
                variant="secondary"
                size="sm"
                class="px-3"
                title="View Full Details"
              >
                <span class="material-symbols-outlined">visibility</span>
              </Button>
              <Button 
                @click.stop="startPreparation(order.id)"
                variant="primary"
                size="sm"
                class="flex-1"
                :class="isOverdue(order.status, order.updatedAt) ? 'animate-pulse' : ''"
              >
                <span class="material-symbols-outlined mr-2">play_arrow</span>
                {{ isOverdue(order.status, order.updatedAt) ? 'START (URGENT)' : 'Start Prep' }}
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Preparing Orders -->
      <div v-if="preparingOrders.length > 0">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
            <span class="material-symbols-outlined text-yellow-600">local_cafe</span>
            Currently Preparing
            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-sm">
              {{ preparingOrders.length }}
            </span>
          </h3>
          <div class="text-sm text-gray-500 leading-snug flex items-center gap-2">
            <i class="fas fa-eye"></i>
            View details for recipes and special instructions
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
          <div 
            v-for="order in preparingOrders" 
            :key="order.id"
            :class="[
              'bg-white dark:bg-gray-900 border-2 rounded-xl p-4 transition-all cursor-pointer',
              isOverdue(order.status, order.updatedAt) 
                ? 'border-red-300 bg-red-50 dark:bg-red-900/10' 
                : 'border-yellow-200 bg-yellow-50 dark:bg-yellow-900/10'
            ]"
            @click="openOrderDetails(order.id)"
          >
            <!-- Order Header -->
            <div class="flex items-center justify-between mb-3">
              <div>
                <h4 class="font-bold text-gray-900 dark:text-white">{{ order.orderNumber }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.tableNumber }}</p>
              </div>
              <div class="text-right">
                <div class="flex items-center gap-1 mb-1">
                  <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span>
                  <span class="text-sm font-medium text-yellow-600">Preparing</span>
                </div>
                <p class="text-xs text-gray-500">
                  {{ getTimeInStatus(order.updatedAt) }}
                </p>
              </div>
            </div>

            <!-- Items -->
            <div class="mb-4">
              <div class="space-y-2">
                <div 
                  v-for="item in order.items.slice(0, 2)" 
                  :key="`preparing-${order.id}-${item.id}`"
                  class="bg-yellow-50 dark:bg-yellow-900/20 p-2 rounded-lg border border-yellow-200"
                >
                  <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2">
                      <span class="bg-yellow-600 text-white px-2 py-1 rounded-full text-xs font-bold min-w-[24px] text-center">
                        {{ item.quantity }}
                      </span>
                      <span class="font-medium">{{ item.name }}</span>
                      <span v-if="item.isCustomized" class="bg-orange-100 text-orange-700 px-1 py-0.5 rounded text-xs">
                        <i class="fas fa-cog text-xs"></i> Custom
                      </span>
                    </span>
                    <span class="text-yellow-600 font-medium">
                      <i class="fas fa-coffee text-xs"></i>
                    </span>
                  </div>
                </div>
                <div v-if="order.items.length > 2" class="text-xs text-gray-500 text-center py-1">
                  +{{ order.items.length - 2 }} more items... (view details for full list)
                </div>
              </div>
              
              <!-- Preparation timer -->
              <div class="bg-yellow-100 dark:bg-yellow-900/30 p-2 rounded-lg border border-yellow-300 mb-2">
                <div class="flex items-center justify-center gap-2 text-yellow-800 dark:text-yellow-200">
                  <i class="fas fa-clock animate-pulse"></i>
                  <span class="text-sm font-medium">In Progress: {{ getTimeInStatus(order.updatedAt) }}</span>
                </div>
              </div>
              
              <div v-if="order.notes" class="mt-2 text-xs text-blue-600 bg-blue-50 p-2 rounded border border-blue-200">
                <i class="fas fa-exclamation-circle mr-1"></i>
                <strong>Special:</strong> {{ order.notes }}
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
              <Button 
                @click.stop="openOrderDetails(order.id)"
                variant="outline"
                size="sm"
                class="px-3"
                title="View Full Details"
              >
                <span class="material-symbols-outlined">visibility</span>
              </Button>
              <Button 
                @click.stop="markReady(order.id)"
                variant="success"
                size="sm"
                class="flex-1"
              >
                <span class="material-symbols-outlined mr-1">task_alt</span>
                Ready
              </Button>
              <Button 
                @click.stop="moveToQueue(order.id)"
                variant="secondary"
                size="sm"
                class="px-3"
                title="Move back to queue"
              >
                <span class="material-symbols-outlined">queue</span>
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Ready Orders -->
      <div v-if="readyOrders.length > 0">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
            <span class="material-symbols-outlined text-purple-600 animate-pulse">notifications_active</span>
            Ready for Pickup
            <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-sm">
              {{ readyOrders.length }}
            </span>
          </h3>
          <div class="text-sm text-gray-500 leading-snug flex items-center gap-2">
            <i class="fas fa-bell"></i>
            Notify staff when customer arrives
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <div 
            v-for="order in readyOrders" 
            :key="order.id"
            class="bg-purple-50 dark:bg-gray-900 border-2 border-purple-200 dark:bg-purple-900/10 rounded-xl p-4 transition-all cursor-pointer"
            @click="openOrderDetails(order.id)"
          >
            <!-- Order Header -->
            <div class="text-center mb-3">
              <h4 class="font-bold text-gray-900 dark:text-white text-lg">{{ order.orderNumber }}</h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.tableNumber }}</p>
              <div class="flex items-center justify-center gap-1 mt-1">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                <span class="text-sm font-medium text-purple-600">Ready</span>
              </div>
            </div>

            <!-- Customer Info -->
            <div class="text-center text-sm text-gray-600 dark:text-gray-400 mb-2">
              {{ order.customerName }}
            </div>

            <!-- Items Count -->
            <div class="text-center">
              <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-sm font-medium">
                {{ order.items.length }} item{{ order.items.length > 1 ? 's' : '' }}
              </span>
            </div>

            <!-- Time Ready -->
            <div class="text-center mt-2 text-xs text-gray-500 mb-3">
              Ready for: {{ getTimeInStatus(order.updatedAt) }}
            </div>

            <!-- Action Button for Ready Orders -->
            <div class="text-center">
              <Button 
                @click.stop="openOrderDetails(order.id)"
                variant="outline"
                size="sm"
                class="px-4"
              >
                <span class="material-symbols-outlined mr-1">visibility</span>
                View Details
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty States -->
      <div v-if="orders.length === 0" class="text-center py-16">
        <div class="text-gray-400 mb-6">
          <span class="material-symbols-outlined text-6xl">local_cafe</span>
        </div>
        <h3 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mb-3">No Orders in Queue</h3>
        <p class="text-gray-600 dark:text-gray-400">
          Orders will appear here once they're confirmed by admin
        </p>
      </div>

      <div v-else-if="queuedOrders.length === 0 && preparingOrders.length === 0 && readyOrders.length === 0" class="text-center py-16">
        <div class="text-green-400 mb-6">
          <span class="material-symbols-outlined text-6xl">check_circle</span>
        </div>
        <h3 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mb-3">All Caught Up!</h3>
        <p class="text-gray-600 dark:text-gray-400">
          No orders in queue. Great job team! ☕
        </p>
      </div>
    </div>

    <!-- Order Details Modal -->
    <AdminModal
      :show="showOrderModal"
      :title="`Order ${selectedOrder?.orderNumber}`"
      subtitle="Order details and preparation info"
      icon="local_cafe"
      max-width="3xl"
      animation-type="fade"
      @close="closeOrderModal"
    >
      <div v-if="selectedOrder" class="space-y-6">
        <!-- Order Summary -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold tracking-tight text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">receipt</span>
            Order Details
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Order Number</label>
                <p class="text-base font-semibold text-black dark:text-white">{{ selectedOrder.orderNumber }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Table</label>
                <p class="text-base text-black dark:text-white">{{ selectedOrder.tableNumber }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Customer</label>
                <p class="text-base text-black dark:text-white">{{ selectedOrder.customerName }}</p>
              </div>
            </div>
            
            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Status</label>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium border" 
                      :class="getStatusClasses(selectedOrder.status)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClasses(selectedOrder.status)"></span>
                  {{ getStatusInfo(selectedOrder.status).label }}
                </span>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Time in Status</label>
                <p class="text-base text-black dark:text-white">{{ getTimeInStatus(selectedOrder.updatedAt) }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Total</label>
                <p class="text-base font-bold text-black dark:text-white">{{ formatPrice(selectedOrder.total) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Items -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold tracking-tight text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">restaurant</span>
            Items to Prepare
            <span class="bg-primary-100 text-primary-800 px-2 py-1 rounded-full text-sm">
              {{ selectedOrder.items?.length || 0 }} item{{ (selectedOrder.items?.length || 0) !== 1 ? 's' : '' }}
            </span>
          </h4>
          
          <div class="space-y-3">
            <div 
              v-for="(item, index) in selectedOrder.items" 
              :key="`modal-${selectedOrder.id}-${item.id}-${index}`"
              class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:border-primary-200 transition-colors"
            >
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <div class="bg-primary-100 text-primary-800 px-2 py-1 rounded-full text-sm font-bold">
                      {{ item.quantity }}x
                    </div>
                    <p class="font-medium text-black dark:text-white text-lg">
                      {{ item.name }}
                    </p>
                    <span v-if="item.isCustomized" class="bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs">
                      <i class="fas fa-cog mr-1"></i> Custom
                    </span>
                  </div>
                  
                  <!-- Item details for preparation -->
                  <div class="text-sm text-black/60 dark:text-white/60 space-y-1">
                    <div class="flex items-center gap-4">
                      <span><strong>Price:</strong> {{ formatPrice(item.price) }} each</span>
                      <span><strong>Subtotal:</strong> {{ formatPrice(item.price * item.quantity) }}</span>
                    </div>
                    
                    <!-- Show customization details if available -->
                    <div v-if="item.isCustomized && selectedOrder.originalData?.items" class="mt-2">
                      <div class="bg-orange-50 dark:bg-orange-900/20 p-3 rounded-lg border border-orange-200 dark:border-orange-800">
                        <p class="text-orange-800 dark:text-orange-200 font-medium mb-1">
                          <i class="fas fa-tools mr-1"></i> Customization Details:
                        </p>
                        <div class="text-xs text-orange-700 dark:text-orange-300 space-y-1">
                          <!-- This would show actual customization details from the original order -->
                          <p>• Check original order for specific customizations</p>
                          <p>• Special size, temperature, or ingredient modifications</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Preparation checklist -->
                <div class="text-right">
                  <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-2 text-center min-w-[80px]">
                    <div class="text-green-800 dark:text-green-200 font-bold text-lg">
                      {{ formatPrice(item.price * item.quantity) }}
                    </div>
                    <div class="text-xs text-green-600 dark:text-green-400">
                      Total
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Special instructions section -->
            <div v-if="selectedOrder.notes" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
              <h5 class="text-blue-900 dark:text-blue-100 font-bold mb-2 flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                Special Instructions
              </h5>
              <p class="text-blue-800 dark:text-blue-200 font-medium">
                {{ selectedOrder.notes }}
              </p>
            </div>
            
            <!-- Customer and service info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
              <div class="bg-white dark:bg-gray-900/50 rounded-lg p-3 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Table</div>
                <div class="font-bold text-black dark:text-white">{{ selectedOrder.tableNumber }}</div>
              </div>
              <div class="bg-white dark:bg-gray-900/50 rounded-lg p-3 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Customer</div>
                <div class="font-bold text-black dark:text-white">{{ selectedOrder.customerName }}</div>
              </div>
              <div class="bg-white dark:bg-gray-900/50 rounded-lg p-3 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-gray-600 dark:text-gray-400 text-xs mb-1">Payment</div>
                <div class="font-bold text-black dark:text-white">
                  {{ selectedOrder.paymentMethod === 'cash' ? 'Cash' : 'GCash' }}
                </div>
              </div>
            </div>

            <!-- Order total summary -->
            <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4 border border-primary-200 dark:border-primary-800">
              <div class="flex justify-between items-center">
                <span class="text-primary-800 dark:text-primary-200 font-medium">Order Total:</span>
                <span class="text-primary-900 dark:text-primary-100 font-bold text-xl">
                  {{ formatPrice(selectedOrder.total) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Preparation Guidelines -->
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-6 border border-yellow-200 dark:border-yellow-800">
          <h4 class="text-lg font-bold tracking-tight text-yellow-900 dark:text-yellow-100 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-yellow-600">lightbulb</span>
            Preparation Guidelines
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <div class="bg-white dark:bg-gray-900/50 rounded-lg p-3 border border-yellow-200 dark:border-yellow-800">
                <h5 class="font-medium text-yellow-900 dark:text-yellow-100 mb-1 flex items-center gap-1">
                  <i class="fas fa-clock text-sm"></i> Timing
                </h5>
                <p class="text-sm text-yellow-800 dark:text-yellow-200">Standard prep: 3-5 minutes per drink</p>
              </div>
              <div class="bg-white dark:bg-gray-900/50 rounded-lg p-3 border border-yellow-200 dark:border-yellow-800">
                <h5 class="font-medium text-yellow-900 dark:text-yellow-100 mb-1 flex items-center gap-1">
                  <i class="fas fa-thermometer-half text-sm"></i> Temperature
                </h5>
                <p class="text-sm text-yellow-800 dark:text-yellow-200">Coffee: 160-180°F, Milk: 150-160°F</p>
              </div>
            </div>
            <div class="space-y-2">
              <div class="bg-white dark:bg-gray-900/50 rounded-lg p-3 border border-yellow-200 dark:border-yellow-800">
                <h5 class="font-medium text-yellow-900 dark:text-yellow-100 mb-1 flex items-center gap-1">
                  <i class="fas fa-check-circle text-sm"></i> Quality
                </h5>
                <p class="text-sm text-yellow-800 dark:text-yellow-200">Check taste, temperature, and presentation</p>
              </div>
              <div class="bg-white dark:bg-gray-900/50 rounded-lg p-3 border border-yellow-200 dark:border-yellow-800">
                <h5 class="font-medium text-yellow-900 dark:text-yellow-100 mb-1 flex items-center gap-1">
                  <i class="fas fa-user text-sm"></i> Service
                </h5>
                <p class="text-sm text-yellow-800 dark:text-yellow-200">Call customer name when ready</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <template #footer>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <button
              @click="closeOrderModal"
              class="px-6 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
            >
              <span class="material-symbols-outlined mr-2">close</span>
              Close
            </button>
            <button class="px-6 py-2 rounded-xl bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800 transition-all font-medium">
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">print</span>
                Print Order
              </span>
            </button>
          </div>

          <div class="flex items-center gap-3">
            <!-- Status-specific action buttons with enhanced visibility -->
            <button
              v-if="selectedOrder.status === 'queued'"
              @click="startPreparation(selectedOrder.id); closeOrderModal()"
              class="px-8 py-3 rounded-xl bg-gradient-to-r from-primary to-primary-600 text-white hover:from-primary-600 hover:to-primary-700 hover:shadow-lg transition-all font-bold text-lg"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-xl">play_arrow</span>
                Start Preparation
              </span>
            </button>
            <button
              v-else-if="selectedOrder.status === 'preparing'"
              @click="markReady(selectedOrder.id); closeOrderModal()"
              class="px-8 py-3 rounded-xl bg-gradient-to-r from-green-500 to-green-600 text-white hover:from-green-600 hover:to-green-700 hover:shadow-lg transition-all font-bold text-lg"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-xl">task_alt</span>
                Mark Ready
              </span>
            </button>
            <button
              v-else-if="selectedOrder.status === 'ready'"
              class="px-8 py-3 rounded-xl bg-gradient-to-r from-purple-500 to-purple-600 text-white transition-all font-bold text-lg cursor-default"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-xl">check_circle</span>
                Ready for Pickup
              </span>
            </button>
            
            <!-- Secondary actions for preparing orders -->
            <button
              v-if="selectedOrder.status === 'preparing'"
              @click="moveToQueue(selectedOrder.id); closeOrderModal()"
              class="px-4 py-2 rounded-xl border border-yellow-300 text-yellow-700 hover:bg-yellow-50 transition-all font-medium"
              title="Move back to queue if needed"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined">queue</span>
                Back to Queue
              </span>
            </button>
          </div>
        </div>
      </template>
    </AdminModal>
    </div>
  </AdminLayout>
</template>

<style scoped>
.barista-typography {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
  letter-spacing: -0.01em;
}

.barista-typography h1,
.barista-typography h2,
.barista-typography h3,
.barista-typography h4,
.barista-typography h5,
.barista-typography h6 {
  font-family: 'Poppins', -apple-system, sans-serif;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.barista-typography .text-caption {
  font-family: 'Inter', sans-serif;
  letter-spacing: 0.01em;
}
</style>