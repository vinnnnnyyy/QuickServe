<script setup>
import { ref, computed, watch } from 'vue';
import AdminModal from '@/Components/Admin/UI/AdminModal.vue';
import FormInput from '@/Components/Admin/Forms/FormInput.vue';
import FormSelect from '@/Components/Admin/Forms/FormSelect.vue';
import FormTextarea from '@/Components/Admin/Forms/FormTextarea.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  existingCategories: {
    type: Array,
    default: () => ['Extras', 'Milk', 'Toppings', 'Syrups']
  }
});

const emit = defineEmits(['close', 'created']);

const isSubmitting = ref(false);
const showNewCategory = ref(false);
const newCategoryName = ref('');
const errors = ref({});

const form = ref({
  name: '',
  description: '',
  price: '',
  category: '',
  available: true,
  max_quantity: 1
});

const categoryOptions = computed(() => {
  const categories = [...new Set([...props.existingCategories])];
  return categories.map(cat => ({
    value: cat,
    label: cat
  }));
});

const maxQuantityOptions = [
  { value: 1, label: '1' },
  { value: 2, label: '2' },
  { value: 3, label: '3' },
  { value: 4, label: '4' },
  { value: 5, label: '5' },
  { value: 6, label: '6' },
  { value: 7, label: '7' },
  { value: 8, label: '8' },
  { value: 9, label: '9' },
  { value: 10, label: '10' }
];

const resetForm = () => {
  form.value = {
    name: '',
    description: '',
    price: '',
    category: '',
    available: true,
    max_quantity: 1
  };
  errors.value = {};
  showNewCategory.value = false;
  newCategoryName.value = '';
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm();
  }
});

const handleCategoryChange = (e) => {
  const value = e?.target?.value || e;
  if (value === 'add_new') {
    showNewCategory.value = true;
    form.value.category = '';
  } else {
    showNewCategory.value = false;
    form.value.category = value;
  }
};

const addNewCategory = () => {
  const categoryName = newCategoryName.value.trim();
  if (!categoryName) return;
  
  form.value.category = categoryName;
  showNewCategory.value = false;
  newCategoryName.value = '';
};

const cancelNewCategory = () => {
  showNewCategory.value = false;
  newCategoryName.value = '';
};

const validateForm = () => {
  errors.value = {};
  
  if (!form.value.name.trim()) {
    errors.value.name = 'Name is required';
  }
  
  if (!form.value.price || parseFloat(form.value.price) < 0) {
    errors.value.price = 'Valid price is required';
  }
  
  if (!form.value.category.trim()) {
    errors.value.category = 'Category is required';
  }
  
  return Object.keys(errors.value).length === 0;
};

const submitForm = async () => {
  if (!validateForm()) return;
  
  isSubmitting.value = true;
  
  try {
    const response = await fetch('/api/addons', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        name: form.value.name.trim(),
        description: form.value.description.trim() || null,
        price: parseFloat(form.value.price),
        category: form.value.category.trim(),
        available: form.value.available,
        max_quantity: parseInt(form.value.max_quantity)
      })
    });
    
    if (response.ok) {
      const newAddon = await response.json();
      emit('created', newAddon);
      emit('close');
    } else {
      const error = await response.json();
      if (error.errors) {
        errors.value = error.errors;
      } else {
        alert('Error: ' + (error.message || 'Failed to create add-on'));
      }
    }
  } catch (error) {
    console.error('Error creating addon:', error);
    alert('Failed to create add-on. Please try again.');
  } finally {
    isSubmitting.value = false;
  }
};

const closeModal = () => {
  emit('close');
};
</script>

<template>
  <AdminModal
    :show="show"
    title="Create New Add-on"
    subtitle="Add a new customization option for menu items"
    icon="extension"
    max-width="2xl"
    @close="closeModal"
  >
    <form @submit.prevent="submitForm" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <FormInput
          v-model="form.name"
          label="Add-on Name"
          placeholder="e.g., Extra Shot, Oat Milk"
          required
          :error="errors.name"
        />
        
        <FormInput
          v-model="form.price"
          label="Price"
          type="number"
          placeholder="0.00"
          min="0"
          step="0.01"
          required
          :error="errors.price"
        />
      </div>

      <FormTextarea
        v-model="form.description"
        label="Description"
        placeholder="Optional description for this add-on..."
        rows="2"
        max-length="255"
      />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <FormSelect
            v-model="form.category"
            label="Category"
            placeholder="Select category"
            :options="categoryOptions"
            allow-custom
            custom-option-label="+ Add New Category"
            required
            :error="errors.category"
            @add-custom="handleCategoryChange('add_new')"
            @change="handleCategoryChange"
          />
          
          <div v-show="showNewCategory" class="space-y-3 mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
            <FormInput
              v-model="newCategoryName"
              placeholder="Enter new category name"
            />
            <div class="flex gap-3">
              <button 
                type="button" 
                @click="addNewCategory"
                class="px-4 py-2 bg-[#ec7813] text-white rounded-lg hover:bg-[#ea580c] transition-all text-sm font-medium"
              >
                <span class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-sm">add</span>
                  Add
                </span>
              </button>
              <button 
                type="button" 
                @click="cancelNewCategory"
                class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all text-sm font-medium"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
        
        <FormSelect
          v-model="form.max_quantity"
          label="Max Quantity Per Item"
          :options="maxQuantityOptions"
        />
      </div>

      <div class="space-y-4">
        <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
          <input
            v-model="form.available"
            type="checkbox"
            class="w-5 h-5 rounded text-[#ec7813] focus:ring-[#ec7813] focus:ring-2"
          >
          <div>
            <span class="text-sm font-medium text-gray-900 dark:text-white block">Available</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">Add-on is available for selection</span>
          </div>
        </label>
      </div>
    </form>

    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <button
          type="button"
          @click="closeModal"
          class="px-6 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 font-medium"
        >
          Cancel
        </button>
        <button
          type="button"
          @click="submitForm"
          :disabled="isSubmitting"
          class="px-6 py-2.5 rounded-xl bg-[#ec7813] text-white hover:bg-[#ea580c] transition-all duration-200 font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <span v-if="isSubmitting" class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
          <span class="material-symbols-outlined text-lg" v-else>add_circle</span>
          {{ isSubmitting ? 'Creating...' : 'Create Add-on' }}
        </button>
      </div>
    </template>
  </AdminModal>
</template>
