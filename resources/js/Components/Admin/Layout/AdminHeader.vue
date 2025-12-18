<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  pageTitle: {
    type: String,
    default: 'Dashboard'
  },
  pageSubtitle: {
    type: String,
    default: 'Welcome back, Admin'
  }
});

const emit = defineEmits(['toggle-sidebar']);
const page = usePage();

const isDarkMode = ref(false);
const showUserMenu = ref(false);

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark');
    localStorage.setItem('darkMode', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('darkMode', 'light');
  }
};

const toggleSidebar = () => {
  emit('toggle-sidebar');
};

const refreshPage = () => {
  window.location.reload();
};

const handleLogout = () => {
  router.post('/admin/logout');
};

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value;
};

const closeUserMenu = () => {
  showUserMenu.value = false;
};

// Get user info from shared auth data
const user = computed(() => page.props.auth?.user || null);
const userName = computed(() => user.value?.name || 'Admin');
const userEmail = computed(() => user.value?.email || '');

// Handle click outside to close menu
const handleClickOutside = (event) => {
  const menuElement = event.target.closest('.user-menu-container');
  const buttonElement = event.target.closest('.user-menu-button');
  if (!menuElement && !buttonElement && showUserMenu.value) {
    closeUserMenu();
  }
};

// Initialize dark mode
onMounted(() => {
  const savedTheme = localStorage.getItem('darkMode');
  if (savedTheme === 'dark') {
    isDarkMode.value = true;
    document.documentElement.classList.add('dark');
  } else {
    isDarkMode.value = false;
    document.documentElement.classList.remove('dark');
  }
  
  // Add click outside listener
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  // Remove click outside listener
  document.removeEventListener('click', handleClickOutside);
  if (showUserMenu.value) {
    showUserMenu.value = false;
  }
});
</script>

<template>
  <header class="bg-white dark:bg-black/20 sticky top-0 z-20 border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between px-4 sm:px-6 py-4">
      <div class="flex items-center gap-4">
        <button 
          class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 lg:hidden"
          @click="toggleSidebar"
        >
          <span class="material-symbols-outlined text-2xl">menu</span>
        </button>
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ pageTitle }}</h1>
          <p class="text-gray-600 dark:text-gray-400 text-sm">{{ pageSubtitle }}</p>
        </div>
      </div>
      
      <div class="flex items-center gap-4">
        <!-- Quick Actions -->
        <div class="hidden sm:flex items-center gap-2">
          <button 
            class="flex items-center gap-2 px-3 py-2 rounded-xl bg-[#ec7813] text-white hover:bg-[#ea580c] transition-all"
            @click="refreshPage"
          >
            <span class="material-symbols-outlined text-lg">refresh</span>
            <span class="text-sm font-medium">Refresh</span>
          </button>
        </div>
        
        <!-- Notifications -->
        <button class="relative p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-white/10 transition-all">
          <span class="material-symbols-outlined text-gray-600 dark:text-gray-400">notifications</span>
          <div class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</div>
        </button>
        
        <!-- Dark Mode Toggle -->
        <button 
          @click="toggleDarkMode"
          class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-white/10 transition-all"
        >
          <span 
            v-if="!isDarkMode"
            class="material-symbols-outlined text-gray-600"
          >
            dark_mode
          </span>
          <span 
            v-else
            class="material-symbols-outlined text-gray-400"
          >
            light_mode
          </span>
        </button>
        
        <!-- Status Indicator -->
        <div class="hidden md:flex items-center gap-2 px-3 py-2 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
          <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
          <span class="text-green-700 dark:text-green-400 text-sm font-medium">Online</span>
        </div>
        
        <!-- User Menu -->
        <div class="relative user-menu-container">
          <button 
            @click="toggleUserMenu"
            class="user-menu-button flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-gray-100 dark:hover:bg-white/10 transition-all"
          >
            <div class="w-8 h-8 rounded-full bg-[#ec7813] text-white flex items-center justify-center font-semibold text-sm">
              {{ userName.charAt(0).toUpperCase() }}
            </div>
            <div class="hidden sm:block text-left">
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ userName }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ userEmail }}</p>
            </div>
            <span class="material-symbols-outlined text-gray-600 dark:text-gray-400 text-lg">
              {{ showUserMenu ? 'expand_less' : 'expand_more' }}
            </span>
          </button>
          
          <!-- Dropdown Menu -->
          <div 
            v-show="showUserMenu"
            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-2 z-50"
          >
            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
              <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ userName }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ userEmail }}</p>
            </div>
            <button
              @click="handleLogout"
              class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
            >
              <span class="material-symbols-outlined text-lg">logout</span>
              <span>Logout</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>
