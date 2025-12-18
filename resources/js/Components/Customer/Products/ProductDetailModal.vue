<script setup>
/* ----------  RE-USED LOGIC FROM YOUR SECOND FILE  ---------- */
import { computed, ref, watch } from 'vue'
import Modal from '../../Shared/Overlay/Modal.vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  product: { type: Object, default: null }
})
const emit = defineEmits(['close', 'add-to-cart'])

const quantity   = ref(1)
const selectedSize = ref('medium')

const sizeOptions = [
  { id: 'small',  name: 'Small (8oz)',  price: 0  },
  { id: 'medium', name: 'Medium (12oz)', price: 0  },
  { id: 'large',  name: 'Large (16oz)',  price: 25 },
  { id: 'xlarge', name: 'Extra Large (20oz)', price: 45 }
]

const formatPrice = p => `₱${p.toFixed(2)}`

const sizePrice   = computed(() => sizeOptions.find(s => s.id === selectedSize.value)?.price ?? 0)
const itemPrice   = computed(() => (props.product?.price ?? 0) + sizePrice.value)
const totalPrice  = computed(() => itemPrice.value * quantity.value)

const inc = () => quantity.value++
const dec = () => { if (quantity.value > 1) quantity.value-- }

const handleAddToCart = () => {
  emit('add-to-cart', {
    ...props.product,
    quantity: quantity.value,
    selectedSize: selectedSize.value,
    sizePrice: sizePrice.value,
    finalPrice: itemPrice.value
  })
  emit('close')
}
const handleClose = () => {
  quantity.value = 1
  selectedSize.value = 'medium'
  emit('close')
}

watch(() => props.show, v => { if (v) { quantity.value = 1; selectedSize.value = 'medium' } })
</script>

