<script setup>
import SidebarLogo from './SidebarLogo.vue';
import SidebarNavigation from './SidebarNavigation.vue';
import SidebarQuickActions from './SidebarQuickActions.vue';
import SidebarOrderSummary from './SidebarOrderSummary.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['category-selected', 'open-my-orders']);

const handleCategorySelected = (category) => {
    emit('category-selected', category);
};

const handleOpenMyOrders = () => {
    emit('open-my-orders');
};
</script>
<template>
    <aside
        class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:block lg:w-72 lg:overflow-y-auto lg:bg-white lg:border-r lg:border-surface-100"
        id="desktopSidebar">
        <div class="flex flex-col h-full px-5 py-6">
            <SidebarLogo />
            <SidebarNavigation 
                v-if="categories.length > 0"
                :categories="categories" 
                @category-selected="handleCategorySelected" 
            />
            <SidebarQuickActions @open-my-orders="handleOpenMyOrders" />
            <SidebarOrderSummary />
        </div>
    </aside>
</template>