<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Admin/UI/Pagination.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import TableActionsDropdown from '@/Components/Admin/UI/TableActionsDropdown.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  addons: {
    type: Array,
    default: () => []
  }
});

const addons = ref(props.addons || []);
const currentFilter = ref('all');
const availabilityFilter = ref('all');
const searchQuery = ref('');
const viewMode = ref('grid');
const currentPage = ref(1);
const gridItemsPerPage = 9;
const tableItemsPerPage = 10;

const categories = computed(() => {
  const cats = {};
  addons.value.forEach(addon => {
    if (!cats[addon.category]) {
      cats[addon.category] = 0;
    }
    cats[addon.category]++;
  });
  return Object.entries(cats).map(([name, count]) => ({
    value: name,
    label: name,
    count
  }));
});

const filteredItems = computed(() => {
  let filtered = addons.value;
  
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim();
    filtered = filtered.filter(item => {
      return item.name.toLowerCase().includes(query) || 
             (item.description || '').toLowerCase().includes(query) || 
             item.category.toLowerCase().includes(query);
    });
  }
  
  if (currentFilter.value !== 'all') {
    filtered = filtered.filter(item => item.category === currentFilter.value);
  }
  
  if (availabilityFilter.value === 'available') {
    filtered = filtered.filter(item => item.available);
  } else if (availabilityFilter.value === 'unavailable') {
    filtered = filtered.filter(item => !item.available);
  }
  
  return filtered;
});

const itemsPerPage = computed(() => viewMode.value === 'table' ? tableItemsPerPage : gridItemsPerPage);
const totalFilteredItems = computed(() => filteredItems.value.length);

const paginatedAddons = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredItems.value.slice(start, end);
});

const handlePageChange = (page) => {
  currentPage.value = page;
};

const setFilter = (filter) => {
  currentFilter.value = filter;
  currentPage.value = 1;
};

const setAvailabilityFilter = (filter) => {
  availabilityFilter.value = filter;
  currentPage.value = 1;
};

const handleSearch = () => {
  currentPage.value = 1;
};

const clearSearch = () => {
  searchQuery.value = '';
  currentPage.value = 1;
};

const setViewMode = (mode) => {
  if (viewMode.value !== mode) {
    viewMode.value = mode;
    currentPage.value = 1;
  }
};

const refreshData = () => {
  router.reload({ only: ['addons'] });
};

const deleteAddon = async (addonId) => {
  if (!confirm('Are you sure you want to delete this add-on?')) {
    return;
  }
  router.delete(route('admin.addons.destroy', addonId), {
    onSuccess: () => {
      addons.value = addons.value.filter(item => item.id !== addonId);
    }
  });
};

