<script setup>
import { computed } from 'vue';

const props = defineProps({
  // Core textarea props
  modelValue: {
    type: String,
    default: ''
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
  rows: {
    type: [String, Number],
    default: 3
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
  
  // Character limit
  maxLength: {
    type: [String, Number],
    default: undefined
  },
  showCharCount: {
    type: Boolean,
    default: false
  },
  
  // Styling options
  size: {
    type: String,
    default: 'md', // sm, md, lg
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  resize: {
    type: String,
    default: 'vertical', // none, vertical, horizontal, both
    validator: (value) => ['none', 'vertical', 'horizontal', 'both'].includes(value)
  },
  
  // Additional classes
  textareaClass: {
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
const textareaClasses = computed(() => {
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
  
  // Resize classes
  const resizeClasses = {
    'none': ['resize-none'],
    'vertical': ['resize-y'],
    'horizontal': ['resize-x'],
    'both': ['resize']
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
    ...resizeClasses[props.resize],
    ...stateClasses,
    ...disabledClasses,
    props.textareaClass
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

// Character count
const characterCount = computed(() => {
  return props.modelValue ? props.modelValue.length : 0;
});

const isOverLimit = computed(() => {
  return props.maxLength && characterCount.value > props.maxLength;
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
    
    <!-- Textarea Field -->
    <textarea 
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      :rows="rows"
      :maxlength="maxLength"
      :class="textareaClasses"
      @input="handleInput"
      @blur="handleBlur"
      @focus="handleFocus"
    ></textarea>
    
    <!-- Character Count -->
    <div v-if="showCharCount || maxLength" class="flex justify-between items-center text-sm">
      <div>
        <!-- Help Text -->
        <span 
          v-if="helpText && !error" 
          class="text-gray-600 dark:text-gray-400"
        >
          {{ helpText }}
        </span>
      </div>
      
      <div v-if="showCharCount || maxLength" class="flex items-center gap-1">
        <span 
          :class="[
            isOverLimit ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'
          ]"
        >
          {{ characterCount }}
        </span>
        <span v-if="maxLength" class="text-gray-400 dark:text-gray-500">
          / {{ maxLength }}
        </span>
      </div>
    </div>
    
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
