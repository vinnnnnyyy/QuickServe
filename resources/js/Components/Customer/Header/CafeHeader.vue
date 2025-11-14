<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import MobileNavigation from './MobileNavigation.vue';
import { useCart } from '../../../composables/useCart.js';

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['category-selected', 'open-checkout', 'open-cart']);

const isMobileMenuOpen = ref(false);
const isScrolled = ref(false);

const { cartItemCount } = useCart();

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const handleCategorySelected = (category) => {
    emit('category-selected', category);
};

const handleCheckout = () => {
    emit('open-checkout');
};

// Handle scroll effect for mobile sticky behavior
const handleScroll = () => {
    isScrolled.value = window.scrollY > 10;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <header 
        class="glass sticky top-0 z-20 py-2 transition-all duration-300 lg:static"
        :class="{ 'shadow-lg bg-white/95 backdrop-blur-md lg:shadow-none lg:bg-transparent': isScrolled }"
    >
        <div class="flex items-center px-4 py-4 sm:px-6 lg:px-6 xl:px-8 2xl:px-12 sm:py-5 justify-between">
            <button
                @click="toggleMobileMenu"
                class="text-surface-600 flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-xl hover:bg-white/50 active:bg-white/80 transition-all touch-target focus-ring lg:hidden"
                :class="{ 'bg-white/50': isMobileMenuOpen }">
                <i class="fas text-2xl sm:text-3xl" :class="isMobileMenuOpen ? 'fa-times' : 'fa-bars'"></i>
            </button>
            
            <!-- Desktop Header Content -->
    
            <div class=" hidden lg:flex items-center gap-4 flex-1">
                <div class="flex-1">
                    <h1 class="text-heading gradient-text text-xl sm:text-2xl lg:text-3xl">Welcome Back!
                    </h1>
                    <p class="text-body text-surface-500 text-sm">Discover our handcrafted beverages and
                        fresh pastries</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input type="search" placeholder="Search menu..."
                            class="pl-10 pr-4 py-2 rounded-xl border border-surface-200 bg-white/80 backdrop-blur-sm text-surface-800 placeholder-surface-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 w-64">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-surface-400"></i>
                    </div>
                    <button
                        class="text-surface-600 flex h-11 w-11 items-center justify-center rounded-xl hover:bg-white/50 active:bg-white/80 transition-all">
                        <i class="fas fa-bell text-2xl"></i>
                    </button>
                    <button
                        class="text-surface-600 flex h-11 w-11 items-center justify-center rounded-xl hover:bg-white/50 active:bg-white/80 transition-all">
                        <i class="fas fa-user text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Header Content -->
            <div class="flex items-center justify-between flex-1 lg:hidden">
                <Link href="/" class="flex items-center gap-2 text-brand gradient-text text-xl sm:text-2xl hover:opacity-80 transition-opacity">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-coffee text-white text-sm"></i>
                    </div>
                    <span>Café Delight</span>
                </Link>
                <div class="flex items-center gap-2">
                    <button
                        class="text-surface-600 flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-xl hover:bg-white/50 active:bg-white/80 transition-all touch-target focus-ring">
                        <i class="fas fa-search text-2xl sm:text-3xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation Component -->
            <MobileNavigation 
                :is-open="isMobileMenuOpen" 
                @close="isMobileMenuOpen = false" 
                :categories="props.categories"
                @category-selected="handleCategorySelected"
            />
        
    </header>
</template>