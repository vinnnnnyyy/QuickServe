<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import FormInput from '@/Components/Admin/Forms/FormInput.vue';
import FormSelect from '@/Components/Admin/Forms/FormSelect.vue';
import FormTextarea from '@/Components/Admin/Forms/FormTextarea.vue';
import FormSection from '@/Components/Admin/Forms/FormSection.vue';
import { ref, watch, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3'; // Import useForm
import { useMenuCategories } from '@/composables/useMenuCategories.js';

// --- Props ---
const props = defineProps({
  categories: {
    type: Array,
    default: () => []
  }
});

// --- Form Data ---
// Replace ref with useForm
const form = useForm({
  name: '',
  description: '',
  category_id: null,      // **CHANGED**: Expecting an ID
  price: '',
  temperature: 'Hot',
  prep_time: '',
  size_preset: '3',       // **RENAMED**: This controls the labels
  size_labels: ['Small', 'Medium', 'Large'], // **ADDED**: This is sent to DB
  featured: false,
  popular: false,
  available: true,
  image: null,            // This will hold the File object
  notes: ''
});

const imagePreview = ref(null);
const newCategory = ref('');
const showNewCategory = ref(false);
const newSize = ref('');
const showNewSize = ref(false);
// isSubmitting is replaced by form.processing

// --- Categories ---
const { 
  getFormSelectOptions, 
  addCategory, 
  getCategoryLabel,
  allCategories 
} = useMenuCategories();

// Initialize category options with database categories
const categoryOptions = ref([]);

// Load categories from database on mount
onMounted(() => {
  // Convert database categories to the format expected by FormSelect
  const dbCategories = props.categories.map(cat => ({
    value: cat.id,
    label: cat.name
  }));
  
  categoryOptions.value = dbCategories;
});

// --- Temperature ---
const temperatureOptions = [
  { value: 'Hot', label: 'Hot' },
  { value: 'Cold', label: 'Cold' },
  { value: 'Both', label: 'Hot & Cold' }
];

// --- Sizes ---
// This map translates the preset dropdown to the array the DB needs
const sizePresetMap = {
  '1': ['Regular'],
  '2': ['Small', 'Large'],
  '3': ['Small', 'Medium', 'Large'],
  '4': ['XS', 'S', 'M', 'L'],
  '5': ['XS', 'S', 'M', 'L', 'XL'],
};
const sizeOptions = ref([
  { value: '1', label: '1 Size (Regular)' },
  { value: '2', label: '2 Sizes (Small, Large)' },
  { value: '3', label: '3 Sizes (Small, Medium, Large)' },
  { value: '4', label: '4 Sizes (XS, S, M, L)' },
  { value: '5', label: '5 Sizes (XS, S, M, L, XL)' }
]);

// Watch the preset and update the form.size_labels array
watch(() => form.size_preset, (newPreset) => {
  if (sizePresetMap[newPreset]) {
    form.size_labels = sizePresetMap[newPreset];
  }
});


// --- Methods ---

const handleCategoryChange = (value) => {
  if (value === 'add_new') {
    showNewCategory.value = true;
    form.category_id = null; // **CHANGED**
  } else {
    showNewCategory.value = false;
    form.category_id = value; // **CHANGED**
  }
};

// **UPDATED**: This now saves to the database
const addNewCategory = async () => {
  const categoryName = newCategory.value.trim();
  if (!categoryName) return;

  try {
    // 1. Send to the new API endpoint
    const response = await fetch(route('api.categories.store'), { // Use Ziggy's route() helper
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        name: categoryName,
        scope: 'menu' // Matches your 'categories' migration
      })
    });

    if (response.ok) {
      const newCategoryData = await response.json(); // e.g., { id: 5, name: 'Pastries' }

      // 2. Add to the local dropdown options
      categoryOptions.value.push({
        value: newCategoryData.id,
        label: newCategoryData.name
      });

      // 3. Set the form's value to the new ID
      form.category_id = newCategoryData.id;
      
      // 4. Reset UI
      showNewCategory.value = false;
      newCategory.value = '';

    } else {
      const error = await response.json();
      alert('Error: ' + (error.message || 'Category might already exist.'));
    }
  } catch (error) {
    console.error('Error adding category:', error);
    alert('Failed to add category.');
  }
};

const cancelNewCategory = () => {
  showNewCategory.value = false;
  newCategory.value = '';
  form.category_id = null; // **CHANGED**
};

const handleSizeChange = (value) => {
  if (value === 'add_new') {
    showNewSize.value = true;
    form.size_preset = null; // **CHANGED**
  } else {
    showNewSize.value = false;
    form.size_preset = value; // **CHANGED**
  }
};

