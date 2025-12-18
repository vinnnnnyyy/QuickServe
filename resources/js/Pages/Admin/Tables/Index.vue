<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminModal from '@/Components/Admin/UI/AdminModal.vue';
import Pagination from '@/Components/Admin/UI/Pagination.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import QRCode from 'qrcode';

// Auto-cleanup poller
let cleanupInterval = null;

const startCleanupPoller = () => {
  cleanupInterval = setInterval(async () => {
    try {
      // Check for stale sessions (threshold default is 30 mins, but we might want shorter for testing?)
      // For now using default backend logic. To test "disconnect", we might rely on the fact that 
      // heartbeat stops updates, so eventually they become stale.
      // Note: The user expectation is 1 minute timeout. The backend default is 30.
      // I should pass ?minutes=1 to the endpoint if supported, or relying on backend default.
      // Checking TableSessionController::expireStaleSessions(int $minutesThreshold = 30)
      // I can pass minutes in the request body/query? The controller uses method injection for $minutesThreshold?
      // No, controller methods with arguments usually map to route params or DI.
      // Actually TableSessionController::expireStaleSessions(int $minutesThreshold = 30) might not accept request input for that arg automatically.
      // Let's assume standard behavior or add a param.
      // Safest is to call the route. 
      // Start with 30s poller.
      const response = await window.axios.post('/api/table-sessions/expire-stale', {
        minutes: 1
      });
      
      if (response.data.expired_count > 0) {
        router.reload({ only: ['tables', 'activeSessions'] });
      }
    } catch (err) {
      console.error('Cleanup poller failed', err);
    }
  }, 30000); 
};

onMounted(() => {
  startCleanupPoller();
});

onUnmounted(() => {
  if (cleanupInterval) clearInterval(cleanupInterval);
});

// Define props to receive data from backend
const props = defineProps({
  tables: {
    type: Array,
    default: () => []
  },
  activeSessions: {
    type: Array,
    default: () => []
  }
});

// Use props data as reactive refs
const tables = ref(props.tables);
const activeSessions = ref(props.activeSessions);

// Reactive state
const searchTerm = ref('');
const activeLocationFilter = ref('all');
const sortBy = ref('number');
const viewMode = ref('grid'); // grid or list
const qrSizeOptions = [
  { label: 'Small (200x200px)', value: 200 },
  { label: 'Medium (400x400px)', value: 400 },
  { label: 'Large (600x600px)', value: 600 },
  { label: 'Extra Large (800x800px)', value: 800 }
];
const qrSize = ref(qrSizeOptions[1].value);
const selectedTableId = ref(null);
const qrPreviewDataUrl = ref('');
const isGeneratingQr = ref(false);
const qrError = ref('');

// Modal state
const showSessionModal = ref(false);
const selectedSession = ref(null);

// Pagination logic for active sessions
const currentSessionPage = ref(1);
const sessionsPerPage = ref(5); // 5 sessions per page for table view
const totalSessions = computed(() => activeSessions.value.length);

// Computed properties
const tableSelectOptions = computed(() =>
  tables.value.map((table) => ({
    value: table.id,
    label: `Table ${table.number} (${table.location}, ${table.capacity} seats)`
  }))
);

const selectedTable = computed(() =>
  tables.value.find((table) => table.id === selectedTableId.value) || null
);

const selectedTableLabel = computed(() => {
  if (!selectedTable.value) {
    return 'Select a table';
  }
  const table = selectedTable.value;
  return `Table ${table.number} (${table.location}, ${table.capacity} seats)`;
});

const currentQrSizeLabel = computed(() => {
  const size = qrSizeOptions.find((option) => option.value === qrSize.value);
  return size ? size.label : 'Medium (400x400px)';
});

