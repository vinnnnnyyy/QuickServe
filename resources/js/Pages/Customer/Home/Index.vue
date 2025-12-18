<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import CustomerLayout from '../../../Layouts/CustomerLayout.vue'
import FeaturedPicks from '../../../Components/Customer/UI/FeaturedPicks.vue'
import ProductsSection from '../../../Components/Customer/Products/ProductList.vue'
import ProductDetailModal from '../../../Components/Customer/Products/ProductDetailModal.vue'
import CustomProductModal from '../../../Components/Customer/Products/CustomProductModal.vue'
import Welcomebanner from '../../../Components/Customer/Header/WelcomeBanner.vue'
import LoadingSpinner from '../../../Components/Shared/Base/LoadingSpinner.vue'
import CustomerTypeSelectionModal from '../../../Components/Customer/Modals/CustomerTypeSelectionModal.vue'
import { useCart } from '../../../composables/useCart.js'

const props = defineProps({
  tableId: {
    type: [Number, String],
    default: null
  },
  tableNumber: {
    type: [Number, String],
    default: null
  },
  customerType: {
    type: String,
    default: null
  },
  token: {
    type: String,
    default: null
  }
})

if (props.tableId) {
  sessionStorage.setItem('currentTableId', props.tableId)
}
if (props.tableNumber) {
  sessionStorage.setItem('currentTableNumber', props.tableNumber)
}
if (props.token) {
  sessionStorage.setItem('authTableToken', props.token)
}

// ============================================================================
// Constants
// ============================================================================
const API_URL = '/api/menu'
const PLACEHOLDER_IMAGE = 'https://via.placeholder.com/400x300?text=Menu+Item'
const DEFAULT_RATING = 4.6
const DEFAULT_REVIEW_COUNT = 128

const page = usePage()

// ============================================================================
// State Management
// ============================================================================
// Core state
const categories = ref([
  { id: 'all', name: 'All Items', active: true, description: 'All available items', icon: 'fas fa-list' }
])
const products = ref([])
const loading = ref(true)
const error = ref(null)
const operatingHours = computed(() => page.props.operatingHours ?? null)
const rating = computed(() => page.props.rating ?? null)

// Modal state
const showModal = ref(false)
const showCustomModal = ref(false)
const showCustomerTypeModal = ref(false)
const selectedProduct = ref(null)

// Cart composable
const { addToCart } = useCart()

// Request cancellation
let abortController = null

// ============================================================================
// Helper Functions
// ============================================================================
/**
 * Convert a string to a URL-friendly slug
 */
const slugify = (str) => {
  if (!str) return ''
  return str
    .toString()
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')
}

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

/**
 * Transform API menu item to UI product format
 */
const toProduct = (item) => {
  // Extract category name from relation or fallback to string
  const categoryName = item?.category?.name ?? item?.category ?? 'uncategorized'
  
  return {
    id: item?.id,
    name: item?.name,
    description: item?.description ?? '',
    // Use price_formatted accessor (cents to dollars) or fallback
    price: Number(item?.price_formatted ?? ((item?.price ?? 0) / 100)),
    price_formatted: Number(item?.price_formatted ?? ((item?.price ?? 0) / 100)),
    // Use image_url accessor from model for storage symlink
    image: item?.image_url ?? PLACEHOLDER_IMAGE,
    image_url: item?.image_url ?? PLACEHOLDER_IMAGE,
    category: slugify(categoryName),
    // Size labels for customization
    size_labels: item?.size_labels ?? ['Small', 'Medium', 'Large'],
    // Addons for customization
    addons: item?.addons ?? [],
    // Use deterministic defaults instead of random values
    rating: item?.rating ?? DEFAULT_RATING,
    reviewCount: item?.review_count ?? DEFAULT_REVIEW_COUNT,
    badge: item?.featured ? {
      text: 'Featured',
      color: 'bg-blue-500 text-white'
    } : item?.popular ? {
      text: 'Popular',
      color: 'bg-primary-500 text-white'
    } : null,
    status: {
      text: item?.available ? 'Available' : 'Out of Stock',
      color: item?.available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
    },
    tags: [
      categoryName,
      item?.temperature,
      ...(item?.popular ? ['popular'] : [])
    ].filter(Boolean)
  }
}

// ============================================================================
// Computed Properties
// ============================================================================
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
 * Get featured products for FeaturedPicks component
 */
const featuredProducts = computed(() => {
  const featuredItems = filteredProducts.value.filter(product =>
    product.badge?.text === 'Featured'
  )
  
  // If no featured items found, show first 4 items as fallback
  if (featuredItems.length === 0 && filteredProducts.value.length > 0) {
    return filteredProducts.value.slice(0, 4)
  }
  
  return featuredItems
})

/**
 * Get popular products for ProductsSection component
 */
const popularProducts = computed(() => {
  const activeCategory = categories.value.find(cat => cat.active)
  
  // If "All Items" is selected, show all popular items from all categories
  const sourceProducts = activeCategory?.id === 'all'
    ? products.value
    : filteredProducts.value
  
  const popularItems = sourceProducts.filter(product =>
    product.tags?.includes('popular')
  )
  
  // Remove featured badge from popular items to avoid confusion
  return popularItems.map(product => ({
    ...product,
    badge: product.badge?.text === 'Featured' ? null : product.badge
  }))
})

// ============================================================================
// API Functions
// ============================================================================
/**
 * Fetch products from API with request cancellation
 */
const fetchProducts = async () => {
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
    products.value = items.map(toProduct)

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
    products.value = []
    
    console.error('Error fetching products:', err)
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
}

const handleAddToCart = (product) => {
  addToCart(product)
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
  handleAddToCart(productWithQuantity)
}

const handleCustomize = (product) => {
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

const handleViewAll = () => {
  if (props.token) {
    router.visit(route('table.fullmenu', { token: props.token }))
  } else {
    router.visit('/menu')
  }
}

const handleCustomerTypeSelect = async (selection) => {
  try {
    // Use prop, sessionStorage, or fallback
    const currentTableId = props.tableId || sessionStorage.getItem('currentTableId');
    
    const response = await window.axios.post('/api/session/customer-type', { 
      type: selection.type,
      payment_mode: selection.payment_mode,
      table_id: currentTableId
    })
    showCustomerTypeModal.value = false
    
    // Reload to ensure backend session state (especially host status) is reflected
    if (response.data.success) {
      router.reload()
    }
  } catch (err) {
    console.error('Failed to save customer type:', err)
  }
}

// ============================================================================
// Lifecycle Hooks
// ============================================================================
onMounted(() => {
  fetchProducts()
  
  if (!props.customerType) {
    showCustomerTypeModal.value = true
  }
})

onUnmounted(() => {
  // Cancel any pending requests to prevent memory leaks
  if (abortController) {
    abortController.abort()
  }
})
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

    <!-- Customer Type Selection Modal -->
    <CustomerTypeSelectionModal
      :show="showCustomerTypeModal"
      @select="handleCustomerTypeSelect"
    />
  </CustomerLayout>
</template>