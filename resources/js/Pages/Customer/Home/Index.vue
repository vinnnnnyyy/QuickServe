<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import CustomerLayout from '../../../Layouts/CustomerLayout.vue'
import FeaturedPicks from '../../../Components/Customer/UI/FeaturedPicks.vue'
import ProductsSection from '../../../Components/Customer/Products/ProductList.vue'
import ProductDetailModal from '../../../Components/Customer/Products/ProductDetailModal.vue'
import CustomProductModal from '../../../Components/Customer/Products/CustomProductModal.vue'
import Welcomebanner from '../../../Components/Customer/Header/WelcomeBanner.vue'
import LoadingSpinner from '../../../Components/Shared/Base/LoadingSpinner.vue'
import { useCart } from '../../../composables/useCart.js'

// Reactive data 
const categories = ref([])
const products = ref([]) // Will be populated from API
const loading = ref(true)

// Global cart
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

// Computed property for featured drinks (for FeaturedPicks component)
const featuredProducts = computed(() => {
    const featuredItems = filteredProducts.value.filter(product => 
        product.badge && product.badge.text === 'Featured'
    )
    
    // If no featured items found, show first 4 items as fallback
    if (featuredItems.length === 0 && filteredProducts.value.length > 0) {
        return filteredProducts.value.slice(0, 4)
    }
    
    return featuredItems
})

// Computed property for popular drinks only (for ProductsSection)
const popularProducts = computed(() => {
    const activeCategory = categories.value.find(cat => cat.active)
    
    // If "All Items" is selected, show all popular items from all categories
    let sourceProducts = activeCategory && activeCategory.id === 'all' 
        ? products.value 
        : filteredProducts.value
    
    const popularItems = sourceProducts.filter(product => 
        product.tags && product.tags.includes('popular')
    )
    
    // Remove featured badge from popular items to avoid confusion
    const popularItemsWithoutFeaturedBadge = popularItems.map(product => ({
        ...product,
        badge: product.badge?.text === 'Featured' ? null : product.badge
    }))
    
    // Debug logging
    console.log('=== POPULAR PRODUCTS DEBUG ===')
    console.log('Active category:', activeCategory?.name)
    console.log('Source products:', sourceProducts.length)
    console.log('All products with popular info:', sourceProducts.map(p => ({ 
        name: p.name, 
        originalPopular: p.originalPopular, 
        tags: p.tags,
        hasPopularTag: p.tags?.includes('popular')
    })))
    console.log('Filtered popular products:', popularItems.length)
    console.log('Popular products:', popularItems.map(p => p.name))
    
    // Only return popular items, no fallback to all items
    return popularItemsWithoutFeaturedBadge
})

// Fetch products from API
const fetchProducts = async () => {
    try {
        const response = await fetch('/api/menu', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        
        if (response.ok) {
            const data = await response.json()
            console.log('=== API DATA DEBUG ===')
            console.log('Raw API response:', data)
            console.log('Data length:', Array.isArray(data) ? data.length : 'Not an array')
            
            // Map API data to expected format
            // Ensure data is always an array
            const items = Array.isArray(data) ? data : [];
            products.value = items.map(item => ({
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
                ].filter(Boolean),
                // Debug info
                originalPopular: item.popular
            }))
            
            // Build dynamic categories from fetched data
            const uniqueNames = [...new Set(items.map(i => i.category).filter(Boolean))]
            categories.value = buildCategories(uniqueNames)
        }
    } catch (error) {
        console.error('Error fetching products:', error)
    } finally {
        loading.value = false
    }
}

// Modal state
const showModal = ref(false)
const showCustomModal = ref(false)
const selectedProduct = ref(null)

// Event handlers
const handleCategorySelected = (selectedCategory) => {
  categories.value.forEach(cat => {
    cat.active = cat.id === selectedCategory.id
  })
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

// Component lifecycle
onMounted(() => {
  fetchProducts()
})

const handleAddCustomToCart = (customizedProduct) => {
  handleAddToCart(customizedProduct)
}

const handleViewAll = () => {
  router.visit('/menu')
}
</script>

<template>
  <CustomerLayout 
    :categories="categories"
    @category-selected="handleCategorySelected"
  >
    <!-- Loading State -->
    <LoadingSpinner 
      v-if="loading"
      message="Loading our featured items..."
      subtitle="Please wait while we prepare our best offerings"
      :full-screen="false"
    />
    
    <template v-else>
      <Welcomebanner />
      
      <FeaturedPicks 
      :featuredProducts="featuredProducts" 
      @add-to-cart="handleAddToCart"
      @view-details="handleViewDetails"
      @customize="handleCustomize"
    />
    
    <ProductsSection
      :categories="categories"
      :products="popularProducts"
      :title="'Popular'"
      :subtitle="'Handpicked favorites by our customers'"
      @category-selected="handleCategorySelected"
      @add-to-cart="handleAddToCart"
      @view-details="handleViewDetails"
      @customize="handleCustomize"
      @view-all="handleViewAll"
    />
    </template>

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
  </CustomerLayout>
</template>