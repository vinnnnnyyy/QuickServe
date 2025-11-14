<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import { ref } from 'vue';

// Sample analytics data matching admin.html
const topProducts = ref([
  {
    id: 1,
    rank: 1,
    name: 'Iced Brown Sugar Oatmilk',
    category: 'Cold Drinks',
    orders: 458,
    revenue: 2497.10,
    growth: 24.5,
    positive: true,
    iconColor: 'primary'
  },
  {
    id: 2,
    rank: 2,
    name: 'Pistachio Cream Cold Brew',
    category: 'Cold Drinks',
    orders: 342,
    revenue: 1692.90,
    growth: 18.2,
    positive: true,
    iconColor: 'blue-600'
  },
  {
    id: 3,
    rank: 3,
    name: 'Caramel Macchiato',
    category: 'Hot Drinks',
    orders: 289,
    revenue: 1387.20,
    growth: -5.3,
    positive: false,
    iconColor: 'purple-600'
  }
]);

const peakHours = ref([
  { time: '8:00 AM - 10:00 AM', orders: 147, percentage: 85 },
  { time: '12:00 PM - 2:00 PM', orders: 132, percentage: 76 },
  { time: '3:00 PM - 5:00 PM', orders: 98, percentage: 56 },
  { time: '6:00 PM - 8:00 PM', orders: 76, percentage: 43 }
]);

const categoryData = ref([
  { category: 'Hot Drinks', percentage: 45, color: 'bg-primary' },
  { category: 'Cold Drinks', percentage: 30, color: 'bg-blue-500' },
  { category: 'Coffee', percentage: 15, color: 'bg-green-500' },
  { category: 'Desserts', percentage: 10, color: 'bg-purple-500' }
]);

const getRankGradient = (rank) => {
  const gradients = {
    1: 'bg-gradient-to-br from-primary/20 to-primary/10',
    2: 'bg-gradient-to-br from-blue-500/20 to-blue-400/10',
    3: 'bg-gradient-to-br from-purple-500/20 to-purple-400/10'
  };
  return gradients[rank] || 'bg-gradient-to-br from-gray-500/20 to-gray-400/10';
};

const getRankColor = (rank) => {
  const colors = {
    1: 'text-primary',
    2: 'text-blue-600',
    3: 'text-purple-600'
  };
  return colors[rank] || 'text-gray-600';
};
</script>

