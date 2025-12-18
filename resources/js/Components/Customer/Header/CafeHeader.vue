<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import HeaderNavigation from './HeaderNavigation.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const token = computed(() => page.props?.token || sessionStorage.getItem('authTableToken'));

const homeHref = computed(() => {
    return token.value ? route('table.menu', { token: token.value }) : '/';
});

const emit = defineEmits(['category-selected', 'open-checkout', 'open-cart']);

const isScrolled = ref(false);

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
            <!-- Desktop Header Content -->
            <div class="hidden lg:flex items-center gap-4 flex-1">
                <div class="flex-1">
                    <h1 class="text-heading gradient-text text-xl sm:text-2xl lg:text-3xl">Welcome Back!</h1>
                    <p class="text-body text-surface-500 text-sm">Discover our handcrafted beverages and fresh pastries</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        class="text-surface-600 flex h-11 w-11 items-center justify-center rounded-xl hover:bg-white/50 active:bg-white/80 transition-all">
                        <i class="fas fa-bell text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Header Content -->
            <div class="flex items-center justify-center flex-1 lg:hidden">
                <Link :href="homeHref" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                    <span class="text-brand gradient-text text-xl sm:text-2xl font-semibold">Cafe Delight</span>
                </Link>
            </div>
        </div>
        
        <!-- Category Navigation -->
        <HeaderNavigation 
            :categories="props.categories"
            @category-selected="handleCategorySelected"
        />
    </header>
</template>