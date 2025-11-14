<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import CustomerLayout from '../../../Layouts/CustomerLayout.vue'
import ProductsSection from '../../../Components/Customer/Products/ProductList.vue'
import ProductDetailModal from '../../../Components/Customer/Products/ProductDetailModal.vue'
import CustomProductModal from '../../../Components/Customer/Products/CustomProductModal.vue'
import Welcomebanner from '../../../Components/Customer/Header/WelcomeBanner.vue'
import Pagination from '../../../Components/Customer/UI/Pagination.vue'
import LoadingSpinner from '../../../Components/Shared/Base/LoadingSpinner.vue'
import { useCart } from '../../../composables/useCart.js'

// Menu items from API
const menuItems = ref([])
const loading = ref(true)
const error = ref(null)

// Categories - will be built dynamically from menu items
const categories = ref([])

// Modal state
const showModal = ref(false)
const showCustomModal = ref(false)
const selectedProduct = ref(null)

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(12)

// Fetch menu items from API
const fetchMenuItems = async () => {
    try {
        loading.value = true
        error.value = null
        
        const response = await fetch('/api/menu', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        
        if (!response.ok) {
            throw new Error(`Failed to fetch menu: ${response.status}`)
        }
        
        const data = await response.json()
        const items = Array.isArray(data) ? data : []
        menuItems.value = items
        
        // Build dynamic categories from fetched data
        const uniqueNames = [...new Set(items.map(i => i.category).filter(Boolean))]
        categories.value = buildCategories(uniqueNames)
        
    } catch (err) {
        console.error('Error fetching menu items:', err)
        error.value = err.message
        menuItems.value = []
    } finally {
        loading.value = false
    }
}

// Map menu items to product format for components (same as home page)
const products = computed(() => {
    // Ensure menuItems.value is always an array
    const items = Array.isArray(menuItems.value) ? menuItems.value : [];
    return items.map(item => ({
        id: item.id,
        name: item.name,
        description: item.description,
        price: Number(item.price) || 0,
        image: item.image || 'https://via.placeholder.com/400x300?text=Menu+Item',
        category: slugify(item.category || 'uncategorized'),
        rating: Number((4.5 + Math.random() * 0.5).toFixed(1)),
        reviewCount: Math.floor(Math.random() * 200) + 50,
        badge: item.featured ? {
            text: 'Featured',
            color: 'bg-blue-500 text-white'
        } : item.popular ? {
            text: 'Popular', 
            color: 'bg-primary-500 text-white'
        } : null,
        status: {
            text: item.available ? 'Available' : 'Out of Stock',
            color: item.available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
        },
        tags: [
            item.category, 
            item.temperature,
            ...(item.popular ? ['popular'] : [])
        ].filter(Boolean)
    }))
})

// Use global cart state
const { addToCart } = useCart()

// Helper functions for dynamic categories
const slugify = (s) => s?.toString().trim().toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'') || ''
const categoryIcon = (name) => ({
  'hot drinks': 'fas fa-mug-hot',
  'cold drinks': 'fas fa-ice-cream',
  'specialty coffee': 'fas fa-coffee',
  'tea & infusions': 'fas fa-leaf',
  'pastries': 'fas fa-bread-slice',
  'sandwiches': 'fas fa-utensils',
  'desserts': 'fas fa-ice-cream'
}[name?.toLowerCase()] || 'fas fa-coffee')
const buildCategories = (names) => [
  { id: 'all', name: 'All Items', active: true, description: 'All available items', icon: 'fas fa-list' },
  ...names.map(n => ({ id: slugify(n), name: n, active: false, description: '', icon: categoryIcon(n) }))
]

// Computed property for filtered products based on active category
const filteredProducts = computed(() => {
    const activeCategory = categories.value.find(cat => cat.active)
    if (!activeCategory || activeCategory.id === 'all') {
        return products.value
    }
    return products.value.filter(product => 
        product.category === activeCategory.id
    )
})

// Computed property for paginated products
const paginatedProducts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return filteredProducts.value.slice(start, end)
})

// Event handlers (same as home page)
const handleCategorySelected = (selectedCategory) => {
    categories.value.forEach(cat => {
        cat.active = cat.id === selectedCategory.id
    })
    // Reset to first page when category changes
    currentPage.value = 1
}

const handleAddToCart = (product) => {
    addToCart(product)
}

const handleViewDetails = (product) => {
    console.log('Viewing details for:', product)
    selectedProduct.value = product
    showModal.value = true
}

const handleCloseModal = () => {
    showModal.value = false
    selectedProduct.value = null
}

const handleAddToCartFromModal = (productWithQuantity) => {
    handleAddToCart(productWithQuantity)
}

const handleCustomize = (product) => {
    console.log('Customizing product:', product)
    selectedProduct.value = product
    showCustomModal.value = true
}

const handleCloseCustomModal = () => {
    showCustomModal.value = false
    selectedProduct.value = null
}

const handleAddCustomToCart = (customizedProduct) => {
    handleAddToCart(customizedProduct)
}

const handlePageChange = (page) => {
    currentPage.value = page
    // Scroll to top when page changes
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const formatPrice = (price) => {
    return `₱${Number(price).toFixed(2)}`
}

// Watch for changes in filtered products to reset pagination
watch(filteredProducts, (newFilteredProducts) => {
    // Reset to first page when filtered products change
    currentPage.value = 1
    console.log('Filtered products changed:', newFilteredProducts.length, 'items')
})

// Component lifecycle
onMounted(() => {
    fetchMenuItems()
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
                :product="selectedProduct"
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
