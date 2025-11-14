<script setup>
import NavigationButton from './Buttons/NavigationButton.vue'

const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    isMobile: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['category-selected'])

const handleCategorySelected = (category) => {
    emit('category-selected', category)
}
</script>

<template>
    <nav class="flex-1" role="navigation" aria-labelledby="sidebar-categories-heading">
        <p id="sidebar-categories-heading" class="text-label text-xs text-surface-500 mb-3 tracking-wide uppercase">Menu Categories</p>
        <div class="flex flex-col gap-1">
            <NavigationButton 
                v-for="category in categories" 
                :key="category.id"
                :category="category"
                :is-active="category.active" 
                :href="`#${category.id}`" 
                :title="category.name || category.title" 
                :subtitle="category.description || category.subtitle" 
                :icon-class="category.icon" 
                :is-mobile="isMobile"
                @category-selected="handleCategorySelected" 
            />
        </div>
    </nav>
</template>