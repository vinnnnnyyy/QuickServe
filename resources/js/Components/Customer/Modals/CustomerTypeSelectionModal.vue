<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['select'])

const step = ref(1)
const selection = ref({
  type: null,
  payment_mode: null
})

// Reset state when modal opens/closes
// Note: In this specific implementation, the modal is only used once per session start,
// but good practice to have reset logic if it were reused.

const handleTypeSelect = (type) => {
  selection.value.type = type
  if (type === 'individual') {
    // Individual implies individual payment (implicit, backend handles it)
    selection.value.payment_mode = null
    emit('select', selection.value)
  } else {
    // Group requires next step
    step.value = 2
  }
}

const handlePaymentSelect = (mode) => {
  selection.value.payment_mode = mode
  emit('select', selection.value)
}

const goBack = () => {
  step.value = 1
  selection.value.type = null
  selection.value.payment_mode = null
}

const stepTitle = computed(() => {
  if (step.value === 1) return "Welcome!"
  return "Payment Preference"
})

const stepSubtitle = computed(() => {
  if (step.value === 1) return "Please select how you are dining today"
  return "How would your group like to pay?"
})
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-opacity duration-300">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden transform transition-all duration-300">
      <!-- Header -->
      <div class="p-6 text-center border-b border-gray-100 relative">
        <button 
          v-if="step === 2" 
          @click="goBack"
          class="absolute left-6 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
        >
          <i class="fas fa-arrow-left text-xl"></i>
        </button>
        
        <h2 class="text-2xl font-bold text-gray-800">{{ stepTitle }}</h2>
        <p class="mt-2 text-gray-600">{{ stepSubtitle }}</p>
      </div>

      <!-- Step 1: Customer Type -->
      <div v-if="step === 1" class="p-6 grid gap-4 transition-all duration-300">
        <!-- Individual Option -->
        <button
          @click="handleTypeSelect('individual')"
          class="group relative flex items-center p-4 border-2 border-gray-100 rounded-xl hover:border-primary-500 hover:bg-primary-50 transition-all duration-300 w-full text-left"
        >
          <div class="flex-shrink-0 h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center group-hover:bg-primary-100 transition-colors">
            <i class="fas fa-user text-xl text-gray-500 group-hover:text-primary-600"></i>
          </div>
          <div class="ml-4 flex-1">
            <h3 class="text-lg font-bold text-gray-800 group-hover:text-primary-700">Individual</h3>
            <p class="text-sm text-gray-500 font-medium">I'm dining alone</p>
          </div>
          <div class="absolute right-4 text-gray-300 group-hover:text-primary-500">
            <i class="fas fa-chevron-right"></i>
          </div>
        </button>

        <!-- Group Option -->
        <button
          @click="handleTypeSelect('group')"
          class="group relative flex items-center p-4 border-2 border-gray-100 rounded-xl hover:border-primary-500 hover:bg-primary-50 transition-all duration-300 w-full text-left"
        >
          <div class="flex-shrink-0 h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center group-hover:bg-primary-100 transition-colors">
            <i class="fas fa-users text-xl text-gray-500 group-hover:text-primary-600"></i>
          </div>
          <div class="ml-4 flex-1">
            <h3 class="text-lg font-bold text-gray-800 group-hover:text-primary-700">Group</h3>
            <p class="text-sm text-gray-500 font-medium">I'm with others</p>
          </div>
          <div class="absolute right-4 text-gray-300 group-hover:text-primary-500">
            <i class="fas fa-chevron-right"></i>
          </div>
        </button>
      </div>

      <!-- Step 2: Payment Mode (Group) -->
      <div v-if="step === 2" class="p-6 grid gap-4 transition-all duration-300">
        <!-- One Bill Option -->
        <button
          @click="handlePaymentSelect('host')"
          class="group relative flex items-center p-4 border-2 border-gray-100 rounded-xl hover:border-primary-500 hover:bg-primary-50 transition-all duration-300 w-full text-left"
        >
          <div class="flex-shrink-0 h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center group-hover:bg-primary-100 transition-colors">
            <i class="fas fa-file-invoice-dollar text-xl text-gray-500 group-hover:text-primary-600"></i>
          </div>
          <div class="ml-4 flex-1">
            <h3 class="text-lg font-bold text-gray-800 group-hover:text-primary-700">One Bill (Hosted)</h3>
            <p class="text-sm text-gray-500 font-medium">One person pays for everyone</p>
          </div>
          <div class="absolute right-4 text-gray-300 group-hover:text-primary-500">
            <i class="fas fa-check"></i>
          </div>
        </button>

        <!-- KKB Option -->
        <button
          @click="handlePaymentSelect('split')"
          class="group relative flex items-center p-4 border-2 border-gray-100 rounded-xl hover:border-primary-500 hover:bg-primary-50 transition-all duration-300 w-full text-left"
        >
          <div class="flex-shrink-0 h-12 w-12 bg-gray-50 rounded-full flex items-center justify-center group-hover:bg-primary-100 transition-colors">
            <i class="fas fa-hand-holding-dollar text-xl text-gray-500 group-hover:text-primary-600"></i>
          </div>
          <div class="ml-4 flex-1">
            <h3 class="text-lg font-bold text-gray-800 group-hover:text-primary-700">KKB (Split Bill)</h3>
            <p class="text-sm text-gray-500 font-medium">Everyone pays their own share</p>
          </div>
          <div class="absolute right-4 text-gray-300 group-hover:text-primary-500">
            <i class="fas fa-check"></i>
          </div>
        </button>
      </div>

      <!-- Footer Info -->
      <div class="p-4 bg-gray-50 text-center text-xs text-gray-400">
        This helps us organize your orders better
      </div>
    </div>
  </div>
</template>
