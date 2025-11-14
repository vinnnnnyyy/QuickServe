<script setup>
const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['category-selected'])

const handleCategoryClick = (category, event) => {
    event.preventDefault()
    emit('category-selected', category)
}
</script>

<template>
    <nav v-if="categories.length > 0" class="sticky top-[73px] z-10 bg-white/95 backdrop-blur-md border-b border-gray-100 flex px-4 sm:px-6 gap-1 sm:gap-2 overflow-x-auto custom-scroll pb-4 lg:hidden">
        <button
            v-for="category in categories"
            :key="category.id"
            @click="handleCategoryClick(category, $event)"
            :class="[
                'relative flex items-center justify-center px-4 py-2 rounded-xl transition-all whitespace-nowrap',
                category.active 
                    ? 'bg-primary-500 text-white shadow-lg shadow-primary-500/20 hover:shadow-primary-500/30' 
                    : 'text-surface-600 hover:bg-white/50 active:bg-white/80'
            ]"
            :aria-current="category.active ? 'page' : null"
        >
            <i :class="[category.icon, 'mr-1.5']"></i>
            <span class="text-button text-sm">{{ category.name || category.title }}</span>
        </button>
    </nav>
</template>