<script setup>
import { computed } from 'vue'
import { useOrderWorkflow } from '../../../composables/useOrderWorkflow.js'

const props = defineProps({
    status: {
        type: String,
        required: true
    }
})

const { normalizeStatusToStepIndex, isTerminalStatus, getStatusInfo } = useOrderWorkflow()

// Define the 5 steps for customer tracker
const steps = [
    { label: 'Confirmed', icon: 'fas fa-check-circle' },
    { label: 'In Process', icon: 'fas fa-receipt' },
    { label: 'Queued', icon: 'fas fa-clock' },
    { label: 'Preparation', icon: 'fas fa-utensils' },
    { label: 'Ready', icon: 'fas fa-bell' }
]

// Compute current step from status
const currentStep = computed(() => {
    const step = normalizeStatusToStepIndex(props.status)
    console.log(`OrderStatusTracker: status="${props.status}", step=${step}`)
    return step
})

// Check if terminal
const terminal = computed(() => isTerminalStatus(props.status))
const terminalInfo = computed(() => {
    if (!terminal.value) return null
    return getStatusInfo(props.status)
})

// Helper to determine step state
const getStepState = (index) => {
    const current = currentStep.value
    if (current === -1) return 'future' // Unknown status
    
    // All steps up to AND INCLUDING current should be shown as completed/active
    if (index < current) return 'completed' // Steps before current = completed (green check)
    if (index === current) {
        // Current step is also "completed" with check mark, but styled differently
        // This shows progression: when "confirmed", both "In Process" and "Confirmed" are checked
        return 'current' // Current step = checked but with current styling
    }
    return 'future' // Steps after current = future (gray)
}
</script>

