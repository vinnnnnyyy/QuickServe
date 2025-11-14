<template>
    <Link href="/" class="flex items-center gap-3 mb-8 hover:opacity-80 transition-opacity cursor-pointer">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg">
        <i class="fas fa-coffee text-white text-2xl"></i>
      </div>
      <div>
        <h1 class="text-brand gradient-text text-2xl">{{ cafeName }}</h1>
        <p class="text-body text-surface-500 text-sm">{{ tagline }}</p>
      </div>
    </Link>
  </template>
  
  <script setup>
  import { Link } from '@inertiajs/vue3'
  
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
  </script>