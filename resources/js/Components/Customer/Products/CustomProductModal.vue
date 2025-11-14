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
            image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAWWVCNFSHrPEl0siJRGmzZ2Uy5IfyLh_EXrYFe4BxeZ56_r_V3PWt2IzifrjZJpL3ulfexN9cuFP2hO0G7wPbap9-nZ5oJp6ORy4Qetogp5Nm6LU_G5xqEmLlVgeI_UvAgVGLqdhRfi7DULFYUsxaZk8XeFX3xYWiQdClhK4p3YBL1s3P27Q0WBn_1u0XaYZF6vi_zqSlNsQlQOx7kd48C58uzs6ZT_gtSr6kXhm4MCkdv_P9rcX_oeoEHsedvDwet6zGFiGWCMGU '
        })
    }
});

const emit = defineEmits(['close', 'add-to-cart']);

/* ----------  state  ---------- */
const selectedSize = ref('medium');
const selectedMilk = ref('whole');
const vanillaPumps = ref(2);
const caramelPumps = ref(0);

/* ----------  options  ---------- */
const sizeOptions = {
    small:  { name: 'Small',  price: 0.00 },
    medium: { name: 'Medium', price: 0.50 },
    large:  { name: 'Large',  price: 1.00 }
};
const milkOptions = {
    whole: { name: 'Whole Milk', price: 0.00 },
    almond: { name: 'Almond Milk', price: 0.80 },
    oat:    { name: 'Oat Milk',   price: 0.80 }
};
const syrupOptions = {
    vanilla: { name: 'Pumps Vanilla Syrup', pricePerPump: 0.25 },
    caramel: { name: 'Pumps Caramel Syrup', pricePerPump: 0.25 }
};

/* ----------  prices  ---------- */
const sizePrice    = computed(() => sizeOptions[selectedSize.value]?.price   ?? 0);
const milkPrice    = computed(() => milkOptions[selectedMilk.value]?.price   ?? 0);
const vanillaPrice = computed(() => vanillaPumps.value * syrupOptions.vanilla.pricePerPump);
const caramelPrice = computed(() => caramelPumps.value * syrupOptions.caramel.pricePerPump);
const totalPrice   = computed(() => {
    const base = props.product?.price ?? 0;
    return base + sizePrice.value + milkPrice.value + vanillaPrice.value + caramelPrice.value;
});

/* ----------  methods  ---------- */
const handleClose = () => emit('close');
const addToOrder  = () => {
    emit('add-to-cart', {
        ...props.product,
        customizations: {
            size: selectedSize.value,
            milk: selectedMilk.value,
            syrups: { vanilla: vanillaPumps.value, caramel: caramelPumps.value }
        },
        totalPrice: totalPrice.value
    });
    handleClose();
};
const incSyrup = s => { if (s==='vanilla') vanillaPumps++; if (s==='caramel') caramelPumps++; };
const decSyrup = s => {
    if (s==='vanilla' && vanillaPumps.value>0) vanillaPumps--;
    if (s==='caramel' && caramelPumps.value>0) caramelPumps--;
};
const formatCurrency = v => `$${v.toFixed(2)}`;

/* ----------  reset on open  ---------- */
watch(() => props.show, v => {
    if (!v) return;
    selectedSize.value = 'medium';
    selectedMilk.value = 'whole';
    vanillaPumps.value = 2;
    caramelPumps.value = 0;
});
</script>

