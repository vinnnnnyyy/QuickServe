<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Admin/UI/Pagination.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import TableActionsDropdown from '@/Components/Admin/UI/TableActionsDropdown.vue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

// Props from backend (InventoryController@index)
const props = defineProps({
  inventory: {
    type: Array,
    default: () => []
  },
  summary: {
    type: Object,
    default: () => ({
      totalItems: 0,
      lowStockCount: 0,
      totalValue: 0
    })
  }
});

// Pagination logic for inventory
const currentPage = ref(1);
const itemsPerPage = ref(10); // 10 items per page for table view
const totalInventoryItems = ref(props.summary.totalItems || props.inventory.length || 0);

// Inventory items from backend
const inventoryItems = ref(props.inventory || []);

watch(
  () => props.inventory,
  (newInventory) => {
    inventoryItems.value = newInventory || [];
    totalInventoryItems.value = props.summary.totalItems || inventoryItems.value.length || 0;
  },
  { immediate: true }
);

// Handle page change
const handlePageChange = (page) => {
  currentPage.value = page;
  // In a real app, you would fetch data for the new page here
  console.log('Inventory page changed to:', page);
};

// Define inventory actions
const getInventoryActions = (item) => {
  return [
    {
      key: 'edit',
      label: 'Edit Item',
      icon: 'edit',
      href: `/admin/inventory/${item.id}/edit`
    },
    {
      key: 'restock',
      label: 'Restock',
      icon: 'add_shopping_cart',
      colorClass: 'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20',
      onClick: () => restockItem(item.id)
    },
    {
      key: 'delete',
      label: 'Delete',
      icon: 'delete',
      colorClass: 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20',
      onClick: () => deleteItem(item.id)
    }
  ];
};

// Handle inventory action
const onInventoryAction = ({ key, row }) => {
  console.log('Inventory action:', key, row.id);
};

// Restock item
const restockItem = (itemId) => {
  const item = inventoryItems.value.find((i) => i.id === itemId);
  if (!item) return;

  const amount = prompt('Enter quantity to add to stock:', '10');
  if (!amount) return;

  const quantity = parseInt(amount, 10);
  if (Number.isNaN(quantity) || quantity <= 0) {
    alert('Please enter a valid positive number.');
    return;
  }

  const newStock = item.stock + quantity;

  router.put(`/api/inventory/${itemId}`, {
    stock: newStock
  }, {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['inventory', 'summary'] });
    },
    onError: () => {
      alert('Failed to restock item. Please try again.');
    }
  });
};

// Delete item
const deleteItem = (itemId) => {
  if (!confirm('Are you sure you want to delete this inventory item?')) {
    return;
  }

  router.delete(`/admin/inventory/${itemId}`, {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['inventory', 'summary'] });
    },
    onError: () => {
      alert('Failed to delete item. Please try again.');
    }
  });
};
</script>

