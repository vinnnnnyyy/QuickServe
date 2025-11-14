<script setup>
const props = defineProps({
  // Padding options: 'sm' (p-4), 'md' (p-6), 'lg' (p-8), 'none' (no padding)
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'none'].includes(value)
  },
  // Border radius: 'default' (rounded-lg), 'xl' (rounded-xl)
  rounded: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'xl'].includes(value)
  },
  // Whether to add overflow-hidden
  overflow: {
    type: Boolean,
    default: false
  },
  // Shadow options: 'none', 'sm', 'hover' (sm + hover:md)
  shadow: {
    type: String,
    default: 'none',
    validator: (value) => ['none', 'sm', 'hover'].includes(value)
  },
  // Whether to add hover effect
  hover: {
    type: Boolean,
    default: false
  },
  // Additional custom classes
  class: {
    type: String,
    default: ''
  }
});

const paddingClasses = {
  'sm': 'p-4',
  'md': 'p-6', 
  'lg': 'p-8',
  'none': ''
};

const roundedClasses = {
  'default': 'rounded-lg',
  'xl': 'rounded-xl'
};

const shadowClasses = {
  'none': '',
  'sm': 'shadow-sm',
  'hover': 'shadow-sm hover:shadow-md'
};

const computedClasses = [
  // Base wrapper styles
  'bg-[#f8f7f6] dark:bg-[#221810]',
  'border border-[#ec7813]/20 dark:border-[#ec7813]/30',
  
  // Dynamic classes based on props
  paddingClasses[props.padding],
  roundedClasses[props.rounded],
  shadowClasses[props.shadow],
  
  // Conditional classes
  props.overflow ? 'overflow-hidden' : '',
  props.hover ? 'hover:shadow-md transition-all duration-300' : '',
  
  // Custom classes
  props.class
].filter(Boolean).join(' ');
</script>

<template>
  <div :class="computedClasses">
    <slot />
  </div>
</template>
