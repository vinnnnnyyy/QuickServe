<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Admin/UI/Pagination.vue';
import AdminModal from '@/Components/Admin/UI/AdminModal.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import TableActionsDropdown from '@/Components/Admin/UI/TableActionsDropdown.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

// Order data from API
const orders = ref([
  // Fallback sample data for backward compatibility
  {
    id: 'A001',
    sessionId: '#1247',
    tableType: 'Table 5',
    orderType: 'Dine In',
    deviceInfo: {
      ip: '192.168.1.45',
      type: 'Mobile Device'
    },
    items: {
      count: 3,
      description: 'Latte (Large, Oat), Croissant, Muffin'
    },
    total: 18.90,
    tax: 1.89,
    payment: {
      status: 'Unpaid',
      method: '',
      color: 'red'
    },
    status: {
      text: 'Preparing',
      color: 'yellow'
    },
    time: '5 min ago'
  },
  {
    id: 'A002',
    sessionId: '#1248',
    tableType: 'Takeaway',
    orderType: 'Counter',
    deviceInfo: {
      ip: '192.168.1.67',
      type: 'Tablet Device'
    },
    items: {
      count: 1,
      description: 'Americano (Medium)'
    },
    total: 4.95,
    tax: 0.45,
    payment: {
      status: 'Paid (Card)',
      method: 'Card',
      color: 'green'
    },
    status: {
      text: 'Ready',
      color: 'purple'
    },
    time: '2 min ago'
  },
  {
    id: 'A003',
    sessionId: '#1249',
    tableType: 'Table 12',
    orderType: 'Dine In',
    deviceInfo: {
      ip: '192.168.1.89',
      type: 'Laptop Device'
    },
    items: {
      count: 2,
      description: 'Cappuccino (Large), Sandwich'
    },
    total: 13.75,
    tax: 1.25,
    payment: {
      status: 'Pending',
      method: '',
      color: 'orange'
    },
    status: {
      text: 'Confirmed',
      color: 'blue'
    },
    time: '1 min ago'
  },
  {
    id: 'A004',
    sessionId: '#1250',
    tableType: 'Table 8',
    orderType: 'Dine In',
    deviceInfo: {
      ip: '192.168.1.56',
      type: 'Tablet Device'
    },
    items: {
      count: 4,
      description: '2x Latte, Bagel, Pastry'
    },
    total: 27.23,
    tax: 2.48,
    payment: {
      status: 'Paid (E-Wallet)',
      method: 'E-Wallet',
      color: 'green'
    },
    status: {
      text: 'Completed',
      color: 'green'
    },
    time: '15 min ago'
  },
  {
    id: 'A005',
    sessionId: '#1251',
    tableType: 'Table 3',
    orderType: 'Dine In',
    deviceInfo: {
      ip: '192.168.1.23',
      type: 'Mobile Device'
    },
    items: {
      count: 1,
      description: 'Espresso (Double)'
    },
    total: 3.85,
    tax: 0.35,
    payment: {
      status: 'Paid (QR)',
      method: 'QR',
      color: 'green'
    },
    status: {
      text: 'Pending',
      color: 'orange'
    },
    time: '30 sec ago'
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
      // Handle new checkout format orders
      if (order.original_data) {
        return {
          id: order.order_number || order.id,
          sessionId: `#${order.id.toString().slice(-4)}`,
          tableType: order.table_number || 'Table 1',
          orderType: 'Customer Order',
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
          total: parseFloat(order.total) || 0,
          tax: 0, // Tax not included in checkout orders
          payment: {
            status: order.payment_method === 'cash' ? 'Pay Cash' : 'Paid (GCash)',
            method: order.payment_method,
            color: order.payment_method === 'cash' ? 'orange' : 'green'
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
          total: parseFloat(order.total) || 0,
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
    'cancelled': 'Cancelled'
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
    'cancelled': 'red'
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
    // type filter
    if (typeFilter.value !== 'all') {
      const isDineIn = o.orderType === 'Dine In'
      if (typeFilter.value === 'dine_in' && !isDineIn) return false
      if (typeFilter.value === 'takeout' && isDineIn) return false
    }
    // category filter
    if (!orderHasCategory(o, categoryFilter.value)) return false
    // status filter
    if (activeStatus.value !== 'all') {
      const s = (o.rawStatus || normalized(o.status?.text)).toLowerCase()
      if (s !== activeStatus.value) return false
    }
    // search filter
    if (!q) return true
    const hay = [o.id, o.sessionId, o.tableType, o.orderType, o.deviceInfo?.ip, o.deviceInfo?.type, o.items?.description, o.payment?.method]
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

// Computed property for displayed orders
const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return sortedOrders.value.slice(start, start + itemsPerPage.value);
});

// Handle page change
const handlePageChange = (page) => {
  currentPage.value = page;
  // In a real app, you would fetch data for the new page here
  console.log('Orders page changed to:', page);
};

const getStatusClasses = (color) => {
  const colorMap = {
    yellow: 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400',
    green: 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400',
    blue: 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400',
    purple: 'bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400',
    orange: 'bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400',
    red: 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400'
  };
  return colorMap[color] || colorMap.gray;
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
        <select v-model="typeFilter" class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#ec7813]">
          <option value="all">All Order Types</option>
          <option value="dine_in">Dine In</option>
          <option value="takeout">Takeout</option>
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

    <!-- Status Filter Tabs -->
    <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2">
      <button 
        @click="activeStatus='all'" 
        :class="activeStatus==='all' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap font-medium transition-all"
      >
        All Orders <span class="ml-1 bg-white/20 px-2 py-0.5 rounded-full text-xs">{{ totalItems }}</span>
      </button>
      <button 
        @click="activeStatus='received'" 
        :class="activeStatus==='received' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
          Received
        </span>
        <span class="ml-1 text-gray-500">{{ orders.filter(o => o.status?.text === 'Received').length }}</span>
      </button>
      <button 
        @click="activeStatus='confirmed'" 
        :class="activeStatus==='confirmed' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
          Confirmed
        </span>
        <span class="ml-1 text-gray-500">{{ orders.filter(o => o.status?.text === 'Confirmed').length }}</span>
      </button>
      <button 
        @click="activeStatus='queued'" 
        :class="activeStatus==='queued' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
          Queued
        </span>
        <span class="ml-1 text-gray-500">{{ orders.filter(o => o.status?.text === 'Queued').length }}</span>
      </button>
      <button 
        @click="activeStatus='preparing'" 
        :class="activeStatus==='preparing' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>
          Preparing
        </span>
        <span class="ml-1 text-gray-500">{{ orders.filter(o => o.status?.text === 'Preparing').length }}</span>
      </button>
      <button 
        @click="activeStatus='ready'" 
        :class="activeStatus==='ready' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
          Ready
        </span>
        <span class="ml-1 text-gray-500">{{ orders.filter(o => o.status?.text === 'Ready').length }}</span>
      </button>
      <button 
        @click="activeStatus='served'" 
        :class="activeStatus==='served' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-green-500 rounded-full"></span>
          Served
        </span>
        <span class="ml-1 text-gray-500">{{ orders.filter(o => o.status?.text === 'Served').length }}</span>
      </button>
      <button 
        @click="activeStatus='completed'" 
        :class="activeStatus==='completed' ? 'bg-[#ec7813] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-green-600 rounded-full"></span>
          Completed
        </span>
        <span class="ml-1 text-gray-500">{{ orders.filter(o => o.status?.text === 'Completed').length }}</span>
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
      <CardWrapper padding="sm">
        <div class="flex items-center justify-between mb-2">
          <div class="p-2 rounded-lg bg-orange-100 dark:bg-orange-900/20">
            <span class="material-symbols-outlined text-orange-600 dark:text-orange-400">pending</span>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ orders.filter(o => o.status?.text === 'Received').length }}</p>
        <p class="text-sm text-gray-700 dark:text-gray-400">Received</p>
      </CardWrapper>
      
      <CardWrapper padding="sm">
        <div class="flex items-center justify-between mb-2">
          <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/20">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">verified</span>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ orders.filter(o => o.status?.text === 'Confirmed').length }}</p>
        <p class="text-sm text-gray-700 dark:text-gray-400">Confirmed</p>
      </CardWrapper>
      
      <CardWrapper padding="sm">
        <div class="flex items-center justify-between mb-2">
          <div class="p-2 rounded-lg bg-yellow-100 dark:bg-yellow-900/20">
            <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400">local_cafe</span>
          </div>
          <span class="text-sm text-yellow-600 dark:text-yellow-400 font-medium">Active</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ orders.filter(o => o.status?.text === 'Preparing').length }}</p>
        <p class="text-sm text-gray-700 dark:text-gray-400">Preparing</p>
      </CardWrapper>
      
      <CardWrapper padding="sm">
        <div class="flex items-center justify-between mb-2">
          <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/20">
            <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">notifications_active</span>
          </div>
          <span class="text-sm text-purple-600 dark:text-purple-400 font-medium">Ready</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ orders.filter(o => o.status?.text === 'Ready').length }}</p>
        <p class="text-sm text-gray-700 dark:text-gray-400">Ready</p>
      </CardWrapper>
      
      <CardWrapper padding="sm">
        <div class="flex items-center justify-between mb-2">
          <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/20">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400">local_shipping</span>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ orders.filter(o => o.status?.text === 'Served').length }}</p>
        <p class="text-sm text-gray-700 dark:text-gray-400">Served</p>
      </CardWrapper>
      
      <CardWrapper padding="sm">
        <div class="flex items-center justify-between mb-2">
          <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/20">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ orders.filter(o => o.status?.text === 'Completed').length }}</p>
        <p class="text-sm text-gray-700 dark:text-gray-400">Completed</p>
      </CardWrapper>
    </div>

    <!-- Orders Table -->
    <CardWrapper overflow>
      <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div class="flex items-center justify-between flex-1">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Live Orders</h3>
            <div class="text-xs text-gray-500">
              <span v-if="lastRefresh">
                Last updated: {{ lastRefresh.toLocaleTimeString() }}
              </span>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <select v-model="categoryFilter" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#ec7813]">
              <option value="all">All Categories</option>
              <option value="cold_drinks">Cold Drinks</option>
              <option value="hot_drinks">Hot Drinks</option>
              <option value="pastries">Pastries</option>
              <option value="sandwiches">Sandwiches</option>
            </select>
            <select v-model="sortKey" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#ec7813]">
              <option value="recent">Sort by: Recent</option>
              <option value="status">Sort by: Status</option>
              <option value="amount">Sort by: Amount</option>
              <option value="table">Sort by: Table</option>
              <option value="priority">Sort by: Priority</option>
            </select>
            <button class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
              <span class="material-symbols-outlined text-sm">filter_list</span>
            </button>
            <button 
              @click="fetchOrders"
              :disabled="loading"
              class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50"
              title="Refresh Orders"
            >
              <span class="material-symbols-outlined text-sm" :class="{ 'animate-spin': loading }">refresh</span>
            </button>
          </div>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
              <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Order #</th>
              <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Table/Type</th>
              <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Device Info</th>
              <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Items</th>
              <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Total</th>
              <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Payment</th>
              <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Status</th>
              <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Time</th>
              <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loading State -->
            <tr v-if="loading">
              <td colspan="9" class="py-12 text-center">
                <div class="flex items-center justify-center gap-3 text-gray-500">
                  <span class="material-symbols-outlined animate-spin">refresh</span>
                  <span>Loading orders...</span>
                </div>
              </td>
            </tr>
            
            <!-- Error State -->
            <tr v-else-if="error">
              <td colspan="9" class="py-12 text-center">
                <div class="text-red-500">
                  <span class="material-symbols-outlined text-4xl mb-2">error</span>
                  <p class="font-medium">Failed to load orders</p>
                  <p class="text-sm text-red-400">{{ error }}</p>
                  <button 
                    @click="fetchOrders"
                    class="mt-3 px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors"
                  >
                    Try Again
                  </button>
                </div>
              </td>
            </tr>
            
            <!-- Empty State -->
            <tr v-else-if="orders.length === 0">
              <td colspan="9" class="py-12 text-center">
                <div class="text-gray-500">
                  <span class="material-symbols-outlined text-4xl mb-2">inbox</span>
                  <p class="font-medium">No orders found</p>
                  <p class="text-sm">Orders will appear here once customers place them</p>
                </div>
              </td>
            </tr>
            
            <!-- Orders -->
            <tr 
              v-else
              v-for="(order, index) in paginatedOrders"
              :key="order.id"
              class="border-b border-gray-100 dark:border-gray-800 hover:bg-white/50 dark:hover:bg-gray-900/20 transition-colors"
            >
              <td class="py-4 px-6">
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ order.id }}</p>
                  <p class="text-gray-700 dark:text-gray-400 text-xs">Session {{ order.sessionId }}</p>
                </div>
              </td>
              <td class="py-4 px-6">
                <div>
                  <p class="text-gray-900 dark:text-white font-medium">{{ order.tableType }}</p>
                  <p class="text-gray-700 dark:text-gray-400 text-sm">{{ order.orderType }}</p>
                </div>
              </td>
              <td class="py-4 px-6">
                <div>
                  <p class="text-gray-900 dark:text-white text-sm">{{ order.deviceInfo.ip }}</p>
                  <p class="text-gray-700 dark:text-gray-400 text-xs">{{ order.deviceInfo.type }}</p>
                </div>
              </td>
              <td class="py-4 px-6">
                <div>
                  <p class="text-gray-900 dark:text-white">{{ order.items.count }} items</p>
                  <p class="text-gray-700 dark:text-gray-400 text-sm line-clamp-1">{{ order.items.description }}</p>
                </div>
              </td>
              <td class="py-4 px-6">
                <div>
                  <p class="text-gray-900 dark:text-white font-bold">₱{{ order.total.toFixed(2) }}</p>
                </div>
              </td>
              <td class="py-4 px-6">
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium" :class="getStatusClasses(order.payment.color)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClasses(order.payment.color)"></span>
                  {{ order.payment.status }}
                </span>
              </td>
              <td class="py-4 px-6">
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium" :class="getStatusClasses(order.status.color)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="[getStatusDotClasses(order.status.color), order.status.color === 'yellow' ? 'animate-pulse' : '']"></span>
                  {{ order.status.text }}
                </span>
              </td>
              <td class="py-4 px-6">
                <div>
                  <p class="text-gray-900 dark:text-white text-sm">{{ order.time }}</p>
                </div>
              </td>
              <td class="py-4 px-6">
                <TableActionsDropdown
                  :row="order"
                  :actions="getOrderActions(order)"
                  placement="bottom-end"
                  width="32"
                  @action="onOrderAction"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Table Footer -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <Pagination 
          :current-page="currentPage"
          :total-items="totalItems"
          :items-per-page="itemsPerPage"
          items-text="orders"
          @page-change="handlePageChange"
        />
      </div>
    </CardWrapper>

    <!-- Order Details Modal -->
    <AdminModal
      :show="showOrderModal"
      :title="`Order ${selectedOrder?.id}`"
      subtitle="Order details and management"
      icon="receipt"
      max-width="4xl"
      animation-type="slide"
      @close="closeOrderModal"
    >
      <!-- Modal Content -->
      <div v-if="selectedOrder" class="space-y-6">
        <!-- Order Summary -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">shopping_cart</span>
            Order Summary
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Order ID</label>
                <p class="text-base font-semibold text-black dark:text-white">{{ selectedOrder.id }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Session</label>
                <p class="text-base text-black dark:text-white">{{ selectedOrder.sessionId }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Table/Location</label>
                <p class="text-base text-black dark:text-white">{{ selectedOrder.tableType }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Order Type</label>
                <p class="text-base text-black dark:text-white">{{ selectedOrder.orderType }}</p>
              </div>
            </div>
            
            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Device Info</label>
                <p class="text-sm text-black dark:text-white">{{ selectedOrder.deviceInfo.ip }}</p>
                <p class="text-xs text-black/60 dark:text-white/60">{{ selectedOrder.deviceInfo.type }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Status</label>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium" :class="getStatusClasses(selectedOrder.status.color)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClasses(selectedOrder.status.color)"></span>
                  {{ selectedOrder.status.text }}
                </span>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Payment Status</label>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium" :class="getStatusClasses(selectedOrder.payment.color)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClasses(selectedOrder.payment.color)"></span>
                  {{ selectedOrder.payment.status }}
                </span>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Time</label>
                <p class="text-base text-black dark:text-white">{{ selectedOrder.time }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Items -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">list_alt</span>
            Order Items
          </h4>
          
          <div class="space-y-3">
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium text-black dark:text-white">{{ selectedOrder.items.count }} items</p>
                  <p class="text-sm text-black/60 dark:text-white/60">{{ selectedOrder.items.description }}</p>
                </div>
                <div class="text-right">
                  <p class="text-lg font-bold text-black dark:text-white">₱{{ selectedOrder.total.toFixed(2) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Timeline -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">timeline</span>
            Order Timeline
          </h4>
          
          <div class="space-y-3">
            <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-sm">check_circle</span>
              </div>
              <div>
                <p class="font-medium text-black dark:text-white">Order Placed</p>
                <p class="text-xs text-black/60 dark:text-white/60">{{ selectedOrder.time }}</p>
              </div>
            </div>
            <div v-if="selectedOrder.status.text !== 'Pending'" class="flex items-center gap-3 p-3 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-sm">verified</span>
              </div>
              <div>
                <p class="font-medium text-black dark:text-white">Order Confirmed</p>
                <p class="text-xs text-black/60 dark:text-white/60">Processing...</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Custom Footer -->
      <template #footer>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <button
              @click="closeOrderModal"
              class="px-6 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
            >
              Close
            </button>
            <button class="px-6 py-2 rounded-xl bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800 transition-all font-medium">
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">print</span>
                Print Receipt
              </span>
            </button>
          </div>

          <div class="flex items-center gap-3">
            <button
              v-if="selectedOrder.status.text === 'Received'"
              @click="updateOrderStatus(selectedOrder.id, 'confirmed'); closeOrderModal()"
              class="px-6 py-2 rounded-xl bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800 transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">check_circle</span>
                Confirm Order
              </span>
            </button>
            <button
              v-if="selectedOrder.status.text === 'Confirmed'"
              @click="updateOrderStatus(selectedOrder.id, 'queued'); closeOrderModal()"
              class="px-6 py-2 rounded-xl bg-indigo-100 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-800 transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">queue</span>
                Send to Queue
              </span>
            </button>
            <button
              v-if="selectedOrder.status.text === 'Ready'"
              @click="updateOrderStatus(selectedOrder.id, 'served'); closeOrderModal()"
              class="px-6 py-2 rounded-xl bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-800 transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">local_shipping</span>
                Mark Served
              </span>
            </button>
            <button
              v-if="selectedOrder.status.text === 'Served'"
              @click="updateOrderStatus(selectedOrder.id, 'completed'); closeOrderModal()"
              class="px-6 py-2 rounded-xl bg-primary text-white hover:bg-primary/90 hover:shadow-lg transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">check_circle</span>
                Mark Completed
              </span>
            </button>
          </div>
        </div>
      </template>
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
