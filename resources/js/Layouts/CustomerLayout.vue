<script setup>
import { ref } from 'vue'
import '../../css/pages/mainpage.css';
import '../../css/app.css';
import SidebarStats from '../Components/Customer/Sidebar/SidebarStats.vue';
import SidebarLogo from '../Components/Customer/Sidebar/SidebarLogo.vue';
import SidebarOrderSummary from '../Components/Customer/Sidebar/SidebarOrderSummary.vue';
import SidebarQuickActions from '../Components/Customer/Sidebar/SidebarQuickActions.vue';
import SidebarNavigation from '../Components/Customer/Sidebar/SidebarNavigation.vue';
import CafeHeader from '../Components/Customer/Header/CafeHeader.vue';
import CheckoutModal from '../Components/Customer/Checkout/CheckoutModal.vue';
import FloatingTrackOrderButton from '../Components/Customer/UI/FloatingTrackOrderButton.vue';
import CartBottomSheet from '../Components/Customer/Cart/CartBottomSheet.vue';
import { useCheckout } from '../composables/useCheckout.js';

// Props for customization
const props = defineProps({
  showHeader: {
    type: Boolean,
    default: true
  },
  headerClass: {
    type: String,
    default: 'sm:mb-6 mb-4'
  },
  categories: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['category-selected']);

// Checkout functionality
const { createOrder, navigateToPayment } = useCheckout()
const showCheckoutModal = ref(false)
const isCartOpen = ref(false)

const handleCategorySelected = (category) => {
  emit('category-selected', category);
};

const handleCheckout = () => {
  showCheckoutModal.value = true
}

const handleCloseCheckout = () => {
  showCheckoutModal.value = false
}

const handleProceedToPayment = (orderData) => {
  const order = createOrder(orderData)
  showCheckoutModal.value = false
  navigateToPayment(order)
}

// Define slots that can be used
defineSlots();
</script>

<template>
  <div class="quest-page">
    <div class="relative flex h-auto min-h-screen w-full flex-col justify-between overflow-x-hidden">
      <!-- Sidebar -->
      <aside
        class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:block lg:w-80 lg:overflow-y-auto lg:bg-white/80 lg:backdrop-blur-xl lg:border-r lg:border-gray-200"
        id="desktopSidebar">
        <div class="flex flex-col h-full p-6">
          <SidebarLogo />
          <SidebarStats />
          <SidebarNavigation 
            v-if="categories.length > 0"
            :categories="categories" 
            @category-selected="handleCategorySelected" 
          />
          <SidebarQuickActions />
          <SidebarOrderSummary @checkout="handleCheckout" />
        </div>
      </aside>
     
      <!-- Main Content Area -->
      <div class="flex flex-col lg:pl-80">
        <main class="flex-1 overflow-y-auto scroll-smooth momentum-scroll">
          <!-- Header (optional) -->
          <CafeHeader 
            v-if="showHeader" 
            :class="headerClass" 
            :categories="categories"
            @category-selected="handleCategorySelected"
            @open-checkout="handleCheckout"
            @open-cart="isCartOpen = true"
          />
          
          <!-- Page Content -->
          <slot />
        </main>
      </div>
    </div>

    <!-- Checkout Modal -->
    <CheckoutModal 
      :show="showCheckoutModal"
      @close="handleCloseCheckout"
      @proceed-to-payment="handleProceedToPayment"
    />

    <!-- Cart Bottom Sheet -->
    <CartBottomSheet 
      :show="isCartOpen"
      @close="isCartOpen = false"
      @checkout="handleCheckout"
    />

    <!-- Mobile-only Floating Track Order Button -->
    <FloatingTrackOrderButton @open-cart="isCartOpen = true" />
  </div>
</template>