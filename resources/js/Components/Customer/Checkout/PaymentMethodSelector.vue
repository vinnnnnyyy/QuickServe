<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const paymentMethods = [
    {
        id: 'gcash',
        name: 'GCash',
        description: 'Pay via GCash mobile wallet',
        icon: 'fas fa-mobile-alt',
        color: 'text-blue-600',
        bgColor: 'bg-blue-50',
        borderColor: 'border-blue-200',
        available: true
    },
    {
        id: 'cash',
        name: 'Pay Cash',
        description: 'Pay with cash at your table',
        icon: 'fas fa-money-bill-wave',
        color: 'text-green-600',
        bgColor: 'bg-green-50',
        borderColor: 'border-green-200',
        available: true
    },
    {
        id: 'card',
        name: 'Credit/Debit Card',
        description: 'Visa, MasterCard, etc.',
        icon: 'fas fa-credit-card',
        color: 'text-purple-600',
        bgColor: 'bg-purple-50',
        borderColor: 'border-purple-200',
        available: false
    },
    {
        id: 'paymaya',
        name: 'PayMaya',
        description: 'Digital wallet payment',
        icon: 'fas fa-wallet',
        color: 'text-orange-600',
        bgColor: 'bg-orange-50',
        borderColor: 'border-orange-200',
        available: false
    }
]

const selectedMethod = computed({
    get() {
        return props.modelValue
    },
    set(value) {
        emit('update:modelValue', value)
    }
})

const selectPaymentMethod = (methodId) => {
    const method = paymentMethods.find(m => m.id === methodId)
    if (method && method.available) {
        selectedMethod.value = methodId
    }
}
</script>

<template>
    <div class="space-y-3">
        <div 
            v-for="method in paymentMethods" 
            :key="method.id"
            @click="selectPaymentMethod(method.id)"
            :class="[
                'relative border-2 rounded-lg p-4 cursor-pointer transition-all duration-200',
                selectedMethod === method.id && method.available
                    ? `${method.borderColor} ${method.bgColor} ring-2 ring-opacity-20`
                    : 'border-gray-200 hover:border-gray-300',
                !method.available ? 'opacity-50 cursor-not-allowed' : ''
            ]"
        >
            <!-- Selection indicator -->
            <div 
                v-if="selectedMethod === method.id && method.available"
                class="absolute top-3 right-3"
            >
                <div :class="['w-5 h-5 rounded-full flex items-center justify-center text-white text-xs', method.color.replace('text-', 'bg-')]">
                    <i class="fas fa-check"></i>
                </div>
            </div>

            <!-- Payment method content -->
            <div class="flex items-center">
                <div :class="['text-2xl mr-4', method.color]">
                    <i :class="method.icon"></i>
                </div>
                
                <div class="flex-1">
                    <div class="flex items-center">
                        <h4 class="text-lg font-semibold text-gray-900">{{ method.name }}</h4>
                        <span v-if="!method.available" class="ml-2 text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded-full">
                            Coming Soon
                        </span>
                    </div>
                    <p class="text-sm text-gray-600">{{ method.description }}</p>
                </div>
                
                <!-- Radio button visual -->
                <div class="ml-4">
                    <div 
                        :class="[
                            'w-5 h-5 rounded-full border-2 flex items-center justify-center',
                            selectedMethod === method.id && method.available
                                ? `border-current ${method.color}`
                                : 'border-gray-300'
                        ]"
                    >
                        <div 
                            v-if="selectedMethod === method.id && method.available"
                            :class="['w-2 h-2 rounded-full', method.color.replace('text-', 'bg-')]"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Additional info for specific methods -->
            <div v-if="method.id === 'gcash' && selectedMethod === method.id" class="mt-3 pt-3 border-t border-blue-200">
                <div class="flex items-center text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    You will be redirected to GCash for secure payment
                </div>
            </div>

            <div v-if="method.id === 'cash' && selectedMethod === method.id" class="mt-3 pt-3 border-t border-green-200">
                <div class="flex items-center text-sm text-green-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Our staff will collect payment at your table
                </div>
            </div>
        </div>
    </div>
</template>
