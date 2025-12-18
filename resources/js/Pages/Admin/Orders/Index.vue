<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Admin/UI/Pagination.vue';
import AdminModal from '@/Components/Admin/UI/AdminModal.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import TableActionsDropdown from '@/Components/Admin/UI/TableActionsDropdown.vue';
import OrderStatusBadge from '@/Components/Admin/UI/OrderStatusBadge.vue';
import OrderTypeBadge from '@/Components/Admin/UI/OrderTypeBadge.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const activeTab = ref('all');

const tabs = [
  { id: 'all', label: 'All Orders', icon: 'fas fa-list' },
  { id: 'kitchen', label: 'Kitchen', icon: 'fas fa-fire', count: 0 },
  { id: 'individual', label: 'Individual', icon: 'fas fa-user' },
  { id: 'group', label: 'One Bill', icon: 'fas fa-users' },
];

// Helper to determine if order is Group (One Bill)
const isGroupOrder = (order) => {
    // Logic: If order has "One Bill" tag or multiple distinct users adding items
    // For now, checks if tableType implies a group setting or metadata
    return order.orderType === 'Group' || order.isGroup; 
};

// Order data from API
const orders = ref([
  // Fallback sample data if API fails or for initial render
  {
    id: 'A001',
    sessionId: '#1247',
    tableType: 'Table 5',
    orderType: 'Dine In',
    items: { count: 3, description: 'Burger, Fries, Coke' },
    total: 18.90,
    status: { text: 'Ready', color: 'purple' },
    payment: { status: 'Unpaid', color: 'red' },
    time: '5 min ago',
    rawStatus: 'ready'
  }
]);

// Loading and error states
const loading = ref(false)
const error = ref(null)
const lastRefresh = ref(null)

// Fetch orders from API
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
      const errorText = await response.text()
      throw new Error(`Failed to fetch orders: ${response.status} - ${errorText}`)
    }
    
    const data = await response.json()
    
    if (!Array.isArray(data)) {
      throw new Error('API returned invalid data format')
    }
    
    // Transform API orders to display format
    const transformedOrders = data.map(order => {
      // Detect if total is in cents (database) or decimal (JSON storage)
      const isInCents = order.total > 1000 || (order.original_data && order.total > order.items?.reduce((sum, item) => sum + (item.price || 0), 0) * 10)
      const displayTotal = isInCents ? order.total / 100 : parseFloat(order.total) || 0
      
        // Handle new checkout format orders
      if (order.original_data) {
        // Determine payment status display
        const isPaid = order.payment_status === 'paid';
        let paymentStatusText = 'Pending';
        let paymentColor = 'red';

        if (isPaid) {
           paymentStatusText = `Paid (${order.payment_method === 'gcash' ? 'GCash' : 'Cash'})`;
           paymentColor = 'green';
        } else if (order.payment_method === 'cash') {
           paymentStatusText = 'Pay Cash';
           paymentColor = 'orange';
        }

        return {
          id: order.order_number || order.id,
          sessionId: `#${order.id.toString().slice(-4)}`,
          tableType: order.table?.number ? `Table ${order.table.number}` : (order.table_number || 'Table 1'),
          orderType: order.original_data.is_group_order ? 'Group' : 'Customer Order',
          isGroup: !!order.original_data.is_group_order,
          deviceInfo: {
            ip: 'Customer Device',
            type: 'Mobile/Web'
          },
          items: {
            count: order.items?.length || 0,
            description: (order.items || []).map(item => 
              `${item.name}${item.quantity > 1 ? ` (${item.quantity})` : ''}`
            ).join(', ') || 'No items'
          },
          total: displayTotal,
          tax: 0, // Tax not included in checkout orders
          payment: {
            status: paymentStatusText,
            method: order.payment_method,
            color: paymentColor
          },
          status: {
            text: capitalizeStatus(order.status),
            color: getStatusColor(order.status)
          },
          time: formatTime(order.created_at),
          // Store original data for detail view
          originalOrder: order,
          // Add raw status for debugging
          rawStatus: order.status
        }
      } else {
        // Handle legacy admin format orders
        return {
          id: order.order_number || order.id,
          sessionId: `#${order.id}`,
          tableType: `Table ${order.table_number || 1}`,
          orderType: 'Admin Order',
          deviceInfo: {
            ip: 'Admin Panel',
            type: 'Backend'
          },
          items: {
            count: order.items?.length || 0,
            description: order.items?.map(item => 
              `${item.name}${item.quantity > 1 ? ` (${item.quantity})` : ''}`
            ).join(', ') || 'No items'
          },
          total: displayTotal,
          tax: 0,
          payment: {
            status: 'Manual Entry',
            method: 'admin',
            color: 'blue'
          },
          status: {
            text: capitalizeStatus(order.status || 'received'),
            color: getStatusColor(order.status || 'received')
          },
          time: formatTime(order.created_at),
          originalOrder: order,
          rawStatus: order.status || 'received'
        }
      }
    })
    
    orders.value = transformedOrders
    lastRefresh.value = new Date()
    
  } catch (err) {
    console.error('Error fetching orders:', err)
    error.value = err.message
  } finally {
    loading.value = false
  }
}

