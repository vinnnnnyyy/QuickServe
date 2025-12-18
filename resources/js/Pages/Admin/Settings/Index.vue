<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// Settings data
const activeTab = ref('general');
const activeAiTab = ref('voice');
const showApiKey = ref(false);

// Settings state
const settings = ref({
  general: {
    language: 'en',
    timezone: 'America/New_York',
    currency: 'USD',
    enableAnimations: true,
    compactMode: false,
    showTooltips: true
  },
  cafe: {
    name: 'Café Delight',
    address: '123 Coffee Street\nDowntown District\nNew York, NY 10001',
    phone: '+1 (555) 123-4567',
    email: 'info@cafedelight.com',
    website: 'https://cafedelight.com',
    openTime: '06:00',
    closeTime: '22:00'
  },
  orders: {
    autoAccept: 'manual',
    orderTimeout: 30,
    maxItems: 20,
    requirePhone: false,
    allowModifications: true,
    enableScheduling: false
  },
  payments: {
    taxRate: 11,
    serviceFee: 0,
    deliveryFee: 3.50,
    acceptCash: true,
    acceptCard: true,
    acceptMobile: true,
    acceptQr: true,
    acceptCrypto: false
  },
  notifications: {
    newOrders: true,
    orderReady: true,
    paymentReceived: false,
    lowStock: true,
    staffBreaks: false,
    systemUpdates: true,
    sound: 'default'
  },
  system: {
    refreshInterval: 10,
    sessionTimeout: 8,
    maxUsers: 50,
    enableAnalytics: true,
    autoBackup: true,
    debugMode: false
  },
  ai: {
    provider: 'openai',
    apiKey: '',
    model: 'gpt-4o-mini',
    enableVoiceOrdering: true,
    voiceLanguage: 'en-US',
    maxTokens: 150,
    temperature: 0.7
  },
  aiInventory: {
    enableAutoRestock: false,
    lowStockThreshold: 10,
    restockQuantity: 50,
    enableAutoEmail: true,
    enableAutoSms: true,
    supplierEmailTemplate: 'professional',
    checkInterval: 'hourly',
    priorityItems: [],
    enableAiPrediction: false,
    predictionDays: 7
  }
});

// System status state
const systemStatus = ref({
  wifi_network: 'CafeOrder_WiFi',
  connected_devices: 0,
  active_sessions: 0,
  max_devices: 50,
  server_status: 'online',
  database_status: 'connected',
  network_info: {
    server_ip: '192.168.1.1',
    server_port: '8000',
    dhcp_range: '100-200',
    base_url: '192.168.1.1/table/',
  },
});
let statusRefreshInterval = null;

// Methods
const setActiveTab = (tab) => {
  activeTab.value = tab;
};

const saveSettings = () => {
  console.log('Saving settings...', settings.value);
  // Implementation for saving settings
  alert('Settings saved successfully!');
};

// Fetch system status
const fetchSystemStatus = async () => {
  try {
    const response = await axios.get('/api/system-status');
    systemStatus.value = response.data;
  } catch (error) {
    console.error('Failed to fetch system status:', error);
  }
};

// Lifecycle hooks
onMounted(() => {
  fetchSystemStatus();
  statusRefreshInterval = setInterval(fetchSystemStatus, 10000);
});

onUnmounted(() => {
  if (statusRefreshInterval) {
    clearInterval(statusRefreshInterval);
  }
});
</script>

