<script setup>
import { computed } from 'vue'
import Button from '../../Shared/Base/Button.vue'
import { useCart } from '../../../composables/useCart.js'

const emit = defineEmits(['checkout'])

const { cartItems, cartTotal, cartItemCount, isEmpty, formatPrice } = useCart()

// Computed property for display formatting
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
    <div class="mt-auto pt-6 border-t border-surface-200">
        <div class="bg-gradient-to-br from-primary-50 to-orange-50 rounded-2xl p-4 border border-primary-200/50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-subheading font-bold text-surface-800">Current Order</h3>
                <span class="bg-primary-500 text-white px-2 py-1 rounded-full text-xs font-bold">{{ cartItemCount }}</span>
            </div>
            
            <!-- Empty Cart State -->
            <div v-if="isEmpty" class="text-center py-6">
                <div class="text-surface-400 mb-2">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
                <p class="text-surface-500 text-sm">Your cart is empty</p>
                <p class="text-surface-400 text-xs mt-1">Add items to get started</p>
            </div>
            
            <!-- Cart Items -->
            <div v-else>
                <div class="space-y-2 mb-3 max-h-48 overflow-y-auto">
                    <div 
                        v-for="(item, index) in cartItems" 
                        :key="`${item.id}-${index}`"
                        class="flex justify-between text-sm items-center"
                    >
                        <div class="flex-1 min-w-0">
                            <span class="text-surface-600 truncate block">{{ item.name }}</span>
                            <div class="text-xs text-surface-400">
                                <span>Qty: {{ item.quantity }}</span>
                                <span v-if="item.isCustomized" class="ml-2 text-primary-500">
                                    <i class="fas fa-cog"></i> Customized
                                </span>
                            </div>
                        </div>
                        <span class="font-medium text-surface-800 ml-2">
                            {{ formatPrice((item.displayPrice || item.finalPrice || item.price) * item.quantity) }}
                        </span>
                    </div>
                </div>
                
                <div class="flex justify-between items-center pt-3 border-t border-primary-200">
                    <span class="text-subheading font-bold text-surface-800">Total</span>
                    <span class="text-price font-bold text-primary-600 text-lg">{{ formattedTotal }}</span>
                </div>
                
                <Button 
                    class="w-full mt-3" 
                    variant="primary" 
                    size="md" 
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
</template>