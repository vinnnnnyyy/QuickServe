<script setup>
import { computed } from 'vue';

const props = defineProps({
  // Current page (1-based)
  currentPage: {
    type: Number,
    default: 1,
    validator: (value) => value >= 1
  },
  // Total number of items
  totalItems: {
    type: Number,
    required: true,
    validator: (value) => value >= 0
  },
  // Items per page
  itemsPerPage: {
    type: Number,
    default: 10,
    validator: (value) => value >= 1
  },
  // Number of visible page buttons around current page
  visiblePages: {
    type: Number,
    default: 5,
    validator: (value) => value >= 3 && value % 2 === 1
  },
  // Show/hide items info text
  showItemsInfo: {
    type: Boolean,
    default: true
  },
  // Custom items info text
  itemsText: {
    type: String,
    default: 'items'
  }
});

const emit = defineEmits(['page-change']);

// Computed properties
const totalPages = computed(() => Math.ceil(props.totalItems / props.itemsPerPage));

const startItem = computed(() => {
  if (props.totalItems === 0) return 0;
  return (props.currentPage - 1) * props.itemsPerPage + 1;
});

const endItem = computed(() => {
  const end = props.currentPage * props.itemsPerPage;
  return Math.min(end, props.totalItems);
});

const hasNextPage = computed(() => props.currentPage < totalPages.value);
const hasPrevPage = computed(() => props.currentPage > 1);

// Generate page numbers to display
const visiblePageNumbers = computed(() => {
  const pages = [];
  const totalPageCount = totalPages.value;
  const current = props.currentPage;
  const visible = props.visiblePages;

  if (totalPageCount <= visible) {
    // Show all pages if total pages is less than or equal to visible pages
    for (let i = 1; i <= totalPageCount; i++) {
      pages.push(i);
    }
  } else {
    const half = Math.floor(visible / 2);
    let start = Math.max(1, current - half);
    let end = Math.min(totalPageCount, current + half);

    // Adjust if we're near the beginning or end
    if (current <= half) {
      start = 1;
      end = visible;
    } else if (current + half >= totalPageCount) {
      start = totalPageCount - visible + 1;
      end = totalPageCount;
    }

    // Add ellipsis and first page if needed
    if (start > 1) {
      pages.push(1);
      if (start > 2) {
        pages.push('...');
      }
    }

    // Add visible pages
    for (let i = start; i <= end; i++) {
      if (i !== 1 && i !== totalPageCount) {
        pages.push(i);
      }
    }

    // Add ellipsis and last page if needed
    if (end < totalPageCount) {
      if (end < totalPageCount - 1) {
        pages.push('...');
      }
      pages.push(totalPageCount);
    }

    // Ensure first and last pages are included
    if (!pages.includes(1) && totalPageCount > 0) {
      pages.unshift(1);
    }
    if (!pages.includes(totalPageCount) && totalPageCount > 1) {
      pages.push(totalPageCount);
    }
  }

  return pages;
});

// Event handlers
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value && page !== props.currentPage) {
    emit('page-change', page);
  }
};

const goToPrevPage = () => {
  if (hasPrevPage.value) {
    goToPage(props.currentPage - 1);
  }
};

const goToNextPage = () => {
  if (hasNextPage.value) {
    goToPage(props.currentPage + 1);
  }
};
</script>

<template>
  <div 
    v-if="totalPages > 1" 
    class="flex items-center justify-between mt-8"
  >
    <!-- Items info -->
    <p 
      v-if="showItemsInfo" 
      class="text-sm text-black/60 dark:text-white/60"
    >
      <template v-if="totalItems > 0">
        Showing {{ startItem }}-{{ endItem }} of {{ totalItems }} {{ itemsText }}
      </template>
      <template v-else>
        No {{ itemsText }} found
      </template>
    </p>
    <div v-else></div>

    <!-- Pagination buttons -->
    <div class="flex items-center gap-2">
      <!-- Previous button -->
      <button 
        @click="goToPrevPage"
        :disabled="!hasPrevPage"
        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
        :class="{ 'hover:bg-gray-50 dark:hover:bg-gray-800': hasPrevPage }"
      >
        <span class="material-symbols-outlined text-sm">chevron_left</span>
      </button>

      <!-- Page numbers -->
      <template v-for="page in visiblePageNumbers" :key="page">
        <!-- Ellipsis -->
        <span 
          v-if="page === '...'" 
          class="px-2 text-gray-400"
        >
          ...
        </span>
        
        <!-- Page number button -->
        <button 
          v-else
          @click="goToPage(page)"
          :class="[
            'px-3 py-1.5 rounded-lg transition-all',
            page === currentPage 
              ? 'bg-primary text-white' 
              : 'border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
          ]"
        >
          {{ page }}
        </button>
      </template>

      <!-- Next button -->
      <button 
        @click="goToNextPage"
        :disabled="!hasNextPage"
        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
        :class="{ 'hover:bg-gray-50 dark:hover:bg-gray-800': hasNextPage }"
      >
        <span class="material-symbols-outlined text-sm">chevron_right</span>
      </button>
    </div>
  </div>
</template>
