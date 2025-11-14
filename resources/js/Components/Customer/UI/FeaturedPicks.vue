<script setup>
import { ref } from 'vue'
import Button from '../../Shared/Base/Button.vue'

// Props
const props = defineProps({
  featuredProducts: {
    type: Array,
    default: () => [...productData]
  }
})

// Emits
const emit = defineEmits(['add-to-cart', 'view-details', 'customize'])

// Methods
const handleAddToCart = (product) => {
  emit('add-to-cart', product)
}

const handleViewDetails = (product) => {
  emit('view-details', product)
}

const handleCustomize = (product) => {
  emit('customize', product)
}
</script>

<template>
  <section class="py-2 sm:py-4">
    <!-- Header -->
    <div class="flex items-center justify-between px-4 sm:px-6 lg:px-6 xl:px-8 2xl:px-12 mb-6">
      <div>
        <h2 class="text-display text-surface-800 text-2xl sm:text-3xl lg:text-4xl xl:text-5xl">
          Featured <span class="text-primary-500    ">Picks</span>
        </h2>
        <p class="text-body text-surface-500 text-sm lg:text-base mt-1">
          Handpicked by our baristas
        </p>
      </div>
      <button
        class="text-surface-600 flex items-center justify-center px-4 py-2 rounded-xl hover:bg-white/50 active:bg-white/80 transition-all group"
        @click="handleViewDetails(featuredProducts)"
        >
        <span class="text-button text-sm mr-1">View All</span>
        <i class="fas fa-chevron-right text-lg group-hover:translate-x-1 transition-transform"></i>
      </button>
    </div>

    <!-- Featured Products Carousel -->
    <div class="flex overflow-x-auto snap-scroll custom-scroll scroll-smooth px-4 sm:px-6 lg:px-6 xl:px-8 2xl:px-12 gap-4 sm:gap-6 lg:gap-8 pb-4">
      <div
        v-for="product in featuredProducts"
        :key="product.id"
        class="group flex flex-col min-w-[280px] sm:min-w-[320px] lg:min-w-[360px] xl:min-w-[400px] snap-item animate-fade-in">
        
        <div class="relative glass card-hover rounded-2xl overflow-hidden">
          <!-- Badge -->
          <div 
            v-if="product.badge"
            :class="product.badge.color"
            class="absolute top-4 left-4 z-30 px-2 py-1 rounded-full text-xs font-bold flex items-center gap-1">
            <i v-if="product.badge.icon" :class="product.badge.icon" class="text-sm"></i>
            {{ product.badge.text }}
          </div>

          

          <!-- Rating Badge -->
          <div class="absolute top-4 right-4 z-30 bg-white/90 backdrop-blur-sm text-surface-800 px-2 py-1 rounded-full text-xs font-bold flex items-center gap-1">
            <span class="text-yellow-500">★</span>
            {{ Number(product.rating ?? 0).toFixed(1) }}
          </div>

          <!-- Gradient Overlay -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent z-10"></div>

          <!-- Product Image -->
          <img 
            :alt="product.name"
            :src="product.image"
            class="w-full h-[320px] sm:h-[360px] object-cover transform group-hover:scale-105 transition-transform duration-700" />

          <!-- Product Info -->
          <div class="absolute bottom-0 left-0 right-0 p-6 z-20">
            <div class="flex items-end justify-between gap-4">
              <div class="flex-1">
                <!-- Tags -->
                <div v-if="product.tags && product.tags.length > 0" class="flex items-center gap-2 mb-2">
                  <span
                    v-for="tag in product.tags"
                    :key="tag.text"
                    :class="tag.color"
                    class="px-2 py-0.5 rounded-full text-xs font-medium">
                    {{ tag.text }}
                  </span>
                </div>

                <!-- Product Name -->
                <h3 class="text-subheading text-white text-lg sm:text-xl leading-tight mb-1">
                  {{ product.name }}
                </h3>

                <!-- Description -->
                <p v-if="product.description" class="text-body text-white/80 text-sm line-clamp-2 mb-3">
                  {{ product.description }}
                </p>

                <!-- Price and Details -->
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-price text-white/90 text-lg">₱{{ product.price }}</p>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex-shrink-0 flex flex-col gap-2">
                <Button
                  @click="handleAddToCart(product)"
                  class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-500 hover:bg-primary-600 text-white shadow-md shadow-primary-500/20 transition-all group-hover:scale-105">
                  <i class="fas fa-plus text-lg"></i>
                </Button>
                <Button
                  @click="handleCustomize(product)"
                  class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white border border-white/30 transition-all group-hover:scale-105">
                  <i class="fas fa-cog text-lg"></i>
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Custom scrollbar styles */
.custom-scroll {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.custom-scroll::-webkit-scrollbar {
  display: none;
}

/* Snap scroll behavior */
.snap-scroll {
  scroll-snap-type: x mandatory;
}

.snap-item {
  scroll-snap-align: start;
}

/* Animation */
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.6s ease-out;
}

/* Line clamp utility */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Gradient text */
.gradient-text {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Glass effect */
.glass {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Card hover effect */
.card-hover {
  transition: all 0.3s ease;
}

.card-hover:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}
</style>