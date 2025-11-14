<script setup>
import SidebarLogo from './SidebarLogo.vue';
import SidebarStats from './SidebarStats.vue';
import SidebarNavigation from './SidebarNavigation.vue';
import SidebarQuickActions from './SidebarQuickActions.vue';
import SidebarOrderSummary from './SidebarOrderSummary.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['category-selected']);

const handleCategorySelected = (category) => {
    emit('category-selected', category);
};
</script>
<template>
    <aside
        class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:block lg:w-80 lg:overflow-y-auto lg:bg-white/80 lg:backdrop-blur-xl lg:border-r lg:border-gray-200"
        id="desktopSidebar">
        <div class="flex flex-col h-full p-6">
            <SidebarLogo />
            <SidebarStats />
            <SidebarNavigation 
                v-if="categories.length > 0"
                :categories="categories" 
                @category-selected="handleCategorySelected" 
            />
            <SidebarQuickActions />
            <SidebarOrderSummary />
        </div>
    </aside>
</template>