<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

// Settings data
const activeTab = ref('general');

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
  }
});

// Methods
const setActiveTab = (tab) => {
  activeTab.value = tab;
};

const saveSettings = () => {
  console.log('Saving settings...', settings.value);
  // Implementation for saving settings
  alert('Settings saved successfully!');
};
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

        <!-- System Status -->
        <div class="bg-background-light dark:bg-background-dark border border-primary/20 dark:border-primary/30 rounded-xl p-8 shadow-sm">
          <h3 class="text-lg font-bold text-black dark:text-white mb-6">System Status</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
              </div>
              <p class="text-sm font-medium text-black dark:text-white">Database</p>
              <p class="text-xs text-green-600 dark:text-green-400">Connected</p>
            </div>

            <div class="text-center p-4 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-green-600 dark:text-green-400">wifi</span>
              </div>
              <p class="text-sm font-medium text-black dark:text-white">Network</p>
              <p class="text-xs text-green-600 dark:text-green-400">Online</p>
            </div>

            <div class="text-center p-4 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">storage</span>
              </div>
              <p class="text-sm font-medium text-black dark:text-white">Storage</p>
              <p class="text-xs text-blue-600 dark:text-blue-400">78% Used</p>
            </div>

            <div class="text-center p-4 bg-white dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-yellow-100 dark:bg-yellow-900/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400">update</span>
              </div>
              <p class="text-sm font-medium text-black dark:text-white">Updates</p>
              <p class="text-xs text-yellow-600 dark:text-yellow-400">1 Available</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
