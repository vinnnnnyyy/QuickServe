<script setup>
import { onMounted, onUnmounted } from 'vue';

const props = defineProps({
    customerName: String,
    tableNumber: [String, Number]
});

const emit = defineEmits(['cancel']);
</script>

<template>
    <div class="fixed inset-0 z-50 bg-white dark:bg-gray-900 flex flex-col items-center justify-center p-6 text-center">
        <!-- Animation -->
        <div class="mb-8 relative">
            <div class="absolute inset-0 bg-primary-100 rounded-full animate-ping opacity-75"></div>
            <div class="relative w-24 h-24 bg-primary-50 text-primary-600 rounded-full flex items-center justify-center shadow-xl border-4 border-white dark:border-gray-800">
                <i class="fas fa-hourglass-half text-4xl animate-pulse"></i>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Request Sent!</h2>
        <p class="text-gray-500 max-w-xs mx-auto mb-8">
            Please wait for the host at <span class="font-bold text-primary-600">Table {{ tableNumber }}</span> to approve your request.
        </p>

        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 w-full max-w-xs mb-8 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                 <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                    <i class="fas fa-user text-gray-500"></i>
                 </div>
                 <div class="text-left">
                    <p class="text-xs text-gray-400 uppercase font-bold">Joining as</p>
                    <p class="font-semibold text-gray-800 dark:text-white">{{ customerName }}</p>
                 </div>
            </div>
        </div>

        <button @click="$emit('cancel')" 
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-sm font-medium transition-colors">
            Cancel Request
        </button>
    </div>
</template>
