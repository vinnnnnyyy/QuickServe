<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import { useCart } from '../../../composables/useCart.js'
import Button from '../../Shared/Base/Button.vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    paymentMode: {
        type: String,
        default: 'host'
    },
    isHostUser: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close', 'checkout'])

const page = usePage()
const { cartItems, cartTotal, cartItemCount, isEmpty, formatPrice, myCartItems, myCartTotal, removeItem } = useCart()

const sheetRef = ref(null)
const isVisible = ref(false)

// Host Logic
const isHost = computed(() => {
    // Determine if current user is host based on session data
    // Ideally this comes from props or a store, but for now we infer or fetch
    // We can check if paymentMode is 'host' AND we are the host device
    // Since we don't have direct access to 'isHost' prop here easily without prop drilling, 
    // let's check the global page props or assume 'host' payment mode implies we need to check role
    // For safety, we can rely on page.props if available
    // A robust way: check if we created the session. 
    // Simplified: If paymentMode is 'host', we show "Checkout" ONLY if we are host.
    // If not host, we show "Request Bill" or "Added to Tab"
    
    // We need to know who we are. 
    // Let's assume the parent passes correct role context or we use the API response stored in page state
    // For now, let's look at the cookie/session if possible, or add a prop.
    return props.isHostUser ?? true // Default true for backward compatibility until prop is passed
})

// Group items by user for Host View
const groupedItems = computed(() => {
    if (props.paymentMode !== 'host') return { 'My Items': cartItems.value };
    
    const groups = {};
    cartItems.value.forEach(item => {
        // We need a 'userName' or 'deviceId' on item
        // TableCartController needs to ensure 'userName' is attached to items
        const rawName = item.added_by_name || 'Guest';
        const key = item.added_by_me ? 'You (Host)' : rawName;
        
        if (!groups[key]) groups[key] = [];
        groups[key].push(item);
    });
    return groups;
});

// Handle backdrop click
const handleBackdropClick = (e) => {
    if (e.target === e.currentTarget) {
        emit('close')
    }
}

// Handle escape key
const handleEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        emit('close')
    }
}

// Watch for show changes and handle animations
watch(() => props.show, (newValue) => {
    if (newValue) {
        isVisible.value = true
        document.body.style.overflow = 'hidden'
        document.addEventListener('keydown', handleEscape)
        // Trigger slide-up animation
        setTimeout(() => {
            if (sheetRef.value) {
                sheetRef.value.style.transform = 'translateY(0)'
            }
        }, 10)
    } else {
        // Trigger slide-down animation
        if (sheetRef.value) {
            sheetRef.value.style.transform = 'translateY(100%)'
        }
        document.body.style.overflow = ''
        document.removeEventListener('keydown', handleEscape)
        // Hide after animation
        setTimeout(() => {
            isVisible.value = false
        }, 300)
    }
})

onUnmounted(() => {
    document.body.style.overflow = ''
    document.removeEventListener('keydown', handleEscape)
})

const handleCheckout = () => {
    if (!isEmpty.value) {
        emit('checkout')
    }
}

const handleRemoveItem = async (itemId) => {
    if(confirm('Remove this item?')) {
        await removeItem(itemId);
    }
}
</script>

<template>
    <!-- Use Teleport to render directly in body -->
    <Teleport to="body">
        <div v-if="isVisible"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity duration-300 lg:hidden"
            style="z-index: 100000;" @click="handleBackdropClick">

            <!-- Bottom Sheet -->
            <div ref="sheetRef"
                class="fixed bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-2xl max-h-[75vh] overflow-y-auto transform transition-transform duration-300 ease-out"
                style="transform: translateY(100%); z-index: 100001;" @click.stop>

                <!-- Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex justify-between items-center rounded-t-2xl z-10">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-gray-800">
                            {{ paymentMode === 'host' ? 'Group Cart' : 'Your Order' }}
                        </h3>
                        <span v-if="cartItemCount > 0" class="bg-primary-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            {{ cartItemCount }}
                        </span>
                    </div>
                    <button @click="$emit('close')" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-xl text-gray-600"></i>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-4">
                    <!-- Empty Cart State -->
                    <div v-if="isEmpty" class="text-center py-8">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-shopping-cart text-4xl"></i>
                        </div>
                        <p class="text-gray-500 text-lg font-medium mb-2">Cart is empty</p>
                        <p class="text-gray-400 text-sm">Waiting for orders...</p>
                    </div>
                    
                    <!-- Cart Items (Grouped) -->
                    <div v-else class="space-y-6">
                        <div v-for="(items, groupName) in groupedItems" :key="groupName">
                            <!-- Group Header for Host Mode -->
                            <h4 v-if="paymentMode === 'host'" class="font-bold text-sm text-gray-500 uppercase tracking-wider mb-2 border-b pb-1">
                                {{ groupName }}
                            </h4>

                            <div class="space-y-3">
                                <div 
                                    v-for="(item, index) in items" 
                                    :key="`${item.id}-${index}`"
                                    class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-100"
                                >
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-900 truncate">{{ item.name }}</h4>
                                        <div class="text-sm text-gray-600 flex items-center gap-2">
                                            <span class="bg-gray-200 text-xs px-1.5 rounded text-gray-700">x{{ item.quantity }}</span>
                                            <span v-if="item.isCustomized" class="text-primary-500 text-xs">
                                                <i class="fas fa-cog"></i> Custom
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 ml-4">
                                        <div class="font-semibold text-gray-900">
                                            {{ formatPrice((item.displayPrice || item.finalPrice || item.price) * item.quantity) }}
                                        </div>
                                        <!-- Host Remove Button -->
                                        <button v-if="props.isHostUser || item.added_by_me" 
                                                @click="handleRemoveItem(item.id)"
                                                class="text-gray-400 hover:text-red-500 transition-colors p-1">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                         <!-- Totals -->
                        <div class="flex justify-between items-center pt-4 border-t border-gray-200 mt-4">
                            <span class="text-lg font-bold text-gray-900">Total</span>
                            <span class="text-2xl font-bold text-primary-600">
                                {{ formatPrice(cartTotal) }}
                            </span>
                        </div>
                        
                        <!-- Actions -->
                        <!-- If Host OR Individual: Show Checkout -->
                        <!-- If Guest (in Host Mode): Show "Added to Tab" Disabled Button -->
                        <div v-if="props.isHostUser || paymentMode === 'individual'" class="mt-4">
                            <Button 
                                class="w-full" 
                                variant="primary" 
                                size="lg" 
                                rounded="xl" 
                                full-width
                                @click="handleCheckout"
                                :disabled="isEmpty"
                            >
                                <i class="fas fa-receipt mr-2"></i>
                                Review & Pay Bill
                            </Button>
                        </div>
                        <div v-else class="mt-4">
                             <div class="w-full bg-green-100 text-green-700 p-4 rounded-xl text-center font-bold flex items-center justify-center gap-2">
                                <i class="fas fa-check-circle"></i>
                                Added to Host's Tab
                            </div>
                            <p class="text-center text-xs text-gray-500 mt-2">
                                Only the host can finalize payment.
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
