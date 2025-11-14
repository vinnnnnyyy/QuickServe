<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
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
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">₱2,847</p>
        <p class="text-sm font-medium text-green-500 mt-1">+12.5%</p>
      </CardWrapper>
      
      <CardWrapper>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Orders Today</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">147</p>
        <p class="text-sm font-medium text-green-500 mt-1">+8.2%</p>
      </CardWrapper>
      
      <CardWrapper>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Active Devices</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">23</p>
        <p class="text-sm font-medium text-green-500 mt-1">+8.5%</p>
      </CardWrapper>
      
      <CardWrapper>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-400">Avg Rating</p>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">4.8</p>
        <p class="text-sm font-medium text-green-500 mt-1">+0.3</p>
      </CardWrapper>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
      <!-- Sales Chart -->
      <CardWrapper class="xl:col-span-2">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Sales Overview</h3>
            <p class="text-gray-700 dark:text-gray-400 text-sm">Last 7 days performance</p>
          </div>
          <div class="flex items-center gap-2">
            <button class="px-3 py-1 rounded-lg bg-[#ec7813] text-white text-sm font-medium">7 Days</button>
            <button class="px-3 py-1 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-[#ec7813]/10 dark:hover:bg-[#ec7813]/20 text-sm font-medium">30 Days</button>
          </div>
        </div>
        <div class="chart-container">
          <div class="w-full h-full bg-white dark:bg-gray-800 rounded-lg flex items-center justify-center border border-gray-200 dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400">Sales Chart Placeholder</p>
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
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#ec7813] to-orange-600 flex items-center justify-center text-white font-bold">1</div>
            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">Iced Brown Sugar Oatmilk</p>
              <p class="text-gray-700 dark:text-gray-400 text-sm">24 orders • ₱130.80</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold">2</div>
            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">Pistachio Cream Cold Brew</p>
              <p class="text-gray-700 dark:text-gray-400 text-sm">18 orders • ₱89.10</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-bold">3</div>
            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">Iced White Chocolate Mocha</p>
              <p class="text-gray-700 dark:text-gray-400 text-sm">15 orders • ₱78.75</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white font-bold">4</div>
            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">Iced Caramel Macchiato</p>
              <p class="text-gray-700 dark:text-gray-400 text-sm">12 orders • ₱61.80</p>
            </div>
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
            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-white/50 dark:hover:bg-gray-900/20">
              <td class="py-4 px-4 text-gray-900 dark:text-white font-medium">#ORD-001</td>
              <td class="py-4 px-4 text-gray-900 dark:text-white">Table 5 - Device #1247</td>
              <td class="py-4 px-4 text-gray-700 dark:text-gray-400">2 items</td>
              <td class="py-4 px-4 text-gray-900 dark:text-white font-medium">₱12.40</td>
              <td class="py-4 px-4">
                <span class="px-3 py-1 rounded-full bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm font-medium">Completed</span>
              </td>
              <td class="py-4 px-4 text-gray-700 dark:text-gray-400">2 min ago</td>
            </tr>
            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-white/50 dark:hover:bg-gray-900/20">
              <td class="py-4 px-4 text-gray-900 dark:text-white font-medium">#ORD-002</td>
              <td class="py-4 px-4 text-gray-900 dark:text-white">Takeaway - Device #1248</td>
              <td class="py-4 px-4 text-gray-700 dark:text-gray-400">1 item</td>
              <td class="py-4 px-4 text-gray-900 dark:text-white font-medium">₱5.45</td>
              <td class="py-4 px-4">
                <span class="px-3 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 text-sm font-medium">Preparing</span>
              </td>
              <td class="py-4 px-4 text-gray-700 dark:text-gray-400">5 min ago</td>
            </tr>
            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-white/50 dark:hover:bg-gray-900/20">
              <td class="py-4 px-4 text-gray-900 dark:text-white font-medium">#ORD-003</td>
              <td class="py-4 px-4 text-gray-900 dark:text-white">Table 12 - Device #1249</td>
              <td class="py-4 px-4 text-gray-700 dark:text-gray-400">3 items</td>
              <td class="py-4 px-4 text-gray-900 dark:text-white font-medium">₱18.90</td>
              <td class="py-4 px-4">
                <span class="px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 text-sm font-medium">New</span>
              </td>
              <td class="py-4 px-4 text-gray-700 dark:text-gray-400">8 min ago</td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardWrapper>
  </AdminLayout>
</template>
