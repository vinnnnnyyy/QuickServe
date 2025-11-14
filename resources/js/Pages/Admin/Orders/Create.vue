<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import FormInput from '@/Components/Admin/Forms/FormInput.vue';
import FormSelect from '@/Components/Admin/Forms/FormSelect.vue';
import FormTextarea from '@/Components/Admin/Forms/FormTextarea.vue';
import FormSection from '@/Components/Admin/Forms/FormSection.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

// Form data
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

// Generate next order ID
const generateOrderId = () => {
  const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const randomLetter = letters[Math.floor(Math.random() * letters.length)];
  const randomNumber = Math.floor(Math.random() * 900) + 100; // 100-999
  return `${randomLetter}${randomNumber.toString().padStart(3, '0')}`;
};

// Generate next session ID
const generateSessionId = () => {
  const randomNumber = Math.floor(Math.random() * 9000) + 1000; // 1000-9999
  return `#${randomNumber}`;
};

// Table options (would come from your tables data)
const tableOptions = ref([
  { value: 'table_1', label: 'Table 1 (Indoor, 3 seats)' },
  { value: 'table_2', label: 'Table 2 (Indoor, 4 seats)' },
  { value: 'table_3', label: 'Table 3 (Outdoor, 2 seats)' },
  { value: 'table_4', label: 'Table 4 (Indoor, 6 seats)' },
  { value: 'table_5', label: 'Table 5 (Patio, 4 seats)' },
  { value: 'table_6', label: 'Table 6 (Bar, 8 seats)' },
  { value: 'takeaway', label: 'Takeaway Counter' }
]);

// Order type options
const orderTypeOptions = [
  { value: 'dine_in', label: 'Dine In' },
  { value: 'takeaway', label: 'Takeaway' },
  { value: 'delivery', label: 'Delivery' }
];

// Device type options
const deviceTypeOptions = [
  { value: 'mobile', label: 'Mobile Device' },
  { value: 'tablet', label: 'Tablet Device' },
  { value: 'laptop', label: 'Laptop Device' },
  { value: 'desktop', label: 'Desktop Computer' }
];

// Payment method options
const paymentOptions = [
  { value: '', label: 'Not Set' },
  { value: 'cash', label: 'Cash' },
  { value: 'card', label: 'Credit/Debit Card' },
  { value: 'e_wallet', label: 'E-Wallet (GCash, Maya)' },
  { value: 'qr', label: 'QR Payment' },
  { value: 'bank_transfer', label: 'Bank Transfer' }
];

// Sample menu items for selection
const menuItems = ref([
  {
    id: 1,
    name: 'Iced Brown Sugar Oatmilk Shaken Espresso',
    price: 5.45,
    category: 'Cold Drinks'
  },
  {
    id: 2,
    name: 'Pistachio Cream Cold Brew',
    price: 4.95,
    category: 'Cold Drinks'
  },
  {
    id: 3,
    name: 'Classic Americano',
    price: 3.85,
    category: 'Hot Drinks'
  },
  {
    id: 4,
    name: 'Caramel Macchiato',
    price: 5.25,
    category: 'Hot Drinks'
  },
  {
    id: 5,
    name: 'Croissant',
    price: 3.50,
    category: 'Pastries'
  },
  {
    id: 6,
    name: 'Blueberry Muffin',
    price: 4.25,
    category: 'Pastries'
  }
]);

// Computed totals
const subtotal = computed(() => {
  return orderItems.value.reduce((total, item) => total + (item.price * item.quantity), 0);
});

const tax = computed(() => {
  return subtotal.value * 0.12; // 12% tax
});

const grandTotal = computed(() => {
  return subtotal.value + tax.value;
});

// Methods
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
    tableOptions.value.push({
      value: newValue,
      label: newCustomTable.value.trim()
    });
    showNewTable.value = false;
    newCustomTable.value = '';
  }
};

const cancelNewTable = () => {
  showNewTable.value = false;
  newCustomTable.value = '';
  form.value.tableType = '';
};

const addMenuItem = (menuItem) => {
  const existingItem = orderItems.value.find(item => item.id === menuItem.id);
  
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    orderItems.value.push({
      id: menuItem.id,
      name: menuItem.name,
      price: menuItem.price,
      category: menuItem.category,
      quantity: 1
    });
  }
};