<template>
  <AdminLayout 
    title="Analytics & Reports"
    page-title="Analytics & Reports"
    page-subtitle="Detailed insights into your café performance"
  >
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <select class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
          <option>Last 7 days</option>
          <option>Last 30 days</option>
          <option>Last 3 months</option>
        </select>
        <button class="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all">
          <span class="material-symbols-outlined">refresh</span>
          <span>Refresh Data</span>
        </button>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-primary/20 to-primary/10">
            <span class="material-symbols-outlined text-primary text-2xl">payments</span>
          </div>
          <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400">+23.5%</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">₱48,574</p>
        <p class="text-sm text-black/60 dark:text-white/60">Total Revenue</p>
        <div class="mt-4 flex items-center text-xs text-black/60 dark:text-white/60">
          <span class="material-symbols-outlined text-sm mr-1">trending_up</span>
          <span>₱8,234 from last period</span>
        </div>
      </CardWrapper>
      
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-blue-500/20 to-blue-400/10">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">shopping_bag</span>
          </div>
          <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400">+15.3%</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">3,458</p>
        <p class="text-sm text-black/60 dark:text-white/60">Total Orders</p>
        <div class="mt-4 flex items-center text-xs text-black/60 dark:text-white/60">
          <span class="material-symbols-outlined text-sm mr-1">trending_up</span>
          <span>+458 from last period</span>
        </div>
      </CardWrapper>
      
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-purple-500/20 to-purple-400/10">
            <span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-2xl">local_cafe</span>
          </div>
          <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400">+15.2%</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">89</p>
        <p class="text-sm text-black/60 dark:text-white/60">Table Turnovers</p>
        <div class="mt-4 flex items-center text-xs text-black/60 dark:text-white/60">
          <span class="material-symbols-outlined text-sm mr-1">trending_up</span>
          <span>+12 from last period</span>
        </div>
      </CardWrapper>
      
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-green-500/20 to-green-400/10">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-2xl">avg_pace</span>
          </div>
          <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400">+5.2%</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">₱14.05</p>
        <p class="text-sm text-black/60 dark:text-white/60">Average Order Value</p>
        <div class="mt-4 flex items-center text-xs text-black/60 dark:text-white/60">
          <span class="material-symbols-outlined text-sm mr-1">trending_up</span>
          <span>+₱0.69 from last period</span>
        </div>
      </CardWrapper>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
      <!-- Revenue Chart -->
      <CardWrapper class="xl:col-span-2">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-bold text-black dark:text-white">Revenue Overview</h3>
            <p class="text-black/60 dark:text-white/60 text-sm">Daily revenue for the selected period</p>
          </div>
          <div class="flex items-center gap-2">
            <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">Daily</button>
            <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-primary text-white">Weekly</button>
            <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">Monthly</button>
          </div>
        </div>
        <div class="chart-container">
          <div class="w-full h-full bg-white dark:bg-gray-800 rounded-lg flex items-center justify-center border border-gray-200 dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400">Revenue Chart Placeholder</p>
          </div>
        </div>
      </CardWrapper>

      <!-- Category Performance -->
      <CardWrapper>
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-bold text-black dark:text-white">Category Performance</h3>
          <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <span class="material-symbols-outlined text-sm">more_vert</span>
          </button>
        </div>
        <div class="chart-container" style="height: 250px;">
          <div class="w-full h-full bg-white dark:bg-gray-800 rounded-lg flex items-center justify-center border border-gray-200 dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400">Doughnut Chart Placeholder</p>
          </div>
        </div>
        <div class="mt-6 space-y-3">
          <div 
            v-for="category in categoryData"
            :key="category.category"
            class="flex items-center justify-between"
          >
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full" :class="category.color"></div>
              <span class="text-sm text-black/60 dark:text-white/60">{{ category.category }}</span>
            </div>
            <span class="text-sm font-medium text-black dark:text-white">{{ category.percentage }}%</span>
          </div>
        </div>
      </CardWrapper>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Peak Hours -->
      <CardWrapper>
        <h3 class="text-lg font-bold text-black dark:text-white mb-4">Peak Hours Analysis</h3>
        <div class="space-y-4">
          <div 
            v-for="hour in peakHours"
            :key="hour.time"
          >
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-black/60 dark:text-white/60">{{ hour.time }}</span>
              <span class="text-sm font-medium text-black dark:text-white">{{ hour.orders }} orders</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div class="bg-gradient-to-r from-primary to-primary/80 h-2 rounded-full" :style="`width: ${hour.percentage}%`"></div>
            </div>
          </div>
        </div>
      </CardWrapper>

      <!-- System Performance -->
      <CardWrapper>
        <h3 class="text-lg font-bold text-black dark:text-white mb-4">System Performance</h3>
        <div class="grid grid-cols-2 gap-4">
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
              <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">devices</span>
            </div>
            <p class="text-2xl font-bold text-black dark:text-white">23</p>
            <p class="text-sm text-black/60 dark:text-white/60">Active Devices</p>
          </div>
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
              <span class="material-symbols-outlined text-green-600 dark:text-green-400">local_cafe</span>
            </div>
            <p class="text-2xl font-bold text-black dark:text-white">8/10</p>
            <p class="text-sm text-black/60 dark:text-white/60">Tables Occupied</p>
          </div>
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center">
              <span class="material-symbols-outlined text-purple-600 dark:text-purple-400">star</span>
            </div>
            <p class="text-2xl font-bold text-black dark:text-white">4.8</p>
            <p class="text-sm text-black/60 dark:text-white/60">Avg Rating</p>
          </div>
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-yellow-100 dark:bg-yellow-900/20 flex items-center justify-center">
              <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400">schedule</span>
            </div>
            <p class="text-2xl font-bold text-black dark:text-white">4.2m</p>
            <p class="text-sm text-black/60 dark:text-white/60">Avg Brew Time</p>
          </div>
        </div>
      </CardWrapper>
    </div>

    <!-- Top Products Table -->
    <CardWrapper overflow>
      <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-black dark:text-white">Top Performing Products</h3>
          <button class="text-sm text-primary hover:text-primary/80">View All Products</button>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Product</th>
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Category</th>
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Orders</th>
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Revenue</th>
              <th class="text-left py-3 px-6 text-black/60 dark:text-white/60 font-medium text-sm">Growth</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="product in topProducts"
              :key="product.id"
              class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900/20"
            >
              <td class="py-4 px-6">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getRankGradient(product.rank)">
                    <span class="font-bold text-sm" :class="getRankColor(product.rank)">{{ product.rank }}</span>
                  </div>
                  <span class="font-medium text-black dark:text-white">{{ product.name }}</span>
                </div>
              </td>
              <td class="py-4 px-6 text-black/60 dark:text-white/60">{{ product.category }}</td>
              <td class="py-4 px-6 text-black dark:text-white font-medium">{{ product.orders.toLocaleString() }}</td>
              <td class="py-4 px-6 text-black dark:text-white font-bold">₱{{ product.revenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
              <td class="py-4 px-6">
                <span 
                  class="inline-flex items-center gap-1 text-sm font-medium"
                  :class="product.positive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                >
                  <span class="material-symbols-outlined text-sm">{{ product.positive ? 'trending_up' : 'trending_down' }}</span>
                  {{ product.positive ? '+' : '' }}{{ product.growth }}%
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardWrapper>
  </AdminLayout>
</template>

<style>
.chart-container {
  position: relative;
  height: 300px;
  width: 100%;
}

@media (max-width: 768px) {
  .chart-container {
    height: 250px;
  }
}
</style>
