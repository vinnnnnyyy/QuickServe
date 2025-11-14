<script setup>
import { ref, onMounted } from 'vue';

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

const isDarkMode = ref(false);

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
        <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
          <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
          <span class="text-green-700 dark:text-green-400 text-sm font-medium">Online</span>
        </div>
      </div>
    </div>
  </header>
</template>
