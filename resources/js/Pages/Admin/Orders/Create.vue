<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import FormInput from '@/Components/Admin/Forms/FormInput.vue';
import FormSelect from '@/Components/Admin/Forms/FormSelect.vue';
import FormTextarea from '@/Components/Admin/Forms/FormTextarea.vue';
import FormSection from '@/Components/Admin/Forms/FormSection.vue';
import AdminModal from '@/Components/Admin/UI/AdminModal.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  menuItems: {
    type: Array,
    default: () => []
  },
  tables: {
    type: Array,
    default: () => []
  }
});

const form = ref({
  sessionId: '',
  tableType: '',
  orderType: 'dine_in',
  customerName: '',
  customerPhone: '',
  deviceInfo: {
    ip: '',
    type: 'mobile'
  },
  paymentMethod: '',
  specialInstructions: '',
  notes: ''
});

const orderItems = ref([]);
const newCustomTable = ref('');
const showNewTable = ref(false);
const showAddonModal = ref(false);
const selectedMenuItem = ref(null);
const selectedAddons = ref([]);
const menuSearch = ref('');

const generateOrderId = () => {
  const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const randomLetter = letters[Math.floor(Math.random() * letters.length)];
  const randomNumber = Math.floor(Math.random() * 900) + 100;
  return `${randomLetter}${randomNumber.toString().padStart(3, '0')}`;
};

const generateSessionId = () => {
  const randomNumber = Math.floor(Math.random() * 9000) + 1000;
  return `#${randomNumber}`;
};

const tableOptions = computed(() => {
  const options = props.tables.map(table => ({
    value: `table_${table.id}`,
    label: `Table ${table.number} (${table.location || 'Indoor'}, ${table.capacity} seats)`
  }));
  options.push({ value: 'takeaway', label: 'Takeaway Counter' });
  return options;
});

const orderTypeOptions = [
  { value: 'dine_in', label: 'Dine In' },
  { value: 'takeaway', label: 'Takeaway' },
  { value: 'delivery', label: 'Delivery' }
];

const deviceTypeOptions = [
  { value: 'mobile', label: 'Mobile Device' },
  { value: 'tablet', label: 'Tablet Device' },
  { value: 'laptop', label: 'Laptop Device' },
  { value: 'desktop', label: 'Desktop Computer' }
];

const paymentOptions = [
  { value: '', label: 'Not Set' },
  { value: 'cash', label: 'Cash' },
  { value: 'card', label: 'Credit/Debit Card' },
  { value: 'e_wallet', label: 'E-Wallet (GCash, Maya)' },
  { value: 'qr', label: 'QR Payment' },
  { value: 'bank_transfer', label: 'Bank Transfer' }
];

const filteredMenuItems = computed(() => {
  if (!menuSearch.value.trim()) return props.menuItems;
  const query = menuSearch.value.toLowerCase();
  return props.menuItems.filter(item =>
    item.name.toLowerCase().includes(query) ||
    (item.category?.name || '').toLowerCase().includes(query)
  );
});

const groupedMenuItems = computed(() => {
  const groups = {};
  filteredMenuItems.value.forEach(item => {
    const category = item.category?.name || 'Other';
    if (!groups[category]) {
      groups[category] = [];
    }
    groups[category].push(item);
  });
  return groups;
});

const subtotal = computed(() => {
  return orderItems.value.reduce((total, item) => {
    const itemTotal = item.price * item.quantity;
    const addonsTotal = (item.addons || []).reduce((sum, addon) => sum + addon.price, 0) * item.quantity;
    return total + itemTotal + addonsTotal;
  }, 0);
});

const tax = computed(() => {
  return subtotal.value * 0.12;
});

const grandTotal = computed(() => {
  return subtotal.value + tax.value;
});

const handleTableChange = (value) => {
  if (value === 'add_new') {
    showNewTable.value = true;
    form.value.tableType = '';
  } else {
    showNewTable.value = false;
    form.value.tableType = value;
  }
};