// Utility functions
const capitalizeStatus = (status) => {
  const statusMap = {
    'received': 'Received',
    'confirmed': 'Confirmed',
    'queued': 'Queued',
    'preparing': 'Preparing', 
    'ready': 'Ready',
    'served': 'Served',
    'completed': 'Completed',
    'cancelled': 'Cancelled',
    'paid': 'Received', // Map paid status to Received for display
    'pending': 'Received', // Map pending to Received
    'payment_failed': 'Cancelled' // Map failed payments to cancelled
  }
  return statusMap[status] || status
}

const getStatusColor = (status) => {
  const colorMap = {
    'received': 'orange',
    'confirmed': 'blue',
    'queued': 'indigo',
    'preparing': 'yellow',
    'ready': 'purple',
    'served': 'green',
    'completed': 'green',
    'cancelled': 'red',
    'paid': 'orange', // Paid orders show as orange (new/received)
    'pending': 'orange',
    'payment_failed': 'red'
  }
  return colorMap[status] || 'gray'
}

const formatTime = (timestamp) => {
  if (!timestamp) return 'Unknown'
  
  try {
    const date = new Date(timestamp)
    const now = new Date()
    const diff = now - date
    
    const minutes = Math.floor(diff / 60000)
    const hours = Math.floor(minutes / 60)
    const days = Math.floor(hours / 24)
    
    if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`
    if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`
    if (minutes > 0) return `${minutes} min ago`
    return 'Just now'
  } catch (e) {
    return 'Unknown'
  }
}

// Update order status
const updateOrderStatus = async (displayOrderId, newStatus) => {
  try {
    // Find the order by display ID
    const order = orders.value.find(o => o.id === displayOrderId)
    if (!order) {
      console.error('Order not found:', displayOrderId)
      alert('Order not found')
      return
    }
    
    // Get the actual database ID (for original_data orders, use the database id, otherwise use display id)
    const databaseOrderId = order.originalOrder?.id || order.id
    console.log('Updating order status:', { displayOrderId, databaseOrderId, newStatus, order })
    
    const response = await fetch(`/api/orders/${databaseOrderId}/status`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ status: newStatus })
    })
    
    if (!response.ok) {
      const errorText = await response.text()
      throw new Error(`Failed to update order status: ${response.status} - ${errorText}`)
    }
    
    // Update local order status
    order.status = {
      text: capitalizeStatus(newStatus),
      color: getStatusColor(newStatus)
    }
    order.rawStatus = newStatus
    
    // If confirmed, auto-queue after a delay
    if (newStatus === 'confirmed') {
      setTimeout(async () => {
        await updateOrderStatus(displayOrderId, 'queued')
      }, 1000)
    }
    
  } catch (err) {
    console.error('Error updating order status:', err)
    alert('Failed to update order status: ' + err.message)
  }
}

