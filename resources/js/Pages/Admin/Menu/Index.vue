<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Admin/UI/Pagination.vue';
import AdminModal from '@/Components/Admin/UI/AdminModal.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import TableActionsDropdown from '@/Components/Admin/UI/TableActionsDropdown.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useMenuCategories } from '@/composables/useMenuCategories.js';

// Accept props from Inertia
const props = defineProps({
  menuItems: {
    type: Array,
    default: () => []
  }
});

// Convert props to reactive ref for local manipulation
const menuItems = ref(props.menuItems || []);

// Use centralized category system
const { getCategoriesWithCounts, getCategoryIcon } = useMenuCategories();

// Categories computed from actual data using centralized system
const categories = computed(() => {
  // Ensure menuItems.value is always an array
  const items = Array.isArray(menuItems.value) ? menuItems.value : [];
  return getCategoriesWithCounts(items);
});

// Current filter
const currentFilter = ref('all');
const availabilityFilter = ref('all');
const searchQuery = ref('');

// View mode state
const viewMode = ref('grid');
const gridItemsPerPage = 6;
const tableItemsPerPage = 10;

// Filtered items
const filteredItems = computed(() => {
  // Ensure menuItems.value is always an array
  const items = Array.isArray(menuItems.value) ? menuItems.value : [];
  
  let filtered = items;
  
  // Filter by search query
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim();
    filtered = filtered.filter(item => {
      const name = (item.name || '').toLowerCase();
      const description = (item.description || '').toLowerCase();
      const category = (item.category || '').toLowerCase();
      const price = (item.price || '').toString();
      
      return name.includes(query) || 
             description.includes(query) || 
             category.includes(query) ||
             price.includes(query);
    });
  }
  
  // Filter by category
  if (currentFilter.value !== 'all') {
    filtered = filtered.filter(item => {
      // Handle both category value and label matching
      const itemCategory = item.category || 'Uncategorized';
      return itemCategory === currentFilter.value || 
             getCategoriesWithCounts([item]).some(cat => cat.value === currentFilter.value);
    });
  }
  
  // Filter by availability
  if (availabilityFilter.value === 'available') {
    filtered = filtered.filter(item => item.available);
  } else if (availabilityFilter.value === 'unavailable') {
    filtered = filtered.filter(item => !item.available);
  }
  
  return filtered;
});

// Modal state
const showMenuModal = ref(false);
const selectedMenuItem = ref(null);

const openMenuItemModal = (itemName) => {
  const item = menuItems.value.find(i => i.name === itemName);
  if (item) {
    selectedMenuItem.value = item;
    showMenuModal.value = true;
  }
};

const closeMenuModal = () => {
  showMenuModal.value = false;
  selectedMenuItem.value = null;
};

// Pagination logic
const currentPage = ref(1);
const itemsPerPage = computed(() => viewMode.value === 'table' ? tableItemsPerPage : gridItemsPerPage);
const totalMenuItems = ref(menuItems.value.length); // Use actual total from data

// Computed property for displayed items
const paginatedMenuItems = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredItems.value.slice(start, end);
});

// Update total items when filtered
const totalFilteredItems = computed(() => filteredItems.value.length);

// Handle page change
const handlePageChange = (page) => {
  currentPage.value = page;
  console.log('Page changed to:', page);
};

// Filter functions
const setFilter = (filter) => {
  currentFilter.value = filter;
  currentPage.value = 1; // Reset to first page
};

const setAvailabilityFilter = (filter) => {
  availabilityFilter.value = filter;
  currentPage.value = 1; // Reset to first page
};

// Search function
const handleSearch = () => {
  currentPage.value = 1; // Reset to first page when searching
};

// Clear search
const clearSearch = () => {
  searchQuery.value = '';
  currentPage.value = 1;
};

// View mode functions
const setViewMode = (mode) => {
  if (viewMode.value !== mode) {
    viewMode.value = mode;
    currentPage.value = 1;
  }
};

// Refresh data function
const refreshData = () => {
  router.reload({ only: ['menuItems'] });
};

