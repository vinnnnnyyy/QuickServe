<script setup>
import Button from '../Shared/Base/Button.vue'
import CardWrapper from '../Admin/UI/CardWrapper.vue'
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
    products: {
        type: Array,
        required: false,
        default: () => []
    },
    fetchFromApi: {
        type: Boolean,
        default: true
    }
})

defineEmits(['add-to-cart', 'view-details', 'customize'])

const menuItems = ref([])
const loading = ref(true)
const error = ref(null)

// Map API data to ProductCard format
const mapApiDataToProductFormat = (apiItems) => {
    // Ensure apiItems is an array
    if (!Array.isArray(apiItems)) {
        console.warn('API response is not an array:', apiItems)
        return []
    }
    
    return apiItems.map(item => ({
        id: item.id,
        name: item.name,
        description: item.description,
        price: Number(item.price) || 0,
        originalPrice: null,
        rating: Number((4.5 + Math.random() * 0.5).toFixed(1)), // Generate random rating between 4.5-5.0
        reviewCount: Math.floor(Math.random() * 2000) + 100, // Random review count
        calories: Math.floor(Math.random() * 200) + 50, // Random calories
        prepTime: item.preparationTime || '3-5 min',
        category: item.category?.toLowerCase() || 'food',
        image: item.image || 'https://via.placeholder.com/400x300?text=Menu+Item',
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
        tags: [item.category, item.temperature].filter(Boolean)
    }))
}

// Fetch menu items from API
const fetchMenuItems = async () => {
    if (!props.fetchFromApi) {
        menuItems.value = props.products
        loading.value = false
        return
    }
    
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
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        console.log('API Response:', data) // Debug log
        menuItems.value = mapApiDataToProductFormat(data)
    } catch (err) {
        console.error('Error fetching menu items:', err)
        error.value = err.message
        // Fallback to empty array
        menuItems.value = []
    } finally {
        loading.value = false
    }
}

const formatPrice = (price) => {
    return `₱${Number(price).toFixed(2)}`
}

// Watch for changes in props.products
watch(() => props.products, (newProducts) => {
    if (!props.fetchFromApi) {
        menuItems.value = newProducts
    }
}, { immediate: true })

// Fetch data on component mount
onMounted(() => {
    fetchMenuItems()
})
</script>

<template>
    <!-- Loading State -->
    <div v-if="loading && fetchFromApi" class="space-y-4">
        <div v-for="i in 3" :key="i" class="bg-white rounded-2xl overflow-hidden border border-gray-100/50 h-full flex flex-col animate-pulse">
            <div class="aspect-square bg-gray-200"></div>
            <div class="p-5 space-y-3">
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                <div class="h-6 bg-gray-200 rounded w-1/2"></div>
            </div>
        </div>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="text-center py-8">
        <div class="text-red-500 mb-2">
            <i class="fas fa-exclamation-triangle text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Error Loading Menu</h3>
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <button @click="fetchMenuItems" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
            <i class="fas fa-refresh mr-2"></i>
            Try Again
        </button>
    </div>
    
    <!-- Empty State -->
    <div v-else-if="menuItems.length === 0" class="text-center py-8">
        <div class="text-gray-400 mb-2">
            <i class="fas fa-utensils text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Menu Items</h3>
        <p class="text-gray-600">No items available at the moment.</p>
    </div>
    
    <!-- Product Cards -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <CardWrapper
            v-for="product in menuItems"
            :key="product.id"
            overflow
            hover
            shadow="hover"
            rounded="xl"
            padding="none"
            class="group h-full flex flex-col"
        >
            <!-- Badge -->
            <div class="relative">
                <div v-if="product.badge" :class="[
                    'absolute top-3 left-3 z-10 px-2 py-1 rounded-lg text-xs font-medium',
                    product.badge.color
                ]">
                    {{ product.badge.text }}
                </div>

                <!-- Image - Fixed height -->
                <div class="relative aspect-square overflow-hidden">
                    <img 
                        :alt="product.name" 
                        :src="product.image"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        @error="$event.target.src='https://via.placeholder.com/400x300?text=Menu+Item'"
                    />
                </div>
            </div>

            <!-- Content - Add flex-1 to take remaining space -->
            <div class="p-5 flex flex-col flex-1">
                <!-- Title and Info Button - Fixed height -->
                <div class="flex items-start justify-between gap-3 mb-3">
                    <h3 class="text-subheading text-black dark:text-white leading-tight flex-1 line-clamp-2 min-h-[2.5rem]">
                        {{ product.name }}
                    </h3>
                    <button @click="$emit('view-details', product)"
                        class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-all flex-shrink-0">
                        <i class="fas fa-info-circle text-lg"></i>
                    </button>
                </div>

                <!-- Description - Fixed height -->
                <p class="text-body text-black/60 dark:text-white/60 text-sm mb-4 line-clamp-3 min-h-[3.75rem]">
                    {{ product.description }}
                </p>

                <!-- Price and Rating - Fixed height -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-price text-xl text-black dark:text-white font-bold">
                            {{ formatPrice(product.price) }}
                        </span>
                        <span v-if="product.status" :class="[
                            'text-xs px-2 py-1 rounded-full font-medium whitespace-nowrap',
                            product.status.color
                        ]">
                            {{ product.status.text }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1 text-xs text-black/60 dark:text-white/60">
                        <i class="fas fa-star text-sm text-yellow-500"></i>
                        <span class="font-medium">{{ product.rating.toFixed(1) }}</span>
                        <span class="text-gray-400 dark:text-gray-500">({{ product.reviewCount }})</span>
                    </div>
                </div>

                <!-- Action Buttons - Push to bottom with mt-auto -->
                <div class="space-y-2 mt-auto">
                    <Button 
                        variant="primary" 
                        size="sm" 
                        class="w-full" 
                        icon-left="fas fa-shopping-cart"
                        @click="$emit('view-details', product)"
                        :disabled="!product.status || product.status.text === 'Out of Stock'"
                    >
                        {{ product.status && product.status.text === 'Out of Stock' ? 'Out of Stock' : 'Add to Cart' }}
                    </Button>
                    <Button 
                        variant="secondary" 
                        size="sm" 
                        class="w-full" 
                        icon-left="fas fa-cog"
                        @click="$emit('customize', product)"
                        :disabled="!product.status || product.status.text === 'Out of Stock'"
                    >
                        Customize Order
                    </Button>
                </div>
            </div>
        </CardWrapper>
    </div>
</template>