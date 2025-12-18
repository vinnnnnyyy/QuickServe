<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useCart } from '../../../composables/useCart.js';
import { useOrderHistory } from '../../../composables/useOrderHistory.js';

const emit = defineEmits(['open-cart', 'scroll-to-menu']);

const { cartItemCount, cartAnimationTrigger } = useCart();
const { activeOrderCount } = useOrderHistory();
const page = usePage();

const isPop = ref(false);

watch(cartAnimationTrigger, () => {
    isPop.value = true;
    setTimeout(() => {
        isPop.value = false;
    }, 300);
});

const currentPath = computed(() => page.url);

const tableId = computed(() => {
    return sessionStorage.getItem('currentTableId') || page.props?.tableId || null;
});

const token = computed(() => {
    return page.props?.token || sessionStorage.getItem('authTableToken') || null;
});

const homeHref = computed(() => {
    return token.value ? route('table.menu', { token: token.value }) : '/';
});

const menuHref = computed(() => {
    return token.value ? route('table.fullmenu', { token: token.value }) : '/menu';
});

const navItems = computed(() => [
    { id: 'home', icon: 'fa-home', label: 'Home', href: homeHref.value },
    { id: 'menu', icon: 'fa-th-large', label: 'Menu', href: menuHref.value },
    { id: 'cart', icon: 'fa-shopping-cart', label: 'Cart', action: 'open-cart', badge: 'cart' },
    { id: 'track', icon: 'fa-map-marker-alt', label: 'Track', href: '/order/status' },
    { id: 'orders', icon: 'fa-receipt', label: 'Orders', href: '/order/history', badge: 'orders' },
]);

const handleNavClick = (item) => {
    if (item.action) {
        emit(item.action);
    }
};

const isActive = (item) => {
    if (item.href) {
        return currentPath.value === item.href;
    }
    return false;
};

const getBadgeCount = (badgeType) => {
    if (badgeType === 'cart') return cartItemCount.value;
    if (badgeType === 'orders') return activeOrderCount.value;
    return 0;
};
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-lg border-t border-gray-200 lg:hidden safe-area-bottom">
        <div class="flex items-center justify-around h-16">
            <template v-for="item in navItems" :key="item.id">
                <Link
                    v-if="item.href"
                    :href="item.href"
                    class="flex flex-col items-center justify-center flex-1 h-full py-2 transition-colors"
                    :class="isActive(item) ? 'text-primary' : 'text-gray-500 hover:text-gray-700'"
                >
                    <div class="relative">
                        <i class="fas text-xl" :class="item.icon"></i>
                        <span 
                            v-if="item.badge && getBadgeCount(item.badge) > 0"
                            class="absolute -top-2 -right-2 bg-primary text-white text-xs font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1"
                        >
                            {{ getBadgeCount(item.badge) > 99 ? '99+' : getBadgeCount(item.badge) }}
                        </span>
                    </div>
                    <span class="text-xs mt-1 font-medium">{{ item.label }}</span>
                </Link>
                
                <button
                    v-else
                    @click="handleNavClick(item)"
                    class="flex flex-col items-center justify-center flex-1 h-full py-2 transition-colors text-gray-500 hover:text-gray-700"
                >
                    <div class="relative">
                        <i class="fas text-xl" :class="item.icon"></i>
                        <span 
                            v-if="item.badge && getBadgeCount(item.badge) > 0"
                            :class="['absolute -top-2 -right-2 bg-primary text-white text-xs font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 transition-transform duration-300', isPop && item.badge === 'cart' ? 'scale-125' : 'scale-100']"
                        >
                            {{ getBadgeCount(item.badge) > 99 ? '99+' : getBadgeCount(item.badge) }}
                        </span>
                    </div>
                    <span class="text-xs mt-1 font-medium">{{ item.label }}</span>
                </button>
            </template>
        </div>
    </nav>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom, 0);
}
</style>