const addNewTable = () => {
  if (newCustomTable.value.trim()) {
    const newValue = newCustomTable.value.trim().toLowerCase().replace(/\s+/g, '_');
    form.value.tableType = newValue;
    showNewTable.value = false;
    newCustomTable.value = '';
  }
};

const cancelNewTable = () => {
  showNewTable.value = false;
  newCustomTable.value = '';
  form.value.tableType = '';
};

const openAddonModal = (menuItem) => {
  selectedMenuItem.value = menuItem;
  selectedAddons.value = [];
  if (menuItem.addons && menuItem.addons.length > 0) {
    showAddonModal.value = true;
  } else {
    addToCart(menuItem, []);
  }
};

const toggleAddon = (addon) => {
  const index = selectedAddons.value.findIndex(a => a.id === addon.id);
  if (index === -1) {
    selectedAddons.value.push({
      id: addon.id,
      name: addon.name,
      price: addon.price / 100
    });
  } else {
    selectedAddons.value.splice(index, 1);
  }
};

const isAddonSelected = (addonId) => {
  return selectedAddons.value.some(a => a.id === addonId);
};

const confirmAddons = () => {
  if (selectedMenuItem.value) {
    addToCart(selectedMenuItem.value, [...selectedAddons.value]);
    showAddonModal.value = false;
    selectedMenuItem.value = null;
    selectedAddons.value = [];
  }
};

const addToCart = (menuItem, addons) => {
  const addonsKey = addons.map(a => a.id).sort().join(',');
  const existingItem = orderItems.value.find(item => 
    item.id === menuItem.id && 
    (item.addons || []).map(a => a.id).sort().join(',') === addonsKey
  );
  
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    orderItems.value.push({
      id: menuItem.id,
      name: menuItem.name,
      price: menuItem.price_formatted,
      category: menuItem.category?.name || 'Other',
      quantity: 1,
      addons: addons
    });
  }
};

const removeMenuItem = (index) => {
  orderItems.value.splice(index, 1);
};

const updateItemQuantity = (index, quantity) => {
  if (quantity <= 0) {
    removeMenuItem(index);
  } else {
    orderItems.value[index].quantity = quantity;
  }
};

const clearCart = () => {
  if (confirm('Are you sure you want to clear all items?')) {
    orderItems.value = [];
  }
};

const getItemTotal = (item) => {
  const itemPrice = item.price * item.quantity;
  const addonsPrice = (item.addons || []).reduce((sum, addon) => sum + addon.price, 0) * item.quantity;
  return itemPrice + addonsPrice;
};

const submitForm = () => {
  if (!form.value.tableType || !form.value.orderType) {
    alert('Please fill in all required fields.');
    return;
  }

  if (orderItems.value.length === 0) {
    alert('Please add at least one item to the order.');
    return;
  }

  const orderId = generateOrderId();
  const sessionId = form.value.sessionId || generateSessionId();

  const newOrder = {
    id: orderId,
    sessionId: sessionId,
    tableType: form.value.tableType,
    orderType: form.value.orderType,
    customerName: form.value.customerName,
    customerPhone: form.value.customerPhone,
    deviceInfo: form.value.deviceInfo,
    items: orderItems.value.map(item => ({
      id: item.id,
      name: item.name,
      quantity: item.quantity,
      price: item.price,
      addons: item.addons || [],
      total: getItemTotal(item)
    })),
    subtotal: subtotal.value,
    tax: tax.value,
    total: grandTotal.value,
    paymentMethod: form.value.paymentMethod,
    specialInstructions: form.value.specialInstructions,
    notes: form.value.notes,
    status: 'pending',
    createdAt: new Date().toISOString()
  };

  console.log('New order:', newOrder);
  alert(`Order ${orderId} has been created successfully!`);
  router.get('/admin/orders');
};

const saveDraft = () => {
  const draft = {
    form: form.value,
    orderItems: orderItems.value
  };
  localStorage.setItem('orderDraft', JSON.stringify(draft));
  alert('Draft saved successfully!');
};

const goBack = () => {
  router.get('/admin/orders');
};

form.value.sessionId = generateSessionId();
</script>