// Delete menu item function
const deleteMenuItem = async (itemId) => {
  if (!confirm('Are you sure you want to delete this menu item?')) {
    return;
  }
  
  try {
    const response = await fetch(`/api/menu/${itemId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    });
    
    if (response.ok) {
      // Remove item from local array
      menuItems.value = menuItems.value.filter(item => item.id !== itemId);
      alert('Menu item deleted successfully!');
      
      // Close modal if the deleted item was selected
      if (selectedMenuItem.value && selectedMenuItem.value.id === itemId) {
        closeMenuModal();
      }
    } else {
      const error = await response.json();
      alert('Error deleting menu item: ' + (error.message || 'Unknown error'));
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error deleting menu item. Please try again.');
  }
};

// Toggle availability function
const toggleAvailability = async (item) => {
  try {
    const response = await fetch(`/api/menu/${item.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        available: !item.available
      })
    });
    
    if (response.ok) {
      const updatedItem = await response.json();
      // Update local item
      const index = menuItems.value.findIndex(i => i.id === item.id);
      if (index !== -1) {
        menuItems.value[index] = updatedItem;
      }
      // Update selected item if modal is open
      if (selectedMenuItem.value && selectedMenuItem.value.id === item.id) {
        selectedMenuItem.value = updatedItem;
      }
    } else {
      const error = await response.json();
      alert('Error updating item: ' + (error.message || 'Unknown error'));
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error updating item. Please try again.');
  }
};

// Edit menu item
const editMenuItem = (itemId) => {
  router.get(`/admin/menu/${itemId}/edit`);
};

// Category icon function is now imported from useMenuCategories

// Table actions
const getMenuActions = (item) => {
  return [
    {
      key: 'view',
      label: 'View Details',
      icon: 'visibility',
      onClick: () => openMenuItemModal(item.name)
    },
    {
      key: 'edit',
      label: 'Edit Item',
      icon: 'edit',
      href: `/admin/menu/${item.id}/edit`
    },
    {
      key: 'toggle',
      label: item.available ? 'Mark Unavailable' : 'Mark Available',
      icon: item.available ? 'cancel' : 'check_circle',
      onClick: () => toggleAvailability(item)
    },
    {
      key: 'delete',
      label: 'Delete Item',
      icon: 'delete',
      colorClass: 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20',
      onClick: () => deleteMenuItem(item.id)
    }
  ];
};

const onMenuAction = ({ key, row }) => {
  console.log('Menu action:', key, row.id);
};
</script>

