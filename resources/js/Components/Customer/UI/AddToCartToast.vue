<script setup>
import { ref, watch } from 'vue'
import { useCart } from '../../../composables/useCart.js'

const { cartAnimationTrigger } = useCart()

const show = ref(false)

watch(cartAnimationTrigger, () => {
    show.value = true
    setTimeout(() => {
        show.value = false
    }, 800)
})
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-50"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-50"
        >
            <div 
                v-if="show"
                class="fixed top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[100] pointer-events-none"
            >
                <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-cart-plus text-white text-2xl"></i>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
