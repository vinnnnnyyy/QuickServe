<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Shared/Navigation/Dropdown.vue';

const props = defineProps({
  row: {
    type: Object,
    required: true,
  },
  actions: {
    type: Array,
    required: true,
    validator: (actions) => {
      return actions.every(action => 
        typeof action === 'object' && 
        typeof action.key === 'string' && 
        typeof action.label === 'string'
      );
    }
  },
  placement: {
    type: String,
    default: 'bottom-end',
  },
  width: {
    type: String,
    default: '32',
  },
});

const emit = defineEmits(['action']);

// Filter visible actions based on show predicate
const visibleActions = computed(() => {
  return props.actions.filter(action => {
    if (typeof action.show === 'function') {
      return action.show(props.row);
    }
    return true;
  });
});

// Handle action click
const handleAction = (action) => {
  if (action.disabled && typeof action.disabled === 'function' && action.disabled(props.row)) {
    return;
  }

  if (action.onClick && typeof action.onClick === 'function') {
    action.onClick(props.row);
  } else if (!action.href) {
    emit('action', { key: action.key, row: props.row });
  }
  // href actions are handled by Link component
};

// Get button classes for action
const getActionClasses = (action) => {
  const baseClasses = 'flex items-center gap-2 px-3 py-2 text-sm transition-colors whitespace-nowrap w-full text-left';
  
  if (action.disabled && typeof action.disabled === 'function' && action.disabled(props.row)) {
    return `${baseClasses} text-gray-400 dark:text-gray-600 cursor-not-allowed`;
  }
  
  if (action.colorClass) {
    return `${baseClasses} ${action.colorClass}`;
  }
  
  return `${baseClasses} text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700`;
};
</script>

<template>
  <Dropdown 
    :placement="placement" 
    :width="width" 
    content-classes="py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 min-w-[140px]"
  >
    <template #trigger>
      <button
        class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-colors"
        title="More actions"
      >
        <span class="material-symbols-outlined text-base">more_horiz</span>
      </button>
    </template>

    <template #content>
      <template v-for="action in visibleActions" :key="action.key">
        <!-- Action with href (navigation) -->
        <Link
          v-if="action.href"
          :href="action.href"
          :class="getActionClasses(action)"
          :disabled="action.disabled && typeof action.disabled === 'function' && action.disabled(row)"
        >
          <span v-if="action.icon" class="material-symbols-outlined text-sm">{{ action.icon }}</span>
          <span>{{ action.label }}</span>
        </Link>

        <!-- Action with onClick or emit -->
        <button
          v-else
          :class="getActionClasses(action)"
          :disabled="action.disabled && typeof action.disabled === 'function' && action.disabled(row)"
          @click="handleAction(action)"
        >
          <span v-if="action.icon" class="material-symbols-outlined text-sm">{{ action.icon }}</span>
          <span>{{ action.label }}</span>
        </button>
      </template>
    </template>
  </Dropdown>
</template>
