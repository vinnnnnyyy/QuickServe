<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close']);
const page = usePage();

// Get user role from shared auth data
const staffRole = computed(() => {
  return page.props.auth?.staffRole || null;
});

// Check if user is barista
const isBarista = computed(() => {
  return staffRole.value === 'barista' || 
         staffRole.value === 'head barista' || 
         staffRole.value === 'senior barista';
});

// All navigation items
const allNavigationItems = [
  { name: 'Dashboard', icon: 'dashboard', href: '/admin/dashboard' },
  { name: 'Orders', icon: 'list_alt', href: '/admin/orders' },
  { name: 'Menu', icon: 'coffee', href: '/admin/menu' },
  { name: 'Analytics', icon: 'analytics', href: '/admin/analytics' },
  { name: 'Inventory', icon: 'inventory', href: '/admin/inventory' },
  { name: 'Staff', icon: 'badge', href: '/admin/staff' },
  { name: 'Tables & QR', icon: 'qr_code', href: '/admin/tables' },
  { name: 'Barista Queue', icon: 'coffee_maker', href: '/admin/barista' },
];

// Filter navigation items based on role
const navigationItems = computed(() => {
  if (isBarista.value) {
    // Baristas only see Dashboard and Barista Queue
    return allNavigationItems.filter(item => 
      item.href === '/admin/dashboard' || item.href === '/admin/barista'
    );
  }
  // All other roles see all items
  return allNavigationItems;
});

const closeSidebar = () => {
  emit('close');
};

const isActive = (href) => {
  return page.url === href;
};
</script>

<template>
  <!-- Mobile Sidebar Overlay -->
  <div 
    v-show="isOpen" 
    class="fixed inset-0 bg-black/40 backdrop-blur-sm z-30 transition-opacity duration-300 lg:hidden" 
    @click="closeSidebar"
  ></div>

  <!-- Sidebar -->
  <aside 
    class="fixed top-0 left-0 bottom-0 w-64 bg-[#f8f7f6] dark:bg-[#221810] z-40 transition-transform duration-300 p-6"
    :class="isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <div class="flex flex-col h-full">
      <!-- Logo -->
      <div class="mb-10">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Coffee Admin</h1>
      </div>

      <!-- Navigation -->
      <nav class="flex flex-col space-y-2">
        <Link
          v-for="item in navigationItems"
          :key="item.href"
          :href="item.href"
          class="nav-item flex items-center gap-3 px-3 py-2 rounded transition-all"
          :class="isActive(item.href)
            ? 'bg-[#ec7813]/20 dark:bg-[#ec7813]/30 text-[#ec7813]' 
            : 'text-gray-700 dark:text-gray-300 hover:bg-[#ec7813]/10 dark:hover:bg-[#ec7813]/20'"
          @click="closeSidebar"
        >
          <span class="material-symbols-outlined">{{ item.icon }}</span>
          <span class="text-sm font-medium">{{ item.name }}</span>
        </Link>
      </nav>

      <!-- Settings at bottom (hidden for baristas) -->
      <div v-if="!isBarista" class="mt-auto">
        <Link
          href="/admin/settings"
          class="nav-item flex items-center gap-3 px-3 py-2 rounded transition-all"
          :class="isActive('/admin/settings')
            ? 'bg-[#ec7813]/20 dark:bg-[#ec7813]/30 text-[#ec7813]'
            : 'text-gray-700 dark:text-gray-300 hover:bg-[#ec7813]/10 dark:hover:bg-[#ec7813]/20'"
          @click="closeSidebar"
        >
          <span class="material-symbols-outlined">settings</span>
          <span class="text-sm font-medium">Settings</span>
        </Link>
      </div>
    </div>
  </aside>
</template>
