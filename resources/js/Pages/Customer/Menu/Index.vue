<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import CustomerLayout from '../../../Layouts/CustomerLayout.vue'
import ProductsSection from '../../../Components/Customer/Products/ProductList.vue'
import ProductDetailModal from '../../../Components/Customer/Products/ProductDetailModal.vue'
import CustomProductModal from '../../../Components/Customer/Products/CustomProductModal.vue'
import Welcomebanner from '../../../Components/Customer/Header/WelcomeBanner.vue'
import Pagination from '../../../Components/Customer/UI/Pagination.vue'
import LoadingSpinner from '../../../Components/Shared/Base/LoadingSpinner.vue'
import { useCart } from '../../../composables/useCart.js'
import { useSelectionModal } from '../../../composables/useSelectionModal.js'
import { toProduct, slugify } from '../../../composables/productTransform.js'

// ============================================================================
// Constants
// ============================================================================
const API_URL = '/api/menu'

// ============================================================================
// State Management
// ============================================================================
// Core state
const menuItems = ref([])
const loading = ref(true)
const error = ref(null)

// Categories derived from API data
const categories = ref([
  { id: 'all', name: 'All Items', active: true, description: 'All available items', icon: 'fas fa-list' }
])

// Modal state (reusable composable for product detail modal)
const { 
  selected: selectedProduct, 
  isOpen: showModal, 
  open: openProductModal, 
  close: closeProductModal 
} = useSelectionModal()

// Custom modal state (still uses inline ref)
const showCustomModal = ref(false)
const selectedCustomProduct = ref(null)

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(12)

// Cart composable
const { addToCart } = useCart()

// Request cancellation
let abortController = null

// ============================================================================
// Helper Functions
// ============================================================================
/**
 * Get FontAwesome icon class for a category
 */
const categoryIcon = (name) => {
  const icons = {
    'hot drinks': 'fas fa-mug-hot',
    'cold drinks': 'fas fa-ice-cream',
    'specialty coffee': 'fas fa-coffee',
    'tea & infusions': 'fas fa-leaf',
    'pastries': 'fas fa-bread-slice',
    'sandwiches': 'fas fa-utensils',
    'desserts': 'fas fa-ice-cream'
  }
  return icons[name?.toLowerCase()] || 'fas fa-coffee'
}

/**
 * Build category list with "All Items" prepended
 */
const buildCategories = (names) => [
  { id: 'all', name: 'All Items', active: true, description: 'All available items', icon: 'fas fa-list' },
  ...names.map(name => ({
    id: slugify(name),
    name,
    active: false,
    description: '',
    icon: categoryIcon(name)
  }))
]

// ============================================================================
// Computed Properties
// ============================================================================
/**
 * Transform menu items into product format for UI components
 */
const products = computed(() => {
  const items = Array.isArray(menuItems.value) ? menuItems.value : []
  return items.map(toProduct)
})

/**
 * Filter products by active category
 */
const filteredProducts = computed(() => {
  const activeCategory = categories.value.find(cat => cat.active)
  if (!activeCategory || activeCategory.id === 'all') {
    return products.value
  }
  return products.value.filter(product => product.category === activeCategory.id)
})

/**
 * Paginate filtered products
 */
const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredProducts.value.slice(start, end)
})

// ============================================================================
// API Functions
// ============================================================================
/**
 * Fetch menu items from API with request cancellation
 */
const fetchMenuItems = async () => {
  try {
    loading.value = true
    error.value = null

    // Cancel any in-flight request
    if (abortController) {
      abortController.abort()
    }
    abortController = new AbortController()

    const response = await window.axios.get(API_URL, {
      headers: { Accept: 'application/json' },
      signal: abortController.signal
    })

    const items = Array.isArray(response.data) ? response.data : []
    menuItems.value = items

    // Build dynamic categories from category relation (item.category.name)
    const uniqueNames = [...new Set(
      items.map(item => item?.category?.name ?? item?.category).filter(Boolean)
    )]
    categories.value = buildCategories(uniqueNames)
  } catch (err) {
    // Handle axios errors gracefully
    if (err.name === 'CanceledError' || err.code === 'ERR_CANCELED') {
      return // Request was cancelled, ignore
    }
    
    const status = err?.response?.status
    const message = err?.response?.data?.message ?? err?.message ?? 'Failed to load menu'
    error.value = status ? `${message} (HTTP ${status})` : message
    menuItems.value = []
    
    console.error('Error fetching menu items:', err)
  } finally {
    loading.value = false
  }
}

