<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Admin/UI/Pagination.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import TableActionsDropdown from '@/Components/Admin/UI/TableActionsDropdown.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

// Pagination logic for inventory
const currentPage = ref(1);
const itemsPerPage = ref(10); // 10 items per page for table view
const totalInventoryItems = ref(247); // Simulate total items (this would come from your backend)

// Handle page change
const handlePageChange = (page) => {
  currentPage.value = page;
  // In a real app, you would fetch data for the new page here
  console.log('Inventory page changed to:', page);
};

// Sample inventory data
const inventoryItems = ref([
  {
    id: 1,
    name: 'Paper Cups (16oz)',
    description: 'Disposable cups',
    category: 'Supplies',
    stock: 450,
    unitPrice: 0.15,
    totalValue: 67.50,
    status: 'In Stock',
    statusColor: 'text-green-600 dark:text-green-400'
  },
  {
    id: 2,
    name: 'Coffee Beans (Arabica)',
    description: 'Premium coffee beans',
    category: 'Ingredients',
    stock: 25,
    unitPrice: 12.50,
    totalValue: 312.50,
    status: 'Low Stock',
    statusColor: 'text-yellow-600 dark:text-yellow-400'
  }
]);

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
  console.log('Restock item:', itemId);
  // TODO: Implement restock functionality
};

// Delete item
const deleteItem = (itemId) => {
  if (confirm('Are you sure you want to delete this inventory item?')) {
    console.log('Delete item:', itemId);
    // TODO: Implement delete functionality
  }
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
          <span class="text-xs font-medium px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400">Active</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">247</p>
        <p class="text-sm text-black/60 dark:text-white/60">Total Items</p>
      </CardWrapper>
      
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-red-500/20 to-red-400/10">
            <span class="material-symbols-outlined text-red-600 dark:text-red-400 text-2xl">warning</span>
          </div>
          <span class="text-xs font-medium px-2 py-1 rounded-full bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400">Critical</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">12</p>
        <p class="text-sm text-black/60 dark:text-white/60">Low Stock</p>
      </CardWrapper>
      
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-green-500/20 to-green-400/10">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-2xl">trending_up</span>
          </div>
          <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400">This month</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">₱4,567</p>
        <p class="text-sm text-black/60 dark:text-white/60">Inventory Value</p>
      </CardWrapper>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2">
      <button class="px-4 py-2 rounded-full bg-primary text-white whitespace-nowrap font-medium transition-all">
        All Items <span class="ml-1 bg-white/20 px-2 py-0.5 rounded-full text-xs">247</span>
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
                <span class="inline-flex items-center px-2 py-1 rounded-full bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 text-sm font-medium">
                  {{ item.category }}
                </span>
              </td>
              <td class="py-4 px-6">
                <div>
                  <p class="text-gray-900 dark:text-white font-medium">{{ item.stock }} units</p>
                </div>
              </td>
              <td class="py-4 px-6">
                <p class="text-gray-900 dark:text-white font-medium">₱{{ item.unitPrice.toFixed(2) }}</p>
              </td>
              <td class="py-4 px-6">
                <p class="text-gray-900 dark:text-white font-bold">₱{{ item.totalValue.toFixed(2) }}</p>
              </td>
              <td class="py-4 px-6">
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm font-medium">
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