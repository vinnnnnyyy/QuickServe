<script setup>
import { computed } from 'vue';

const props = defineProps({
  // Core input props
  modelValue: {
    type: [String, Number],
    default: ''
  },
  type: {
    type: String,
    default: 'text'
  },
  placeholder: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  
  // Label and help text
  label: {
    type: String,
    default: ''
  },
  helpText: {
    type: String,
    default: ''
  },
  
  // Validation
  error: {
    type: String,
    default: ''
  },
  
  // Number input specific
  min: {
    type: [String, Number],
    default: undefined
  },
  max: {
    type: [String, Number],
    default: undefined
  },
  step: {
    type: [String, Number],
    default: undefined
  },
  
  // Styling options
  size: {
    type: String,
    default: 'md', // sm, md, lg
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  
  // Additional classes
  inputClass: {
    type: String,
    default: ''
  },
  labelClass: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus', 'input']);

// Computed classes
const inputClasses = computed(() => {
  const baseClasses = [
    'w-full',
    'rounded-xl',
    'border',
    'bg-white',
    'dark:bg-black/20',
    'text-gray-900',
    'dark:text-white',
    'placeholder-gray-500',
    'dark:placeholder-gray-400',
    'focus:outline-none',
    'focus:ring-2',
    'transition-all',
    'duration-200'
  ];
  
  // Size classes
  const sizeClasses = {
    'sm': ['px-3', 'py-2', 'text-sm'],
    'md': ['px-4', 'py-3', 'text-base'],
    'lg': ['px-5', 'py-4', 'text-lg']
  };
  
  // State classes
  const stateClasses = props.error 
    ? [
        'border-red-300',
        'dark:border-red-600',
        'focus:ring-red-500',
        'focus:border-red-500'
      ]
    : [
        'border-gray-200',
        'dark:border-gray-700',
        'focus:ring-[#ec7813]',
        'focus:border-[#ec7813]'
      ];
  
  // Disabled classes
  const disabledClasses = props.disabled
    ? [
        'opacity-50',
        'cursor-not-allowed',
        'bg-gray-50',
        'dark:bg-gray-800'
      ]
    : [];
  
  return [
    ...baseClasses,
    ...sizeClasses[props.size],
    ...stateClasses,
    ...disabledClasses,
    props.inputClass
  ].join(' ');
});

const labelClasses = computed(() => {
  const baseClasses = [
    'block',
    'text-sm',
    'font-semibold',
    'text-gray-900',
    'dark:text-white',
    'mb-3'
  ];
  
  return [
    ...baseClasses,
    props.labelClass
  ].join(' ');
});

// Event handlers
const handleInput = (event) => {
  const value = event.target.value;
  emit('update:modelValue', value);
  emit('input', event);
};

const handleBlur = (event) => {
  emit('blur', event);
};

const handleFocus = (event) => {
  emit('focus', event);
};
</script>

<template>
  <div class="space-y-2">
    <!-- Label -->
    <label 
      v-if="label" 
      :class="labelClasses"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
    
    <!-- Input Field -->
    <input 
      :value="modelValue"
      :type="type"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      :min="min"
      :max="max"
      :step="step"
      :class="inputClasses"
      @input="handleInput"
      @blur="handleBlur"
      @focus="handleFocus"
    />
    
    <!-- Help Text -->
    <p 
      v-if="helpText && !error" 
      class="text-sm text-gray-600 dark:text-gray-400"
    >
      {{ helpText }}
    </p>
    
    <!-- Error Message -->
    <p 
      v-if="error" 
      class="text-sm text-red-600 dark:text-red-400 flex items-center gap-1"
    >
      <span class="material-symbols-outlined text-sm">error</span>
      {{ error }}
    </p>
  </div>
</template>