<template>
  <AdminLayout 
    title="Menu Management"
    page-title="Menu Management"
    page-subtitle="Add, edit, and organize your menu items"
  >
    <!-- Header with Filters -->
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
      <!-- Left side - Filters -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-1">
        <!-- Search -->
        <div class="relative">
          <input 
            v-model="searchQuery"
            @input="handleSearch"
            type="search" 
            placeholder="Search menu items..." 
            class="pl-10 pr-10 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary min-w-[200px]"
          >
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
          <button 
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
            title="Clear search"
          >
            <span class="material-symbols-outlined text-sm">close</span>
          </button>
        </div>

        <!-- Category Filter Dropdown -->
        <div class="relative">
          <select 
            v-model="currentFilter"
            @change="setFilter($event.target.value)"
            class="pl-10 pr-8 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary appearance-none cursor-pointer min-w-[160px]"
          >
            <option value="all">All Categories ({{ menuItems.length }})</option>
            <option 
              v-for="category in categories" 
              :key="category.value" 
              :value="category.value"
            >
              {{ category.label }} ({{ category.count }})
            </option>
          </select>
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">category</span>
          <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">expand_more</span>
        </div>

        <!-- Availability Filter Dropdown -->
        <div class="relative">
          <select 
            v-model="availabilityFilter"
            @change="setAvailabilityFilter($event.target.value)"
            class="pl-10 pr-8 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary appearance-none cursor-pointer min-w-[140px]"
          >
            <option value="all">All Items</option>
            <option value="available">Available Only</option>
            <option value="unavailable">Out of Stock</option>
          </select>
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
            {{ availabilityFilter === 'available' ? 'check_circle' : availabilityFilter === 'unavailable' ? 'cancel' : 'inventory' }}
          </span>
          <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">expand_more</span>
        </div>
      </div>

      <!-- Right side - Actions -->
      <div class="flex items-center gap-3">
        <button 
          @click="refreshData"
          class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
          title="Refresh data"
        >
          <span class="material-symbols-outlined">refresh</span>
        </button>
        <button 
          @click="router.get('/admin/menu/add')"
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all"
        >
          <span class="material-symbols-outlined">add</span>
          <span>Add Item</span>
        </button>
      </div>
    </div>

    <!-- Menu Items - Full Width -->
    <div>
        <!-- Filter Status and View Options -->
        <div class="flex items-center justify-between mb-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">
            <span v-if="!searchQuery && currentFilter === 'all' && availabilityFilter === 'all'">
              Showing all {{ menuItems.length }} items
            </span>
            <span v-else>
              Showing {{ filteredItems.length }} of {{ menuItems.length }} items
              <span v-if="searchQuery" class="font-medium text-primary">
                for "{{ searchQuery }}"
              </span>
              <span v-if="currentFilter !== 'all'" class="font-medium">
                in {{ categories.find(cat => cat.value === currentFilter)?.label || currentFilter }}
              </span>
              <span v-if="availabilityFilter !== 'all'" class="font-medium">
                ({{ availabilityFilter === 'available' ? 'available only' : 'out of stock only' }})
              </span>
            </span>
          </div>
          <div class="flex items-center gap-3">
            <select class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-sm text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
              <option>Sort by: Name</option>
              <option>Sort by: Price</option>
              <option>Sort by: Popular</option>
              <option>Sort by: Recent</option>
            </select>
            <div class="flex items-center gap-1 border border-gray-200 dark:border-gray-700 rounded-lg p-1">
              <button 
                @click="setViewMode('grid')"
                :class="viewMode === 'grid' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'"
                class="p-1.5 rounded transition-all"
                title="Grid View"
              >
                <span class="material-symbols-outlined text-sm">grid_view</span>
              </button>
              <button 
                @click="setViewMode('table')"
                :class="viewMode === 'table' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'"
                class="p-1.5 rounded transition-all"
                title="Table View"
              >
                <span class="material-symbols-outlined text-sm">table_rows</span>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Empty State - No Menu Items -->
          <div 
            v-if="menuItems.length === 0" 
            class="col-span-full text-center py-12"
          >
            <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <span class="material-symbols-outlined text-4xl text-gray-400">restaurant_menu</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No menu items found</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Get started by adding your first menu item.</p>
            <button 
              @click="router.get('/admin/menu/add')"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all"
            >
              <span class="material-symbols-outlined">add</span>
              <span>Add First Item</span>
            </button>
          </div>

          <!-- No Search Results State -->
          <div 
            v-else-if="filteredItems.length === 0 && (searchQuery || currentFilter !== 'all' || availabilityFilter !== 'all')" 
            class="col-span-full text-center py-12"
          >
            <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <span class="material-symbols-outlined text-4xl text-gray-400">search_off</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No results found</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
              <span v-if="searchQuery">No items match "{{ searchQuery }}"</span>
              <span v-else>No items match the selected filters</span>
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
              <button 
                v-if="searchQuery"
                @click="clearSearch"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
              >
                <span class="material-symbols-outlined">close</span>
                <span>Clear Search</span>
              </button>
              <button 
                v-if="currentFilter !== 'all' || availabilityFilter !== 'all'"
                @click="currentFilter = 'all'; availabilityFilter = 'all'; currentPage = 1;"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
              >
                <span class="material-symbols-outlined">filter_alt_off</span>
                <span>Clear Filters</span>
              </button>
            </div>
          </div>

          <!-- Menu Item Cards -->
          <CardWrapper
            v-for="item in paginatedMenuItems"
            :key="item.id"
            overflow
            hover
            shadow="hover"
            class="group focus-within:ring-2 focus-within:ring-primary/20 focus-within:ring-offset-2"
          >
            <div class="relative group">
              <img 
                :src="item.image" 
                :alt="item.name" 
                loading="lazy"
                @error="e => { if (e.target.src !== '/images/placeholder.svg') e.target.src = '/images/placeholder.svg' }"
                class="w-full aspect-[3/2] object-cover transition-transform duration-300 group-hover:scale-[1.02]"
              >
              <!-- Gradient overlay for better contrast -->
              <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
              
              <!-- Hover quick actions -->
              <div class="absolute inset-x-3 bottom-3 flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <button 
                  @click="openMenuItemModal(item.name)" 
                  class="p-1.5 rounded-lg bg-white/90 dark:bg-black/60 text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-black transition"
                  title="View"
                >
                  <span class="material-symbols-outlined text-sm">visibility</span>
                </button>
                <button 
                  @click="editMenuItem(item.id)" 
                  class="p-1.5 rounded-lg bg-white/90 dark:bg-black/60 text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-black transition"
                  title="Edit"
                >
                  <span class="material-symbols-outlined text-sm">edit</span>
                </button>
                <button 
                  @click="toggleAvailability(item)" 
                  class="p-1.5 rounded-lg bg-white/90 dark:bg-black/60 text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-black transition"
                  :title="item.available ? 'Mark Unavailable' : 'Mark Available'"
                >
                  <span class="material-symbols-outlined text-sm">{{ item.available ? 'cancel' : 'check_circle' }}</span>
                </button>
                <button 
                  @click="deleteMenuItem(item.id)" 
                  class="p-1.5 rounded-lg bg-white/90 dark:bg-black/60 text-red-600 dark:text-red-400 hover:bg-white dark:hover:bg-black transition"
                  title="Delete"
                >
                  <span class="material-symbols-outlined text-sm">delete</span>
                </button>
              </div>
            </div>
            <div class="p-6">
              <!-- Header: name + price -->
              <div class="flex items-start justify-between mb-3">
                <h4 class="font-semibold text-lg text-black dark:text-white truncate">{{ item.name }}</h4>
                <span class="text-xl font-bold text-black dark:text-white">₱{{ Number(item.price || 0).toFixed(2) }}</span>
              </div>

              <!-- Description -->
              <p class="text-black/60 dark:text-white/60 text-sm line-clamp-2 mb-4 leading-relaxed">{{ item.description }}</p>

              <!-- Category and Temperature badges -->
              <div class="flex items-center gap-2 mb-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium">
                  <span class="material-symbols-outlined text-sm">category</span>
                  {{ item.category || 'Uncategorized' }}
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium">
                  <span class="material-symbols-outlined text-sm">{{ item.temperature === 'Cold' ? 'ac_unit' : 'local_fire_department' }}</span>
                  {{ item.temperature || '—' }}
                </span>
              </div>

              <!-- Availability section -->
              <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                  <span :class="item.available ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ item.available ? 'Available' : 'Out of Stock' }}
                  </span>
                </div>
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                  <input type="checkbox" class="sr-only" :checked="item.available" @change="toggleAvailability(item)"/>
                  <span :class="item.available ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700'" class="w-11 h-6 rounded-full relative transition-colors">
                    <span :class="item.available ? 'translate-x-5' : 'translate-x-0'" class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"></span>
                  </span>
                </label>
              </div>
            </div>
          </CardWrapper>

          <!-- Add New Item Card (only show if there are existing items) -->
          <div 
            v-if="menuItems.length > 0"
            @click="router.get('/admin/menu/add')"
            class="bg-gray-50 dark:bg-gray-900/20 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg min-h-[420px] flex items-center justify-center hover:border-primary dark:hover:border-primary transition-all cursor-pointer group"
          >
            <div class="text-center">
              <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center group-hover:bg-primary/10 dark:group-hover:bg-primary/20 transition-all">
                <span class="material-symbols-outlined text-3xl text-gray-400 group-hover:text-primary">add</span>
              </div>
              <p class="font-medium text-gray-600 dark:text-gray-400 group-hover:text-primary">Add New Item</p>
              <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Click to add a new menu item</p>
            </div>
          </div>
        </div>

        <!-- Table View -->
        <CardWrapper v-else>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                  <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Item</th>
                  <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Category</th>
                  <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Price</th>
                  <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Availability</th>
                  <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Temperature</th>
                  <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Actions</th>
                </tr>
              </thead>
              <tbody>
                <!-- Empty State - No Menu Items -->
                <tr v-if="menuItems.length === 0">
                  <td colspan="6" class="py-12 text-center">
                    <div class="text-gray-500">
                      <span class="material-symbols-outlined text-4xl mb-2">restaurant_menu</span>
                      <p class="font-medium">No menu items found</p>
                      <p class="text-sm">Menu items will appear here once added</p>
                    </div>
                  </td>
                </tr>

                <!-- No Search Results State -->
                <tr v-else-if="paginatedMenuItems.length === 0 && (searchQuery || currentFilter !== 'all' || availabilityFilter !== 'all')">
                  <td colspan="6" class="py-12 text-center">
                    <div class="text-gray-500">
                      <span class="material-symbols-outlined text-4xl mb-2">search_off</span>
                      <p class="font-medium">No results found</p>
                      <p class="text-sm">
                        <span v-if="searchQuery">No items match "{{ searchQuery }}"</span>
                        <span v-else>No items match the selected filters</span>
                      </p>
                      <div class="flex items-center justify-center gap-3 mt-4">
                        <button 
                          v-if="searchQuery"
                          @click="clearSearch"
                          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-sm"
                        >
                          <span class="material-symbols-outlined text-sm">close</span>
                          <span>Clear Search</span>
                        </button>
                        <button 
                          v-if="currentFilter !== 'all' || availabilityFilter !== 'all'"
                          @click="currentFilter = 'all'; availabilityFilter = 'all'; currentPage = 1;"
                          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-sm"
                        >
                          <span class="material-symbols-outlined text-sm">filter_alt_off</span>
                          <span>Clear Filters</span>
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
                
                <!-- Menu Items -->
                <tr 
                  v-for="item in paginatedMenuItems"
                  :key="item.id"
                  class="border-b border-gray-100 dark:border-gray-800 hover:bg-white/50 dark:hover:bg-gray-900/20 transition-colors"
                >
                  <td class="py-4 px-6">
                    <div class="font-medium text-black dark:text-white">{{ item.name }}</div>
                    <div class="text-sm text-black/60 dark:text-white/60 line-clamp-1">{{ item.description }}</div>
                  </td>
                  <td class="py-4 px-6">
                    <span class="text-gray-900 dark:text-white">{{ item.category || 'Uncategorized' }}</span>
                  </td>
                  <td class="py-4 px-6">
                    <span class="text-gray-900 dark:text-white font-medium">₱{{ Number(item.price || 0).toFixed(2) }}</span>
                  </td>
                  <td class="py-4 px-6">
                    <span 
                      :class="item.available 
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' 
                        : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'"
                      class="px-2 py-1 rounded-full text-xs font-medium"
                    >
                      {{ item.available ? 'Available' : 'Out of Stock' }}
                    </span>
                  </td>
                  <td class="py-4 px-6">
                    <span class="text-gray-900 dark:text-white">{{ item.temperature }}</span>
                  </td>
                  <td class="py-4 px-6">
                    <TableActionsDropdown
                      :row="item"
                      :actions="getMenuActions(item)"
                      placement="bottom-end"
                      width="32"
                      @action="onMenuAction"
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
              :total-items="totalFilteredItems"
              :items-per-page="itemsPerPage"
              items-text="items"
              @page-change="handlePageChange"
            />
          </div>
        </CardWrapper>
        
        <!-- Grid View Pagination (only show if there are items) -->
        <Pagination 
          v-if="viewMode === 'grid' && totalFilteredItems > itemsPerPage"
          :current-page="currentPage"
          :total-items="totalFilteredItems"
          :items-per-page="itemsPerPage"
          items-text="items"
          @page-change="handlePageChange"
        />
    </div>

    <!-- Menu Item Details Modal -->
    <AdminModal
      :show="showMenuModal"
      :title="selectedMenuItem?.name"
      subtitle="Menu item details and management"
      icon="restaurant_menu"
      max-width="4xl"
      animation-type="scale"
      @close="closeMenuModal"
    >
      <!-- Modal Content -->
      <div v-if="selectedMenuItem" class="space-y-6">
        <!-- Item Overview -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Item Image -->
            <div class="space-y-4">
              <div class="relative">
                <img 
                  :src="selectedMenuItem.image" 
                  :alt="selectedMenuItem.name" 
                  class="w-full h-64 object-cover rounded-lg"
                >
                <div class="absolute top-3 right-3 flex items-center gap-2">
                  <span v-if="selectedMenuItem.featured" class="bg-blue-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                    Featured
                  </span>
                  <span v-if="selectedMenuItem.popular" class="bg-purple-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                    Popular
                  </span>
                </div>
                <div class="absolute bottom-3 left-3">
                  <span class="bg-black/70 backdrop-blur text-white px-3 py-1 rounded-full text-sm font-medium">
                    {{ selectedMenuItem.category }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Item Details -->
            <div class="space-y-4">
              <div>
                <h4 class="text-lg font-bold text-black dark:text-white mb-2 flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary">restaurant_menu</span>
                  Item Details
                </h4>
                <p class="text-black/60 dark:text-white/60 leading-relaxed">{{ selectedMenuItem.description }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Price</label>
                  <p class="text-2xl font-bold text-primary">₱{{ Number(selectedMenuItem.price || 0).toFixed(2) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Category</label>
                  <p class="text-base text-black dark:text-white">{{ selectedMenuItem.category }}</p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Temperature</label>
                  <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-500">{{ selectedMenuItem.temperature === 'Cold' ? 'ac_unit' : 'local_fire_department' }}</span>
                    <p class="text-base text-black dark:text-white">{{ selectedMenuItem.temperature }}</p>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Available Sizes</label>
                  <p class="text-base text-black dark:text-white">{{ selectedMenuItem.sizes || 1 }} sizes</p>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Availability</label>
                <div class="flex items-center gap-2">
                  <span :class="selectedMenuItem.available ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
                  <span :class="selectedMenuItem.available ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="text-sm font-medium">
                    {{ selectedMenuItem.available ? 'Available' : 'Out of Stock' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Item Specifications -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">tune</span>
            Specifications
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <h5 class="font-medium text-black dark:text-white mb-2">Preparation Time</h5>
              <p class="text-sm text-black/60 dark:text-white/60">{{ selectedMenuItem.preparationTime || '3-5 minutes' }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <h5 class="font-medium text-black dark:text-white mb-2">Calories</h5>
              <p class="text-sm text-black/60 dark:text-white/60">120-180 cal</p>
            </div>
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <h5 class="font-medium text-black dark:text-white mb-2">Allergens</h5>
              <p class="text-sm text-black/60 dark:text-white/60">Milk, Nuts</p>
            </div>
          </div>
        </div>

        <!-- Sales Analytics -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">analytics</span>
            Performance Analytics
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700 text-center">
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">42</p>
              <p class="text-sm text-black/60 dark:text-white/60">Orders Today</p>
            </div>
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700 text-center">
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">₱2,289</p>
              <p class="text-sm text-black/60 dark:text-white/60">Revenue Today</p>
            </div>
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700 text-center">
              <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">4.8</p>
              <p class="text-sm text-black/60 dark:text-white/60">Customer Rating</p>
            </div>
            <div class="bg-white dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700 text-center">
              <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">#3</p>
              <p class="text-sm text-black/60 dark:text-white/60">Popularity Rank</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Custom Footer -->
      <template #footer>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <button
              @click="closeMenuModal"
              class="px-6 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
            >
              Close
            </button>
            <button class="px-6 py-2 rounded-xl bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800 transition-all font-medium">
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">content_copy</span>
                Duplicate Item
              </span>
            </button>
          </div>

          <div class="flex items-center gap-3">
            <button
              @click="toggleAvailability(selectedMenuItem)"
              :class="selectedMenuItem.available 
                ? 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-800' 
                : 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-800'"
              class="px-6 py-2 rounded-xl transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">{{ selectedMenuItem.available ? 'cancel' : 'check_circle' }}</span>
                {{ selectedMenuItem.available ? 'Mark Unavailable' : 'Mark Available' }}
              </span>
            </button>
            <button
              @click="editMenuItem(selectedMenuItem.id); closeMenuModal();"
              class="px-6 py-2 rounded-xl bg-primary text-white hover:bg-primary/90 hover:shadow-lg transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">edit</span>
                Edit Item
              </span>
            </button>
          </div>
        </div>
      </template>
    </AdminModal>
  </AdminLayout>
</template>

<style>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>