<template>
  <Modal :show="show" max-width="4xl" @close="handleClose">
    <div class="layout-content-container flex flex-col w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white max-h-[90vh] overflow-hidden">

      <!--  Header  -->
      <div class="flex justify-between items-center p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl sm:text-2xl font-bold">Customize {{ product.name }}</h2>
        <button @click="handleClose" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!--  Body  -->
      <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
        <!--  Left: customisation  -->
        <div class="w-full md:w-3/5 p-4 sm:p-6 overflow-y-auto">
          <!--  Size  -->
          <details class="border-t border-gray-200 dark:border-gray-700 py-2 group" open>
            <summary class="flex cursor-pointer items-center justify-between py-2">
              <p class="text-base font-medium">Size</p>
              <i class="fas fa-chevron-down text-sm transition-transform group-open:rotate-180"></i>
            </summary>
            <div class="pb-2 space-y-3 pt-2">
              <label v-for="(d,k) in sizeOptions" :key="k"
                     class="flex items-center gap-4 p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/10">
                <input v-model="selectedSize" :value="k" type="radio" name="size" class="form-radio text-primary focus:ring-primary/50">
                <span class="flex-1">{{ d.name }}</span>
                <span class="text-sm font-medium">+{{ formatCurrency(d.price) }}</span>
              </label>
            </div>
          </details>

          <!--  Milk  -->
          <details class="border-t border-gray-200 dark:border-gray-700 py-2 group" open>
            <summary class="flex cursor-pointer items-center justify-between py-2">
              <p class="text-base font-medium">Milk Options</p>
              <i class="fas fa-chevron-down text-sm transition-transform group-open:rotate-180"></i>
            </summary>
            <div class="pb-2 space-y-3 pt-2">
              <label v-for="(d,k) in milkOptions" :key="k"
                     class="flex items-center gap-4 p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/10">
                <input v-model="selectedMilk" :value="k" type="radio" name="milk" class="form-radio text-primary focus:ring-primary/50">
                <span class="flex-1">{{ d.name }}</span>
                <span class="text-sm font-medium">+{{ formatCurrency(d.price) }}</span>
              </label>
            </div>
          </details>

          <!--  Syrups  -->
          <details class="border-t border-gray-200 dark:border-gray-700 py-2 group" open>
            <summary class="flex cursor-pointer items-center justify-between py-2">
              <p class="text-base font-medium">Syrups & Sweeteners</p>
              <i class="fas fa-chevron-down text-sm transition-transform group-open:rotate-180"></i>
            </summary>
            <div class="pb-2 space-y-3 pt-2">
              <div class="flex items-center justify-between gap-4 p-3 border border-gray-300 dark:border-gray-600 rounded-lg">
                <span class="flex-1">Vanilla Syrup</span>
                <div class="flex items-center gap-3">
                  <button @click="decSyrup('vanilla')" class="w-7 h-7 rounded-full bg-primary/20 text-primary hover:bg-primary/30"><i class="fas fa-minus text-xs"></i></button>
                  <span class="font-medium w-4 text-center">{{ vanillaPumps }}</span>
                  <button @click="incSyrup('vanilla')" class="w-7 h-7 rounded-full bg-primary/20 text-primary hover:bg-primary/30"><i class="fas fa-plus text-xs"></i></button>
                </div>
              </div>
              <div class="flex items-center justify-between gap-4 p-3 border border-gray-300 dark:border-gray-600 rounded-lg">
                <span class="flex-1">Caramel Syrup</span>
                <div class="flex items-center gap-3">
                  <button @click="decSyrup('caramel')" class="w-7 h-7 rounded-full bg-primary/20 text-primary hover:bg-primary/30"><i class="fas fa-minus text-xs"></i></button>
                  <span class="font-medium w-4 text-center">{{ caramelPumps }}</span>
                  <button @click="incSyrup('caramel')" class="w-7 h-7 rounded-full bg-primary/20 text-primary hover:bg-primary/30"><i class="fas fa-plus text-xs"></i></button>
                </div>
              </div>
            </div>
          </details>
        </div>

        <!--  Right: summary (FIRST-modal style)  -->
        <div class="w-full md:w-2/5 p-4 sm:p-6 bg-gray-50 dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col">
          <div class="flex-1 space-y-4">
            <!--  image  -->
            <div class="w-full bg-center bg-no-repeat bg-cover rounded-lg min-h-48"
                 :style="{backgroundImage:`url('${product.image}')`}"></div>

            <!--  name + description (like first modal)  -->
            <div>
              <h3 class="text-lg font-bold">{{ product.name }}</h3>
              <p class="text-sm text-gray-600 leading-relaxed mt-2">{{ product.description }}</p>
            </div>

            <!--  price breakdown  -->
            <div class="space-y-2 border-b border-gray-200 dark:border-gray-700 pb-3">
              <div class="flex justify-between gap-x-6">
                <p class="text-sm">{{ product.name }}</p>
                <p class="text-sm text-right">{{ formatCurrency(product.price) }}</p>
              </div>
              <div v-if="sizePrice>0" class="flex justify-between gap-x-6">
                <p class="text-sm">{{ sizeOptions[selectedSize].name }}</p>
                <p class="text-sm text-right">+{{ formatCurrency(sizePrice) }}</p>
              </div>
              <div v-if="milkPrice>0" class="flex justify-between gap-x-6">
                <p class="text-sm">{{ milkOptions[selectedMilk].name }}</p>
                <p class="text-sm text-right">+{{ formatCurrency(milkPrice) }}</p>
              </div>
              <div v-if="vanillaPumps>0" class="flex justify-between gap-x-6">
                <p class="text-sm">{{ vanillaPumps }} {{ syrupOptions.vanilla.name }}</p>
                <p class="text-sm text-right">+{{ formatCurrency(vanillaPrice) }}</p>
              </div>
              <div v-if="caramelPumps>0" class="flex justify-between gap-x-6">
                <p class="text-sm">{{ caramelPumps }} {{ syrupOptions.caramel.name }}</p>
                <p class="text-sm text-right">+{{ formatCurrency(caramelPrice) }}</p>
              </div>
            </div>

            <!--  total  -->
            <div class="flex justify-between gap-x-6 pt-1">
              <p class="text-base font-bold">Total</p>
              <p class="text-lg font-bold text-right">{{ formatCurrency(totalPrice) }}</p>
            </div>
          </div>

          <!--  button  -->
          <div class="mt-6">
            <button @click="addToOrder"
                    class="w-full bg-primary text-white font-bold py-3 px-6 rounded-lg hover:bg-primary/90 transition-colors">
              Add to Order
            </button>
          </div>
        </div>
      </div>
    </div>
  </Modal>
</template>