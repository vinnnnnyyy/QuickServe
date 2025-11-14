<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import FormInput from '@/Components/Admin/Forms/FormInput.vue';
import FormSelect from '@/Components/Admin/Forms/FormSelect.vue';
import FormTextarea from '@/Components/Admin/Forms/FormTextarea.vue';
import FormSection from '@/Components/Admin/Forms/FormSection.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

// Form data
const form = ref({
  number: '',
  location: '',
  capacity: '',
  description: '',
  notes: ''
});

const newLocation = ref('');
const showNewLocation = ref(false);
const newCapacity = ref('');
const showNewCapacity = ref(false);

// Location options with colors (matching Tables.vue)
const locationOptions = ref([
  { value: 'indoor', label: 'Indoor', color: 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400', dot: 'bg-green-500' },
  { value: 'outdoor', label: 'Outdoor', color: 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400', dot: 'bg-blue-500' },
  { value: 'patio', label: 'Patio', color: 'bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400', dot: 'bg-purple-500' },
  { value: 'bar', label: 'Bar', color: 'bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400', dot: 'bg-amber-500' }
]);

// Capacity options
const capacityOptions = ref([
  { value: '1', label: '1 Person (Single)' },
  { value: '2', label: '2 People (Couple)' },
  { value: '3', label: '3 People (Small Group)' },
  { value: '4', label: '4 People (Family)' },
  { value: '5', label: '5 People (Medium Group)' },
  { value: '6', label: '6 People (Large Group)' },
  { value: '8', label: '8 People (Large Table)' },
  { value: '10', label: '10 People (Conference)' },
  { value: '12', label: '12+ People (Event Table)' }
]);

// Methods
const handleLocationChange = (value) => {
  if (value === 'add_new') {
    showNewLocation.value = true;
    form.value.location = '';
  } else {
    showNewLocation.value = false;
    form.value.location = value;
  }
};

const addNewLocation = () => {
  if (newLocation.value.trim()) {
    const newValue = newLocation.value.trim().toLowerCase().replace(/\s+/g, '_');
    form.value.location = newValue;
    locationOptions.value.push({
      value: newValue,
      label: newLocation.value.trim(),
      color: 'bg-gray-100 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400',
      dot: 'bg-gray-500'
    });
    showNewLocation.value = false;
    newLocation.value = '';
  }
};

const cancelNewLocation = () => {
  showNewLocation.value = false;
  newLocation.value = '';
  form.value.location = '';
};

const handleCapacityChange = (value) => {
  if (value === 'add_new') {
    showNewCapacity.value = true;
    form.value.capacity = '';
  } else {
    showNewCapacity.value = false;
    form.value.capacity = value;
  }
};

const addNewCapacity = () => {
  if (newCapacity.value.trim() && !isNaN(parseInt(newCapacity.value))) {
    const capacityNum = parseInt(newCapacity.value.trim());
    if (capacityNum > 0 && capacityNum <= 50) { // Reasonable limits
      const newValue = capacityNum.toString();
      form.value.capacity = newValue;
      capacityOptions.value.push({
        value: newValue,
        label: `${capacityNum} ${capacityNum === 1 ? 'Person' : 'People'} (Custom)`
      });
      showNewCapacity.value = false;
      newCapacity.value = '';
    } else {
      alert('Please enter a valid capacity between 1 and 50.');
    }
  } else {
    alert('Please enter a valid number for capacity.');
  }
};

const cancelNewCapacity = () => {
  showNewCapacity.value = false;
  newCapacity.value = '';
  form.value.capacity = '';
};

const generateQRCode = (tableNumber) => {
  // Generate QR code format similar to existing tables
  return `QR_TABLE_${String(tableNumber).padStart(3, '0')}`;
};

const getLocationConfig = (locationValue) => {
  const location = locationOptions.value.find(loc => loc.value === locationValue);
  return location || {
    label: locationValue,
    color: 'bg-gray-100 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400',
    dot: 'bg-gray-500'
  };
};

const submitForm = () => {
  // Validate required fields
  if (!form.value.number || !form.value.location || !form.value.capacity) {
    alert('Please fill in all required fields.');
    return;
  }

  // Validate table number
  const tableNumber = parseInt(form.value.number);
  if (isNaN(tableNumber) || tableNumber <= 0) {
    alert('Please enter a valid table number.');
    return;
  }

  // Validate capacity
  const capacity = parseInt(form.value.capacity);
  if (isNaN(capacity) || capacity <= 0) {
    alert('Please enter a valid capacity.');
    return;
  }

  const locationConfig = getLocationConfig(form.value.location);

  const newTable = {
    id: Date.now(),
    number: tableNumber,
    location: locationConfig.label,
    locationColor: locationConfig.color,
    capacity: capacity,
    occupied: 0,
    status: 'available',
    statusColor: 'bg-green-200 dark:bg-green-800 border-green-200 dark:border-green-800',
    statusText: 'Available',
    statusDot: 'bg-green-500',
    qrCode: generateQRCode(tableNumber),
    sessions: [],
    description: form.value.description,
    notes: form.value.notes,
    createdAt: new Date().toISOString().split('T')[0]
  };

  console.log('New table:', newTable);
  alert(`Table ${form.value.number} has been added successfully!`);
  
  // Navigate back to tables page
  router.get('/admin/tables');
};

const saveDraft = () => {
  localStorage.setItem('tableDraft', JSON.stringify(form.value));
  alert('Draft saved successfully!');
};

const goBack = () => {
  router.get('/admin/tables');
};

// Auto-generate next table number (in real app, this would come from backend)
const generateNextTableNumber = () => {
  // This would typically be calculated from existing tables
  // For now, we'll start with a reasonable number
  return '7'; // Next available number after the sample data
};

// Set default table number on component mount
form.value.number = generateNextTableNumber();
</script>

<template>
  <AdminLayout 
    title="Add New Table"
    page-title="Add New Table"
    page-subtitle="Create a new table for your restaurant"
  >
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <button 
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
        >
          <span class="material-symbols-outlined">arrow_back</span>
          <span>Back to Tables</span>
        </button>
      </div>
    </div>

    <div class="max-w-7xl mx-auto">
      <form @submit.prevent="submitForm" class="space-y-12">
        <!-- Basic Information -->
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Basic Information"
            subtitle="Essential details about your table"
            icon="table_restaurant"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <FormInput
              v-model="form.number"
              label="Table Number"
              type="number"
              placeholder="Enter table number (e.g., 7)"
              min="1"
              max="999"
              required
            />
            
            <div class="space-y-2">
              <FormSelect
                v-model="form.capacity"
                label="Seating Capacity"
                placeholder="Select seating capacity"
                :options="capacityOptions"
                allow-custom
                custom-option-label="+ Add Custom Capacity"
                required
                @add-custom="handleCapacityChange('add_new')"
                @change="(e) => handleCapacityChange(e.target.value)"
              />
              
              <!-- New Capacity Input -->
              <div v-show="showNewCapacity" class="space-y-3 mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <FormInput
                  v-model="newCapacity"
                  type="number"
                  placeholder="Enter seating capacity (e.g., 14, 16, 20)"
                  min="1"
                  max="50"
                />
                <div class="flex gap-3">
                  <button 
                    type="button" 
                    @click="addNewCapacity"
                    class="px-4 py-2 bg-[#ec7813] text-white rounded-lg hover:bg-[#ea580c] transition-all text-sm font-medium"
                  >
                    <span class="flex items-center gap-2">
                      <span class="material-symbols-outlined text-sm">add</span>
                      Add
                    </span>
                  </button>
                  <button 
                    type="button" 
                    @click="cancelNewCapacity"
                    class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all text-sm font-medium"
                  >
                    Cancel
                  </button>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  <p><strong>Examples:</strong></p>
                  <p>• "14" - for large dining tables</p>
                  <p>• "16" - for conference/meeting tables</p>
                  <p>• "20" - for banquet or event tables</p>
                  <p>• "24" - for large group reservations</p>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6">
            <FormTextarea
              v-model="form.description"
              label="Table Description"
              placeholder="Describe the table's features, view, or special characteristics..."
              rows="3"
              max-length="300"
              show-char-count
            />
          </div>
        </CardWrapper>

        <!-- Location & Setup -->
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Location & Setup"
            subtitle="Table placement and area configuration"
            icon="location_on"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-2">
              <FormSelect
                v-model="form.location"
                label="Location Area"
                placeholder="Select table location"
                :options="locationOptions"
                allow-custom
                custom-option-label="+ Add New Location"
                required
                @add-custom="handleLocationChange('add_new')"
                @change="(e) => handleLocationChange(e.target.value)"
              />
              
              <!-- New Location Input -->
              <div v-show="showNewLocation" class="space-y-3 mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <FormInput
                  v-model="newLocation"
                  type="text"
                  placeholder="Enter new location name (e.g., Rooftop, Garden, VIP)"
                />
                <div class="flex gap-3">
                  <button 
                    type="button" 
                    @click="addNewLocation"
                    class="px-4 py-2 bg-[#ec7813] text-white rounded-lg hover:bg-[#ea580c] transition-all text-sm font-medium"
                  >
                    <span class="flex items-center gap-2">
                      <span class="material-symbols-outlined text-sm">add</span>
                      Add
                    </span>
                  </button>
                  <button 
                    type="button" 
                    @click="cancelNewLocation"
                    class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all text-sm font-medium"
                  >
                    Cancel
                  </button>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  <p><strong>Examples:</strong></p>
                  <p>• "Rooftop" - for outdoor terrace seating</p>
                  <p>• "Garden" - for outdoor garden area</p>
                  <p>• "VIP" - for premium/private dining</p>
                  <p>• "Window" - for tables with window views</p>
                </div>
              </div>
            </div>

            <!-- Location Preview -->
            <div class="space-y-4">
              <label class="block text-sm font-semibold text-gray-900 dark:text-white">Location Preview</label>
              
              <div class="bg-white dark:bg-gray-900/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="text-center">
                  <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl text-gray-500">table_restaurant</span>
                  </div>
                  
                  <div v-if="form.location && form.number" class="space-y-2">
                    <div class="flex items-center justify-center gap-2">
                      <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ form.number }}</span>
                      <span 
                        v-if="form.location" 
                        class="text-xs px-2 py-1 rounded-full"
                        :class="getLocationConfig(form.location).color"
                      >
                        {{ getLocationConfig(form.location).label }}
                      </span>
                    </div>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                      <p v-if="form.capacity">{{ form.capacity }} {{ form.capacity === '1' ? 'seat' : 'seats' }}</p>
                      <p class="text-xs mt-1">QR: {{ generateQRCode(form.number) }}</p>
                    </div>
                  </div>
                  
                  <div v-else class="text-gray-500 dark:text-gray-400">
                    <p class="text-sm">Preview will appear when</p>
                    <p class="text-sm">table details are filled</p>
                  </div>
                </div>
              </div>

              <!-- Location Stats -->
              <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Quick Stats</h4>
                <div class="grid grid-cols-2 gap-4 text-center">
                  <div>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">QR</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Auto-generated</p>
                  </div>
                  <div>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">Available</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Initial Status</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardWrapper>

        <!-- Additional Settings -->
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Additional Settings"
            subtitle="Extra configuration and notes"
            icon="settings"
          />
          
          <div class="space-y-6">
            <FormTextarea
              v-model="form.notes"
              label="Internal Notes"
              placeholder="Add any internal notes about this table (maintenance, special requirements, etc.)..."
              rows="3"
            />

            <!-- Features Checklist -->
            <div class="space-y-3">
              <label class="block text-sm font-semibold text-gray-900 dark:text-white">Table Features</label>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                  <input
                    type="checkbox"
                    class="w-4 h-4 rounded text-[#ec7813] focus:ring-[#ec7813] focus:ring-2"
                  >
                  <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm text-gray-600 dark:text-gray-400">visibility</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Window View</span>
                  </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                  <input
                    type="checkbox"
                    class="w-4 h-4 rounded text-[#ec7813] focus:ring-[#ec7813] focus:ring-2"
                  >
                  <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm text-gray-600 dark:text-gray-400">power</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Power Outlets</span>
                  </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                  <input
                    type="checkbox"
                    class="w-4 h-4 rounded text-[#ec7813] focus:ring-[#ec7813] focus:ring-2"
                  >
                  <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm text-gray-600 dark:text-gray-400">accessible</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Wheelchair Accessible</span>
                  </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                  <input
                    type="checkbox"
                    class="w-4 h-4 rounded text-[#ec7813] focus:ring-[#ec7813] focus:ring-2"
                  >
                  <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm text-gray-600 dark:text-gray-400">volume_off</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Quiet Area</span>
                  </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                  <input
                    type="checkbox"
                    class="w-4 h-4 rounded text-[#ec7813] focus:ring-[#ec7813] focus:ring-2"
                  >
                  <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm text-gray-600 dark:text-gray-400">star</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Premium Table</span>
                  </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                  <input
                    type="checkbox"
                    class="w-4 h-4 rounded text-[#ec7813] focus:ring-[#ec7813] focus:ring-2"
                  >
                  <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm text-gray-600 dark:text-gray-400">shield</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Private/Secluded</span>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </CardWrapper>

        <!-- Form Actions -->
        <CardWrapper rounded="xl" padding="lg" shadow="sm">
          <div class="flex flex-col sm:flex-row items-center justify-end gap-6">
            <button 
              type="button" 
              @click="goBack"
              class="w-full sm:w-auto px-8 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200 font-medium"
            >
              <span class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Cancel
              </span>
            </button>
            <button 
              type="button" 
              @click="saveDraft"
              class="w-full sm:w-auto px-8 py-3 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200 font-medium"
            >
              <span class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">draft</span>
                Save as Draft
              </span>
            </button>
            <button 
              type="submit"
              class="w-full sm:w-auto px-8 py-3 rounded-xl bg-[#ec7813] text-white hover:bg-[#ea580c] hover:shadow-lg transition-all duration-200 font-semibold"
            >
              <span class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">add_circle</span>
                Add Table
              </span>
            </button>
          </div>
        </CardWrapper>
      </form>
    </div>
  </AdminLayout>
</template>

<style scoped>
/* Component-specific styles if needed */
</style>
