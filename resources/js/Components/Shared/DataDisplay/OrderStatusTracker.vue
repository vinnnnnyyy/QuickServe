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
                            class="w-10 h-10 rounded-full bg-[#3B82F6] flex items-center justify-center text-white transition-all duration-300"
                        >
                            <i class="fas fa-check text-sm"></i>
                        </div>

                        <!-- Current state with pulse -->
                        <div 
                            v-else-if="getStepState(index) === 'current'"
                            class="relative"
                        >
                            <!-- Pulsing rings -->
                            <div class="absolute inset-0 w-10 h-10 rounded-full bg-[#3B82F6] motion-reduce:hidden animate-ping opacity-20"></div>
                            <!-- Main circle -->
                            <div class="relative w-10 h-10 rounded-full bg-[#3B82F6] flex items-center justify-center text-white z-10 ring-4 ring-blue-50 dark:ring-blue-900/20">
                                <i :class="step.icon" class="text-sm"></i>
                            </div>
                        </div>

                        <!-- Future state -->
                        <div 
                            v-else
                            class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 flex items-center justify-center text-gray-300 dark:text-gray-600 border border-gray-200 dark:border-gray-700"
                        >
                            <i :class="step.icon" class="text-sm"></i>
                        </div>
                    </div>

                    <!-- Step label -->
                    <div class="mt-3 text-center">
                        <p 
                            class="text-xs font-medium transition-colors duration-200 uppercase tracking-wider"
                            :class="{
                                'text-gray-900 dark:text-gray-100': getStepState(index) === 'completed',
                                'text-[#3B82F6] font-bold': getStepState(index) === 'current',
                                'text-gray-400 dark:text-gray-500': getStepState(index) === 'future'
                            }"
                        >
                            {{ step.label }}
                        </p>
                    </div>

                    <!-- Connector line -->
                    <div 
                        v-if="index < steps.length - 1"
                        class="absolute top-5 left-1/2 w-full h-0.5 -z-10 transition-colors duration-300"
                        :class="{
                            'bg-[#3B82F6]': getStepState(index) === 'completed',
                            'bg-gray-200 dark:bg-gray-700': getStepState(index) !== 'completed'
                        }"
                    ></div>
                </li>
            </ol>

            <!-- Mobile: vertical layout -->
            <ol class="sm:hidden space-y-4 mb-6">
                <li 
                    v-for="(step, index) in steps"
                    :key="index"
                    class="relative flex items-start space-x-4"
                >
                    <!-- Step circle -->
                    <div class="relative z-10 flex flex-col items-center">
                        <!-- Completed state -->
                        <div 
                            v-if="getStepState(index) === 'completed'"
                            class="w-8 h-8 rounded-full bg-[#3B82F6] flex items-center justify-center text-white"
                        >
                            <i class="fas fa-check text-xs"></i>
                        </div>

                        <!-- Current state -->
                        <div 
                            v-else-if="getStepState(index) === 'current'"
                            class="relative"
                        >
                            <div class="absolute inset-0 w-8 h-8 rounded-full bg-[#3B82F6] motion-reduce:hidden animate-ping opacity-20"></div>
                            <div class="relative w-8 h-8 rounded-full bg-[#3B82F6] flex items-center justify-center text-white ring-4 ring-blue-50 dark:ring-blue-900/20">
                                <i :class="step.icon" class="text-xs"></i>
                            </div>
                        </div>

                        <!-- Future state -->
                        <div 
                            v-else
                            class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 flex items-center justify-center text-gray-300 dark:text-gray-600 border border-gray-200 dark:border-gray-700"
                        >
                            <i :class="step.icon" class="text-xs"></i>
                        </div>
                    </div>

                    <!-- Connector line for mobile -->
                    <div 
                        v-if="index < steps.length - 1"
                        class="absolute left-4 top-8 w-0.5 h-[calc(100%+1rem)] -ml-px -z-0"
                        :class="{
                            'bg-[#3B82F6]': getStepState(index) === 'completed',
                            'bg-gray-200 dark:bg-gray-700': getStepState(index) !== 'completed'
                        }"
                    ></div>

                    <!-- Step info -->
                    <div class="flex-1 min-w-0 pt-1">
                        <p 
                            class="text-sm font-medium transition-colors duration-200"
                            :class="{
                                'text-gray-900 dark:text-gray-100': getStepState(index) === 'completed',
                                'text-[#3B82F6] font-bold': getStepState(index) === 'current',
                                'text-gray-400 dark:text-gray-500': getStepState(index) === 'future'
                            }"
                        >
                            {{ step.label }}
                        </p>
                        <p 
                            v-if="getStepState(index) === 'current'"
                            class="text-xs text-blue-500 dark:text-blue-400 mt-0.5"
                        >
                            Currently in progress...
                        </p>
                    </div>
                </li>
            </ol>
        </div>

        <!-- Terminal status banner -->
        <div 
            v-if="terminal && terminalInfo"
            class="mt-6 p-4 rounded-xl text-center text-sm font-medium border transition-all duration-200"
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