<template>
  <AdminLayout
    title="System Settings"
    page-title="System Settings"
    page-subtitle="Configure your café management system preferences"
  >
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <button
          @click="saveSettings"
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all"
        >
          <span class="material-symbols-outlined">save</span>
          <span>Save Changes</span>
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
      <!-- Settings Navigation -->
      <div class="bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-lg p-6">
        <h3 class="text-lg font-bold text-black dark:text-white mb-4">Settings Categories</h3>
        <div class="space-y-2">
          <button
            @click="setActiveTab('general')"
            :class="activeTab === 'general' ? 'bg-primary/20 dark:bg-primary/30 text-primary border-primary/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="w-full text-left px-4 py-3 rounded-lg border transition-all"
          >
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg">tune</span>
              <span class="font-medium">General</span>
            </div>
          </button>
          <button
            @click="setActiveTab('cafe')"
            :class="activeTab === 'cafe' ? 'bg-primary/20 dark:bg-primary/30 text-primary border-primary/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="w-full text-left px-4 py-3 rounded-lg border transition-all"
          >
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg">storefront</span>
              <span class="font-medium">Café Info</span>
            </div>
          </button>
          <button
            @click="setActiveTab('orders')"
            :class="activeTab === 'orders' ? 'bg-primary/20 dark:bg-primary/30 text-primary border-primary/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="w-full text-left px-4 py-3 rounded-lg border transition-all"
          >
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg">receipt_long</span>
              <span class="font-medium">Orders</span>
            </div>
          </button>
          <button
            @click="setActiveTab('payments')"
            :class="activeTab === 'payments' ? 'bg-primary/20 dark:bg-primary/30 text-primary border-primary/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="w-full text-left px-4 py-3 rounded-lg border transition-all"
          >
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg">payments</span>
              <span class="font-medium">Payments</span>
            </div>
          </button>
          <button
            @click="setActiveTab('notifications')"
            :class="activeTab === 'notifications' ? 'bg-primary/20 dark:bg-primary/30 text-primary border-primary/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="w-full text-left px-4 py-3 rounded-lg border transition-all"
          >
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg">notifications</span>
              <span class="font-medium">Notifications</span>
            </div>
          </button>
          <button
            @click="setActiveTab('system')"
            :class="activeTab === 'system' ? 'bg-primary/20 dark:bg-primary/30 text-primary border-primary/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="w-full text-left px-4 py-3 rounded-lg border transition-all"
          >
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg">computer</span>
              <span class="font-medium">System</span>
            </div>
          </button>
          <button
            @click="setActiveTab('security')"
            :class="activeTab === 'security' ? 'bg-primary/20 dark:bg-primary/30 text-primary border-primary/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="w-full text-left px-4 py-3 rounded-lg border transition-all"
          >
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg">security</span>
              <span class="font-medium">Security</span>
            </div>
          </button>
          <button
            @click="setActiveTab('ai')"
            :class="activeTab === 'ai' ? 'bg-primary/20 dark:bg-primary/30 text-primary border-primary/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="w-full text-left px-4 py-3 rounded-lg border transition-all"
          >
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg">smart_toy</span>
              <span class="font-medium">AI Integration</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Settings Content -->
      <div class="xl:col-span-3">
        <!-- General Settings Tab -->
        <div v-if="activeTab === 'general'" class="bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-xl p-8 shadow-sm hover:shadow-md transition-all duration-300 mb-6">
          <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-8">
            <h3 class="text-xl font-bold text-black dark:text-white flex items-center gap-3">
              <div class="p-2 rounded-lg bg-primary/10 dark:bg-primary/20">
                <span class="material-symbols-outlined text-primary text-xl">tune</span>
              </div>
              General Settings
            </h3>
            <p class="text-sm text-black/60 dark:text-white/60 mt-2">Basic system preferences and display options</p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Language</label>
                <select
                  v-model="settings.general.language"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
                  <option value="en">English</option>
                  <option value="es">Español</option>
                  <option value="fr">Français</option>
                  <option value="de">Deutsch</option>
                  <option value="it">Italiano</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Timezone</label>
                <select
                  v-model="settings.general.timezone"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
                  <option value="America/New_York">Eastern Time (ET)</option>
                  <option value="America/Chicago">Central Time (CT)</option>
                  <option value="America/Denver">Mountain Time (MT)</option>
                  <option value="America/Los_Angeles">Pacific Time (PT)</option>
                  <option value="Europe/London">Greenwich Mean Time (GMT)</option>
                  <option value="Europe/Paris">Central European Time (CET)</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Currency</label>
                <select
                  v-model="settings.general.currency"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
                  <option value="USD">US Dollar ($)</option>
                  <option value="EUR">Euro (€)</option>
                  <option value="GBP">British Pound (£)</option>
                  <option value="CAD">Canadian Dollar (C$)</option>
                  <option value="AUD">Australian Dollar (A$)</option>
                </select>
              </div>
            </div>

            <div class="space-y-6">
              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-6">Display Preferences</label>
                <div class="space-y-4">
                  <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                    <input
                      v-model="settings.general.enableAnimations"
                      type="checkbox"
                      class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                    >
                    <div>
                      <span class="text-sm font-medium text-black dark:text-white block">Enable Animations</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">Smooth transitions and effects</span>
                    </div>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                    <input
                      v-model="settings.general.compactMode"
                      type="checkbox"
                      class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                    >
                    <div>
                      <span class="text-sm font-medium text-black dark:text-white block">Compact Mode</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">Reduce spacing and padding</span>
                    </div>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                    <input
                      v-model="settings.general.showTooltips"
                      type="checkbox"
                      class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                    >
                    <div>
                      <span class="text-sm font-medium text-black dark:text-white block">Show Tooltips</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">Helpful hints on hover</span>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Café Info Settings Tab -->
        <div v-if="activeTab === 'cafe'" class="bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-xl p-8 shadow-sm hover:shadow-md transition-all duration-300 mb-6">
          <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-8">
            <h3 class="text-xl font-bold text-black dark:text-white flex items-center gap-3">
              <div class="p-2 rounded-lg bg-primary/10 dark:bg-primary/20">
                <span class="material-symbols-outlined text-primary text-xl">storefront</span>
              </div>
              Café Information
            </h3>
            <p class="text-sm text-black/60 dark:text-white/60 mt-2">Business details and contact information</p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Café Name</label>
                <input
                  v-model="settings.cafe.name"
                  type="text"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
              </div>

              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Address</label>
                <textarea
                  v-model="settings.cafe.address"
                  rows="3"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base resize-none"
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Phone Number</label>
                <input
                  v-model="settings.cafe.phone"
                  type="tel"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
              </div>
            </div>

            <div class="space-y-6">
              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Email</label>
                <input
                  v-model="settings.cafe.email"
                  type="email"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
              </div>

              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Website</label>
                <input
                  v-model="settings.cafe.website"
                  type="url"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
              </div>

              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Operating Hours</label>
                <div class="grid grid-cols-2 gap-3">
                  <input
                    v-model="settings.cafe.openTime"
                    type="time"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                  >
                  <input
                    v-model="settings.cafe.closeTime"
                    type="time"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                  >
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders Settings Tab -->
        <div v-if="activeTab === 'orders'" class="bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-xl p-8 shadow-sm hover:shadow-md transition-all duration-300 mb-6">
          <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-8">
            <h3 class="text-xl font-bold text-black dark:text-white flex items-center gap-3">
              <div class="p-2 rounded-lg bg-primary/10 dark:bg-primary/20">
                <span class="material-symbols-outlined text-primary text-xl">receipt_long</span>
              </div>
              Order Management
            </h3>
            <p class="text-sm text-black/60 dark:text-white/60 mt-2">Configure order processing and timing settings</p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Auto-Accept Orders</label>
                <select
                  v-model="settings.orders.autoAccept"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
                  <option value="manual">Manual Review</option>
                  <option value="auto">Auto Accept</option>
                  <option value="auto_paid">Auto Accept (Paid Only)</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Order Timeout (minutes)</label>
                <input
                  v-model="settings.orders.orderTimeout"
                  type="number"
                  min="5"
                  max="120"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
              </div>

              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Max Items per Order</label>
                <input
                  v-model="settings.orders.maxItems"
                  type="number"
                  min="1"
                  max="100"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
              </div>
            </div>

            <div class="space-y-6">
              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-6">Order Processing</label>
                <div class="space-y-4">
                  <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                    <input
                      v-model="settings.orders.requirePhone"
                      type="checkbox"
                      class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                    >
                    <div>
                      <span class="text-sm font-medium text-black dark:text-white block">Require Phone Number</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">Mandatory for all orders</span>
                    </div>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                    <input
                      v-model="settings.orders.allowModifications"
                      type="checkbox"
                      class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                    >
                    <div>
                      <span class="text-sm font-medium text-black dark:text-white block">Allow Modifications</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">Let customers customize items</span>
                    </div>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                    <input
                      v-model="settings.orders.enableScheduling"
                      type="checkbox"
                      class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                    >
                    <div>
                      <span class="text-sm font-medium text-black dark:text-white block">Enable Order Scheduling</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">Allow future order placement</span>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- System Settings Tab -->
        <div v-if="activeTab === 'system'" class="bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-xl p-8 shadow-sm hover:shadow-md transition-all duration-300 mb-6">
          <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-8">
            <h3 class="text-xl font-bold text-black dark:text-white flex items-center gap-3">
              <div class="p-2 rounded-lg bg-primary/10 dark:bg-primary/20">
                <span class="material-symbols-outlined text-primary text-xl">computer</span>
              </div>
              System Configuration
            </h3>
            <p class="text-sm text-black/60 dark:text-white/60 mt-2">System performance and technical settings</p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Data Refresh Interval</label>
                <select
                  v-model="settings.system.refreshInterval"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
                  <option value="5">5 seconds</option>
                  <option value="10">10 seconds</option>
                  <option value="30">30 seconds</option>
                  <option value="60">1 minute</option>
                  <option value="300">5 minutes</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Session Timeout (hours)</label>
                <input
                  v-model="settings.system.sessionTimeout"
                  type="number"
                  min="1"
                  max="24"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
              </div>

              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-3">Max Concurrent Users</label>
                <input
                  v-model="settings.system.maxUsers"
                  type="number"
                  min="10"
                  max="200"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                >
              </div>
            </div>

            <div class="space-y-6">
              <div>
                <label class="block text-sm font-semibold text-black dark:text-white mb-6">System Features</label>
                <div class="space-y-4">
                  <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                    <input
                      v-model="settings.system.enableAnalytics"
                      type="checkbox"
                      class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                    >
                    <div>
                      <span class="text-sm font-medium text-black dark:text-white block">Analytics Tracking</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">Collect usage and performance data</span>
                    </div>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                    <input
                      v-model="settings.system.autoBackup"
                      type="checkbox"
                      class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                    >
                    <div>
                      <span class="text-sm font-medium text-black dark:text-white block">Auto Backup</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">Automatic daily data backups</span>
                    </div>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                    <input
                      v-model="settings.system.debugMode"
                      type="checkbox"
                      class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                    >
                    <div>
                      <span class="text-sm font-medium text-black dark:text-white block">Debug Mode</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">Enable detailed logging</span>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- AI Integration Settings Tab -->
        <div v-if="activeTab === 'ai'" class="space-y-6 mb-6">
          <!-- Header Card -->
          <div class="bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-xl p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-primary/10 dark:bg-primary/20">
                  <span class="material-symbols-outlined text-primary text-xl">smart_toy</span>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-black dark:text-white">AI Integration</h3>
                  <p class="text-sm text-black/60 dark:text-white/60">Configure AI-powered features for voice ordering and inventory management</p>
                </div>
              </div>
              <!-- Sub-tabs -->
              <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                <button
                  @click="activeAiTab = 'voice'"
                  :class="activeAiTab === 'voice' ? 'bg-white dark:bg-gray-700 text-primary shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'"
                  class="flex items-center gap-2 px-4 py-2 rounded-md font-medium transition-all"
                >
                  <span class="material-symbols-outlined text-lg">mic</span>
                  <span>Voice AI</span>
                </button>
                <button
                  @click="activeAiTab = 'inventory'"
                  :class="activeAiTab === 'inventory' ? 'bg-white dark:bg-gray-700 text-primary shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'"
                  class="flex items-center gap-2 px-4 py-2 rounded-md font-medium transition-all"
                >
                  <span class="material-symbols-outlined text-lg">inventory_2</span>
                  <span>Inventory AI</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Voice AI Content -->
          <div v-if="activeAiTab === 'voice'" class="bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-xl p-8 shadow-sm hover:shadow-md transition-all duration-300">
            <!-- Voice Ordering Status Banner -->
            <div class="mb-8 p-4 rounded-xl border" :class="settings.ai.apiKey ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800'">
              <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg" :class="settings.ai.apiKey ? 'bg-green-100 dark:bg-green-900/40' : 'bg-yellow-100 dark:bg-yellow-900/40'">
                  <span class="material-symbols-outlined" :class="settings.ai.apiKey ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'">
                    {{ settings.ai.apiKey ? 'check_circle' : 'warning' }}
                  </span>
                </div>
                <div>
                  <p class="font-medium" :class="settings.ai.apiKey ? 'text-green-800 dark:text-green-200' : 'text-yellow-800 dark:text-yellow-200'">
                    {{ settings.ai.apiKey ? 'Voice Ordering is Active' : 'API Key Required' }}
                  </p>
                  <p class="text-sm" :class="settings.ai.apiKey ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'">
                    {{ settings.ai.apiKey ? 'Customers can use voice commands to place orders' : 'Add your API key to enable AI-powered voice ordering' }}
                  </p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div class="space-y-6">
                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">AI Provider</label>
                  <select
                    v-model="settings.ai.provider"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                  >
                    <option value="openai">OpenAI</option>
                    <option value="anthropic">Anthropic (Claude)</option>
                    <option value="google">Google AI (Gemini)</option>
                    <option value="groq">Groq</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">API Key</label>
                  <div class="relative">
                    <input
                      v-model="settings.ai.apiKey"
                      :type="showApiKey ? 'text' : 'password'"
                      placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxx"
                      class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base font-mono"
                    >
                    <button
                      type="button"
                      @click="showApiKey = !showApiKey"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                    >
                      <span class="material-symbols-outlined text-xl">{{ showApiKey ? 'visibility_off' : 'visibility' }}</span>
                    </button>
                  </div>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Your API key is stored securely and never shared. Get your key from your AI provider's dashboard.
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">AI Model</label>
                  <select
                    v-model="settings.ai.model"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                  >
                    <template v-if="settings.ai.provider === 'openai'">
                      <option value="gpt-4o">GPT-4o (Recommended)</option>
                      <option value="gpt-4o-mini">GPT-4o Mini (Faster)</option>
                      <option value="gpt-4-turbo">GPT-4 Turbo</option>
                      <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Budget)</option>
                    </template>
                    <template v-else-if="settings.ai.provider === 'anthropic'">
                      <option value="claude-3-5-sonnet">Claude 3.5 Sonnet (Recommended)</option>
                      <option value="claude-3-opus">Claude 3 Opus</option>
                      <option value="claude-3-haiku">Claude 3 Haiku (Faster)</option>
                    </template>
                    <template v-else-if="settings.ai.provider === 'google'">
                      <option value="gemini-1.5-pro">Gemini 1.5 Pro (Recommended)</option>
                      <option value="gemini-1.5-flash">Gemini 1.5 Flash (Faster)</option>
                    </template>
                    <template v-else-if="settings.ai.provider === 'groq'">
                      <option value="llama-3.1-70b">Llama 3.1 70B (Recommended)</option>
                      <option value="llama-3.1-8b">Llama 3.1 8B (Faster)</option>
                      <option value="mixtral-8x7b">Mixtral 8x7B</option>
                    </template>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">Voice Language</label>
                  <select
                    v-model="settings.ai.voiceLanguage"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                  >
                    <option value="en-US">English (US)</option>
                    <option value="en-GB">English (UK)</option>
                    <option value="es-ES">Spanish (Spain)</option>
                    <option value="es-MX">Spanish (Mexico)</option>
                    <option value="fr-FR">French</option>
                    <option value="de-DE">German</option>
                    <option value="it-IT">Italian</option>
                    <option value="pt-BR">Portuguese (Brazil)</option>
                    <option value="zh-CN">Chinese (Simplified)</option>
                    <option value="ja-JP">Japanese</option>
                    <option value="ko-KR">Korean</option>
                    <option value="fil-PH">Filipino</option>
                  </select>
                </div>
              </div>

              <div class="space-y-6">
                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-6">Voice Ordering Features</label>
                  <div class="space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                      <input
                        v-model="settings.ai.enableVoiceOrdering"
                        type="checkbox"
                        class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                      >
                      <div>
                        <span class="text-sm font-medium text-black dark:text-white block">Enable Voice Ordering</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Allow customers to order using voice commands</span>
                      </div>
                    </label>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">
                    Response Length (Max Tokens): {{ settings.ai.maxTokens }}
                  </label>
                  <input
                    v-model="settings.ai.maxTokens"
                    type="range"
                    min="50"
                    max="500"
                    step="10"
                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-primary"
                  >
                  <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span>Concise</span>
                    <span>Detailed</span>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">
                    AI Creativity (Temperature): {{ settings.ai.temperature }}
                  </label>
                  <input
                    v-model="settings.ai.temperature"
                    type="range"
                    min="0"
                    max="1"
                    step="0.1"
                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-primary"
                  >
                  <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span>Precise</span>
                    <span>Creative</span>
                  </div>
                </div>

                <div class="pt-4">
                  <button
                    :disabled="!settings.ai.apiKey"
                    :class="settings.ai.apiKey ? 'bg-primary hover:bg-primary/90 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'"
                    class="w-full px-4 py-3 rounded-xl font-medium transition-all flex items-center justify-center gap-2"
                  >
                    <span class="material-symbols-outlined text-lg">science</span>
                    Test Connection
                  </button>
                </div>
              </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
              <h4 class="text-sm font-semibold text-black dark:text-white mb-4">How Voice Ordering Works</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                  <div class="w-10 h-10 bg-primary/10 dark:bg-primary/20 rounded-lg flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-primary">mic</span>
                  </div>
                  <h5 class="font-medium text-black dark:text-white mb-1">1. Speak</h5>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Customer taps the voice button and speaks their order naturally</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                  <div class="w-10 h-10 bg-primary/10 dark:bg-primary/20 rounded-lg flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-primary">psychology</span>
                  </div>
                  <h5 class="font-medium text-black dark:text-white mb-1">2. Process</h5>
                  <p class="text-xs text-gray-500 dark:text-gray-400">AI understands the request and matches it with menu items</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                  <div class="w-10 h-10 bg-primary/10 dark:bg-primary/20 rounded-lg flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-primary">shopping_cart</span>
                  </div>
                  <h5 class="font-medium text-black dark:text-white mb-1">3. Add to Order</h5>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Items are automatically added to the order for review</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Inventory AI Content -->
          <div v-if="activeAiTab === 'inventory'" class="bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-xl p-8 shadow-sm hover:shadow-md transition-all duration-300">
            <!-- AI Inventory Status Banner -->
            <div class="mb-8 p-4 rounded-xl border" :class="settings.aiInventory.enableAutoRestock ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700'">
              <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg" :class="settings.aiInventory.enableAutoRestock ? 'bg-green-100 dark:bg-green-900/40' : 'bg-gray-100 dark:bg-gray-800'">
                  <span class="material-symbols-outlined" :class="settings.aiInventory.enableAutoRestock ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                    {{ settings.aiInventory.enableAutoRestock ? 'auto_mode' : 'pause_circle' }}
                  </span>
                </div>
                <div>
                  <p class="font-medium" :class="settings.aiInventory.enableAutoRestock ? 'text-green-800 dark:text-green-200' : 'text-gray-700 dark:text-gray-300'">
                    {{ settings.aiInventory.enableAutoRestock ? 'AI Auto-Restock is Active' : 'AI Auto-Restock is Disabled' }}
                  </p>
                  <p class="text-sm" :class="settings.aiInventory.enableAutoRestock ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                    {{ settings.aiInventory.enableAutoRestock ? 'System will automatically monitor stock and contact suppliers when needed' : 'Enable to let AI manage your inventory automatically' }}
                  </p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div class="space-y-6">
                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-6">Auto-Restock Settings</label>
                  <div class="space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                      <input
                        v-model="settings.aiInventory.enableAutoRestock"
                        type="checkbox"
                        class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                      >
                      <div>
                        <span class="text-sm font-medium text-black dark:text-white block">Enable AI Auto-Restock</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Automatically restock items when stock falls below threshold</span>
                      </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                      <input
                        v-model="settings.aiInventory.enableAiPrediction"
                        type="checkbox"
                        class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                      >
                      <div>
                        <span class="text-sm font-medium text-black dark:text-white block">Predictive Restocking</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">AI predicts future demand and restocks proactively</span>
                      </div>
                    </label>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">Low Stock Threshold</label>
                  <div class="flex items-center gap-4">
                    <input
                      v-model="settings.aiInventory.lowStockThreshold"
                      type="number"
                      min="1"
                      max="100"
                      class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                    >
                    <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">units</span>
                  </div>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Trigger restock when item quantity falls below this number</p>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">Default Restock Quantity</label>
                  <div class="flex items-center gap-4">
                    <input
                      v-model="settings.aiInventory.restockQuantity"
                      type="number"
                      min="1"
                      max="1000"
                      class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                    >
                    <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">units</span>
                  </div>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Default quantity to order when restocking</p>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">Stock Check Interval</label>
                  <select
                    v-model="settings.aiInventory.checkInterval"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                  >
                    <option value="realtime">Real-time</option>
                    <option value="hourly">Every Hour</option>
                    <option value="daily">Once Daily</option>
                    <option value="weekly">Once Weekly</option>
                  </select>
                </div>

                <div v-if="settings.aiInventory.enableAiPrediction">
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">Prediction Lookahead</label>
                  <select
                    v-model="settings.aiInventory.predictionDays"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                  >
                    <option :value="3">3 days ahead</option>
                    <option :value="7">7 days ahead</option>
                    <option :value="14">14 days ahead</option>
                    <option :value="30">30 days ahead</option>
                  </select>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">AI will predict stock needs for this time period</p>
                </div>
              </div>

              <div class="space-y-6">
                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-6">Supplier Communication</label>
                  <div class="space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                      <input
                        v-model="settings.aiInventory.enableAutoEmail"
                        type="checkbox"
                        class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                      >
                      <div>
                        <span class="text-sm font-medium text-black dark:text-white block">Auto Email Suppliers</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">AI composes and sends restock emails automatically</span>
                      </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                      <input
                        v-model="settings.aiInventory.enableAutoSms"
                        type="checkbox"
                        class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                      >
                      <div>
                        <span class="text-sm font-medium text-black dark:text-white block">Auto SMS Suppliers</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">AI sends SMS alerts to suppliers for urgent restocks</span>
                      </div>
                    </label>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-semibold text-black dark:text-white mb-3">Email Template Style</label>
                  <select
                    v-model="settings.aiInventory.supplierEmailTemplate"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-base"
                  >
                    <option value="professional">Professional</option>
                    <option value="friendly">Friendly</option>
                    <option value="urgent">Urgent</option>
                    <option value="concise">Concise</option>
                  </select>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">AI will use this tone when composing supplier emails</p>
                </div>

                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
                  <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-gray-500 dark:text-gray-400 text-lg">mail</span>
                    <span class="text-sm font-medium text-black dark:text-white">Sample AI Email Preview</span>
                  </div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 space-y-2">
                    <p><strong>Subject:</strong> Restock Request - [Item Name]</p>
                    <p class="border-t border-gray-200 dark:border-gray-700 pt-2">
                      <template v-if="settings.aiInventory.supplierEmailTemplate === 'professional'">
                        Dear Supplier, We would like to place an order for [quantity] units of [item]. Please confirm availability and expected delivery date.
                      </template>
                      <template v-else-if="settings.aiInventory.supplierEmailTemplate === 'friendly'">
                        Hi! We're running low on [item] and would love to order [quantity] more. Let us know when you can deliver!
                      </template>
                      <template v-else-if="settings.aiInventory.supplierEmailTemplate === 'urgent'">
                        URGENT: We need [quantity] units of [item] immediately. Stock level is critical. Please expedite this order.
                      </template>
                      <template v-else>
                        Order: [quantity] x [item]. Please confirm.
                      </template>
                    </p>
                  </div>
                </div>

                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
                  <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-gray-500 dark:text-gray-400 text-lg">sms</span>
                    <span class="text-sm font-medium text-black dark:text-white">Sample AI SMS Preview</span>
                  </div>
                  <div class="text-xs text-gray-600 dark:text-gray-400">
                    <p class="bg-primary/10 dark:bg-primary/20 p-3 rounded-lg">
                      [Cafe Name]: Low stock alert - [Item]. Need [qty] units. Reply YES to confirm order or call us.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
              <h4 class="text-sm font-semibold text-black dark:text-white mb-4">How AI Inventory Works</h4>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                  <div class="w-10 h-10 bg-primary/10 dark:bg-primary/20 rounded-lg flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-primary">monitoring</span>
                  </div>
                  <h5 class="font-medium text-black dark:text-white mb-1">1. Monitor</h5>
                  <p class="text-xs text-gray-500 dark:text-gray-400">AI continuously tracks stock levels and sales patterns</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                  <div class="w-10 h-10 bg-primary/10 dark:bg-primary/20 rounded-lg flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-primary">warning</span>
                  </div>
                  <h5 class="font-medium text-black dark:text-white mb-1">2. Detect</h5>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Identifies low stock items before they run out</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                  <div class="w-10 h-10 bg-primary/10 dark:bg-primary/20 rounded-lg flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-primary">edit_note</span>
                  </div>
                  <h5 class="font-medium text-black dark:text-white mb-1">3. Compose</h5>
                  <p class="text-xs text-gray-500 dark:text-gray-400">AI drafts personalized messages to suppliers</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                  <div class="w-10 h-10 bg-primary/10 dark:bg-primary/20 rounded-lg flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-primary">send</span>
                  </div>
                  <h5 class="font-medium text-black dark:text-white mb-1">4. Send</h5>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Automatically emails and texts suppliers for restock</p>
                </div>
              </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
              <div class="flex flex-wrap gap-3">
                <button
                  :disabled="!settings.aiInventory.enableAutoRestock"
                  :class="settings.aiInventory.enableAutoRestock ? 'bg-primary hover:bg-primary/90 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'"
                  class="px-4 py-2 rounded-xl font-medium transition-all flex items-center gap-2"
                >
                  <span class="material-symbols-outlined text-lg">play_arrow</span>
                  Run Stock Check Now
                </button>
                <button
                  :disabled="!settings.aiInventory.enableAutoEmail"
                  :class="settings.aiInventory.enableAutoEmail ? 'border-primary text-primary hover:bg-primary/10' : 'border-gray-300 dark:border-gray-600 text-gray-400 cursor-not-allowed'"
                  class="px-4 py-2 rounded-xl font-medium transition-all flex items-center gap-2 border"
                >
                  <span class="material-symbols-outlined text-lg">forward_to_inbox</span>
                  Send Test Email
                </button>
                <button
                  :disabled="!settings.aiInventory.enableAutoSms"
                  :class="settings.aiInventory.enableAutoSms ? 'border-primary text-primary hover:bg-primary/10' : 'border-gray-300 dark:border-gray-600 text-gray-400 cursor-not-allowed'"
                  class="px-4 py-2 rounded-xl font-medium transition-all flex items-center gap-2 border"
                >
                  <span class="material-symbols-outlined text-lg">perm_phone_msg</span>
                  Send Test SMS
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- System Status -->
        <div class="bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-xl p-8 shadow-sm">
          <h3 class="text-lg font-bold text-black dark:text-white mb-6">System Status</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div 
                class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center"
                :class="systemStatus.database_status === 'connected' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20'"
              >
                <span 
                  class="material-symbols-outlined"
                  :class="systemStatus.database_status === 'connected' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                >
                  {{ systemStatus.database_status === 'connected' ? 'check_circle' : 'error' }}
                </span>
              </div>
              <p class="text-sm font-medium text-black dark:text-white">Database</p>
              <p 
                class="text-xs capitalize"
                :class="systemStatus.database_status === 'connected' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
              >
                {{ systemStatus.database_status }}
              </p>
            </div>

            <div class="text-center p-4 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div 
                class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center"
                :class="systemStatus.server_status === 'online' ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20'"
              >
                <span 
                  class="material-symbols-outlined"
                  :class="systemStatus.server_status === 'online' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                >
                  wifi
                </span>
              </div>
              <p class="text-sm font-medium text-black dark:text-white">Network</p>
              <p 
                class="text-xs capitalize"
                :class="systemStatus.server_status === 'online' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
              >
                {{ systemStatus.server_status }}
              </p>
            </div>

            <div class="text-center p-4 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">devices</span>
              </div>
              <p class="text-sm font-medium text-black dark:text-white">Connected Devices</p>
              <p class="text-xs text-blue-600 dark:text-blue-400">{{ systemStatus.connected_devices }}/{{ systemStatus.max_devices }}</p>
            </div>

            <div class="text-center p-4 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">query_stats</span>
              </div>
              <p class="text-sm font-medium text-black dark:text-white">Active Sessions</p>
              <p class="text-xs text-purple-600 dark:text-purple-400">{{ systemStatus.active_sessions }}</p>
            </div>
          </div>

          <!-- Network Details -->
          <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-medium text-black dark:text-white mb-4">Network Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-black/60 dark:text-white/60">WiFi Network</span>
                    <span class="text-xs font-medium text-black dark:text-white">{{ systemStatus.wifi_network }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-black/60 dark:text-white/60">Server IP</span>
                    <span class="text-xs font-medium text-black dark:text-white">{{ systemStatus.network_info.server_ip }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-black/60 dark:text-white/60">Port</span>
                    <span class="text-xs font-medium text-black dark:text-white">{{ systemStatus.network_info.server_port }}</span>
                  </div>
                </div>
              </div>
              
              <div class="bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-black/60 dark:text-white/60">DHCP Range</span>
                    <span class="text-xs font-medium text-black dark:text-white">{{ systemStatus.network_info.dhcp_range }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-black/60 dark:text-white/60">Base URL</span>
                    <span class="text-xs font-medium text-black dark:text-white truncate ml-2">{{ systemStatus.network_info.base_url }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
