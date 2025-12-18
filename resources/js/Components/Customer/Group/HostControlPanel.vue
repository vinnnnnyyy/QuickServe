<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    tableId: [String, Number],
    currentPaymentMode: {
        type: String,
        default: 'host'
    }
});

const emit = defineEmits(['refresh', 'payment-mode-changed']);

const activeGuests = computed(() => props.users.filter(u => u.status === 'approved' && u.role !== 'host'));
const pendingRequests = computed(() => props.users.filter(u => u.status === 'pending'));

const isUpdatingMode = ref(false);
const localPaymentMode = ref(props.currentPaymentMode);

watch(() => props.currentPaymentMode, (newVal) => {
    localPaymentMode.value = newVal;
});

const handleAction = async (deviceId, action) => {
    try {
        await axios.post('/api/session/guest-action', {
            table_id: props.tableId,
            target_device_id: deviceId,
            action: action
        });
        emit('refresh'); // Trigger refresh of user list
    } catch (error) {
        console.error('Action failed', error);
        alert('Failed to process request');
    }
};

const togglePaymentMode = async () => {
    const newMode = localPaymentMode.value === 'host' ? 'individual' : 'host';
    isUpdatingMode.value = true;
    try {
        await axios.post('/api/session/update-settings', {
            table_id: props.tableId,
            payment_mode: newMode
        });
        localPaymentMode.value = newMode;
        emit('payment-mode-changed', newMode);
        emit('refresh');
    } catch (error) {
        console.error('Failed to update payment mode', error);
        alert('Failed to update setting');
    } finally {
        isUpdatingMode.value = false;
    }
};
</script>

<template>
    <div class="host-panel bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
        <div class="flex justify-between items-center mb-4 border-b pb-2 dark:border-gray-700">
            <h3 class="font-bold text-lg text-gray-800 dark:text-white">
                <i class="fas fa-crown text-yellow-500 mr-2"></i> Host Control
            </h3>
            <span class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded">
                Group ID: #{{ tableId }}
            </span>
        </div>

        <!-- Payment Mode Toggle -->
        <div class="mb-4 p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg border border-indigo-100 dark:border-indigo-800">
            <label class="flex items-center justify-between cursor-pointer">
                <div>
                    <p class="font-semibold text-sm text-gray-800 dark:text-gray-200">Bill Setting</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ localPaymentMode === 'host' ? 'You pay for everyone' : 'Everyone pays their own' }}
                    </p>
                </div>
                <button 
                    @click="togglePaymentMode"
                    :disabled="isUpdatingMode"
                    :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                        localPaymentMode === 'host' ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600'
                    ]"
                >
                    <span 
                        :class="[
                            'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                            localPaymentMode === 'host' ? 'translate-x-6' : 'translate-x-1'
                        ]"
                    />
                </button>
            </label>
            <div class="flex gap-2 mt-2 text-xs">
                <span :class="['px-2 py-0.5 rounded-full', localPaymentMode === 'individual' ? 'bg-green-100 text-green-700 font-bold' : 'text-gray-400']">
                    Split Bill
                </span>
                <span :class="['px-2 py-0.5 rounded-full', localPaymentMode === 'host' ? 'bg-indigo-100 text-indigo-700 font-bold' : 'text-gray-400']">
                    Host Pays
                </span>
            </div>
        </div>

        <!-- Pending Requests -->
        <div v-if="pendingRequests.length > 0" class="mb-4">
            <h4 class="text-xs font-bold text-orange-600 uppercase tracking-wide mb-2 animate-pulse">
                Pending Requests ({{ pendingRequests.length }})
            </h4>
            <div class="space-y-2">
                <div v-for="req in pendingRequests" :key="req.device_id" 
                     class="flex items-center justify-between bg-orange-50 dark:bg-orange-900/20 p-3 rounded-lg border border-orange-100 dark:border-orange-800">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm text-gray-800 dark:text-gray-200">{{ req.name }}</p>
                            <p class="text-[10px] text-gray-500">Wants to join</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="handleAction(req.device_id, 'reject')" 
                                class="p-2 text-red-500 hover:bg-red-50 rounded-full transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                        <button @click="handleAction(req.device_id, 'approve')" 
                                class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full shadow hover:bg-green-600 transition-all">
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Guests -->
        <div>
            <div class="flex justify-between items-center mb-2">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide">
                    Active Guests ({{ activeGuests.length }})
                </h4>
            </div>
            
            <div v-if="activeGuests.length === 0" class="text-center py-4 text-gray-400 text-sm italic bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                No guests in the group yet.
            </div>

            <div v-else class="grid grid-cols-2 gap-2">
                <div v-for="guest in activeGuests" :key="guest.device_id"
                     class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 p-2 rounded border border-gray-100 dark:border-gray-600">
                     <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ guest.name }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

