<script setup>
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    tableNumber: {
        type: [Number, String],
        default: null
    },
    capacity: {
        type: Number,
        default: 0
    },
    tableId: {
        type: [Number, String],
        default: null
    }
})

const checkAgain = () => {
    window.location.reload()
}
</script>

<template>
    <Head title="Table Full" />

    <div class="min-h-screen bg-gradient-to-br from-orange-50 to-red-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 sm:p-12 max-w-md w-full text-center">
            <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-users-slash text-4xl text-red-500"></i>
            </div>
            
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">
                Table {{ tableNumber }} is Full
            </h1>
            
            <p class="text-gray-600 mb-6">
                This table has reached its maximum capacity of <span class="font-semibold text-gray-900">{{ capacity }} seats</span>.
            </p>
            
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-8">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-amber-500 mt-0.5"></i>
                    <div class="text-left">
                        <p class="text-sm text-amber-800 font-medium">What can you do?</p>
                        <ul class="text-sm text-amber-700 mt-1 space-y-1">
                            <li>- Wait for a seat to become available</li>
                            <li>- Ask staff for another table</li>
                            <li>- Scan a different table's QR code</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="space-y-3">
                <a 
                    href="/"
                    class="block w-full bg-gradient-to-r from-primary to-primary-600 text-white px-6 py-3.5 rounded-xl font-medium hover:shadow-lg hover:shadow-primary/25 transition-all"
                >
                    <i class="fas fa-qrcode mr-2"></i>
                    Scan Another Table
                </a>
                
                <button 
                    @click="checkAgain"
                    class="block w-full bg-white border border-gray-200 text-gray-700 px-6 py-3.5 rounded-xl font-medium hover:bg-gray-50 transition-colors"
                >
                    <i class="fas fa-redo mr-2"></i>
                    Check Again
                </button>
            </div>
            
            <p class="text-xs text-gray-400 mt-6">
                Refresh this page to check for availability
            </p>
        </div>
    </div>
</template>
