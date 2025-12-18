<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import LineChart from '@/Components/Admin/Charts/LineChart.vue';
import { defineProps, computed } from 'vue';

const props = defineProps({
  stats: Object,
  salesChart: Array,
  topProducts: Array,
  recentOrders: Array,
});

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(amount);
};
</script>

<template>
  <AdminLayout 
    title="Admin Dashboard"
    page-title="Dashboard"
    page-subtitle="Welcome back, Admin"
  >
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
      <CardWrapper>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Today's Revenue</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ formatCurrency(stats.revenue.value) }}</p>
        <p class="text-sm font-medium mt-1" :class="stats.revenue.growth >= 0 ? 'text-green-500' : 'text-red-500'">
            {{ stats.revenue.growth >= 0 ? '+' : '' }}{{ stats.revenue.growth }}%
        </p>
      </CardWrapper>
      
      <CardWrapper>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Orders Today</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ stats.orders.value }}</p>
        <p class="text-sm font-medium mt-1" :class="stats.orders.growth >= 0 ? 'text-green-500' : 'text-red-500'">
            {{ stats.orders.growth >= 0 ? '+' : '' }}{{ stats.orders.growth }}%
        </p>
      </CardWrapper>
      
      <CardWrapper>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Active Devices</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ stats.devices.value }}</p>
        <p class="text-sm font-medium mt-1" :class="stats.devices.growth >= 0 ? 'text-green-500' : 'text-red-500'">
            {{ stats.devices.growth >= 0 ? '+' : '' }}{{ stats.devices.growth }}%
        </p>
      </CardWrapper>
      
      <CardWrapper>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Avg Rating</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ stats.rating.value }}</p>
        <p class="text-sm font-medium mt-1" :class="stats.rating.growth >= 0 ? 'text-green-500' : 'text-red-500'">
            {{ stats.rating.growth >= 0 ? '+' : '' }}{{ stats.rating.growth }}
        </p>
      </CardWrapper>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
      <!-- Sales Chart -->
      <CardWrapper class="xl:col-span-2">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Sales Overview</h3>
            <p class="text-gray-700 dark:text-gray-400 text-sm">Last 30 days performance</p>
          </div>
          <div class="flex items-center gap-2">
            <!-- <button class="px-3 py-1 rounded-lg bg-[#ec7813] text-white text-sm font-medium">7 Days</button> -->
            <button class="px-3 py-1 rounded-lg text-gray-700 dark:text-gray-300 bg-[#ec7813]/10 dark:bg-[#ec7813]/20 text-sm font-medium">30 Days</button>
          </div>
        </div>
        <div class="chart-container">
          <div class="w-full h-full bg-white dark:bg-gray-800 rounded-lg flex items-center justify-center border border-gray-200 dark:border-gray-700 p-4">
             <div v-if="salesChart && salesChart.length > 0" class="w-full h-full">
                <LineChart :data="salesChart" />
             </div>
             <div v-else class="text-gray-500 dark:text-gray-400">No data available</div>
          </div>
        </div>
      </CardWrapper>

      <!-- Top Products -->
      <CardWrapper>
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Top Products</h3>
            <p class="text-gray-700 dark:text-gray-400 text-sm">Best sellers today</p>
          </div>
        </div>
        <div class="space-y-4">
          <div v-for="(product, index) in topProducts" :key="product.name" class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold"
                 :class="[
                    index === 0 ? 'bg-gradient-to-br from-[#ec7813] to-orange-600' : 
                    index === 1 ? 'bg-gradient-to-br from-blue-400 to-blue-600' : 
                    index === 2 ? 'bg-gradient-to-br from-purple-400 to-purple-600' : 
                    'bg-gradient-to-br from-green-400 to-green-600'
                 ]">
                {{ index + 1 }}
            </div>
            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">{{ product.name }}</p>
              <p class="text-gray-700 dark:text-gray-400 text-sm">{{ product.orders }} orders • {{ formatCurrency(product.revenue) }}</p>
            </div>
          </div>
          <div v-if="topProducts.length === 0" class="text-center text-gray-500 py-4">
            No orders today
          </div>
        </div>
      </CardWrapper>
    </div>

    <!-- Recent Orders -->
    <CardWrapper>
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Orders</h3>
          <p class="text-gray-700 dark:text-gray-400 text-sm">Latest customer orders</p>
        </div>
        <button class="flex items-center gap-2 px-4 py-2 rounded-xl text-[#ec7813] hover:bg-[#ec7813]/10 dark:hover:bg-[#ec7813]/20 transition-all">
          <span class="text-sm font-medium">View All</span>
          <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </button>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="text-left py-3 px-4 text-gray-700 dark:text-gray-400 font-medium text-sm">Order ID</th>
              <th class="text-left py-3 px-4 text-gray-700 dark:text-gray-400 font-medium text-sm">Device</th>
              <th class="text-left py-3 px-4 text-gray-700 dark:text-gray-400 font-medium text-sm">Items</th>
              <th class="text-left py-3 px-4 text-gray-700 dark:text-gray-400 font-medium text-sm">Total</th>
              <th class="text-left py-3 px-4 text-gray-700 dark:text-gray-400 font-medium text-sm">Status</th>
              <th class="text-left py-3 px-4 text-gray-700 dark:text-gray-400 font-medium text-sm">Time</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in recentOrders" :key="order.id" class="border-b border-gray-100 dark:border-gray-800 hover:bg-white/50 dark:hover:bg-gray-900/20">
              <td class="py-4 px-4 text-gray-900 dark:text-white font-medium">{{ order.order_number }}</td>
              <td class="py-4 px-4 text-gray-900 dark:text-white">{{ order.device_info }}</td>
              <td class="py-4 px-4 text-gray-700 dark:text-gray-400">{{ order.items_count }} items</td>
              <td class="py-4 px-4 text-gray-900 dark:text-white font-medium">{{ formatCurrency(order.total) }}</td>
              <td class="py-4 px-4">
                <span class="px-3 py-1 rounded-full text-sm font-medium capitalize"
                    :class="{
                        'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400': order.status === 'completed' || order.status === 'served',
                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400': order.status === 'preparing' || order.status === 'queued',
                        'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400': order.status === 'received' || order.status === 'new',
                        'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400': order.status === 'cancelled'
                    }">
                    {{ order.status }}
                </span>
              </td>
              <td class="py-4 px-4 text-gray-700 dark:text-gray-400">{{ order.created_at_human }}</td>
            </tr>
            <tr v-if="recentOrders.length === 0">
                <td colspan="6" class="text-center py-4 text-gray-500">No recent orders</td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardWrapper>
  </AdminLayout>
</template>
