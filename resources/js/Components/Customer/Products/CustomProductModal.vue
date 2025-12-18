<script setup>
import { computed, ref, watch } from 'vue';
import Modal from '../../Shared/Overlay/Modal.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    product: {
        type: Object,
        default: () => ({
            name: 'Caramel Macchiato',
            price: 4.75,
            image: null,
            size_labels: ['Small', 'Medium', 'Large'],
            addons: []
        })
    }
});

const emit = defineEmits(['close', 'add-to-cart']);

const selectedSize = ref(null);
const selectedAddons = ref({});

const sizeOptions = computed(() => {
    const labels = props.product?.size_labels || ['Small', 'Medium', 'Large'];
    const prices = {
        'Small': 0,
        'Medium': 0.50,
        'Large': 1.00,
        'Regular': 0,
        'XS': 0,
        'S': 0.25,
        'M': 0.50,
        'L': 0.75,
        'XL': 1.00
    };
    
    return labels.reduce((acc, label, index) => {
        acc[label.toLowerCase().replace(/\s+/g, '_')] = {
            name: label,
            price: prices[label] ?? (index * 0.50)
        };
        return acc;
    }, {});
});

const groupedAddons = computed(() => {
    const addons = props.product?.addons || [];
    const groups = {};
    
    addons.forEach(addon => {
        if (!addon.available) return;
        const category = addon.category || 'Extras';
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(addon);
    });
    
    return groups;
});

const hasAddons = computed(() => {
    return Object.keys(groupedAddons.value).length > 0;
});

const getCategoryIcon = (category) => {
    const icons = {
        'Milk': 'fa-droplet',
        'Extras': 'fa-plus-circle',
        'Toppings': 'fa-cookie-bite',
        'Syrups': 'fa-flask',
        'Sweeteners': 'fa-cubes'
    };
    return icons[category] || 'fa-circle-plus';
};

const sizePrice = computed(() => {
    if (!selectedSize.value) return 0;
    return sizeOptions.value[selectedSize.value]?.price ?? 0;
});

const addonsPrice = computed(() => {
    let total = 0;
    Object.entries(selectedAddons.value).forEach(([addonId, quantity]) => {
        const addon = props.product?.addons?.find(a => a.id === parseInt(addonId));
        if (addon && quantity > 0) {
            total += (addon.price_formatted || addon.price / 100) * quantity;
        }
    });
    return total;
});

const totalPrice = computed(() => {
    const base = props.product?.price_formatted ?? props.product?.price ?? 0;
    return base + sizePrice.value + addonsPrice.value;
});

const handleClose = () => emit('close');

const addToOrder = () => {
    const selectedAddonsList = [];
    Object.entries(selectedAddons.value).forEach(([addonId, quantity]) => {
        if (quantity > 0) {
            const addon = props.product?.addons?.find(a => a.id === parseInt(addonId));
            if (addon) {
                selectedAddonsList.push({
                    id: addon.id,
                    name: addon.name,
                    quantity: quantity,
                    price: addon.price_formatted || addon.price / 100
                });
            }
        }
    });

    emit('add-to-cart', {
        ...props.product,
        customizations: {
            size: selectedSize.value ? sizeOptions.value[selectedSize.value]?.name : null,
            sizeKey: selectedSize.value,
            addons: selectedAddonsList
        },
        totalPrice: totalPrice.value
    });
    handleClose();
};

const incrementAddon = (addonId, maxQty) => {
    const current = selectedAddons.value[addonId] || 0;
    if (current < maxQty) {
        selectedAddons.value[addonId] = current + 1;
    }
};

const decrementAddon = (addonId) => {
    const current = selectedAddons.value[addonId] || 0;
    if (current > 0) {
        selectedAddons.value[addonId] = current - 1;
    }
};

const getAddonQuantity = (addonId) => {
    return selectedAddons.value[addonId] || 0;
};

const formatCurrency = v => `₱${v.toFixed(2)}`;