<template>
  <AdminLayout 
    title="Inventory Management"
    page-title="Inventory Management"
    page-subtitle="Track stock levels and manage supplies"
  >
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <div class="relative">
          <input type="search" placeholder="Search inventory..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
        </div>
        <button 
          @click="router.get('/admin/inventory/add')"
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all"
        >
          <span class="material-symbols-outlined">add</span>
          <span>Add Inventory</span>
        </button>
      </div>
    </div>

    <!-- Inventory Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-blue-500/20 to-blue-400/10">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">inventory_2</span>
          </div>
          <span class="text-xs font-medium text-blue-600 dark:text-blue-400">Active</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">{{ summary.totalItems ?? 0 }}</p>
        <p class="text-sm text-black/60 dark:text-white/60">Total Items</p>
      </CardWrapper>
      
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-red-500/20 to-red-400/10">
            <span class="material-symbols-outlined text-red-600 dark:text-red-400 text-2xl">warning</span>
          </div>
          <span class="text-xs font-medium text-red-600 dark:text-red-400">Critical</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">{{ summary.lowStockCount ?? 0 }}</p>
        <p class="text-sm text-black/60 dark:text-white/60">Low Stock</p>
      </CardWrapper>
      
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-green-500/20 to-green-400/10">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-2xl">trending_up</span>
          </div>
          <span class="text-xs font-medium text-green-600 dark:text-green-400">This month</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">₱{{ (summary.totalValue ?? 0).toFixed(2) }}</p>
        <p class="text-sm text-black/60 dark:text-white/60">Inventory Value</p>
      </CardWrapper>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2">
      <button class="px-4 py-2 rounded-full bg-primary text-white whitespace-nowrap font-medium transition-all">
        All Items <span class="ml-1 bg-white/20 px-2 py-0.5 rounded-full text-xs">{{ summary.totalItems ?? 0 }}</span>
      </button>
      <button class="px-4 py-2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 whitespace-nowrap transition-all">
        Beverages <span class="ml-1 text-gray-500">89</span>
      </button>
      <button class="px-4 py-2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 whitespace-nowrap transition-all">
        Supplies <span class="ml-1 text-gray-500">91</span>
      </button>
      <button class="px-4 py-2 rounded-full bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-700 whitespace-nowrap transition-all">
        Low Stock <span class="ml-1 text-red-600">12</span>
      </button>
    </div>

    <!-- Full Width Inventory Table -->
    <CardWrapper overflow>
      <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-black dark:text-white">Inventory Items</h3>
          <div class="flex items-center gap-3">
            <select class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-sm text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
              <option>Sort by: Stock Level</option>
              <option>Sort by: Name</option>
              <option>Sort by: Category</option>
              <option>Sort by: Value</option>
              <option>Sort by: Last Updated</option>
            </select>
            <button class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
              <span class="material-symbols-outlined text-sm">filter_list</span>
            </button>
          </div>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Item</th>
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Category</th>
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Stock</th>
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Unit Price</th>
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Total Value</th>
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Status</th>
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Actions</th>
            </tr>
          </thead>
          <tbody id="inventory-table-body">
            <!-- Empty State -->
            <tr v-if="inventoryItems.length === 0" id="empty-inventory-message">
              <td colspan="7" class="py-12 text-center">
                <div class="flex flex-col items-center gap-4">
                  <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <span class="material-symbols-outlined text-gray-400 text-2xl">inventory_2</span>
                  </div>
                  <div>
                    <h3 class="text-lg font-medium text-black dark:text-white mb-2">No inventory items yet</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Start by adding your first inventory item</p>
                    <button class="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all mx-auto">
                      <span class="material-symbols-outlined">add</span>
                      <span>Add First Item</span>
                    </button>
                  </div>
                </div>
              </td>
            </tr>

            <!-- Inventory Items -->
            <tr 
              v-for="item in inventoryItems" 
              :key="item.id"
              class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900/20 transition-colors"
            >
              <td class="py-4 px-6">
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                  <p class="text-gray-700 dark:text-gray-400 text-sm">{{ item.description }}</p>
                </div>
              </td>
              <td class="py-4 px-6">
                <span class="inline-flex items-center text-sm font-medium text-purple-600 dark:text-purple-400">
                  {{ item.category }}
                </span>
              </td>
              <td class="py-4 px-6">
                <div>
                  <p class="text-gray-900 dark:text-white font-medium">{{ item.stock }} {{ item.unit || 'units' }}</p>
                </div>
              </td>
              <td class="py-4 px-6">
                <p class="text-gray-900 dark:text-white font-medium">₱{{ item.unitPrice.toFixed(2) }}</p>
              </td>
              <td class="py-4 px-6">
                <p class="text-gray-900 dark:text-white font-bold">₱{{ item.totalValue.toFixed(2) }}</p>
              </td>
              <td class="py-4 px-6">
                <span class="inline-flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400">
                  <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                  {{ item.status }}
                </span>
              </td>
              <td class="py-4 px-6">
                <TableActionsDropdown
                  :row="item"
                  :actions="getInventoryActions(item)"
                  placement="bottom-end"
                  width="32"
                  @action="onInventoryAction"
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
          :total-items="totalInventoryItems"
          :items-per-page="itemsPerPage"
          items-text="items"
          @page-change="handlePageChange"
        />
      </div>
    </CardWrapper>
  </AdminLayout>
</template>