// ============================================================================
// Event Handlers
// ============================================================================
const handleCategorySelected = (selectedCategory) => {
  categories.value.forEach(cat => {
    cat.active = cat.id === selectedCategory.id
  })
  currentPage.value = 1
}

const handleAddToCart = (product) => {
  addToCart(product)
}

const handleViewDetails = (product) => {
  openProductModal(product)
}

const handleCloseModal = () => {
  closeProductModal()
}

const handleAddToCartFromModal = (productWithQuantity) => {
  handleAddToCart(productWithQuantity)
}

const handleCustomize = (product) => {
  selectedCustomProduct.value = product
  showCustomModal.value = true
}

const handleCloseCustomModal = () => {
  showCustomModal.value = false
  selectedCustomProduct.value = null
}

const handleAddCustomToCart = (customizedProduct) => {
  handleAddToCart(customizedProduct)
}

const handlePageChange = (page) => {
  currentPage.value = page
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ============================================================================
// Watchers
// ============================================================================
/**
 * Reset pagination when filtered products change
 */
watch(filteredProducts, () => {
  currentPage.value = 1
})

// ============================================================================
// Lifecycle Hooks
// ============================================================================
onMounted(() => {
  fetchMenuItems()
})

onUnmounted(() => {
  // Cancel any pending requests to prevent memory leaks
  if (abortController) {
    abortController.abort()
  }
})
</script>

<template>
    <Head title="Menu" />
    
    <CustomerLayout 
        :categories="categories"
        @category-selected="handleCategorySelected"
    >
        <!-- Loading State -->
        <LoadingSpinner 
            v-if="loading"
            message="Loading our delicious menu..."
            subtitle="Please wait while we prepare our offerings"
            :full-screen="false"
        />
        
        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
            <div class="text-red-500 mb-4">
                <i class="fas fa-exclamation-triangle text-4xl"></i>
            </div>
            <h3 class="text-xl font-bold text-surface-900 mb-2">Unable to Load Menu</h3>
            <p class="text-surface-600 mb-6">{{ error }}</p>
            <button 
                @click="fetchMenuItems" 
                class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium"
            >
                <i class="fas fa-refresh mr-2"></i>
                Try Again
            </button>
        </div>
        
        <!-- Menu Content -->
        <div v-else-if="menuItems.length > 0">
            <Welcomebanner />
            
            <ProductsSection
                :categories="categories"
                :products="paginatedProducts"
                @category-selected="handleCategorySelected"
                @add-to-cart="handleAddToCart"
                @view-details="handleViewDetails"
                @customize="handleCustomize"
            />

            <!-- Pagination -->
            <Pagination
                :current-page="currentPage"
                :total-items="filteredProducts.length"
                :items-per-page="itemsPerPage"
                :items-text="'products'"
                @page-change="handlePageChange"
            />

            <!-- Product Detail Modal -->
            <ProductDetailModal
                :show="showModal"
                :product="selectedProduct"
                @close="handleCloseModal"
                @add-to-cart="handleAddToCartFromModal"
            />

            <!-- Custom Product Modal -->
            <CustomProductModal
                :show="showCustomModal"
                :product="selectedCustomProduct"
                @close="handleCloseCustomModal"
                @add-to-cart="handleAddCustomToCart"
            />
        </div>
        
        <!-- No Menu Items State -->
        <div v-else class="text-center py-16">
            <div class="text-surface-400 mb-6">
                <i class="fas fa-utensils text-6xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-surface-900 mb-3">Menu Coming Soon</h3>
            <p class="text-surface-600 mb-6 max-w-md mx-auto">
                We're preparing something amazing for you! Our menu will be available shortly.
            </p>
            <button 
                @click="fetchMenuItems" 
                class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium"
            >
                <i class="fas fa-refresh mr-2"></i>
                Check Again
            </button>
        </div>
    </CustomerLayout>
</template>

<style scoped>
/* Custom styles if needed */
</style>
