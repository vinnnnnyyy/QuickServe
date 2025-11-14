<script setup>
import ProductGrid from '../../UI/ProductGrid.vue'

// Props
const props = defineProps({
  categories: {
    type: Array,
    required: true
  },
  products: {
    type: Array,
    required: true
  },
  title: {
    type: String,
    default: 'Main'
  },
  subtitle: {
    type: String,
    default: 'Most loved by our customers'
  }
})

// Emits
const emit = defineEmits(['category-selected', 'add-to-cart', 'view-details', 'customize', 'view-all'])

// Event handlers
const handleCategorySelected = (selectedCategory) => {
  emit('category-selected', selectedCategory)
}

const handleAddToCart = (product) => {
  emit('add-to-cart', product)
}

const handleViewDetails = (product) => {
  emit('view-details', product)
}

const handleCustomize = (product) => {
  emit('customize', product)
}

const handleViewAll = () => {
  emit('view-all')
}
</script>

<template>
  <section class="py-2 sm:py-4">
    <!-- Product Grid -->
    <ProductGrid
      :products="products"
      :title="title"
      :subtitle="subtitle"
      @add-to-cart="handleAddToCart"
      @view-details="handleViewDetails"
      @customize="handleCustomize"
      @view-all="handleViewAll"
    /> 
  </section>
</template>