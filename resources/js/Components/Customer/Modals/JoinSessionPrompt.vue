<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false
  },
  hostName: {
    type: String,
    default: 'Host'
  }
})

const emit = defineEmits(['join', 'individual'])
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden p-6 animate-fade-in-up">
      <div class="text-center mb-6">
        <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="fas fa-users text-2xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Group Session Active</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          <span class="font-bold text-indigo-600">{{ hostName }}</span> has started a group order at this table.
        </p>
      </div>

      <div class="space-y-3">
        <button 
          @click="$emit('join')"
          class="w-full py-3.5 px-4 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold shadow-lg shadow-primary-500/30 flex items-center justify-center gap-2 transition-all"
        >
          <i class="fas fa-user-plus"></i>
          Join Group
        </button>

        <button 
          @click="$emit('individual')"
          class="w-full py-3.5 px-4 bg-white dark:bg-gray-700 border-2 border-gray-100 dark:border-gray-600 text-gray-700 dark:text-white rounded-xl font-bold hover:bg-gray-50 dark:hover:bg-gray-600 flex items-center justify-center gap-2 transition-all"
        >
          <i class="fas fa-user"></i>
          Dine Individually
        </button>
      </div>
      
      <p class="text-xs text-center text-gray-400 mt-4">
        Choosing "Individual" will create a separate cart just for you.
      </p>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
