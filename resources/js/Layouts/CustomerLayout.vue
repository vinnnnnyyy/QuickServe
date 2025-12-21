<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'           
import '../../css/pages/mainpage.css';
import '../../css/app.css';
import SidebarLogo from '../Components/Customer/Sidebar/SidebarLogo.vue';
import SidebarOrderSummary from '../Components/Customer/Sidebar/SidebarOrderSummary.vue';
import SidebarQuickActions from '../Components/Customer/Sidebar/SidebarQuickActions.vue';
import SidebarNavigation from '../Components/Customer/Sidebar/SidebarNavigation.vue';
import SidebarVoiceOrder from '../Components/Customer/Sidebar/SidebarVoiceOrder.vue';
import CafeHeader from '../Components/Customer/Header/CafeHeader.vue';
import CheckoutModal from '../Components/Customer/Checkout/CheckoutModal.vue';
import FloatingTrackOrderButton from '../Components/Customer/UI/FloatingTrackOrderButton.vue';
import AddToCartToast from '../Components/Customer/UI/AddToCartToast.vue';
import CartBottomSheet from '../Components/Customer/Cart/CartBottomSheet.vue';
import VoiceOrderModal from '../Components/Customer/Voice/VoiceOrderModal.vue';
import BottomNavigation from '../Components/Customer/Navigation/BottomNavigation.vue';
import HostControlPanel from '../Components/Customer/Group/HostControlPanel.vue';
import WaitingRoom from '../Pages/Customer/Group/WaitingRoom.vue';
import JoinSessionPrompt from '../Components/Customer/Modals/JoinSessionPrompt.vue';
import { useCheckout } from '../composables/useCheckout.js';
import { useOrderHistory } from '../composables/useOrderHistory.js';
import { usePage } from '@inertiajs/vue3'

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
  },
});

const emit = defineEmits(['category-selected']);

// Checkout functionality
const { createOrder, navigateToPayment } = useCheckout()
const { initializeOrders } = useOrderHistory()
// Initialize cart
import { useCart } from '../composables/useCart.js';
const { setMode, refreshCart } = useCart();

const showCheckoutModal = ref(false)
const isCartOpen = ref(false)
const showVoiceModal = ref(false)
const page = usePage()
const paymentMode = ref(page.props.paymentMode || 'host')
const joinRequestSent = ref(false)
const userChangedToIndividual = ref(false)
const showJoinPrompt = ref(false)

// Group Session State
const isHost = ref(false);
const myStatus = ref('approved'); // approved, pending, rejected
const sessionUsers = ref([]);
const tableId = computed(() => page.props.tableId);
const isPendingApproval = computed(() => myStatus.value === 'pending');
const hostName = computed(() => {
    // Attempt to find host name in users list
    const hostUser = sessionUsers.value.find(u => u.role === 'host');
    return hostUser ? hostUser.name : 'Host';
});

// Heartbeat & Cart Polling
let heartbeatInterval = null
let cartPollingInterval = null
let sessionPollingInterval = null;

const startPolling = () => {
    const session = page.props.tableSession
    const sessionId = session ? session.id : null

    // 1. Heartbeat
    if (sessionId) {
        pingServer(sessionId)
        heartbeatInterval = setInterval(() => {
            pingServer(sessionId)
        }, 30000)
    }

    // 2. Shared Cart & Session Status Polling
    // Only start polling if we haven't explicitly opted out (Individual Mode)
    if (session && session.payment_mode === 'host' && !userChangedToIndividual.value) {
        console.log('Initializing One Bill (Shared Cart) Mode')
        setMode('table')
        
        // Initial fetches
        refreshCart()
        checkSessionStatus()

        // Poll every 5 seconds
        cartPollingInterval = setInterval(() => {
            if (!userChangedToIndividual.value) {
                refreshCart()
                checkSessionStatus()
            }
        }, 5000)
    } else {
        setMode('local')
    }
}

const checkSessionStatus = async () => {
    if (!tableId.value || userChangedToIndividual.value) return;
    try {
        const response = await axios.get(`/api/session/${tableId.value}/status`);
        isHost.value = response.data.is_host;
        
        // If I am the host, I am implicitly approved
        if (isHost.value) {
             myStatus.value = 'approved';
        } else {
             myStatus.value = response.data.my_status;
        }

        sessionUsers.value = response.data.users;
        if (response.data.payment_mode) {
            paymentMode.value = response.data.payment_mode;
        }

        // Show Join Prompt if active host session exists, I'm unknown, and haven't chosen yet
        if (paymentMode.value === 'host' && myStatus.value === 'unknown' && !joinRequestSent.value && !userChangedToIndividual.value) {
            showJoinPrompt.value = true;
        }
        
    } catch (error) {
        console.error('Failed to check session status:', error);
    }
}

const joinSession = async () => {
  try {
    joinRequestSent.value = true;
    showJoinPrompt.value = false;
    await axios.post('/api/session/join-request', {
      table_id: tableId.value,
      customer_name: 'Guest' // Could prompt for name later
    })
    // Immediately check status to update UI to "Pending"
    checkSessionStatus();
  } catch (err) {
    console.error('Join failed:', err)
    joinRequestSent.value = false;
    alert('Failed to send join request. Please try again.');
  }
}

const handleIndividualSession = () => {
    userChangedToIndividual.value = true;
    showJoinPrompt.value = false;
    paymentMode.value = 'individual'; // Explicitly set mode
    setMode('local'); // Switch to local cart
    // Clear any polling intervals that rely on shared state? 
    // Actually keep heartbeat but stop cart syncing
}

