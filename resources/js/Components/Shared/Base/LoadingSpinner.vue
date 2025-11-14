<script setup>
defineProps({
  message: {
    type: String,
    default: 'Loading...'
  },
  subtitle: {
    type: String,
    default: null
  },
  size: {
    type: String,
    default: 'lg',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  },
  fullScreen: {
    type: Boolean,
    default: true
  }
})

const sizeClasses = {
  sm: 'h-8 w-8',
  md: 'h-12 w-12', 
  lg: 'h-16 w-16',
  xl: 'h-20 w-20'
}

const textSizeClasses = {
  sm: 'text-sm',
  md: 'text-base',
  lg: 'text-lg',
  xl: 'text-xl'
}
</script>

<template>
  <div 
    :class="[
      'flex items-center justify-center',
      fullScreen ? 'min-h-screen' : 'min-h-[60vh]'
    ]"
  >
    <div class="text-center">
      <div 
        :class="[
          'animate-spin rounded-full border-4 border-primary-200 border-t-primary-600 mx-auto mb-4',
          sizeClasses[size]
        ]"
      ></div>
      <p 
        :class="[
          'font-medium text-gray-700 dark:text-gray-300',
          textSizeClasses[size]
        ]"
      >
        {{ message }}
      </p>
      <p 
        v-if="subtitle" 
        class="text-sm text-gray-500 dark:text-gray-400 mt-2"
      >
        {{ subtitle }}
      </p>
    </div>
  </div>
</template>
