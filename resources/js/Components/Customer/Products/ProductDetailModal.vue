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
  <Modal :show="show" max-width="4xl" @close="handleClose">
    <div class="layout-content-container flex flex-col w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white max-h-[90vh] overflow-hidden">

      <!--  Header  -->
      <div class="flex justify-between items-center p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl sm:text-2xl font-bold">Customize {{ product?.name ?? 'Product' }}</h2>
        <button @click="handleClose" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!--  Body  -->
      <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
        <!--  Left: customisation  -->
        <div class="w-full md:w-3/5 p-4 sm:p-6 overflow-y-auto space-y-4">

          <!--  Size  -->
          <details class="border-t border-gray-200 dark:border-gray-700 py-2 group" open>
            <summary class="flex cursor-pointer items-center justify-between py-2">
              <p class="text-base font-medium">Size</p>
              <i class="fas fa-chevron-down text-sm transition-transform group-open:rotate-180"></i>
            </summary>
            <div class="pb-2 space-y-3 pt-2">
              <label v-for="s in sizeOptions" :key="s.id"
                     class="flex items-center gap-4 p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/10 transition-colors">
                <input v-model="selectedSize" :value="s.id" type="radio" name="size"
                       class="form-radio text-primary focus:ring-primary/50">
                <span class="flex-1">{{ s.name }}</span>
                <span v-if="s.price" class="text-sm font-medium">+{{ formatPrice(s.price) }}</span>
              </label>
            </div>
          </details>

          <!--  Quantity  -->
          <details class="border-t border-gray-200 dark:border-gray-700 py-2 group" open>
            <summary class="flex cursor-pointer items-center justify-between py-2">
              <p class="text-base font-medium">Quantity</p>
              <i class="fas fa-chevron-down text-sm transition-transform group-open:rotate-180"></i>
            </summary>
            <div class="pb-2 pt-2 flex items-center gap-3">
              <button @click="dec" :disabled="quantity<=1"
                      class="w-8 h-8 rounded-full bg-primary/20 text-primary hover:bg-primary/30 disabled:opacity-40">
                <i class="fas fa-minus text-xs"></i>
              </button>
              <span class="font-medium w-8 text-center">{{ quantity }}</span>
              <button @click="inc"
                      class="w-8 h-8 rounded-full bg-primary/20 text-primary hover:bg-primary/30">
                <i class="fas fa-plus text-xs"></i>
              </button>
            </div>
          </details>

        </div>

        <!--  Right: summary  -->
        <div class="w-full md:w-2/5 p-4 sm:p-6 bg-gray-50 dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col">
          <div class="flex-1 space-y-4">
            <!--  image  -->
            <div class="w-full bg-center bg-no-repeat bg-cover rounded-lg min-h-48"
                 :style="{backgroundImage:`url('${product?.image}')`}"></div>

            <!--  name + rating + description  -->
            <div>
              <h3 class="text-lg font-bold">{{ product?.name }}</h3>
              <div class="flex items-center gap-2 mt-1 text-sm">
                <div class="flex items-center gap-1">
                  <i class="fas fa-star text-yellow-500"></i>
                  <span class="font-medium text-gray-900">{{ Number(product?.rating ?? 0).toFixed(1) }}</span>
                </div>
                <span class="text-gray-500">({{ product?.reviewCount ?? 0 }} reviews)</span>
              </div>
              <p class="text-sm text-gray-600 mt-2 leading-relaxed">{{ product?.description }}</p>
            </div>

            <!--  price breakdown  -->
            <div class="space-y-2 border-b border-gray-200 dark:border-gray-700 pb-3">
              <div class="flex justify-between gap-x-6">
                <p class="text-sm">Base Price</p>
                <p class="text-sm text-right">{{ formatPrice(product?.price ?? 0) }}</p>
              </div>
              <div v-if="sizePrice>0" class="flex justify-between gap-x-6">
                <p class="text-sm">{{ sizeOptions.find(s=>s.id===selectedSize)?.name }}</p>
                <p class="text-sm text-right">+{{ formatPrice(sizePrice) }}</p>
              </div>
            </div>

            <!--  total  -->
            <div class="flex justify-between gap-x-6 pt-1">
              <p class="text-base font-bold">Total (×{{ quantity }})</p>
              <p class="text-lg font-bold text-right">{{ formatPrice(totalPrice) }}</p>
            </div>
          </div>

          <!--  buttons  -->
          <div class="mt-6 space-y-3">
            <button @click="handleAddToCart"
                    class="w-full bg-primary text-white font-bold py-3 px-6 rounded-lg hover:bg-primary/90 transition-colors">
              Add {{ quantity }} to Cart
            </button>
            <button @click="handleClose"
                    class="w-full border border-gray-300 dark:border-gray-600 font-medium py-3 px-6 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </Modal>
</template>