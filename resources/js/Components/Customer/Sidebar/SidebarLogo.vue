<template>
    <Link :href="homeHref" class="flex items-center gap-3 mb-6 hover:opacity-90 transition-opacity">
      <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center">
        <i class="fa-solid fa-mug-hot text-white text-lg"></i>
      </div>
      <div>
        <h1 class="text-lg font-semibold text-surface-800">{{ cafeName }}</h1>
        <p class="text-xs text-surface-400">{{ tagline }}</p>
      </div>
    </Link>
  </template>
  
  <script setup>
  import { Link, usePage } from '@inertiajs/vue3'
  import { computed } from 'vue'
  
  defineProps({
    cafeName: {
      type: String,
      default: 'Coffee Shop'
    },
    tagline: {
      type: String,
      default: 'Best Coffee in Town'
    }
  })

  const page = usePage()
  const token = computed(() => page.props?.token || sessionStorage.getItem('authTableToken'))
  
  const homeHref = computed(() => {
      return token.value ? route('table.menu', { token: token.value }) : '/'
  })
  </script>