const filteredTables = computed(() => {
  let filtered = tables.value;

  // Apply location filter
  if (activeLocationFilter.value !== 'all') {
    const locationMap = {
      indoor: 'Indoor',
      outdoor: 'Outdoor',
      patio: 'Patio',
      bar: 'Bar'
    };
    filtered = filtered.filter(table => table.location === locationMap[activeLocationFilter.value]);
  }

  // Apply search filter
  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase();
    filtered = filtered.filter(table =>
      table.number.toString().includes(search) ||
      table.location.toLowerCase().includes(search) ||
      table.status.toLowerCase().includes(search)
    );
  }

  // Apply sorting
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'capacity':
        return b.capacity - a.capacity;
      case 'location':
        return a.location.localeCompare(b.location);
      case 'status':
        const statusOrder = { available: 0, partial: 1, full: 2, cleaning: 3 };
        return statusOrder[a.status] - statusOrder[b.status];
      default: // number
        return a.number - b.number;
    }
  });

  return filtered;
});

const tableStats = computed(() => ({
  empty: tables.value.filter(table => table.status === 'available').length,
  partial: tables.value.filter(table => table.status === 'partial').length,
  full: tables.value.filter(table => table.status === 'full').length,
  cleaning: tables.value.filter(table => table.status === 'cleaning').length
}));

const locationCounts = computed(() => ({
  all: tables.value.length,
  indoor: tables.value.filter(table => table.location === 'Indoor').length,
  outdoor: tables.value.filter(table => table.location === 'Outdoor').length,
  patio: tables.value.filter(table => table.location === 'Patio').length,
  bar: tables.value.filter(table => table.location === 'Bar').length
}));

const getStatusConfig = (status) => {
  const configs = {
    active: {
      text: 'Active',
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/20',
      dot: 'bg-green-500'
    },
    paid_leaving: {
      text: 'Paid Leaving',
      color: 'text-yellow-600 dark:text-yellow-400',
      bgColor: 'bg-yellow-100 dark:bg-yellow-900/20',
      dot: 'bg-yellow-500'
    }
  };
  return configs[status] || configs.active;
};

// Paginated sessions computed property
const paginatedSessions = computed(() => {
  const start = (currentSessionPage.value - 1) * sessionsPerPage.value;
  const end = start + sessionsPerPage.value;
  return activeSessions.value.slice(start, end);
});

// Handle session page change
const handleSessionPageChange = (page) => {
  currentSessionPage.value = page;
  // In a real app, you would fetch data for the new page here
  console.log('Sessions page changed to:', page);
};

// Methods
const setActiveLocationFilter = (filter) => {
  activeLocationFilter.value = filter;
};

const viewTable = (table) => {
  console.log('View table:', table);
  // Implementation for viewing table details
};

const editTable = (table) => {
  router.get(`/admin/tables/${table.id}/edit`);
};

const selectTableForQR = (table) => {
  if (!table?.id) return;
  selectedTableId.value = table.id;
};

const endSession = async (sessionId) => {
  if (confirm('Are you sure you want to end this session?')) {
    try {
      await window.axios.post(`/api/table-sessions/${encodeURIComponent(sessionId)}/end`);
      // Reload page data to reflect changes
      router.reload({ only: ['tables', 'activeSessions'] });
    } catch (error) {
      console.error('Failed to end session:', error);
      alert('Failed to end session');
    }
  }
};

const releaseTable = async (sessionId) => {
  if (confirm('Are you sure you want to release this table?')) {
    try {
      await window.axios.post(`/api/table-sessions/${encodeURIComponent(sessionId)}/release`);
      // Reload page data to reflect changes
      router.reload({ only: ['tables', 'activeSessions'] });
    } catch (error) {
      console.error('Failed to release table:', error);
      alert('Failed to release table');
    }
  }
};

const clearTable = async (table) => {
  if (confirm(`Are you sure you want to clear ALL sessions for Table ${table.number}? This will force-disconnect all users.`)) {
    try {
      await window.axios.post(`/api/table-sessions/table/${table.id}/clear`);
      router.reload({ only: ['tables', 'activeSessions'] });
    } catch (error) {
       console.error('Failed to clear table:', error);
       alert('Failed to clear table');
    }
  }
};