const addNewSize = () => {
  if (newSize.value.trim()) {
    const newLabel = newSize.value.trim();
    const newValue = `custom_${sizeOptions.value.length + 1}`;
    
    // Add to dropdown
    sizeOptions.value.push({
      value: newValue,
      label: newLabel
    });
    
    // Set form value
    form.size_preset = newValue;
    
    // **ADDED**: Update the map and the form.size_labels
    // This simple parser assumes labels are comma-separated
    const labels = newLabel.split('(')[0].split(',').map(s => s.trim());
    sizePresetMap[newValue] = labels;
    form.size_labels = labels;

    showNewSize.value = false;
    newSize.value = '';
  }
};

const cancelNewSize = () => {
  showNewSize.value = false;
  newSize.value = '';
  form.size_preset = '3'; // **CHANGED**
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  
  // Validate file type
  const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
  if (!validTypes.includes(file.type)) {
    alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
    return;
  }
  
  // Validate file size (10MB max)
  const maxSize = 10 * 1024 * 1024;
  if (file.size > maxSize) {
    alert('File size must be less than 10MB');
    return;
  }
  
  // **CHANGED**: Set the File object on the form
  form.image = file;

  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const clearImage = () => {
  imagePreview.value = null;
  form.image = null; // **CHANGED**
  document.getElementById('image-upload').value = '';
};


// **COMPLETELY REWRITTEN**
const submitForm = () => {
  // Client-side validation is still good
  if (!form.name || !form.category_id || !form.price || !form.description) {
    alert('Please fill in all required fields.');
    return;
  }

  // Use Inertia to POST the form.
  // This automatically handles file uploads (multipart/form-data).
  form.post(route('admin.menu.store'), {
    onError: (errors) => {
      // Show validation errors from Laravel
      console.error(errors);
      alert('Error: ' + Object.values(errors).join('\n'));
    },
    onSuccess: () => {
      // Controller will handle the redirect and success flash message
      // form.reset(); // Optionally reset the form
    }
  });
};

const saveDraft = () => {
  // **CHANGED**: Use form.data() to get values
  localStorage.setItem('menuItemDraft', JSON.stringify(form.data()));
  alert('Draft saved successfully!');
};

const goBack = () => {
  router.get('/admin/menu'); // Assuming this is your index
};
</script>

<template>
  <AdminLayout 
    title="Add New Menu Item"
    page-title="Add New Menu Item"
    page-subtitle="Create a new menu item for your cafe"
  >
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <button 
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
        >
          <span class="material-symbols-outlined">arrow_back</span>
          <span>Back to Menu</span>
        </button>
      </div>
    </div>

    <div class="max-w-7xl mx-auto">
      <form @submit.prevent="submitForm" class="space-y-12">
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Basic Information"
            subtitle="Essential details about your menu item"
            icon="restaurant_menu"
          />
          
          <div class="mb-8">
            <div class="bg-white dark:bg-gray-900/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
              <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-6">Item Image</label>
              <div class="flex flex-col sm:flex-row items-center gap-6">
                <div class="flex-shrink-0">
                  <div class="w-48 h-32 rounded-lg bg-gray-100 dark:bg-gray-800 border-4 border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden">
                    <div v-if="imagePreview" class="w-full h-full">
                      <img :src="imagePreview" alt="Item Preview" class="w-full h-full object-cover rounded-lg">
                    </div>
                    <div v-else class="w-full h-full flex items-center justify-center">
                      <span class="material-symbols-outlined text-4xl text-gray-400">image</span>
                    </div>
                  </div>
                </div>
                <div class="flex-1 space-y-4">
                  <div class="flex flex-col sm:flex-row gap-3">
                    <label class="flex-1 cursor-pointer">
                      <input 
                        type="file" 
                        accept="image/*" 
                        class="hidden" 
                        id="image-upload" 
                        @change="handleImageUpload"
                      >
                      <div class="flex items-center justify-center gap-2 px-6 py-3 rounded-xl border-2 border-dashed border-[#ec7813]/30 text-[#ec7813] hover:border-[#ec7813]/50 hover:bg-[#ec7813]/5 transition-all duration-200 font-medium">
                        <span class="material-symbols-outlined">upload</span>
                        <span>Upload Image</span>
                      </div>
                    </label>
                    <button 
                      type="button" 
                      @click="clearImage"
                      class="px-6 py-3 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 font-medium"
                    >
                      <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">delete</span>
                        Remove
                      </span>
                    </button>
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    <p>• Recommended: 800x600px or larger</p>
                    <p>• Supported formats: JPG, PNG, GIF, WebP</p>
                    <p>• Maximum file size: 10MB</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <FormInput
              v-model="form.name"
              label="Item Name"
              type="text"
              placeholder="Enter item name (e.g., Iced Brown Sugar Latte)"
              required
            />
            
            <FormInput
              v-model="form.price"
              label="Price"
              type="number"
              placeholder="0.00"
              min="0"
              step="0.01"
              required
            />
          </div>

          <div class="mt-6">
            <FormTextarea
              v-model="form.description"
              label="Description"
              placeholder="Describe your menu item, ingredients, and what makes it special..."
              rows="4"
              max-length="500"
              show-char-count
              required
            />
          </div>
        </CardWrapper>

        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Category & Details"
            subtitle="Categorization and serving information"
            icon="category"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-2">
              <FormSelect
                v-model="form.category_id" label="Category"
                placeholder="Select category"
                :options="categoryOptions"
                allow-custom
                custom-option-label="+ Add New Category"
                required
                @add-custom="handleCategoryChange('add_new')"
                @change="(e) => handleCategoryChange(e.target.value)"
              />
              
              <div v-show="showNewCategory" class="space-y-3 mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <FormInput
                  v-model="newCategory"
                  type="text"
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
              v-model="form.temperature"
              label="Temperature"
              :options="temperatureOptions"
              required
            />
            
            <div class="space-y-2">
              <FormSelect
                v-model="form.size_preset" label="Available Sizes"
                placeholder="Select size options"
                :options="sizeOptions"
                allow-custom
                custom-option-label="+ Add Custom Size Configuration"
                required
                @add-custom="handleSizeChange('add_new')"
                @change="(e) => handleSizeChange(e.target.value)"
              />
              
              <div v-show="showNewSize" class="space-y-3 mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <FormInput
                  v-model="newSize"
                  type="text"
                  placeholder="e.g., Small, Medium, Large"
                />
                <div class="flex gap-3">
                  <button 
                    type="button" 
                    @click="addNewSize"
                    class="px-4 py-2 bg-[#ec7813] text-white rounded-lg hover:bg-[#ea580c] transition-all text-sm font-medium"
                  >
                    <span class="flex items-center gap-2">
                      <span class="material-symbols-outlined text-sm">add</span>
                      Add
                    </span>
                  </button>
                  <button 
                    type="button" 
                    @click="cancelNewSize"
                    class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all text-sm font-medium"
                  >
                    Cancel
                  </button>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  <p><strong>Examples:</strong></p>
                  <p>• "Small, Medium, Large, X-Large"</p>
                  <p>• "Kids, Regular, Large"</p>
                  <p>• "8oz, 12oz, 16oz"</p>
                </div>
              </div>
            </div>
            
            <FormInput
              v-model="form.prep_time"
              label="Preparation Time"
              type="text"
              placeholder="e.g., 3-5 minutes"
            />
          </div>
        </CardWrapper>

        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Settings & Status"
            subtitle="Item visibility and promotional settings"
            icon="settings"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-4">
              <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                <input
                  v-model="form.available"
                  type="checkbox"
                  class="w-5 h-5 rounded text-[#ec7813] focus:ring-[#ec7813] focus:ring-2"
                >
                <div>
                  <span class="text-sm font-medium text-gray-900 dark:text-white block">Available</span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">Item is available for ordering</span>
                </div>
              </label>

              <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                <input
                  v-model="form.featured"
                  type="checkbox"
                  class="w-5 h-5 rounded text-[#ec7813] focus:ring-[#ec7813] focus:ring-2"
                >
                <div>
                  <span class="text-sm font-medium text-gray-900 dark:text-white block">Featured Item</span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">Highlight this item prominently</span>
                </div>
              </label>

              <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200">
                <input
                  v-model="form.popular"
                  type="checkbox"
                  class="w-5 h-5 rounded text-[#ec7813] focus:ring-[#ec7813] focus:ring-2"
                >
                <div>
                  <span class="text-sm font-medium text-gray-900 dark:text-white block">Popular Item</span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">Mark as a customer favorite</span>
                </div>
              </label>
            </div>
            
            <div class="space-y-2">
              <FormTextarea
                v-model="form.notes"
                label="Internal Notes"
                placeholder="Add any internal notes about this menu item..."
                rows="3"
              />
            </div>
          </div>
        </CardWrapper>

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
                :disabled="form.processing" class="w-full sm:w-auto px-8 py-3 rounded-xl bg-[#ec7813] text-white hover:bg-[#ea580c] hover:shadow-lg transition-all duration-200 font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-lg">{{ form.processing ? 'hourglass_empty' : 'add_circle' }}</span> {{ form.processing ? 'Adding...' : 'Add Menu Item' }} </span>
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