<template>
  <Modal :show="show" max-width="5xl" @close="handleClose">
    <div v-if="product" class="flex flex-col lg:flex-row w-full bg-white dark:bg-surface-900 text-surface-900 dark:text-white h-[90vh] sm:h-[85vh] lg:h-[80vh] overflow-hidden sm:rounded-2xl shadow-2xl">
      
      <!-- Close Button (Mobile/Tablet) -->
      <button @click="handleClose" class="lg:hidden absolute top-4 right-4 z-20 w-8 h-8 flex items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70 transition-all backdrop-blur-sm">
        <i class="fas fa-times text-sm"></i>
      </button>

      <!-- LEFT COLUMN: Image & Scrollable Content -->
      <div class="w-full lg:w-3/5 flex flex-col flex-1 lg:flex-none lg:h-full relative min-h-0">
        <!-- Image Area -->
        <div class="relative w-full h-48 sm:h-64 lg:h-72 shrink-0 bg-surface-100 dark:bg-surface-800 group overflow-hidden">
          <img :src="product.image || product.image_url" :alt="product.name" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent lg:hidden"></div>
           <div class="absolute bottom-4 left-4 lg:hidden text-white drop-shadow-md">
              <h2 class="text-2xl font-bold leading-tight">{{ product.name }}</h2>
               <div v-if="product.rating" class="flex items-center gap-1.5 mt-1">
                 <i class="fas fa-star text-yellow-400 text-xs"></i>
                 <span class="font-bold text-xs">{{ Number(product.rating).toFixed(1) }}</span>
               </div>
           </div>
        </div>

        <!-- Scrollable Options -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-8 bg-surface-50/50 dark:bg-surface-900">
           <!-- Title (Desktop) -->
           <div class="hidden lg:block">
              <h2 class="text-3xl font-bold font-display text-surface-900 dark:text-white mb-2">{{ product.name }}</h2>
              <div v-if="product.rating" class="flex items-center gap-2 mb-3">
                <div class="flex items-center gap-1 bg-yellow-100 dark:bg-yellow-900/30 px-2 py-0.5 rounded-full">
                  <i class="fas fa-star text-yellow-500 text-xs"></i>
                  <span class="font-bold text-xs text-yellow-700 dark:text-yellow-400">{{ Number(product.rating).toFixed(1) }}</span>
                </div>
                <span class="text-xs text-surface-500 dark:text-surface-400">({{ product.reviewCount || 0 }} reviews)</span>
              </div>
              <p v-if="product.description" class="text-surface-600 dark:text-surface-400 leading-relaxed max-w-2xl">{{ product.description }}</p>
           </div>
           
            <!-- Mobile Description -->
            <div class="lg:hidden" v-if="product.description">
               <p class="text-sm text-surface-600 dark:text-surface-400 leading-relaxed">{{ product.description }}</p>
            </div>

           <!-- Sizes -->
           <div class="animate-fade-in-up" style="animation-delay: 0.1s;">
              <div class="flex items-center justify-between mb-4">
                 <h3 class="text-lg font-bold text-surface-900 dark:text-white flex items-center gap-2">
                    <span class="w-1 h-6 bg-primary rounded-full"></span>
                    Select Size
                 </h3>
                 <span class="text-xs font-semibold text-primary bg-primary/10 px-2 py-1 rounded-full uppercase tracking-wider">Required</span>
              </div>
              
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <label v-for="s in sizeOptions" :key="s.id"
                       class="relative flex flex-col items-center justify-center p-4 border rounded-xl cursor-pointer transition-all duration-200 group hover:shadow-md"
                       :class="selectedSize === s.id 
                         ? 'border-primary bg-primary/5 dark:bg-primary/10 ring-1 ring-primary ring-opacity-50' 
                         : 'border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-800 hover:border-surface-300 dark:hover:border-surface-600'">
                  <input v-model="selectedSize" :value="s.id" type="radio" name="size" class="sr-only">
                  <div class="w-full text-center">
                     <span class="block text-sm font-bold mb-1 group-hover:text-primary transition-colors" :class="selectedSize === s.id ? 'text-primary' : 'text-surface-900 dark:text-white'">{{ s.name.split(' ')[0] }}</span>
                     <span class="block text-xs font-medium" :class="selectedSize === s.id ? 'text-primary' : 'text-surface-500'">
                       {{ s.price > 0 ? '+' + formatPrice(s.price) : 'Standard' }}
                     </span>
                  </div>
                  <!-- Checkmark -->
                  <div v-if="selectedSize === s.id" class="absolute top-2 right-2 text-primary">
                    <i class="fas fa-check-circle text-sm"></i>
                  </div>
                </label>
              </div>
           </div>

           <!-- Quantity -->
           <div class="animate-fade-in-up" style="animation-delay: 0.2s;">
              <div class="p-5 bg-white dark:bg-surface-800/50 rounded-2xl border border-surface-100 dark:border-surface-800 shadow-sm">
                 <div class="flex items-center justify-between">
                    <div>
                       <h3 class="text-lg font-bold text-surface-900 dark:text-white mb-1">Quantity</h3>
                       <p class="text-xs text-surface-500">How many do you want?</p>
                    </div>
                    
                    <div class="flex items-center gap-4 bg-surface-50 dark:bg-surface-900 p-2 rounded-xl border border-surface-200 dark:border-surface-700">
                        <button @click="dec" :disabled="quantity<=1" type="button"
                                class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white dark:hover:bg-surface-800 text-surface-600 dark:text-surface-300 transition-all shadow-sm disabled:opacity-40 disabled:shadow-none">
                          <i class="fas fa-minus text-sm"></i>
                        </button>
                        <span class="text-xl font-bold w-8 text-center text-surface-900 dark:text-white font-display">{{ quantity }}</span>
                        <button @click="inc" type="button"
                                class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white dark:hover:bg-surface-800 text-primary transition-all shadow-sm">
                          <i class="fas fa-plus text-sm"></i>
                        </button>
                    </div>
                 </div>
              </div>
           </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Summary & Actions (Sticky on Desktop) -->
      <div class="flex-none lg:flex-1 lg:w-2/5 flex flex-col bg-surface-50 dark:bg-surface-900/90 border-t lg:border-t-0 lg:border-l border-surface-200 dark:border-surface-700 z-10 shadow-[-5px_0_15px_-5px_rgba(0,0,0,0.05)]">
        
        <!-- Desktop Close -->
        <div class="hidden lg:flex justify-end p-4 pb-0">
           <button @click="handleClose" class="text-surface-400 hover:text-surface-600 dark:hover:text-surface-200 transition-colors p-2">
               <span class="sr-only">Close</span>
               <i class="fas fa-times text-xl"></i>
           </button>
        </div>

        <div class="flex-1 flex flex-col p-6 lg:p-8 overflow-hidden">
           <h3 class="text-xl font-bold font-display text-surface-900 dark:text-white mb-6 hidden lg:block">Order Summary</h3>

           <!-- Itemized List (Desktop: Scrollable, Mobile: Hidden) -->
           <div class="hidden lg:block flex-1 overflow-y-auto space-y-3 lg:pr-2 custom-scrollbar mb-6">
              <div class="flex justify-between items-start py-2 border-b border-dashed border-surface-200 dark:border-surface-700">
                  <span class="text-sm text-surface-600 dark:text-surface-300">Base Price</span>
                  <span class="text-sm font-semibold text-surface-900 dark:text-white">{{ formatPrice(product?.price ?? 0) }}</span>
              </div>
              
              <div v-if="sizePrice > 0" class="flex justify-between items-start py-2 border-b border-dashed border-surface-200 dark:border-surface-700 animate-fade-in-left">
                  <span class="text-sm text-surface-600 dark:text-surface-300">Size: <span class="font-medium text-primary">{{ sizeOptions.find(s=>s.id===selectedSize)?.name }}</span></span>
                  <span class="text-sm font-semibold text-primary">+{{ formatPrice(sizePrice) }}</span>
              </div>
              
               <div class="flex justify-between items-start py-2 border-b border-dashed border-surface-200 dark:border-surface-700">
                  <span class="text-sm text-surface-600 dark:text-surface-300">Quantity</span>
                  <span class="text-sm font-semibold text-surface-900 dark:text-white">x{{ quantity }}</span>
              </div>
           </div>

           <!-- Totals & Actions -->
           <div class="mt-auto pt-4 border-t border-surface-200 dark:border-surface-700 space-y-4">
              <div class="flex justify-between items-end mb-2">
                 <span class="text-surface-500 dark:text-surface-400 font-medium">Total Amount</span>
                 <span class="text-3xl font-bold text-primary tracking-tight font-display">{{ formatPrice(totalPrice) }}</span>
              </div>

              <button @click="handleAddToCart" type="button"
                  class="w-full bg-primary text-white font-bold text-lg py-4 px-6 rounded-xl hover:bg-primary-600 active:scale-[0.98] transition-all shadow-xl shadow-primary/20 flex items-center justify-center gap-3 group">
                   <i class="fas fa-shopping-cart group-hover:-translate-y-0.5 transition-transform"></i>
                   Add to Order
              </button>
           </div>
        </div>
      </div>
      
    </div>
  </Modal>
</template>