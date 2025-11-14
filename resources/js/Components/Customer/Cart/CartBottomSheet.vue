<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useCart } from '../../../composables/useCart.js'
import Button from '../../Shared/Base/Button.vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close', 'checkout'])

const { cartItems, cartTotal, cartItemCount, isEmpty, formatPrice } = useCart()

const sheetRef = ref(null)
const isVisible = ref(false)

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
                <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex justify-between items-center rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-gray-800">Current Order</h3>
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
                        <p class="text-gray-500 text-lg font-medium mb-2">Your cart is empty</p>
                        <p class="text-gray-400 text-sm">Add items to get started</p>
                    </div>
                    
                    <!-- Cart Items -->
                    <div v-else class="space-y-4">
                        <div 
                            v-for="(item, index) in cartItems" 
                            :key="`${item.id}-${index}`"
                            class="flex justify-between items-center bg-gray-50 p-3 rounded-lg"
                        >
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 truncate">{{ item.name }}</h4>
                                <div class="text-sm text-gray-600">
                                    <span>Qty: {{ item.quantity }}</span>
                                    <span v-if="item.isCustomized" class="ml-2 text-primary-500">
                                        <i class="fas fa-cog text-xs"></i> Customized
                                    </span>
                                </div>
                            </div>
                            <div class="text-right ml-4">
                                <div class="font-semibold text-gray-900">
                                    {{ formatPrice((item.displayPrice || item.finalPrice || item.price) * item.quantity) }}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total -->
                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <span class="text-lg font-bold text-gray-900">Total</span>
                            <span class="text-xl font-bold text-primary-600">{{ formatPrice(cartTotal) }}</span>
                        </div>
                        
                        <!-- Checkout Button -->
                        <Button 
                            class="w-full mt-4" 
                            variant="primary" 
                            size="lg" 
                            rounded="xl" 
                            full-width
                            @click="handleCheckout"
                            :disabled="isEmpty"
                        >
                            <i class="fas fa-credit-card mr-2"></i>
                            Checkout
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