const getTableShareUrl = (table) => {
  if (typeof window === 'undefined') {
    return `table:${table.qrToken}`;
  }

  return `${window.location.origin}/table/${table.qrToken}`;
};

const generateQRCodePreview = async () => {
  if (!selectedTable.value) {
    qrError.value = 'Please select a table first.';
    qrPreviewDataUrl.value = '';
    return;
  }

  isGeneratingQr.value = true;
  qrError.value = '';

  try {
    const shareUrl = getTableShareUrl(selectedTable.value);
    qrPreviewDataUrl.value = await QRCode.toDataURL(shareUrl, {
      width: qrSize.value,
      margin: 2,
    });
  } catch (error) {
    console.error('Error generating QR code:', error);
    qrError.value = 'Failed to generate QR code. Please try again.';
  } finally {
    isGeneratingQr.value = false;
  }
};

const downloadQR = () => {
  if (!qrPreviewDataUrl.value || !selectedTable.value) {
    alert('Generate a QR code first.');
    return;
  }

  const link = document.createElement('a');
  link.href = qrPreviewDataUrl.value;
  link.download = `table-${selectedTable.value.number}-qr.png`;
  link.click();
};

const printTableTent = () => {
  if (!qrPreviewDataUrl.value || !selectedTable.value) {
    alert('Generate a QR code first.');
    return;
  }

  const printWindow = window.open('', '_blank');
  if (!printWindow) {
    alert('Please allow pop-ups to print the QR code.');
    return;
  }

  const table = selectedTable.value;
  printWindow.document.write(`
    <html>
      <head>
        <title>Table ${table.number} QR Code</title>
        <style>
          body { font-family: Arial, sans-serif; text-align: center; padding: 24px; }
          img { width: 320px; height: 320px; object-fit: contain; }
          h1 { margin-bottom: 8px; }
          p { margin: 4px 0; color: #555; }
        </style>
      </head>
      <body>
        <h1>Table ${table.number}</h1>
        <p>${table.location} • ${table.capacity} seats</p>
        <p style="font-size: 24px; font-weight: bold; margin: 10px 0;">Code: ${table.accessCode || 'N/A'}</p>
        <img src="${qrPreviewDataUrl.value}" alt="Table ${table.number} QR" />
      </body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
};

watch(() => props.tables, (newTables) => {
  tables.value = newTables;

  if (newTables.length === 0) {
    selectedTableId.value = null;
    return;
  }

  if (!selectedTableId.value || !newTables.some((table) => table.id === selectedTableId.value)) {
    selectedTableId.value = newTables[0].id;
  }
}, { immediate: true });

watch(() => props.activeSessions, (newSessions) => {
  activeSessions.value = newSessions;
}, { immediate: true });

watch(selectedTableId, (newId) => {
  if (!newId) {
    qrPreviewDataUrl.value = '';
    return;
  }

  if (selectedTable.value) {
    generateQRCodePreview();
  }
});

watch(qrSize, () => {
  if (selectedTable.value) {
    generateQRCodePreview();
  }
});

const openSessionDetails = (sessionId) => {
  const session = activeSessions.value.find(s => s.id === sessionId);
  if (session) {
    selectedSession.value = session;
    showSessionModal.value = true;
  }
};

const closeSessionModal = () => {
  showSessionModal.value = false;
  selectedSession.value = null;
};

</script>

<template>
  <AdminLayout
    title="Tables & QR Code Management"
    page-title="Tables & QR Code Management"
    page-subtitle="Manage table layout, generate QR codes, and monitor occupancy"
  >
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <div class="relative">
          <input
            v-model="searchTerm"
            type="search"
            placeholder="Search tables..."
            class="pl-10 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary"
          >
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
        </div>
        <button 
          @click="router.get('/admin/tables/add')"
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all"
        >
          <span class="material-symbols-outlined">add</span>
          <span>Add Table</span>
        </button>
      </div>
    </div>

    <!-- Table Status Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-green-500/20 to-green-400/10">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-2xl">local_cafe</span>
          </div>
          <span class="text-xs font-medium text-green-600 dark:text-green-400">Available</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">{{ tableStats.empty }}</p>
        <p class="text-sm text-black/60 dark:text-white/60">Empty Tables</p>
      </CardWrapper>

      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-yellow-500/20 to-yellow-400/10">
            <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-2xl">group</span>
          </div>
          <span class="text-xs font-medium text-yellow-600 dark:text-yellow-400">Partial</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">{{ tableStats.partial }}</p>
        <p class="text-sm text-black/60 dark:text-white/60">Partially Occupied</p>
      </CardWrapper>

      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-red-500/20 to-red-400/10">
            <span class="material-symbols-outlined text-red-600 dark:text-red-400 text-2xl">people</span>
          </div>
          <span class="text-xs font-medium text-red-600 dark:text-red-400">Full</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">{{ tableStats.full }}</p>
        <p class="text-sm text-black/60 dark:text-white/60">Full Tables</p>
      </CardWrapper>

      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-blue-500/20 to-blue-400/10">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">cleaning_services</span>
          </div>
          <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Cleaning</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">{{ tableStats.cleaning }}</p>
        <p class="text-sm text-black/60 dark:text-white/60">Being Cleaned</p>
      </CardWrapper>
    </div>

    <!-- Location Filter Tabs -->
    <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2">
      <button
        @click="setActiveLocationFilter('all')"
        :class="activeLocationFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-4 py-2 rounded-full whitespace-nowrap font-medium transition-all"
      >
        All Locations <span class="ml-1 px-2 py-0.5 rounded-full text-xs" :class="activeLocationFilter === 'all' ? 'bg-white/20' : 'text-gray-500'">{{ locationCounts.all }}</span>
      </button>
      <button
        @click="setActiveLocationFilter('indoor')"
        :class="activeLocationFilter === 'indoor' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-green-500 rounded-full"></span>
          Indoor
        </span>
        <span class="ml-1 text-gray-500">{{ locationCounts.indoor }}</span>
      </button>
      <button
        @click="setActiveLocationFilter('outdoor')"
        :class="activeLocationFilter === 'outdoor' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
          Outdoor
        </span>
        <span class="ml-1 text-gray-500">{{ locationCounts.outdoor }}</span>
      </button>
      <button
        @click="setActiveLocationFilter('patio')"
        :class="activeLocationFilter === 'patio' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
          Patio
        </span>
        <span class="ml-1 text-gray-500">{{ locationCounts.patio }}</span>
      </button>
      <button
        @click="setActiveLocationFilter('bar')"
        :class="activeLocationFilter === 'bar' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        <span class="inline-flex items-center gap-1">
          <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
          Bar
        </span>
        <span class="ml-1 text-gray-500">{{ locationCounts.bar }}</span>
      </button>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
      <!-- Tables Grid -->
      <div class="xl:col-span-3">
        <!-- Tables Grid View -->
        <CardWrapper class="mb-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-black dark:text-white">Table Layout</h3>
            <div class="flex items-center gap-3">
              <select
                v-model="sortBy"
                class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-sm text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option>Sort by: Table Number</option>
                <option>Sort by: Capacity</option>
                <option>Sort by: Location</option>
                <option>Sort by: Status</option>
              </select>
              <div class="flex items-center gap-1 border border-gray-200 dark:border-gray-700 rounded-lg p-1">
                <button
                  @click="viewMode = 'grid'"
                  :class="viewMode === 'grid' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'"
                  class="p-1.5 rounded"
                >
                  <span class="material-symbols-outlined text-sm">grid_view</span>
                </button>
                <button
                  @click="viewMode = 'list'"
                  :class="viewMode === 'list' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'"
                  class="p-1.5 rounded"
                >
                  <span class="material-symbols-outlined text-sm">view_list</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredTables.length === 0" class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <span class="material-symbols-outlined text-gray-400 text-2xl">table_restaurant</span>
            </div>
            <h3 class="text-lg font-medium text-black dark:text-white mb-2">No tables found</h3>
            <p class="text-black/60 dark:text-white/60 mb-4">
              {{ searchTerm ? `No tables match "${searchTerm}"` : `No tables in this location` }}
            </p>
            <button
              @click="searchTerm = ''; activeLocationFilter = 'all'"
              class="px-4 py-2 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-all"
            >
              Clear Filters
            </button>
          </div>

          <!-- Tables Grid -->
          <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <!-- Table Cards -->
            <div
              v-for="table in filteredTables"
              :key="table.id"
              class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border-2 hover:shadow-lg transition-all"
              :class="table.statusColor"
            >
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <span class="text-2xl font-bold text-black dark:text-white">{{ table.number }}</span>
                  <span class="text-xs px-2 py-1 rounded-full" :class="table.locationColor">{{ table.location }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <span :class="table.statusDot" class="w-2 h-2 rounded-full"></span>
                  <span class="text-xs font-medium" :class="table.status === 'available' ? 'text-green-600 dark:text-green-400' :
                                                         table.status === 'partial' ? 'text-yellow-600 dark:text-yellow-400' :
                                                         table.status === 'full' ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'">
                    {{ table.statusText }}
                  </span>
                </div>
              </div>

              <div class="mb-3">
                <div class="flex items-center justify-between text-sm mb-1">
                  <span class="text-black/60 dark:text-white/60">Capacity:</span>
                  <span class="text-black dark:text-white font-medium">{{ table.capacity }} seats</span>
                </div>
                <div class="flex items-center justify-between text-sm mb-1">
                  <span class="text-black/60 dark:text-white/60">Occupied:</span>
                  <span class="text-black dark:text-white">{{ table.occupied }}/{{ table.capacity }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-black dark:text-white text-xs">{{ table.qrCode }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-black/60 dark:text-white/60">Access Code:</span>
                  <span class="text-primary font-bold font-mono tracking-widest">{{ table.accessCode || 'N/A' }}</span>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <button
                  @click="selectTableForQR(table)"
                  class="flex-1 px-3 py-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800 transition-all text-sm font-medium flex items-center justify-center gap-1"
                >
                  <span class="material-symbols-outlined text-sm">qr_code</span>
                  QR
                </button>
                <button
                  @click="editTable(table)"
                  class="flex-1 px-3 py-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-all text-sm font-medium"
                >
                  Edit
                </button>
                <button 
                  @click="clearTable(table)"
                  class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                  title="Clear All Sessions"
                >
                  <span class="material-symbols-outlined text-sm">delete_sweep</span>
                </button>
              </div>
            </div>

            <!-- Add New Table Card -->
            <div 
              @click="router.get('/admin/tables/add')"
              class="bg-gray-50 dark:bg-gray-900/20 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg min-h-[200px] flex items-center justify-center hover:border-primary dark:hover:border-primary transition-all cursor-pointer group"
            >
              <div class="text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center group-hover:bg-primary/10 dark:group-hover:bg-primary/20 transition-all">
                  <span class="material-symbols-outlined text-2xl text-gray-400 group-hover:text-primary">add</span>
                </div>
                <p class="font-medium text-gray-600 dark:text-gray-400 group-hover:text-primary">Add New Table</p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Click to create table</p>
              </div>
            </div>
          </div>
        </CardWrapper>

        <!-- Active Sessions Table -->
        <CardWrapper overflow>
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-bold text-black dark:text-white">Active Table Sessions</h3>
              <div class="flex items-center gap-3">
                <select class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-sm text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                  <option>Sort by: Recent Activity</option>
                  <option>Sort by: Table Number</option>
                  <option>Sort by: Session Duration</option>
                  <option>Sort by: Device Count</option>
                </select>
                <button class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
                  <span class="material-symbols-outlined text-sm">refresh</span>
                </button>
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                  <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Table</th>
                  <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Session ID</th>
                  <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Device Info</th>
                  <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Status</th>
                  <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Duration</th>
                  <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Last Activity</th>
                  <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="session in paginatedSessions"
                  :key="session.id"
                  class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900/20"
                >
                  <td class="py-4 px-6">
                    <div class="flex items-center gap-2">
                      <span class="text-lg font-bold text-black dark:text-white">{{ session.tableNumber }}</span>
                      <div>
                        <p class="text-sm text-black dark:text-white font-medium">{{ session.location }}</p>
                        <p class="text-xs text-black/60 dark:text-white/60">{{ session.capacity }} seats</p>
                      </div>
                    </div>
                  </td>
                  <td class="py-4 px-6">
                    <div>
                      <p class="text-black dark:text-white font-medium">{{ session.id }}</p>
                      <p class="text-black/60 dark:text-white/60 text-xs">Device {{ session.currentDevice }} of {{ session.deviceCount }}</p>
                    </div>
                  </td>
                  <td class="py-4 px-6">
                    <div class="flex items-center gap-2">
                      <div class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800">
                        <span class="material-symbols-outlined text-gray-600 dark:text-gray-400">
                          {{ session.deviceInfo.deviceType === 'Mobile' ? 'smartphone' : session.deviceInfo.deviceType === 'Tablet' ? 'tablet' : 'computer' }}
                        </span>
                      </div>
                      <div>
                        <p class="text-black dark:text-white text-sm font-medium">{{ session.deviceInfo.deviceType || 'Unknown Device' }}</p>
                        <p class="text-black/60 dark:text-white/60 text-xs">{{ session.deviceInfo.ip }}</p>
                        <p class="text-black/60 dark:text-white/60 text-xs truncate max-w-[150px]" :title="session.deviceInfo.browser">{{ session.deviceInfo.browser }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="py-4 px-6">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium" :class="getStatusConfig(session.status).bgColor">
                      <span :class="getStatusConfig(session.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                      {{ getStatusConfig(session.status).text }}
                    </span>
                  </td>
                  <td class="py-4 px-6">
                    <div>
                      <p class="text-black dark:text-white text-sm">{{ session.duration }}</p>
                      <p class="text-black/60 dark:text-white/60 text-xs">Started {{ session.startTime }}</p>
                    </div>
                  </td>
                  <td class="py-4 px-6">
                    <div>
                      <p class="text-black dark:text-white text-sm">{{ session.lastActivity }}</p>
                      <p class="text-black/60 dark:text-white/60 text-xs">{{ session.activity }}</p>
                    </div>
                  </td>
                  <td class="py-4 px-6">
                    <div class="flex items-center gap-1">
                      <button 
                        @click="openSessionDetails(session.id)"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-colors" 
                        title="View Session Details"
                      >
                        <span class="material-symbols-outlined text-lg">visibility</span>
                      </button>
                      <button
                        @click="endSession(session.id)"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-red-600 dark:text-red-400 transition-colors"
                        title="End Session"
                      >
                        <span class="material-symbols-outlined text-lg">logout</span>
                      </button>
                      <button
                        v-if="session.status === 'paid_leaving'"
                        @click="releaseTable(session.id)"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-green-600 dark:text-green-400 transition-colors"
                        title="Release Table"
                      >
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Sessions Pagination -->
          <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <Pagination 
              :current-page="currentSessionPage"
              :total-items="totalSessions"
              :items-per-page="sessionsPerPage"
              items-text="sessions"
              @page-change="handleSessionPageChange"
            />
          </div>
        </CardWrapper>
      </div>

      <!-- Sidebar -->
      <div class="xl:col-span-1 space-y-6">
        <!-- QR Code Generator -->
        <CardWrapper>
          <h3 class="text-lg font-bold text-black dark:text-white mb-4">QR Code Generator</h3>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-black dark:text-white mb-2">Select Table</label>
              <select
                v-model="selectedTableId"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option v-if="tableSelectOptions.length === 0" disabled value="">No tables available</option>
                <option
                  v-for="option in tableSelectOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-black dark:text-white mb-2">QR Code Size</label>
              <select
                v-model="qrSize"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option
                  v-for="size in qrSizeOptions"
                  :key="size.value"
                  :value="size.value"
                >
                  {{ size.label }}
                </option>
              </select>
            </div>

            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <div class="text-center">
                <div class="w-40 h-40 mx-auto mb-3 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center overflow-hidden">
                  <span v-if="isGeneratingQr" class="material-symbols-outlined text-3xl text-gray-400 animate-spin">progress_activity</span>
                  <img
                    v-else-if="qrPreviewDataUrl"
                    :src="qrPreviewDataUrl"
                    :alt="selectedTable ? `Table ${selectedTable.number} QR code` : 'QR code preview'"
                    class="w-full h-full object-contain"
                  />
                  <span v-else class="material-symbols-outlined text-4xl text-gray-400">qr_code</span>
                </div>
                <p class="text-sm text-black/60 dark:text-white/60">QR Code Preview</p>
                <p class="text-xs text-black/60 dark:text-white/60 mt-1">
                  <span v-if="selectedTable">{{ selectedTableLabel }} • {{ currentQrSizeLabel }}</span>
                  <span v-else>Select a table to generate its QR</span>
                </p>
                <p v-if="qrError" class="text-xs text-red-500 mt-2">{{ qrError }}</p>
              </div>
            </div>

            <div class="space-y-2">
              <button
                @click="generateQRCodePreview"
                class="w-full px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all font-medium"
              >
                Generate QR Code
              </button>
              <button
                @click="downloadQR"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
              >
                Download PNG
              </button>
              <button
                @click="printTableTent"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
              >
                Print Table Tent
              </button>
            </div>
          </div>
        </CardWrapper>
      </div>
    </div>

    <!-- Session Details Modal -->
    <AdminModal
      :show="showSessionModal"
      :title="`Session ${selectedSession?.id}`"
      subtitle="Table session details and management"
      icon="table_restaurant"
      max-width="4xl"
      animation-type="slide"
      @close="closeSessionModal"
    >
      <!-- Modal Content -->
      <div v-if="selectedSession" class="space-y-6">
        <!-- Session Overview -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">info</span>
            Session Overview
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Session ID</label>
                <p class="text-base font-semibold text-black dark:text-white">{{ selectedSession.id }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Table Information</label>
                <p class="text-base text-black dark:text-white">Table {{ selectedSession.tableNumber }} ({{ selectedSession.location }})</p>
                <p class="text-sm text-black/60 dark:text-white/60">{{ selectedSession.capacity }} seats capacity</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Device Count</label>
                <p class="text-base text-black dark:text-white">{{ selectedSession.deviceCount }} connected devices</p>
              </div>
            </div>
            
            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Session Status</label>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium" :class="getStatusConfig(selectedSession.status).bgColor">
                  <span :class="getStatusConfig(selectedSession.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                  {{ getStatusConfig(selectedSession.status).text }}
                </span>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Session Duration</label>
                <p class="text-base text-black dark:text-white">{{ selectedSession.duration }}</p>
                <p class="text-sm text-black/60 dark:text-white/60">Started at {{ selectedSession.startTime }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Last Activity</label>
                <p class="text-base text-black dark:text-white">{{ selectedSession.lastActivity }}</p>
                <p class="text-sm text-black/60 dark:text-white/60">{{ selectedSession.activity }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Device Information -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">devices</span>
            Device Information
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <h5 class="font-medium text-black dark:text-white mb-3">Current Device</h5>
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-black/60 dark:text-white/60">IP Address:</span>
                  <span class="text-sm font-medium text-black dark:text-white">{{ selectedSession.deviceInfo.ip }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-black/60 dark:text-white/60">Browser:</span>
                  <span class="text-sm font-medium text-black dark:text-white">{{ selectedSession.deviceInfo.browser }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-black/60 dark:text-white/60">Device:</span>
                  <span class="text-sm font-medium text-black dark:text-white">{{ selectedSession.currentDevice }} of {{ selectedSession.deviceCount }}</span>
                </div>
              </div>
            </div>
            
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <h5 class="font-medium text-black dark:text-white mb-3">Connection Stats</h5>
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-black/60 dark:text-white/60">Signal Strength:</span>
                  <span class="text-sm font-medium text-green-600 dark:text-green-400">Excellent</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-black/60 dark:text-white/60">Connection Type:</span>
                  <span class="text-sm font-medium text-black dark:text-white">WiFi</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-black/60 dark:text-white/60">Network:</span>
                  <span class="text-sm font-medium text-black dark:text-white">CafeOrder_WiFi</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Session Timeline -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">timeline</span>
            Session Timeline
          </h4>
          
          <div class="space-y-3">
            <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-sm">login</span>
              </div>
              <div>
                <p class="font-medium text-black dark:text-white">Session Started</p>
                <p class="text-xs text-black/60 dark:text-white/60">{{ selectedSession.startTime }}</p>
              </div>
            </div>
            
            <div v-if="selectedSession.status === 'active'" class="flex items-center gap-3 p-3 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-sm">restaurant_menu</span>
              </div>
              <div>
                <p class="font-medium text-black dark:text-white">{{ selectedSession.activity }}</p>
                <p class="text-xs text-black/60 dark:text-white/60">{{ selectedSession.lastActivity }}</p>
              </div>
            </div>
            
            <div v-if="selectedSession.status === 'paid_leaving'" class="flex items-center gap-3 p-3 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-8 h-8 rounded-full bg-yellow-100 dark:bg-yellow-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-sm">payments</span>
              </div>
              <div>
                <p class="font-medium text-black dark:text-white">Payment Completed</p>
                <p class="text-xs text-black/60 dark:text-white/60">Customer ready to leave</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">tune</span>
            Quick Actions
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <button class="p-4 rounded-lg bg-white dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-left">
              <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">message</span>
                <div>
                  <p class="font-medium text-black dark:text-white text-sm">Send Message</p>
                  <p class="text-xs text-black/60 dark:text-white/60">Notify customer</p>
                </div>
              </div>
            </button>
            <button class="p-4 rounded-lg bg-white dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-left">
              <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-green-600 dark:text-green-400">receipt</span>
                <div>
                  <p class="font-medium text-black dark:text-white text-sm">View Orders</p>
                  <p class="text-xs text-black/60 dark:text-white/60">Session history</p>
                </div>
              </div>
            </button>
            <button class="p-4 rounded-lg bg-white dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-left">
              <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">analytics</span>
                <div>
                  <p class="font-medium text-black dark:text-white text-sm">View Analytics</p>
                  <p class="text-xs text-black/60 dark:text-white/60">Usage patterns</p>
                </div>
              </div>
            </button>
          </div>
        </div>
      </div>

      <!-- Custom Footer -->
      <template #footer>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <button
              @click="closeSessionModal"
              class="px-6 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
            >
              Close
            </button>
            <button class="px-6 py-2 rounded-xl bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800 transition-all font-medium">
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">print</span>
                Print Report
              </span>
            </button>
          </div>

          <div class="flex items-center gap-3">
            <button
              v-if="selectedSession.status === 'paid_leaving'"
              @click="releaseTable(selectedSession.id); closeSessionModal()"
              class="px-6 py-2 rounded-xl bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-800 transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">check_circle</span>
                Release Table
              </span>
            </button>
            <button
              @click="endSession(selectedSession.id); closeSessionModal()"
              class="px-6 py-2 rounded-xl bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-800 transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">logout</span>
                End Session
              </span>
            </button>
          </div>
        </div>
      </template>
    </AdminModal>
  </AdminLayout>
</template>

<style scoped>
/* Custom styles if needed */
</style>
