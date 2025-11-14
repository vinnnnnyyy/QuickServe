<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import AdminSidebar from '@/Components/Admin/Layout/AdminSidebar.vue';
import AdminHeader from '@/Components/Admin/Layout/AdminHeader.vue';

const props = defineProps({
  title: String,
  pageTitle: {
    type: String,
    default: 'Dashboard'
  },
  pageSubtitle: {
    type: String,
    default: 'Welcome back, Admin'
  }
});

const isSidebarOpen = ref(false);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};

const closeSidebar = () => {
  isSidebarOpen.value = false;
};

// Initialize dark mode
onMounted(() => {
  const savedTheme = localStorage.getItem('darkMode');
  if (savedTheme === 'dark') {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
});
</script>

<template>
  <div class="admin-layout min-h-screen bg-background-light dark:bg-background-dark">
    <Head>
      <title>{{ title }}</title>
      <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&f[]=clash-display@600,700&display=swap" rel="stylesheet">
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    </Head>
    
    <!-- Sidebar -->
    <AdminSidebar :is-open="isSidebarOpen" @close="closeSidebar" />
    
    <!-- Main Content -->
    <div class="lg:ml-64 flex flex-col min-h-screen">
      <!-- Header -->
      <AdminHeader 
        :page-title="pageTitle" 
        :page-subtitle="pageSubtitle"
        @toggle-sidebar="toggleSidebar"
      />
      
      <!-- Page Content -->
      <main class="flex-1 p-8 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<style>
@import '../../css/admin.css';

/* Layout-specific styles matching admin.html */
body {
  min-height: 100dvh;
  -webkit-overflow-scrolling: touch;
  font-family: 'Satoshi', sans-serif;
  color: rgb(31 41 55); /* text-gray-800 */
}

.dark body {
  color: rgb(229 231 235); /* text-gray-200 */
}

.admin-layout {
  background: #f8fafc;
  background-image:
    radial-gradient(at 100% 0%, rgba(249, 115, 22, 0.03) 0px, transparent 50%),
    radial-gradient(at 0% 100%, rgba(31, 41, 55, 0.05) 0px, transparent 50%);
  background-attachment: fixed;
}

.dark .admin-layout {
  background: #1a1a1a;
}

h1, h2, h3, h4, h5, h6 {
  font-family: 'Clash Display', sans-serif;
}

html {
  scroll-behavior: smooth;
  scroll-padding-top: 80px;
}

/* iOS text size fix */
@media screen and (-webkit-min-device-pixel-ratio: 0) {
  input, select, textarea {
    font-size: 16px;
  }
}
</style>