watch(() => props.show, v => {
    if (!v) return;
    const sizeKeys = Object.keys(sizeOptions.value);
    selectedSize.value = sizeKeys.length > 1 ? sizeKeys[1] : sizeKeys[0];
    selectedAddons.value = {};
});
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
          <img :src="product.image_url || product.image" :alt="product.name" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent lg:hidden"></div>
           <div class="absolute bottom-4 left-4 lg:hidden text-white drop-shadow-md">
              <h2 class="text-2xl font-bold leading-tight">{{ product.name }}</h2>
              <p class="text-sm opacity-90 line-clamp-1">{{ product.description }}</p>
           </div>
        </div>

        <!-- Scrollable Options -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-8 bg-surface-50/50 dark:bg-surface-900">
           <!-- Title (Desktop) -->
           <div class="hidden lg:block">
              <h2 class="text-3xl font-bold font-display text-surface-900 dark:text-white mb-2">{{ product.name }}</h2>
              <p class="text-surface-600 dark:text-surface-400 leading-relaxed max-w-2xl">{{ product.description }}</p>
           </div>

           <!-- Sizes -->
           <div v-if="Object.keys(sizeOptions).length > 0" class="animate-fade-in-up" style="animation-delay: 0.1s;">
              <div class="flex items-center justify-between mb-4">
                 <h3 class="text-lg font-bold text-surface-900 dark:text-white flex items-center gap-2">
                    <span class="w-1 h-6 bg-primary rounded-full"></span>
                    Select Size
                 </h3>
                 <span class="text-xs font-semibold text-primary bg-primary/10 px-2 py-1 rounded-full uppercase tracking-wider">Required</span>
              </div>
              
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <label v-for="(d, k) in sizeOptions" :key="k"
                       class="relative flex flex-col items-center justify-center p-4 border rounded-xl cursor-pointer transition-all duration-200 group hover:shadow-md"
                       :class="selectedSize === k 
                         ? 'border-primary bg-primary/5 dark:bg-primary/10 ring-1 ring-primary ring-opacity-50' 
                         : 'border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-800 hover:border-surface-300 dark:hover:border-surface-600'">
                  <input v-model="selectedSize" :value="k" type="radio" name="size" class="sr-only">
                  <div class="w-full text-center">
                     <span class="block text-sm font-bold mb-1 group-hover:text-primary transition-colors" :class="selectedSize === k ? 'text-primary' : 'text-surface-900 dark:text-white'">{{ d.name }}</span>
                     <span class="block text-xs font-medium" :class="selectedSize === k ? 'text-primary' : 'text-surface-500'">
                       {{ d.price > 0 ? '+' + formatCurrency(d.price) : 'Free' }}
                     </span>
                  </div>
                  <!-- Checkmark -->
                  <div v-if="selectedSize === k" class="absolute top-2 right-2 text-primary">
                    <i class="fas fa-check-circle text-sm"></i>
                  </div>
                </label>
              </div>
           </div>

           <!-- Addons -->
           <div v-if="hasAddons" class="space-y-6 animate-fade-in-up" style="animation-delay: 0.2s;">
              <div v-for="(addons, category) in groupedAddons" :key="category" class="p-5 bg-white dark:bg-surface-800/50 rounded-2xl border border-surface-100 dark:border-surface-800 shadow-sm">
                <h3 class="text-lg font-bold text-surface-900 dark:text-white mb-4 flex items-center gap-2">
                  <i :class="['fas', getCategoryIcon(category), 'text-primary']"></i>
                  {{ category }}
                </h3>
                <div class="space-y-3">
                  <div v-for="addon in addons" :key="addon.id"
                       class="flex items-center justify-between p-3 rounded-xl transition-all hover:bg-surface-50 dark:hover:bg-surface-700/50 group"
                       :class="getAddonQuantity(addon.id) > 0 ? 'bg-primary/5 dark:bg-primary/10 border-transparent' : 'bg-transparent'">
                    
                    <!-- Addon Info -->
                    <div class="flex-1 pr-4">
                      <div class="flex items-baseline gap-2">
                         <span class="font-medium text-surface-900 dark:text-white group-hover:text-primary transition-colors">{{ addon.name }}</span>
                         <span class="text-xs font-bold text-primary bg-primary/10 px-1.5 py-0.5 rounded">+{{ formatCurrency(addon.price_formatted || addon.price / 100) }}</span>
                      </div>
                      <p v-if="addon.description" class="text-xs text-surface-500 dark:text-surface-400 mt-0.5 line-clamp-1">{{ addon.description }}</p>
                    </div>

                    <!-- Quantity Controls -->
                    <div class="flex items-center gap-3 bg-white dark:bg-surface-900 rounded-lg shadow-sm border border-surface-200 dark:border-surface-700 p-1">
                      <button @click="decrementAddon(addon.id)" 
                              type="button"
                              class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-surface-100 dark:hover:bg-surface-700 text-surface-600 dark:text-surface-300 transition-colors disabled:opacity-30"
                              :disabled="getAddonQuantity(addon.id) === 0">
                        <i class="fas fa-minus text-xs"></i>
                      </button>
                      <span class="text-sm font-bold w-4 text-center text-surface-900 dark:text-white">{{ getAddonQuantity(addon.id) }}</span>
                      <button @click="incrementAddon(addon.id, addon.max_quantity || 5)" 
                              type="button"
                              class="w-8 h-8 flex items-center justify-center rounded-md hover:bg-surface-100 dark:hover:bg-surface-700 text-primary transition-colors disabled:opacity-30"
                              :disabled="getAddonQuantity(addon.id) >= (addon.max_quantity || 5)">
                        <i class="fas fa-plus text-xs"></i>
                      </button>
                    </div>

                  </div>
                </div>
              </div>
           </div>

           <!-- No Options Fallback -->
           <div v-if="!hasAddons && Object.keys(sizeOptions).length === 0" class="text-center py-12">
              <div class="w-16 h-16 rounded-full bg-surface-100 dark:bg-surface-800 mx-auto flex items-center justify-center mb-4 text-surface-400">
                  <i class="fas fa-star text-2xl"></i>
              </div>
              <p class="text-surface-900 dark:text-white font-medium">No customizations available</p>
              <p class="text-sm text-surface-500">This item comes as is.</p>
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

           <!-- Itemized List (Desktop: Scrollable, Mobile: Hidden/Compact) -->
           <div class="hidden lg:block flex-1 overflow-y-auto space-y-3 lg:pr-2 custom-scrollbar mb-6">
              <!-- Base Item -->
              <div class="flex justify-between items-start py-2 border-b border-dashed border-surface-200 dark:border-surface-700">
                  <span class="text-sm text-surface-600 dark:text-surface-300">Base Price</span>
                  <span class="text-sm font-semibold text-surface-900 dark:text-white">{{ formatCurrency(product?.price_formatted ?? product?.price ?? 0) }}</span>
              </div>
              
              <!-- Size -->
              <div v-if="sizePrice > 0" class="flex justify-between items-start py-2 border-b border-dashed border-surface-200 dark:border-surface-700 animate-fade-in-left">
                  <span class="text-sm text-surface-600 dark:text-surface-300">Size: <span class="font-medium text-primary">{{ sizeOptions[selectedSize]?.name }}</span></span>
                  <span class="text-sm font-semibold text-primary">+{{ formatCurrency(sizePrice) }}</span>
              </div>

               <!-- Addons List -->
               <transition-group name="list" tag="div" class="space-y-2">
                  <template v-for="(quantity, addonId) in selectedAddons" :key="addonId">
                    <div v-if="quantity > 0" class="flex justify-between items-start py-2 border-b border-dashed border-surface-200 dark:border-surface-700">
                      <div class="flex flex-col">
                         <span class="text-sm text-surface-600 dark:text-surface-300">{{ product.addons?.find(a => a.id === parseInt(addonId))?.name }}</span>
                         <span class="text-xs text-surface-400">x{{ quantity }}</span>
                      </div>
                      <span class="text-sm font-semibold text-primary">
                        +{{ formatCurrency((product.addons?.find(a => a.id === parseInt(addonId))?.price_formatted || 0) * quantity) }}
                      </span>
                    </div>
                  </template>
               </transition-group>
               
               <div v-if="addonsPrice === 0 && sizePrice === 0" class="text-center py-4 text-xs text-surface-400 italic">
                  No customizations selected
               </div>
           </div>

           <!-- Totals & Actions -->
           <div class="mt-auto pt-4 border-t border-surface-200 dark:border-surface-700 space-y-4">
              <div class="flex justify-between items-end mb-2">
                 <span class="text-surface-500 dark:text-surface-400 font-medium">Total Amount</span>
                 <span class="text-3xl font-bold text-primary tracking-tight font-display">{{ formatCurrency(totalPrice) }}</span>
              </div>

              <button @click="addToOrder" type="button"
                  class="w-full bg-primary text-white font-bold text-lg py-4 px-6 rounded-xl hover:bg-primary-600 active:scale-[0.98] transition-all shadow-xl shadow-primary/20 flex items-center justify-center gap-3 group">
                   <i class="fas fa-shopping-bag group-hover:-translate-y-0.5 transition-transform"></i>
                   Add to Order
              </button>
           </div>
        </div>
      </div>
      
    </div>
  </Modal>
</template>
