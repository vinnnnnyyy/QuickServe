<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const form = useForm({
    code: ''
})

const joinTable = () => {
    form.post('/join', {
        onFinish: () => form.reset('code'),
    })
}
</script>

<template>
  <Head title="Scan QR Code" />
  
  <div class="min-h-screen bg-gradient-to-br from-amber-50 to-orange-100 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
      <div class="mb-6">
        <div class="w-24 h-24 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-12 h-12 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
          </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-800 mb-2">
          Scan QR Code to Order
        </h1>
        
        <p class="text-gray-600 mb-6">
          Please scan the QR code on your table to access the menu and place your order.
        </p>
      </div>
      
      <div class="bg-amber-50 rounded-xl p-6 mb-6">
        <div class="flex items-start space-x-3">
          <svg class="w-6 h-6 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="text-left">
            <p class="text-sm text-amber-800 font-medium mb-1">How to order:</p>
            <ol class="text-sm text-amber-700 space-y-1 list-decimal list-inside">
              <li>Find the QR code on your table</li>
              <li>Open your phone's camera</li>
              <li>Point it at the QR code</li>
              <li>Tap the link to open the menu</li>
            </ol>
          </div>
        </div>
      </div>

       <!-- Manual Entry -->
      <div class="mb-6 border-t border-gray-100 pt-6">
        <p class="text-gray-600 mb-4 font-medium">Or enter table code:</p>
        <form @submit.prevent="joinTable" class="flex gap-2">
            <input 
                v-model="form.code" 
                type="text" 
                placeholder="Ex. K9X2M" 
                class="flex-1 rounded-lg border-gray-300 focus:border-amber-500 focus:ring focus:ring-amber-200 uppercase text-center font-mono placeholder:normal-case"
                maxlength="8"
            >
            <button 
                type="submit" 
                :disabled="form.processing || !form.code"
                class="bg-amber-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-amber-700 disabled:opacity-50 transition-colors"
            >
                Join
            </button>
        </form>
        <p v-if="form.errors.code" class="text-red-500 text-sm mt-2">{{ form.errors.code }}</p>
      </div>
      
      <p class="text-xs text-gray-500">
        Need help? Ask our staff for assistance.
      </p>
    </div>
  </div>
</template>
