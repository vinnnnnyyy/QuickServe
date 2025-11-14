<script setup>
import { Link } from '@inertiajs/vue3'
import { useCart } from '../../../composables/useCart.js'

const emit = defineEmits(['open-cart'])
const { cartItemCount } = useCart()
</script>

<template>
  <div class="lg:hidden fixed left-4 right-4 bottom-[max(env(safe-area-inset-bottom),1rem)] z-50">
    <div class="mx-auto flex w-full max-w-sm items-center justify-between rounded-full bg-white shadow-lg ring-1 ring-black/10 p-1">
      <Link 
        :href="route('order.status')" 
        class="flex flex-1 items-center justify-center gap-2 rounded-full px-4 py-3 text-primary-700 hover:bg-primary-50"
      >
        <i class="fas fa-clipboard-check"></i>
        <span>Track order</span>
      </Link>
      <button 
        @click="$emit('open-cart')" 
        class="relative flex h-11 w-11 items-center justify-center rounded-full bg-primary-600 text-white hover:bg-primary-700"
      >
        <i class="fas fa-shopping-cart text-lg"></i>
        <span v-if="cartItemCount > 0" class="absolute -top-1 -right-1 min-w-[1.1rem] rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ cartItemCount }}</span>
      </button>
    </div>
  </div>
</template>