const toggleAvailability = async (item) => {
  try {
    const response = await fetch(route('admin.addons.toggle', item.id), {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    });
    
    if (response.ok) {
      const updatedItem = await response.json();
      const index = addons.value.findIndex(i => i.id === item.id);
      if (index !== -1) {
        addons.value[index] = updatedItem;
      }
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error updating add-on. Please try again.');
  }
};

const editAddon = (addonId) => {
  router.get(route('admin.addons.edit', addonId));
};

const getAddonActions = (item) => {
  return [
    {
      key: 'edit',
      label: 'Edit Add-on',
      icon: 'edit',
      href: route('admin.addons.edit', item.id)
    },
    {
      key: 'toggle',
      label: item.available ? 'Mark Unavailable' : 'Mark Available',
      icon: item.available ? 'cancel' : 'check_circle',
      onClick: () => toggleAvailability(item)
    },
    {
      key: 'delete',
      label: 'Delete Add-on',
      icon: 'delete',
      colorClass: 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20',
      onClick: () => deleteAddon(item.id)
    }
  ];
};

const getCategoryIcon = (category) => {
  const icons = {
    'Milk': 'water_drop',
    'Extras': 'add_circle',
    'Toppings': 'cake',
    'Syrups': 'local_cafe',
    'Sweeteners': 'nutrition'
  };
  return icons[category] || 'extension';
};
</script>

<template>
  <AdminLayout 
    title="Add-ons Management"
    page-title="Add-ons Management"
    page-subtitle="Create and manage customization options for menu items"
  >
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-1">
        <div class="relative">
          <input 
            v-model="searchQuery"
            @input="handleSearch"
            type="search" 
            placeholder="Search add-ons..." 
            class="pl-10 pr-10 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary min-w-[200px]"
          >
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
          <button 
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          >
            <span class="material-symbols-outlined text-sm">close</span>
          </button>
        </div>

        <div class="relative">
          <select 
            v-model="currentFilter"
            @change="setFilter($event.target.value)"
            class="pl-10 pr-8 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary appearance-none cursor-pointer min-w-[160px]"
          >
            <option value="all">All Categories ({{ addons.length }})</option>
            <option v-for="category in categories" :key="category.value" :value="category.value">
              {{ category.label }} ({{ category.count }})
            </option>
          </select>
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">category</span>
          <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">expand_more</span>
        </div>

        <div class="relative">
          <select 
            v-model="availabilityFilter"
            @change="setAvailabilityFilter($event.target.value)"
            class="pl-10 pr-8 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary appearance-none cursor-pointer min-w-[140px]"
          >
            <option value="all">All Items</option>
            <option value="available">Available Only</option>
            <option value="unavailable">Unavailable</option>
          </select>
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
            {{ availabilityFilter === 'available' ? 'check_circle' : availabilityFilter === 'unavailable' ? 'cancel' : 'inventory' }}
          </span>
          <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">expand_more</span>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button 
          @click="refreshData"
          class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
          title="Refresh data"
        >
          <span class="material-symbols-outlined">refresh</span>
        </button>
        <button 
          @click="router.get(route('admin.addons.create'))"
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all"
        >
          <span class="material-symbols-outlined">add</span>
          <span>Add Add-on</span>
        </button>
      </div>
    </div>

    <div>
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          <span v-if="!searchQuery && currentFilter === 'all' && availabilityFilter === 'all'">
            Showing all {{ addons.length }} add-ons
          </span>
          <span v-else>
            Showing {{ filteredItems.length }} of {{ addons.length }} add-ons
          </span>
        </div>
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1 border border-gray-200 dark:border-gray-700 rounded-lg p-1">
            <button 
              @click="setViewMode('grid')"
              :class="viewMode === 'grid' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'"
              class="p-1.5 rounded transition-all"
            >
              <span class="material-symbols-outlined text-sm">grid_view</span>
            </button>
            <button 
              @click="setViewMode('table')"
              :class="viewMode === 'table' ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'"
              class="p-1.5 rounded transition-all"
            >
              <span class="material-symbols-outlined text-sm">table_rows</span>
            </button>
          </div>
        </div>
      </div>
      
      <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-if="addons.length === 0" class="col-span-full text-center py-12">
          <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
            <span class="material-symbols-outlined text-4xl text-gray-400">extension</span>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No add-ons found</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-4">Get started by creating your first add-on.</p>
          <button 
            @click="router.get(route('admin.addons.create'))"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all"
          >
            <span class="material-symbols-outlined">add</span>
            <span>Add First Add-on</span>
          </button>
        </div>

        <div 
          v-else-if="filteredItems.length === 0" 
          class="col-span-full text-center py-12"
        >
          <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
            <span class="material-symbols-outlined text-4xl text-gray-400">search_off</span>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No results found</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-4">No add-ons match the selected filters</p>
        </div>

        <CardWrapper
          v-for="item in paginatedAddons"
          :key="item.id"
          hover
          shadow="hover"
          class="group"
        >
          <div class="p-6">
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                  <span class="material-symbols-outlined text-primary text-2xl">{{ getCategoryIcon(item.category) }}</span>
                </div>
                <div>
                  <h4 class="font-semibold text-lg text-black dark:text-white">{{ item.name }}</h4>
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ item.category }}</span>
                </div>
              </div>
              <span class="text-xl font-bold text-primary">+₱{{ item.price_formatted.toFixed(2) }}</span>
            </div>

            <p v-if="item.description" class="text-black/60 dark:text-white/60 text-sm mb-4 line-clamp-2">
              {{ item.description }}
            </p>

            <div class="flex items-center gap-2 mb-4">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium">
                <span class="material-symbols-outlined text-sm">repeat</span>
                Max: {{ item.max_quantity }}
              </span>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2">
                <span :class="item.available ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ item.available ? 'Available' : 'Unavailable' }}
                </span>
              </div>
              <div class="flex items-center gap-2">
                <button 
                  @click="editAddon(item.id)"
                  class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                >
                  <span class="material-symbols-outlined text-sm">edit</span>
                </button>
                <button 
                  @click="toggleAvailability(item)"
                  class="p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                >
                  <span class="material-symbols-outlined text-sm">{{ item.available ? 'cancel' : 'check_circle' }}</span>
                </button>
                <button 
                  @click="deleteAddon(item.id)"
                  class="p-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                >
                  <span class="material-symbols-outlined text-sm">delete</span>
                </button>
              </div>
            </div>
          </div>
        </CardWrapper>

        <div 
          v-if="addons.length > 0"
          @click="router.get(route('admin.addons.create'))"
          class="bg-gray-50 dark:bg-gray-900/20 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg min-h-[200px] flex items-center justify-center hover:border-primary dark:hover:border-primary transition-all cursor-pointer group"
        >
          <div class="text-center">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center group-hover:bg-primary/10 transition-all">
              <span class="material-symbols-outlined text-2xl text-gray-400 group-hover:text-primary">add</span>
            </div>
            <p class="font-medium text-gray-600 dark:text-gray-400 group-hover:text-primary">Add New Add-on</p>
          </div>
        </div>
      </div>

      <CardWrapper v-else>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Add-on</th>
                <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Category</th>
                <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Price</th>
                <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Max Qty</th>
                <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Status</th>
                <th class="text-left py-3 px-6 text-gray-700 dark:text-gray-400 font-medium text-sm">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="addons.length === 0">
                <td colspan="6" class="py-12 text-center">
                  <div class="text-gray-500">
                    <span class="material-symbols-outlined text-4xl mb-2">extension</span>
                    <p class="font-medium">No add-ons found</p>
                  </div>
                </td>
              </tr>
              
              <tr 
                v-for="item in paginatedAddons"
                :key="item.id"
                class="border-b border-gray-100 dark:border-gray-800 hover:bg-white/50 dark:hover:bg-gray-900/20 transition-colors"
              >
                <td class="py-4 px-6">
                  <div class="font-medium text-black dark:text-white">{{ item.name }}</div>
                  <div class="text-sm text-black/60 dark:text-white/60 line-clamp-1">{{ item.description }}</div>
                </td>
                <td class="py-4 px-6">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium">
                    <span class="material-symbols-outlined text-sm">{{ getCategoryIcon(item.category) }}</span>
                    {{ item.category }}
                  </span>
                </td>
                <td class="py-4 px-6">
                  <span class="text-gray-900 dark:text-white font-medium">+₱{{ item.price_formatted.toFixed(2) }}</span>
                </td>
                <td class="py-4 px-6">
                  <span class="text-gray-900 dark:text-white">{{ item.max_quantity }}</span>
                </td>
                <td class="py-4 px-6">
                  <span 
                    :class="item.available 
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' 
                      : 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400'"
                    class="px-2 py-1 rounded-full text-xs font-medium"
                  >
                    {{ item.available ? 'Available' : 'Unavailable' }}
                  </span>
                </td>
                <td class="py-4 px-6">
                  <TableActionsDropdown
                    :row="item"
                    :actions="getAddonActions(item)"
                    placement="bottom-end"
                    width="32"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
          <Pagination 
            :current-page="currentPage"
            :total-items="totalFilteredItems"
            :items-per-page="itemsPerPage"
            items-text="add-ons"
            @page-change="handlePageChange"
          />
        </div>
      </CardWrapper>
      
      <Pagination 
        v-if="viewMode === 'grid' && totalFilteredItems > itemsPerPage"
        :current-page="currentPage"
        :total-items="totalFilteredItems"
        :items-per-page="itemsPerPage"
        items-text="add-ons"
        @page-change="handlePageChange"
      />
    </div>
  </AdminLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