// Mark order as paid
const markOrderAsPaid = async (displayOrderId) => {
  try {
    // Find the order by display ID
    const order = orders.value.find(o => o.id === displayOrderId)
    if (!order) {
      alert('Order not found')
      return
    }
    
    const databaseOrderId = order.originalOrder?.id || order.id
    
    const response = await fetch(`/api/orders/${databaseOrderId}/payment`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (!response.ok) {
      const errorText = await response.text()
      throw new Error(`Failed to mark order as paid: ${response.status} - ${errorText}`)
    }
    
    // Update local order
    const isCash = order.payment.method === 'cash'
    order.payment.status = `Paid (${isCash ? 'Cash' : (order.payment.method === 'gcash' ? 'GCash' : 'Other')})`
    order.payment.color = 'green'
    
    // Update status to confirmed locally (to match backend auto-confirm)
    if (order.rawStatus === 'received') {
        order.status = {
          text: 'Confirmed',
          color: getStatusColor('confirmed')
        }
        order.rawStatus = 'confirmed'
    }
    
  } catch (err) {
    console.error('Error marking order as paid:', err)
    alert('Failed to mark order as paid: ' + err.message)
  }
}

// Auto-refresh functionality
const autoRefreshEnabled = ref(true)
const autoRefreshInterval = ref(null)

const startAutoRefresh = () => {
  if (autoRefreshInterval.value) {
    clearInterval(autoRefreshInterval.value)
  }
  
  if (autoRefreshEnabled.value) {
    autoRefreshInterval.value = setInterval(() => {
      fetchOrders()
    }, 30000) // Refresh every 30 seconds
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

// Load orders on component mount
onMounted(() => {
  fetchOrders()
  startAutoRefresh()
})

// Cleanup on unmount
onMounted(() => {
  onUnmounted(() => {
    stopAutoRefresh()
  })
})

// Filter and sort state
const searchQuery = ref('')
const activeStatus = ref('all') // 'all' | received | confirmed | queued | preparing | ready | served | completed
const typeFilter = ref('all')   // 'all' | 'dine_in' | 'takeout'
const categoryFilter = ref('all') // 'all' | 'cold_drinks' | 'hot_drinks' | 'pastries' | 'sandwiches'
const sortKey = ref('recent')   // 'recent' | 'status' | 'amount' | 'table' | 'priority'
const sortDir = ref('desc')

// Utility function for normalization
const normalized = (s) => (s || '').toString().toLowerCase()

// Category mapping for item names
const getItemCategory = (itemName) => {
  const name = normalized(itemName)
  if (name.includes('iced') || name.includes('cold') || name.includes('frapp') || name.includes('smoothie')) {
    return 'cold_drinks'
  }
  if (name.includes('latte') || name.includes('americano') || name.includes('cappuccino') || name.includes('espresso') || name.includes('macchiato') || name.includes('coffee') || name.includes('tea') || name.includes('hot')) {
    return 'hot_drinks'
  }
  if (name.includes('muffin') || name.includes('croissant') || name.includes('pastry') || name.includes('cake') || name.includes('cookie') || name.includes('bread')) {
    return 'pastries'
  }
  if (name.includes('sandwich') || name.includes('wrap') || name.includes('burger') || name.includes('salad') || name.includes('bowl')) {
    return 'sandwiches'
  }
  return 'other'
}

// Check if order contains items from selected category
const orderHasCategory = (order, category) => {
  if (category === 'all') return true
  
  // Check items description for category keywords
  const description = normalized(order.items?.description || '')
  const itemNames = description.split(',').map(name => name.trim())
  
  return itemNames.some(itemName => {
    const itemCategory = getItemCategory(itemName)
    return itemCategory === category
  })
}

// Filtering logic
const filteredOrders = computed(() => {
  const q = normalized(searchQuery.value)
  return orders.value.filter(o => {
    // Tab Filter
    if (activeTab.value === 'kitchen') {
        if (!['queued', 'preparing'].includes(o.rawStatus || normalized(o.status?.text))) return false;
    } else if (activeTab.value === 'individual') {
        if (isGroupOrder(o)) return false;
    } else if (activeTab.value === 'group') {
        if (!isGroupOrder(o)) return false;
    }

    // Existing search filter
    if (!q) return true
    const hay = [o.id, o.sessionId, o.tableType, o.orderType, o.customerName, o.items?.description]
      .map(v => normalized(v)).join(' ')
    return hay.includes(q)
  })
})

// Status ranking for sorting
const statusRank = { received:1, confirmed:2, queued:3, preparing:4, ready:5, served:6, completed:7 }

// Sorting logic
const sortedOrders = computed(() => {
  const list = [...filteredOrders.value]
  switch (sortKey.value) {
    case 'status':
      return list.sort((a,b) => (statusRank[(a.rawStatus||'')]|0) - (statusRank[(b.rawStatus||'')]|0))
    case 'amount':
      return list.sort((a,b) => (b.total||0) - (a.total||0))
    case 'table':
      const n = v => parseInt((v?.match(/\d+/)||[])[0]||0,10)
      return list.sort((a,b) => n(a.tableType) - n(b.tableType))
    case 'priority':
      // treat earlier statuses as higher priority
      return list.sort((a,b) => (statusRank[(a.rawStatus||'')]|0) - (statusRank[(b.rawStatus||'')]|0))
    case 'recent':
    default:
      return list // already in recent order from API
  }
})

// Pagination logic
const currentPage = ref(1);
const itemsPerPage = ref(5); // 5 orders per page
const totalItems = computed(() => sortedOrders.value.length)
const pageLoading = ref(false)

// Computed property for displayed orders
const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return sortedOrders.value.slice(start, start + itemsPerPage.value);
});

// Handle page change
const handlePageChange = async (page) => {
  pageLoading.value = true;
  await new Promise(resolve => setTimeout(resolve, 300));
  currentPage.value = page;
  pageLoading.value = false;
};

const getStatusClasses = (color) => {
  const colorMap = {
    yellow: 'text-yellow-600 dark:text-yellow-400',
    green: 'text-green-600 dark:text-green-400',
    blue: 'text-blue-600 dark:text-blue-400',
    purple: 'text-purple-600 dark:text-purple-400',
    orange: 'text-orange-600 dark:text-orange-400',
    red: 'text-red-600 dark:text-red-400'
  };
  return colorMap[color] || 'text-gray-600 dark:text-gray-400';
};

const getStatusDotClasses = (color) => {
  const colorMap = {
    yellow: 'bg-yellow-500',
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
    orange: 'bg-orange-500',
    red: 'bg-red-500'
  };
  return colorMap[color] || 'bg-gray-500';
};

// Modal state
const showOrderModal = ref(false);
const selectedOrder = ref(null);


const openOrderDetails = (orderId) => {
  const order = orders.value.find(o => o.id === orderId);
  if (order) {
    selectedOrder.value = order;
    showOrderModal.value = true;
  }
};

const closeOrderModal = () => {
  showOrderModal.value = false;
  selectedOrder.value = null;
};

// Edit order function
const editOrder = (orderId) => {
  router.get(`/admin/orders/${orderId}/edit`);
};

// Define order actions
const getOrderActions = (order) => {
  return [
    {
      key: 'mark_paid',
      label: 'Mark as Paid',
      icon: 'payments',
      colorClass: 'text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20',
      // Show only if not already paid (check both text content and logic)
      show: () => {
        const status = order.payment?.status || '';
        return !status.toString().toLowerCase().includes('paid');
      },
      onClick: () => markOrderAsPaid(order.id)
    },
    {
      key: 'view',
      label: 'View Details',
      icon: 'visibility',
      onClick: () => openOrderDetails(order.id)
    },
    {
      key: 'edit',
      label: 'Edit Order',
      icon: 'edit',
      href: `/admin/orders/${order.id}/edit`
    },
    {
      key: 'confirm',
      label: 'Confirm',
      icon: 'check_circle',
      colorClass: 'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20',
      show: () => order.status && (order.status.text === 'Received' || order.rawStatus === 'received'),
      onClick: () => updateOrderStatus(order.id, 'confirmed')
    },
    {
      key: 'queue',
      label: 'Queue',
      icon: 'queue',
      colorClass: 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20',
      show: () => order.status && (order.status.text === 'Confirmed' || order.rawStatus === 'confirmed'),
      onClick: () => updateOrderStatus(order.id, 'queued')
    },
    {
      key: 'serve',
      label: 'Serve',
      icon: 'local_shipping',
      colorClass: 'text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20',
      show: () => order.status && (order.status.text === 'Ready' || order.rawStatus === 'ready'),
      onClick: () => updateOrderStatus(order.id, 'served')
    },
    {
      key: 'complete',
      label: 'Complete',
      icon: 'check_circle',
      colorClass: 'text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20',
      show: () => order.status && (order.status.text === 'Served' || order.rawStatus === 'served'),
      onClick: () => updateOrderStatus(order.id, 'completed')
    },
    {
      key: 'process',
      label: 'Process',
      icon: 'arrow_forward',
      colorClass: 'text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20',
      show: () => !order.status || (!['Received', 'Confirmed', 'Queued', 'Preparing', 'Ready', 'Served', 'Completed'].includes(order.status.text)),
      onClick: () => updateOrderStatus(order.id, 'confirmed')
    },
    {
      key: 'receipt',
      label: 'Receipt',
      icon: 'receipt',
      show: () => order.status && (order.status.text === 'Completed' || order.rawStatus === 'completed'),
      onClick: () => {
        // TODO: Implement print receipt functionality
        console.log('Print receipt for order:', order.id);
      }
    }
  ];
};

// Handle order action
const onOrderAction = ({ key, row }) => {
  // This is handled by individual onClick functions in getOrderActions
  console.log('Order action:', key, row.id);
};
</script>

<template>
  <AdminLayout 
    title="Order Management"
    page-title="Order Management"
    page-subtitle="Manage and track all customer orders across all tables"
  >
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <div class="relative">
          <input v-model="searchQuery" type="search" placeholder="Search by order #, table, or device..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ec7813]">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
        </div>
        
        <!-- Tab Filter Dropdown (Mobile/Compact) -->
         <select v-model="activeTab" class="sm:hidden px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#ec7813]">
             <option v-for="tab in tabs" :key="tab.id" :value="tab.id">{{ tab.label }}</option>
        </select>

        <button 
          @click="router.get('/admin/barista')"
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-yellow-600 text-white hover:bg-yellow-700 transition-all"
        >
          <span class="material-symbols-outlined">local_cafe</span>
          <span>Barista Queue</span>
        </button>
        <button 
          @click="router.get('/admin/orders/add')"
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[#ec7813] text-white hover:bg-[#ea580c] transition-all"
        >
          <span class="material-symbols-outlined">add</span>
          <span>New Order</span>
        </button>
      </div>
    </div>

    <!-- Main Tabs -->
    <div class="hidden sm:flex border-b border-gray-200 dark:border-gray-700 mb-6">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button 
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-[#ec7813] text-[#ec7813]'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
            'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all'
          ]"
        >
          <i :class="[tab.icon, activeTab === tab.id ? 'text-[#ec7813]' : 'text-gray-400 group-hover:text-gray-500', '-ml-0.5 mr-2 h-5 w-5']"></i>
          <span>{{ tab.label }}</span>
          <span v-if="tab.count > 0" class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white">
            {{ tab.count }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Status Filter Tabs (Pills) -->
    <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2 scrollbar-hide">
       <!-- Simplified status filters just use activeStatus directly -->
      <button 
        @click="activeStatus='all'" 
        :class="activeStatus==='all' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap font-medium transition-all"
      >
        All
      </button>
       <button 
        @click="activeStatus='received'" 
        :class="activeStatus==='received' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all flex items-center gap-2"
      >
        <span class="w-2 h-2 rounded-full bg-orange-500"></span> Received
      </button>
       <button 
        @click="activeStatus='preparing'" 
        :class="activeStatus==='preparing' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all flex items-center gap-2"
      >
        <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span> Preparing
      </button>
       <button 
        @click="activeStatus='ready'" 
        :class="activeStatus==='ready' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all flex items-center gap-2"
      >
        <span class="w-2 h-2 rounded-full bg-purple-500"></span> Ready
      </button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-4">
             <div class="p-3 rounded-full bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400">
                <span class="material-symbols-outlined">inbox</span>
             </div>
             <div>
                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Pending</p>
                <p class="text-2xl font-bold font-display text-gray-900 dark:text-white">{{ orders.filter(o => o.status?.text === 'Received').length }}</p>
             </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-4">
             <div class="p-3 rounded-full bg-yellow-50 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400">
                <span class="material-symbols-outlined">skillet</span>
             </div>
             <div>
                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Kitchen</p>
                <p class="text-2xl font-bold font-display text-gray-900 dark:text-white">{{ orders.filter(o => ['Queued','Preparing'].includes(o.status?.text)).length }}</p>
             </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-4">
             <div class="p-3 rounded-full bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">
                <span class="material-symbols-outlined">notifications_active</span>
             </div>
             <div>
                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Ready</p>
                <p class="text-2xl font-bold font-display text-gray-900 dark:text-white">{{ orders.filter(o => o.status?.text === 'Ready').length }}</p>
             </div>
        </div>
         <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-4">
             <div class="p-3 rounded-full bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400">
                <span class="material-symbols-outlined">check_circle</span>
             </div>
             <div>
                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Served</p>
                <p class="text-2xl font-bold font-display text-gray-900 dark:text-white">{{ orders.filter(o => o.status?.text === 'Served').length }}</p>
             </div>
        </div>
    </div>

    <!-- Orders Table -->
    <CardWrapper class="overflow-hidden">
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
        <div class="flex items-center gap-2">
            <span class="font-bold text-gray-700 dark:text-gray-300">Live Orders</span>
             <span v-if="loading" class="animate-spin ml-2 text-gray-400"><i class="fas fa-circle-notch"></i></span>
        </div>
        <button @click="fetchOrders" class="text-sm text-[#ec7813] hover:underline flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">refresh</span> Refresh
        </button>
      </div>

      <div class="overflow-x-auto min-h-[300px]">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Table</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Items</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payment</th>
              <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-if="paginatedOrders.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <div class="flex flex-col items-center">
                    <span class="material-symbols-outlined text-4xl mb-2 opacity-50">search_off</span>
                    <span>No orders found matching your filters.</span>
                </div>
              </td>
            </tr>
            <tr v-for="order in paginatedOrders" :key="order.id" @click="openOrderDetails(order.id)" class="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-colors group">
              <!-- Order Info -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                   <span class="text-sm font-bold text-gray-900 dark:text-white font-mono">#{{ order.id }}</span>
                   <div class="text-xs text-gray-500 flex items-center mt-1">
                      <span class="material-symbols-outlined text-[14px] mr-1">schedule</span> {{ order.time }}
                   </div>
                </div>
              </td>
              
              <!-- Table / Type -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                    <OrderTypeBadge :is-group="isGroupOrder(order)" :table-number="order.tableType" />
                </div>
                <div class="text-xs text-gray-500 mt-1 pl-1 truncate max-w-[120px]" v-if="order.customerName">{{ order.customerName }}</div>
              </td>

              <!-- Status -->
              <td class="px-6 py-4 whitespace-nowrap">
                <OrderStatusBadge :status="order.rawStatus || 'received'" />
              </td>

              <!-- Items -->
              <td class="px-6 py-4">
                 <div class="text-sm text-gray-900 dark:text-white max-w-xs truncate font-medium">
                  {{ order.items.description }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ order.items.count }} item{{ order.items.count !== 1 ? 's' : '' }}
                </div>
              </td>

              <!-- Payment -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-gray-900 dark:text-white">₱{{ order.total }}</div>
                 <span 
                  class="text-xs font-semibold inline-flex items-center mt-0.5"
                  :class="order.payment.color === 'green' ? 'text-green-600' : 'text-red-500'"
                >
                  {{ order.payment.status }}
                </span>
              </td>

              <!-- Quick Actions -->
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                      <button 
                        v-if="['queued','received'].includes(order.rawStatus)"
                        @click="updateOrderStatus(order.id, 'preparing')"
                        class="text-xs bg-orange-50 text-orange-600 hover:bg-orange-100 border border-orange-200 px-2 py-1 rounded shadow-sm"
                        title="Start Preparing"
                    >
                        <i class="fas fa-fire"></i>
                    </button>
                     <button 
                        v-if="order.rawStatus === 'preparing'"
                        @click="updateOrderStatus(order.id, 'ready')"
                        class="text-xs bg-purple-50 text-purple-600 hover:bg-purple-100 border border-purple-200 px-2 py-1 rounded shadow-sm"
                        title="Mark Ready"
                    >
                        <i class="fas fa-bell"></i>
                    </button>
                    <button 
                        v-if="order.rawStatus === 'ready'"
                        @click="updateOrderStatus(order.id, 'served')"
                        class="text-xs bg-green-50 text-green-600 hover:bg-green-100 border border-green-200 px-2 py-1 rounded shadow-sm"
                         title="Mark Served"
                    >
                        <i class="fas fa-check"></i>
                    </button>

                    <TableActionsDropdown :actions="getOrderActions(order)" :row="order" @action="onOrderAction" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

       <!-- Pagination -->
      <Pagination 
        v-if="orders.length > 0"
        :current-page="currentPage"
        :total-items="totalItems"
        :items-per-page="itemsPerPage"
        @page-change="handlePageChange"
        class="border-t border-gray-200 dark:border-gray-700"
      />
    </CardWrapper>

    <!-- Order Details Modal -->
    <AdminModal
      :show="showOrderModal"
      :title="`Order ${selectedOrder?.id}`"
      max-width="2xl"
      @close="closeOrderModal"
    >
      <div v-if="selectedOrder" class="space-y-6">
        <!-- Header Info -->
        <div class="flex justify-between items-start">
             <div>
                 <h2 class="text-xl font-bold dark:text-white">Order #{{ selectedOrder.id }}</h2>
                 <p class="text-sm text-gray-500">{{ selectedOrder.time }}</p>
             </div>
             <div class="text-right">
                 <OrderStatusBadge :status="selectedOrder.rawStatus" />
             </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="block text-gray-500 text-xs uppercase font-bold">Table</span>
                <span class="font-medium dark:text-white">{{ selectedOrder.tableType }}</span>
            </div>
             <div>
                <span class="block text-gray-500 text-xs uppercase font-bold">Type</span>
                <span class="font-medium dark:text-white">{{ selectedOrder.orderType }}</span>
            </div>
             <div>
                <span class="block text-gray-500 text-xs uppercase font-bold">Session</span>
                <span class="font-medium dark:text-white">{{ selectedOrder.sessionId }}</span>
            </div>
             <div>
                <span class="block text-gray-500 text-xs uppercase font-bold">Payment</span>
                <span class="font-medium" :class="selectedOrder.payment.color === 'green' ? 'text-green-600' : 'text-red-500'">{{ selectedOrder.payment.status }}</span>
            </div>
        </div>

        <!-- Items -->
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <h3 class="font-bold text-gray-700 dark:text-gray-300 text-sm uppercase mb-3">Order Items</h3>
            <div class="flex justify-between items-center">
                <span class="text-gray-900 dark:text-white font-medium">{{ selectedOrder.items.description }}</span>
                <span class="text-gray-900 dark:text-white font-bold">₱{{ selectedOrder.total }}</span>
            </div>
            <p v-if="selectedOrder.originalOrder?.customer_notes" class="mt-2 text-sm text-orange-600 italic bg-orange-50 px-2 py-1 rounded border border-orange-100 inline-block">
                "{{ selectedOrder.originalOrder.customer_notes }}"
            </p>
        </div>

        <!-- Actions Footer -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
            <button @click="closeOrderModal" class="btn-secondary">Close</button>
            <button v-if="selectedOrder.payment.status === 'Unpaid'" @click="markOrderAsPaid(selectedOrder.id); closeOrderModal()" class="btn-success">Mark Paid</button>
            
            <!-- Workflow Buttons based on status -->
             <button v-if="['queued','received'].includes(selectedOrder.rawStatus)" @click="updateOrderStatus(selectedOrder.id, 'preparing'); closeOrderModal()" class="btn-primary">Start Preparing</button>
             <button v-if="selectedOrder.rawStatus === 'preparing'" @click="updateOrderStatus(selectedOrder.id, 'ready'); closeOrderModal()" class="btn-primary bg-purple-600 hover:bg-purple-700">Mark Ready</button>
             <button v-if="selectedOrder.rawStatus === 'ready'" @click="updateOrderStatus(selectedOrder.id, 'served'); closeOrderModal()" class="btn-success">Mark Served</button>
        </div>
      </div>
    </AdminModal>

  </AdminLayout>
</template>

<style>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