const removeMenuItem = (itemId) => {
  const index = orderItems.value.findIndex(item => item.id === itemId);
  if (index > -1) {
    orderItems.value.splice(index, 1);
  }
};

const updateItemQuantity = (itemId, quantity) => {
  const item = orderItems.value.find(item => item.id === itemId);
  if (item) {
    if (quantity <= 0) {
      removeMenuItem(itemId);
    } else {
      item.quantity = quantity;
    }
  }
};

const clearCart = () => {
  if (confirm('Are you sure you want to clear all items?')) {
    orderItems.value = [];
  }
};

const submitForm = () => {
  // Validate required fields
  if (!form.value.tableType || !form.value.orderType) {
    alert('Please fill in all required fields.');
    return;
  }

  // Validate order items
  if (orderItems.value.length === 0) {
    alert('Please add at least one item to the order.');
    return;
  }

  const orderId = generateOrderId();
  const sessionId = form.value.sessionId || generateSessionId();

  const newOrder = {
    id: orderId,
    sessionId: sessionId,
    tableType: tableOptions.value.find(option => option.value === form.value.tableType)?.label || form.value.tableType,
    orderType: orderTypeOptions.find(option => option.value === form.value.orderType)?.label || form.value.orderType,
    customerName: form.value.customerName,
    customerPhone: form.value.customerPhone,
    deviceInfo: {
      ip: form.value.deviceInfo.ip || '192.168.1.' + Math.floor(Math.random() * 200 + 1),
      type: deviceTypeOptions.find(option => option.value === form.value.deviceInfo.type)?.label || 'Mobile Device'
    },
    items: {
      count: orderItems.value.length,
      description: orderItems.value.map(item => `${item.name} (${item.quantity}x)`).join(', '),
      details: orderItems.value
    },
    total: grandTotal.value,
    subtotal: subtotal.value,
    tax: tax.value,
    payment: {
      status: form.value.paymentMethod ? `Pre-paid (${paymentOptions.find(opt => opt.value === form.value.paymentMethod)?.label})` : 'Unpaid',
      method: form.value.paymentMethod,
      color: form.value.paymentMethod ? 'green' : 'red'
    },
    status: {
      text: 'Pending',
      color: 'orange'
    },
    specialInstructions: form.value.specialInstructions,
    notes: form.value.notes,
    time: 'Just now',
    createdAt: new Date().toISOString()
  };

  console.log('New order:', newOrder);
  alert(`Order ${orderId} has been created successfully!`);
  
  // Navigate back to orders page
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

// Initialize form with generated IDs
form.value.sessionId = generateSessionId();
</script>

<template>
  <AdminLayout 
    title="Create New Order"
    page-title="Create New Order"
    page-subtitle="Create a new order for customers"
  >
    <!-- Header Actions -->
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
        <!-- Order Information -->
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
              
              <!-- New Table Input -->
              <div v-show="showNewTable" class="space-y-3 mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <FormInput
                  v-model="newCustomTable"
                  type="text"
                  placeholder="Enter table/area name (e.g., VIP Room 1, Counter A)"
                />
                <div class="flex gap-3">
                  <button 
                    type="button" 
                    @click="addNewTable"
                    class="px-4 py-2 bg-[#ec7813] text-white rounded-lg hover:bg-[#ea580c] transition-all text-sm font-medium"
                  >
                    <span class="flex items-center gap-2">
                      <span class="material-symbols-outlined text-sm">add</span>
                      Add
                    </span>
                  </button>
                  <button 
                    type="button" 
                    @click="cancelNewTable"
                    class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all text-sm font-medium"
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

        <!-- Customer Information -->
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
              placeholder="e.g., 192.168.1.45 (auto-detected if available)"
            />
            
            <FormSelect
              v-model="form.deviceInfo.type"
              label="Device Type"
              :options="deviceTypeOptions"
            />
          </div>
        </CardWrapper>

        <!-- Order Items -->
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Order Items"
            subtitle="Add items to this order"
            icon="shopping_cart"
          />
          
          <!-- Menu Items Selection -->
          <div class="mb-8">
            <div class="bg-white dark:bg-gray-900/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
              <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-6">Available Menu Items</label>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div 
                  v-for="item in menuItems"
                  :key="item.id"
                  class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:border-[#ec7813]/30 transition-all cursor-pointer group"
                  @click="addMenuItem(item)"
                >
                  <div class="flex items-center justify-between mb-2">
                    <h5 class="font-medium text-gray-900 dark:text-white group-hover:text-[#ec7813]">{{ item.name }}</h5>
                    <span class="material-symbols-outlined text-gray-400 group-hover:text-[#ec7813]">add</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ item.category }}</span>
                    <span class="font-bold text-gray-900 dark:text-white">₱{{ item.price.toFixed(2) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Selected Items -->
          <div v-if="orderItems.length > 0">
            <div class="bg-white dark:bg-gray-900/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between mb-4">
                <label class="block text-sm font-semibold text-gray-900 dark:text-white">Selected Items</label>
                <button 
                  type="button"
                  @click="clearCart"
                  class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 flex items-center gap-1"
                >
                  <span class="material-symbols-outlined text-sm">clear_all</span>
                  Clear All
                </button>
              </div>
              
              <div class="space-y-3">
                <div 
                  v-for="item in orderItems"
                  :key="item.id"
                  class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                >
                  <div class="flex-1">
                    <h6 class="font-medium text-gray-900 dark:text-white">{{ item.name }}</h6>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ item.category }} • ₱{{ item.price.toFixed(2) }} each</p>
                  </div>
                  
                  <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                      <button 
                        type="button"
                        @click="updateItemQuantity(item.id, item.quantity - 1)"
                        class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all flex items-center justify-center"
                      >
                        <span class="material-symbols-outlined text-sm">remove</span>
                      </button>
                      <span class="w-12 text-center font-medium text-gray-900 dark:text-white">{{ item.quantity }}</span>
                      <button 
                        type="button"
                        @click="updateItemQuantity(item.id, item.quantity + 1)"
                        class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all flex items-center justify-center"
                      >
                        <span class="material-symbols-outlined text-sm">add</span>
                      </button>
                    </div>
                    
                    <div class="text-right min-w-[80px]">
                      <p class="font-bold text-gray-900 dark:text-white">₱{{ (item.price * item.quantity).toFixed(2) }}</p>
                    </div>
                    
                    <button 
                      type="button"
                      @click="removeMenuItem(item.id)"
                      class="p-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                      title="Remove item"
                    >
                      <span class="material-symbols-outlined text-sm">delete</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Order Summary -->
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

          <!-- Empty State -->
          <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-900/20 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <span class="material-symbols-outlined text-gray-400 text-2xl">shopping_cart</span>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No items added</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Click on menu items above to add them to this order</p>
          </div>
        </CardWrapper>

        <!-- Payment & Additional Details -->
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
              <!-- Payment Status Preview -->
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
              placeholder="Add any special preparation instructions or customer requests..."
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

        <!-- Form Actions -->
        <CardWrapper rounded="xl" padding="lg" shadow="sm">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
            <!-- Left side - Order Summary -->
            <div class="text-left">
              <p class="text-sm text-gray-600 dark:text-gray-400">Order Summary:</p>
              <p class="text-lg font-bold text-gray-900 dark:text-white">
                {{ orderItems.length }} {{ orderItems.length === 1 ? 'item' : 'items' }} • ₱{{ grandTotal.toFixed(2) }}
              </p>
            </div>

            <!-- Right side - Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center gap-3">
              <button 
                type="button" 
                @click="goBack"
                class="w-full sm:w-auto px-8 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200 font-medium"
              >
                <span class="flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-lg">close</span>
                  Cancel
                </span>
              </button>
              <button 
                type="button" 
                @click="saveDraft"
                class="w-full sm:w-auto px-8 py-3 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200 font-medium"
              >
                <span class="flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-lg">draft</span>
                  Save as Draft
                </span>
              </button>
              <button 
                type="submit"
                class="w-full sm:w-auto px-8 py-3 rounded-xl bg-[#ec7813] text-white hover:bg-[#ea580c] hover:shadow-lg transition-all duration-200 font-semibold"
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
  </AdminLayout>
</template>

<style scoped>
/* Component-specific styles if needed */
</style>