<template>
    <div class="w-full">
        <!-- 5-step tracker -->
        <div class="relative">
            <!-- Desktop: horizontal layout -->
            <ol class="hidden sm:flex justify-between items-center mb-6">
                <li 
                    v-for="(step, index) in steps"
                    :key="index"
                    class="relative flex flex-col items-center flex-1"
                >
                    <!-- Step circle -->
                    <div class="relative flex items-center justify-center z-10">
                        <!-- Completed state -->
                        <div 
                            v-if="getStepState(index) === 'completed'"
                            class="w-14 h-14 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white shadow-lg transform motion-reduce:transform-none animate-scale-in"
                        >
                            <i class="fas fa-check text-xl"></i>
                        </div>

                        <!-- Current state with pulse -->
                        <div 
                            v-else-if="getStepState(index) === 'current'"
                            class="relative"
                        >
                            <!-- Pulsing rings -->
                            <div class="absolute inset-0 w-14 h-14 rounded-full bg-blue-400 motion-reduce:hidden animate-ping opacity-30"></div>
                            <div class="absolute inset-1 w-12 h-12 rounded-full bg-blue-400 motion-reduce:hidden animate-ping opacity-50 animation-delay-75"></div>
                            <!-- Main circle -->
                            <div class="relative w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white shadow-xl">
                                <i :class="step.icon" class="text-xl"></i>
                            </div>
                        </div>

                        <!-- Future state -->
                        <div 
                            v-else
                            class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500 border-2 border-gray-200 dark:border-gray-600"
                        >
                            <i :class="step.icon" class="text-lg"></i>
                        </div>
                    </div>

                    <!-- Step label -->
                    <div class="mt-4 text-center">
                        <p 
                            class="text-sm font-medium transition-colors duration-200"
                            :class="{
                                'text-green-600 dark:text-green-400': getStepState(index) === 'completed',
                                'text-blue-600 dark:text-blue-400 font-semibold': getStepState(index) === 'current',
                                'text-gray-400 dark:text-gray-500': getStepState(index) === 'future'
                            }"
                        >
                            {{ step.label }}
                        </p>
                    </div>

                    <!-- Connector line -->
                    <div 
                        v-if="index < steps.length - 1"
                        class="absolute top-7 left-1/2 w-full h-1 -z-10 transition-colors duration-300"
                        :class="{
                            'bg-gradient-to-r from-green-500 to-green-400': getStepState(index) === 'completed',
                            'bg-gradient-to-r from-blue-400 to-gray-200 dark:to-gray-600': getStepState(index) === 'current',
                            'bg-gray-200 dark:bg-gray-600': getStepState(index) === 'future'
                        }"
                    ></div>
                </li>
            </ol>

            <!-- Mobile: vertical layout -->
            <ol class="sm:hidden space-y-4 mb-6">
                <li 
                    v-for="(step, index) in steps"
                    :key="index"
                    class="flex items-center space-x-4"
                >
                    <!-- Step circle -->
                    <div class="relative flex items-center justify-center flex-shrink-0">
                        <!-- Completed state -->
                        <div 
                            v-if="getStepState(index) === 'completed'"
                            class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white shadow-lg"
                        >
                            <i class="fas fa-check text-lg"></i>
                        </div>

                        <!-- Current state -->
                        <div 
                            v-else-if="getStepState(index) === 'current'"
                            class="relative"
                        >
                            <div class="absolute inset-0 w-12 h-12 rounded-full bg-blue-400 motion-reduce:hidden animate-ping opacity-30"></div>
                            <div class="relative w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white shadow-lg">
                                <i :class="step.icon" class="text-lg"></i>
                            </div>
                        </div>

                        <!-- Future state -->
                        <div 
                            v-else
                            class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500 border-2 border-gray-200 dark:border-gray-600"
                        >
                            <i :class="step.icon" class="text-lg"></i>
                        </div>
                    </div>

                    <!-- Step info -->
                    <div class="flex-1 min-w-0">
                        <p 
                            class="text-base font-medium transition-colors duration-200"
                            :class="{
                                'text-green-600 dark:text-green-400': getStepState(index) === 'completed',
                                'text-blue-600 dark:text-blue-400 font-semibold': getStepState(index) === 'current',
                                'text-gray-400 dark:text-gray-500': getStepState(index) === 'future'
                            }"
                        >
                            {{ step.label }}
                        </p>
                        <p 
                            v-if="getStepState(index) === 'current'"
                            class="text-sm text-blue-500 dark:text-blue-400 mt-1"
                        >
                            Currently in progress...
                        </p>
                    </div>

                    <!-- Connector line for mobile -->
                    <div 
                        v-if="index < steps.length - 1"
                        class="absolute left-6 mt-12 w-0.5 h-8 transition-colors duration-300"
                        :class="{
                            'bg-green-400': getStepState(index) === 'completed',
                            'bg-blue-300': getStepState(index) === 'current',
                            'bg-gray-200 dark:bg-gray-600': getStepState(index) === 'future'
                        }"
                    ></div>
                </li>
            </ol>
        </div>

        <!-- Terminal status banner -->
        <div 
            v-if="terminal && terminalInfo"
            class="mt-6 p-4 rounded-xl text-center text-sm font-medium border shadow-sm transition-all duration-200"
            :class="{
                'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800': terminalInfo.color === 'green',
                'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800': terminalInfo.color === 'red',
                'bg-gray-50 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-700': terminalInfo.color === 'gray'
            }"
        >
            <div class="flex items-center justify-center space-x-2">
                <i 
                    :class="{
                        'fas fa-check-circle': terminalInfo.color === 'green',
                        'fas fa-times-circle': terminalInfo.color === 'red',
                        'fas fa-info-circle': terminalInfo.color === 'gray'
                    }"
                    class="text-lg"
                ></i>
                <span>
                    <strong>{{ terminalInfo.label }}:</strong> {{ terminalInfo.description }}
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes scale-in {
    from {
        transform: scale(0.5);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-scale-in {
    animation: scale-in 0.3s ease-out;
}
</style>

