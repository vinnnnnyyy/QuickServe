<script setup>
import ProductCard from './ProductCard.vue'
import Button from '../Shared/Base/Button.vue'

const props = defineProps({
  title: {
    type: String,
    default: 'Main'
  },
  subtitle: {
    type: String,
    default: 'Most loved by our customers'
  },
  products: {
    type: Array,
    default: () => []
  },
  showViewAll: {
    type: Boolean,
    default: true
  },
  fetchFromApi: {
    type: Boolean,
    default: true
  }
})

defineEmits(['view-all', 'add-to-cart', 'view-details', 'customize'])
</script>

<template>
  <section class="py-8">
    <!-- Header -->
    <div class="flex items-center justify-between px-4 sm:px-6 lg:px-6 xl:px-8 2xl:px-12 mb-6">
      <div>
        <h2 class="text-display text-surface-800 text-2xl sm:text-3xl lg:text-4xl xl:text-5xl">
          {{ title }} <span class="gradient-text">Drinks</span>
        </h2>
        <p class="text-body text-surface-500 text-sm lg:text-base mt-1">
          {{ subtitle }}
        </p>
      </div>
      
      <Button
        v-if="showViewAll"
        variant="ghost"
        icon-right="fas fa-chevron-right"
        @click="$emit('view-all')"
      >
        View All
      </Button>
    </div>
    
    <!-- Products Grid -->
    <div class="px-4 sm:px-6 lg:px-6 xl:px-8 2xl:px-12">
      <ProductCard
        :products="products"
        :fetchFromApi="false"
        @add-to-cart="$emit('add-to-cart', $event)"
        @view-details="$emit('view-details', $event)"
        @customize="$emit('customize', $event)"
      />
    </div>
  </section>
</template>