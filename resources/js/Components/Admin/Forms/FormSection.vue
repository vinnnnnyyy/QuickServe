<script setup>
const props = defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  },
  icon: {
    type: String,
    default: 'info'
  },
  iconColor: {
    type: String,
    default: 'text-[#ec7813]'
  },
  backgroundColor: {
    type: String,
    default: 'bg-[#ec7813]/10 dark:bg-[#ec7813]/20'
  }
});
</script>

<template>
  <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-8">
    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
      <div class="p-2 rounded-lg" :class="backgroundColor">
        <span class="material-symbols-outlined text-xl" :class="iconColor">{{ icon }}</span>
      </div>
      {{ title }}
    </h3>
    <p v-if="subtitle" class="text-sm text-gray-700 dark:text-gray-400 mt-2">{{ subtitle }}</p>
  </div>
</template>