<template>
  <AdminLayout 
    title="Create New Order"
    page-title="Create New Order"
    page-subtitle="Create a new order for customers"
  >
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <button 
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
        >
          <span class="material-symbols-outlined">arrow_back</span>
          <span>Back to Orders</span>
        </button>
      </div>
    </div>

    <div class="max-w-7xl mx-auto">
      <form @submit.prevent="submitForm" class="space-y-12">
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Order Information"
            subtitle="Basic order details and identification"
            icon="receipt"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <FormInput
              v-model="form.sessionId"
              label="Session ID"
              type="text"
              placeholder="Auto-generated session ID"
              readonly
            />
            
            <div class="space-y-2">
              <FormSelect
                v-model="form.tableType"
                label="Table/Location"
                placeholder="Select table or service type"
                :options="tableOptions"
                allow-custom
                custom-option-label="+ Add Custom Table/Area"
                required
                @add-custom="handleTableChange('add_new')"
                @change="(e) => handleTableChange(e.target.value)"
              />
              
              <div v-show="showNewTable" class="space-y-3 mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <FormInput
                  v-model="newCustomTable"
                  type="text"
                  placeholder="Enter table/area name"
                />
                <div class="flex gap-3">
                  <button 
                    type="button" 
                    @click="addNewTable"
                    class="px-4 py-2 bg-[#ec7813] text-white rounded-lg hover:bg-[#ea580c] transition-all text-sm font-medium"
                  >
                    Add
                  </button>
                  <button 
                    type="button" 
                    @click="cancelNewTable"
                    class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 rounded-lg text-sm font-medium"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </div>
            
            <FormSelect
              v-model="form.orderType"
              label="Order Type"
              :options="orderTypeOptions"
              required
            />
          </div>
        </CardWrapper>

        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Customer Information"
            subtitle="Customer details and device information"
            icon="person"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <FormInput
              v-model="form.customerName"
              label="Customer Name"
              type="text"
              placeholder="Enter customer name (optional)"
            />
            
            <FormInput
              v-model="form.customerPhone"
              label="Phone Number"
              type="tel"
              placeholder="Enter phone number (optional)"
            />
            
            <FormInput
              v-model="form.deviceInfo.ip"
              label="Device IP Address"
              type="text"
              placeholder="e.g., 192.168.1.45"
            />
            
            <FormSelect
              v-model="form.deviceInfo.type"
              label="Device Type"
              :options="deviceTypeOptions"
            />
          </div>
        </CardWrapper>

        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Order Items"
            subtitle="Add items to this order"
            icon="shopping_cart"
          />
          
          <div class="mb-8">
            <div class="bg-white dark:bg-gray-900/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                <label class="block text-sm font-semibold text-gray-900 dark:text-white">Available Menu Items</label>
                <div class="relative w-full sm:w-64">
                  <input 
                    v-model="menuSearch"
                    type="search" 
                    placeholder="Search menu..." 
                    class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                  >
                  <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                </div>
              </div>
              
              <div v-if="menuItems.length === 0" class="text-center py-8">
                <p class="text-gray-500 dark:text-gray-400">No menu items available</p>
              </div>

              <div v-else class="space-y-6">
                <div v-for="(items, category) in groupedMenuItems" :key="category">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ category }}</h4>
                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                      v-for="item in items"
                      :key="item.id"
                      class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:border-[#ec7813]/30 transition-all cursor-pointer group"
                      @click="openAddonModal(item)"
                    >
                      <div class="flex items-center justify-between mb-2">
                        <h5 class="font-medium text-gray-900 dark:text-white group-hover:text-[#ec7813] text-sm truncate pr-2">{{ item.name }}</h5>
                        <span class="material-symbols-outlined text-gray-400 group-hover:text-[#ec7813] flex-shrink-0">add</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                          <span class="text-xs text-gray-500 dark:text-gray-400">{{ item.category?.name || 'Other' }}</span>
                          <span v-if="item.addons && item.addons.length > 0" class="text-xs text-primary">
                            +{{ item.addons.length }} add-ons
                          </span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">₱{{ Number(item.price_formatted || 0).toFixed(2) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="orderItems.length > 0">
            <div class="bg-white dark:bg-gray-900/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between mb-4">
                <label class="block text-sm font-semibold text-gray-900 dark:text-white">Selected Items</label>
                <button 
                  type="button"
                  @click="clearCart"
                  class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 flex items-center gap-1"
                >
                  <span class="material-symbols-outlined text-sm">clear_all</span>
                  Clear All
                </button>
              </div>
              
              <div class="space-y-3">
                <div 
                  v-for="(item, index) in orderItems"
                  :key="index"
                  class="flex flex-col p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <h6 class="font-medium text-gray-900 dark:text-white">{{ item.name }}</h6>
                      <p class="text-sm text-gray-600 dark:text-gray-400">{{ item.category }} - ₱{{ item.price.toFixed(2) }} each</p>
                      <div v-if="item.addons && item.addons.length > 0" class="mt-2">
                        <div class="flex flex-wrap gap-1">
                          <span 
                            v-for="addon in item.addons" 
                            :key="addon.id"
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-primary/10 text-primary"
                          >
                            {{ addon.name }} (+₱{{ addon.price.toFixed(2) }})
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    <div class="flex items-center gap-3 ml-4">
                      <div class="flex items-center gap-2">
                        <button 
                          type="button"
                          @click="updateItemQuantity(index, item.quantity - 1)"
                          class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 flex items-center justify-center"
                        >
                          <span class="material-symbols-outlined text-sm">remove</span>
                        </button>
                        <span class="w-12 text-center font-medium text-gray-900 dark:text-white">{{ item.quantity }}</span>
                        <button 
                          type="button"
                          @click="updateItemQuantity(index, item.quantity + 1)"
                          class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 flex items-center justify-center"
                        >
                          <span class="material-symbols-outlined text-sm">add</span>
                        </button>
                      </div>
                      
                      <div class="text-right min-w-[80px]">
                        <p class="font-bold text-gray-900 dark:text-white">₱{{ getItemTotal(item).toFixed(2) }}</p>
                      </div>
                      
                      <button 
                        type="button"
                        @click="removeMenuItem(index)"
                        class="p-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                      >
                        <span class="material-symbols-outlined text-sm">delete</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="space-y-2 max-w-sm ml-auto">
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                    <span class="font-medium text-gray-900 dark:text-white">₱{{ subtotal.toFixed(2) }}</span>
                  </div>
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Tax (12%):</span>
                    <span class="font-medium text-gray-900 dark:text-white">₱{{ tax.toFixed(2) }}</span>
                  </div>
                  <div class="flex items-center justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-2">
                    <span class="text-gray-900 dark:text-white">Total:</span>
                    <span class="text-[#ec7813]">₱{{ grandTotal.toFixed(2) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-900/20 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <span class="material-symbols-outlined text-gray-400 text-2xl">shopping_cart</span>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No items added</h3>
            <p class="text-gray-600 dark:text-gray-400">Click on menu items above to add them to this order</p>
          </div>
        </CardWrapper>

        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Payment & Additional Details"
            subtitle="Payment method and special instructions"
            icon="payments"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <FormSelect
              v-model="form.paymentMethod"
              label="Payment Method"
              placeholder="Select payment method (optional)"
              :options="paymentOptions"
            />
            
            <div class="space-y-4">
              <label class="block text-sm font-semibold text-gray-900 dark:text-white">Payment Status Preview</label>
              <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="flex items-center gap-2">
                  <span 
                    class="w-3 h-3 rounded-full"
                    :class="form.paymentMethod ? 'bg-green-500' : 'bg-red-500'"
                  ></span>
                  <span class="text-sm font-medium">
                    {{ form.paymentMethod ? `Pre-paid (${paymentOptions.find(opt => opt.value === form.paymentMethod)?.label})` : 'Unpaid - Pay at counter' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 space-y-6">
            <FormTextarea
              v-model="form.specialInstructions"
              label="Special Instructions"
              placeholder="Add any special preparation instructions..."
              rows="3"
              max-length="500"
              show-char-count
            />
            
            <FormTextarea
              v-model="form.notes"
              label="Internal Notes"
              placeholder="Add any internal notes for staff..."
              rows="2"
            />
          </div>
        </CardWrapper>

        <CardWrapper rounded="xl" padding="lg" shadow="sm">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="text-left">
              <p class="text-sm text-gray-600 dark:text-gray-400">Order Summary:</p>
              <p class="text-lg font-bold text-gray-900 dark:text-white">
                {{ orderItems.length }} {{ orderItems.length === 1 ? 'item' : 'items' }} - ₱{{ grandTotal.toFixed(2) }}
              </p>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-3">
              <button 
                type="button" 
                @click="goBack"
                class="w-full sm:w-auto px-8 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all font-medium"
              >
                <span class="flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-lg">close</span>
                  Cancel
                </span>
              </button>
              <button 
                type="button" 
                @click="saveDraft"
                class="w-full sm:w-auto px-8 py-3 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 transition-all font-medium"
              >
                <span class="flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-lg">draft</span>
                  Save as Draft
                </span>
              </button>
              <button 
                type="submit"
                class="w-full sm:w-auto px-8 py-3 rounded-xl bg-[#ec7813] text-white hover:bg-[#ea580c] hover:shadow-lg transition-all font-semibold"
                :disabled="orderItems.length === 0"
                :class="orderItems.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
              >
                <span class="flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-lg">add_shopping_cart</span>
                  Create Order
                </span>
              </button>
            </div>
          </div>
        </CardWrapper>
      </form>
    </div>

    <AdminModal
      :show="showAddonModal"
      :title="selectedMenuItem?.name"
      subtitle="Select add-ons for this item"
      icon="extension"
      max-width="lg"
      @close="showAddonModal = false"
    >
      <div v-if="selectedMenuItem" class="space-y-4">
        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
          <div>
            <p class="font-medium text-gray-900 dark:text-white">{{ selectedMenuItem.name }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedMenuItem.category?.name || 'Other' }}</p>
          </div>
          <p class="text-xl font-bold text-primary">₱{{ Number(selectedMenuItem.price_formatted || 0).toFixed(2) }}</p>
        </div>

        <div v-if="selectedMenuItem.addons && selectedMenuItem.addons.length > 0">
          <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Available Add-ons</h4>
          <div class="space-y-2">
            <label
              v-for="addon in selectedMenuItem.addons"
              :key="addon.id"
              class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition-all duration-200"
              :class="isAddonSelected(addon.id) 
                ? 'border-primary bg-primary/5 dark:bg-primary/10' 
                : 'border-gray-200 dark:border-gray-700 hover:border-primary/30 hover:bg-gray-50 dark:hover:bg-gray-800'"
            >
              <input
                type="checkbox"
                :checked="isAddonSelected(addon.id)"
                @change="toggleAddon(addon)"
                class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
              >
              <div class="flex-1">
                <span class="text-sm font-medium text-gray-900 dark:text-white block">{{ addon.name }}</span>
                <span v-if="addon.description" class="text-xs text-gray-500 dark:text-gray-400">{{ addon.description }}</span>
              </div>
              <span class="text-sm font-medium text-primary">+₱{{ addon.price_formatted.toFixed(2) }}</span>
            </label>
          </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
          <div class="flex items-center justify-between mb-4">
            <span class="text-sm text-gray-600 dark:text-gray-400">Selected add-ons:</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ selectedAddons.length }} item(s) - +₱{{ selectedAddons.reduce((sum, a) => sum + a.price, 0).toFixed(2) }}
            </span>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-3">
          <button
            @click="showAddonModal = false"
            class="px-6 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
          >
            Cancel
          </button>
          <button
            @click="confirmAddons"
            class="px-6 py-2 rounded-xl bg-primary text-white hover:bg-primary/90 transition-all font-medium"
          >
            <span class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg">add_shopping_cart</span>
              Add to Order
            </span>
          </button>
        </div>
      </template>
    </AdminModal>
  </AdminLayout>
</template>
