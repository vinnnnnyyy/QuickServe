<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  // Core select props
  modelValue: {
    type: [String, Number, Boolean],
    default: ''
  },
  options: {
    type: Array,
    default: () => []
  },
  placeholder: {
    type: String,
    default: 'Select an option'
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
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
  
  // Custom options
  allowCustom: {
    type: Boolean,
    default: false
  },
  customOptionLabel: {
    type: String,
    default: '+ Add New'
  },
  customOptionValue: {
    type: String,
    default: 'add_new'
  },
  
  // Styling options
  size: {
    type: String,
    default: 'md', // sm, md, lg
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  
  // Additional classes
  selectClass: {
    type: String,
    default: ''
  },
  labelClass: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'change', 'add-custom', 'blur', 'focus']);

// Computed classes
const selectClasses = computed(() => {
  const baseClasses = [
    'w-full',
    'rounded-xl',
    'border',
    'bg-white',
    'dark:bg-black/20',
    'text-gray-900',
    'dark:text-white',
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
    props.selectClass
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

// Computed options with custom option if enabled
const allOptions = computed(() => {
  const options = [...props.options];
  
  if (props.allowCustom) {
    options.push({
      value: props.customOptionValue,
      label: props.customOptionLabel
    });
  }
  
  return options;
});

// Event handlers
const handleChange = (event) => {
  const value = event.target.value;
  
  // Handle custom option
  if (props.allowCustom && value === props.customOptionValue) {
    emit('add-custom', value);
  } else {
    emit('update:modelValue', value);
  }
  
  emit('change', event);
};

const handleBlur = (event) => {
  emit('blur', event);
};

const handleFocus = (event) => {
  emit('focus', event);
};

// Helper function to get option value and label
const getOptionValue = (option) => {
  if (typeof option === 'string') return option;
  return option.value || option;
};

const getOptionLabel = (option) => {
  if (typeof option === 'string') return option;
  return option.label || option.text || option.name || option.value || option;
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
    
    <!-- Select Field -->
    <select 
      :value="modelValue"
      :required="required"
      :disabled="disabled"
      :class="selectClasses"
      @change="handleChange"
      @blur="handleBlur"
      @focus="handleFocus"
    >
      <!-- Placeholder option -->
      <option value="" disabled>{{ placeholder }}</option>
      
      <!-- Options -->
      <option 
        v-for="option in allOptions"
        :key="getOptionValue(option)"
        :value="getOptionValue(option)"
      >
        {{ getOptionLabel(option) }}
      </option>
    </select>
    
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
