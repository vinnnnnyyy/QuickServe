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
    <nav class="flex-1 mt-6" role="navigation" aria-labelledby="sidebar-categories-heading">
        <p id="sidebar-categories-heading" class="text-xs font-medium text-surface-400 mb-3 uppercase tracking-wider">Menu</p>
        <div class="flex flex-col gap-0.5">
            <NavigationButton 
                v-for="category in categories" 
                :key="category.id"
                :category="category"
                :is-active="category.active" 
                :href="`#${category.id}`" 
                :title="category.name || category.title" 
                :icon-class="category.icon" 
                :is-mobile="isMobile"
                @category-selected="handleCategorySelected" 
            />
        </div>
    </nav>
</template>