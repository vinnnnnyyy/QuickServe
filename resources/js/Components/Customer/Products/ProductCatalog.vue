<script setup>
import { ref } from 'vue'
import ProductSection from './ProductList.vue'
import ProductDetailModal from './ProductDetailModal.vue'

const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    products: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['category-selected', 'add-to-cart'])

// Modal state
const showModal = ref(false)
const selectedProduct = ref(null)

// Event handlers
const handleCategorySelected = (selectedCategory) => {
    emit('category-selected', selectedCategory)
}

const handleAddToCart = (product) => {
    emit('add-to-cart', product)
}

const handleViewDetails = (product) => {
    selectedProduct.value = product
    showModal.value = true
}

const handleCloseModal = () => {
    showModal.value = false
    selectedProduct.value = null
}

const handleAddToCartFromModal = (productWithQuantity) => {
    emit('add-to-cart', productWithQuantity)
}
</script>

<template>
    <div>
        <!-- Product Section -->
        <ProductSection
            :categories="categories"
            :products="products"
            @category-selected="handleCategorySelected"
            @view-details="handleViewDetails"
        />

        <!-- Product Detail Modal -->
        <ProductDetailModal
            :show="showModal"
            :product="selectedProduct"
            @close="handleCloseModal"
            @add-to-cart="handleAddToCartFromModal"
        />
    </div>
</template>
