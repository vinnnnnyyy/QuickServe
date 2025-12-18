<script setup>
const emit = defineEmits(['open-voice-modal'])

const openVoiceModal = () => {
  emit('open-voice-modal')
}
</script>

<template>
  <div class="mt-4 p-4 bg-surface-50 rounded-xl">
    <div class="flex items-center gap-3 mb-3">
      <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
        </svg>
      </div>
      <div>
        <h4 class="font-medium text-surface-800 text-sm">Voice Order</h4>
        <p class="text-xs text-surface-400">Order hands-free</p>
      </div>
    </div>
    <button
      @click="openVoiceModal"
      class="w-full py-2 border border-primary text-primary rounded-lg font-medium text-sm hover:bg-primary hover:text-white transition-colors flex items-center justify-center gap-2"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
      </svg>
      Start Voice Order
    </button>
  </div>
</template>