const handleCancelRequest = async () => {
    try {
        await axios.post('/api/session/disconnect'); // Removes user from session in backend
        joinRequestSent.value = false;
        myStatus.value = 'unknown'; // Reset status
        
        // Re-show prompt or let them choose?
        // Let's show the prompt again so they can choose Individual this time
        showJoinPrompt.value = true; 
        
    } catch (err) {
        console.error('Cancel failed', err);
    }
}


const pingServer = async (sessionId) => {
  try {
    if (document.visibilityState === 'visible') {
      await window.axios.put(`/api/table-sessions/${encodeURIComponent(sessionId)}/activity`, {
        activity: 'Active'
      })
    }
  } catch (err) {
    console.error('Heartbeat failed', err)
  }
}

onMounted(() => {
  initializeOrders()
  startPolling()
})

onUnmounted(() => {
  if (heartbeatInterval) clearInterval(heartbeatInterval)
  if (cartPollingInterval) clearInterval(cartPollingInterval)
})

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
  const data = {
    ...orderData,
    paymentMode: paymentMode.value
  }
  const order = createOrder(data)
  showCheckoutModal.value = false
  navigateToPayment(order)
}

const openVoiceModal = () => {
  showVoiceModal.value = true
}

const closeVoiceModal = () => {
  showVoiceModal.value = false
}

const handleVoiceAddToCart = (transcript) => {
  console.log('Voice order:', transcript)
}

// Define slots that can be used
defineSlots();
</script>

<template>
  <div class="quest-page">
    <WaitingRoom 
        v-if="isPendingApproval" 
        :customer-name="page.props.customerType?.name || 'Guest'" 
        :table-number="page.props.tableNumber"
        @cancel="handleCancelRequest"
    />

    <div v-else class="relative flex h-auto min-h-screen w-full flex-col justify-between overflow-x-hidden">
      <!-- Sidebar -->
      <aside
        class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:block lg:w-72 lg:overflow-y-auto lg:bg-white lg:border-r lg:border-surface-100"
        id="desktopSidebar">
        <div class="flex flex-col h-full px-5 py-6">
          <SidebarLogo />
          
          <!-- Host Control Panel (Desktop) -->
          <HostControlPanel 
              v-if="isHost" 
              :users="sessionUsers" 
              :tableId="tableId"
              :currentPaymentMode="paymentMode"
              @refresh="checkSessionStatus" 
          />

          <SidebarNavigation 
            v-if="categories.length > 0"
            :categories="categories" 
            @category-selected="handleCategorySelected" 
          />
          <SidebarQuickActions />
          <SidebarVoiceOrder @open-voice-modal="openVoiceModal" />
          <SidebarOrderSummary @checkout="handleCheckout" />
        </div>
      </aside>
     
      <!-- Main Content Area -->
      <div class="flex flex-col lg:pl-72">
        <main class="flex-1 overflow-y-auto scroll-smooth momentum-scroll pb-20 lg:pb-0">
          <!-- Header (optional) -->
          <CafeHeader 
            v-if="showHeader" 
            :class="headerClass" 
            :categories="categories"
            @category-selected="handleCategorySelected"
            @open-checkout="handleCheckout"
            @open-cart="isCartOpen = true"
          />
          
          <!-- Mobile Host Panel -->
           <div class="lg:hidden px-4 mb-4" v-if="isHost">
              <HostControlPanel 
                  :users="sessionUsers" 
                  :tableId="tableId"
                  :currentPaymentMode="paymentMode"
                  @refresh="checkSessionStatus" 
              />
           </div>

          <!-- Page Content -->
          <slot />
        </main>
      </div>
    </div>

    <!-- Checkout Modal -->
    <CheckoutModal 
      :show="showCheckoutModal"
      :payment-mode="paymentMode"
      @close="handleCloseCheckout"
      @proceed-to-payment="handleProceedToPayment"
    />

    <!-- Cart Bottom Sheet -->
    <CartBottomSheet 
      :show="isCartOpen"
      :payment-mode="paymentMode"
      :is-host-user="isHost"
      @close="isCartOpen = false"
      @checkout="handleCheckout"
    />

    <!-- Mobile-only Floating Track Order Button -->
    <FloatingTrackOrderButton @open-cart="isCartOpen = true" />

    <!-- Mobile-only Floating Voice Order Button (hidden - using bottom nav instead) -->
    <button
      @click="openVoiceModal"
      class="hidden fixed bottom-24 right-6 z-40 w-14 h-14 bg-gradient-to-r from-primary to-primary/80 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 items-center justify-center"
      aria-label="Voice Order"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
      </svg>
    </button>

    <!-- Voice Order Modal -->
    <VoiceOrderModal 
      :show="showVoiceModal"
      @close="closeVoiceModal"
      @add-to-cart="handleVoiceAddToCart"
    />

    <!-- Bottom Navigation (Mobile) -->
    <BottomNavigation 
      @open-cart="isCartOpen = true"
      @scroll-to-menu="() => {}"
    />

    <!-- Join Session Prompt Modal -->
    <JoinSessionPrompt 
        :show="showJoinPrompt"
        :host-name="hostName"
        @join="joinSession"
        @individual="handleIndividualSession"
    />

    <!-- Add to Cart Toast -->
    <AddToCartToast />
  </div>
</template>