<script setup>
import { computed, ref, watch } from 'vue'
import { useCart } from '../../../composables/useCart.js'

const emit = defineEmits(['checkout'])

const { cartItems, cartTotal, cartItemCount, isEmpty, formatPrice, cartAnimationTrigger } = useCart()

const isPop = ref(false)

watch(cartAnimationTrigger, () => {
    isPop.value = true
    setTimeout(() => {
        isPop.value = false
    }, 300)
})

const formattedTotal = computed(() => {
    return formatPrice(cartTotal.value)
})

const handleCheckout = () => {
    if (!isEmpty.value) {
        emit('checkout')
    }
}
</script>


<template>
    <div class="mt-auto pt-4 border-t border-surface-100">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-surface-800">Current Order</h3>
            <span 
                v-if="cartItemCount > 0" 
                :class="['bg-primary text-white px-2 py-0.5 rounded-full text-xs font-medium transition-transform duration-300', isPop ? 'scale-125' : 'scale-100']"
            >{{ cartItemCount }}</span>
        </div>
        
        <div v-if="isEmpty" class="text-center py-6">
            <div class="text-surface-300 mb-2">
                <i class="fas fa-shopping-cart text-xl"></i>
            </div>
            <p class="text-surface-400 text-sm">Your cart is empty</p>
        </div>
        
        <div v-else>
            <div class="space-y-2 mb-3 max-h-40 overflow-y-auto">
                <div 
                    v-for="(item, index) in cartItems" 
                    :key="`${item.id}-${index}`"
                    class="flex justify-between text-sm items-center py-1"
                >
                    <div class="flex-1 min-w-0">
                        <span class="text-surface-700 truncate block text-sm">{{ item.name }}</span>
                        <span class="text-xs text-surface-400">x{{ item.quantity }}</span>
                    </div>
                    <span class="font-medium text-surface-800 text-sm ml-2">
                        {{ formatPrice((item.displayPrice || item.finalPrice || item.price) * item.quantity) }}
                    </span>
                </div>
            </div>
            
            <div class="flex justify-between items-center py-3 border-t border-surface-100">
                <span class="text-sm font-medium text-surface-800">Total</span>
                <span class="font-semibold text-primary text-base">{{ formattedTotal }}</span>
            </div>
            
            <button 
                class="w-full py-2.5 bg-primary text-white rounded-lg font-medium text-sm hover:bg-primary-600 transition-colors flex items-center justify-center gap-2"
                @click="handleCheckout"
                :disabled="isEmpty"
            >
                <i class="fas fa-credit-card"></i>
                Checkout
            </button>
        </div>
    </div>
</template>