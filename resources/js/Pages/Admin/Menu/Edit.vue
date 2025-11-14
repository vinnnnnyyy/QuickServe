<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import FormInput from '@/Components/Admin/Forms/FormInput.vue';
import FormSelect from '@/Components/Admin/Forms/FormSelect.vue';
import FormTextarea from '@/Components/Admin/Forms/FormTextarea.vue';
import FormSection from '@/Components/Admin/Forms/FormSection.vue';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useMenuCategories } from '@/composables/useMenuCategories.js';

// Props (would come from the route in a real app)
const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  }
});

// Form data
const form = ref({
  name: '',
  description: '',
  category: '',
  price: '',
  temperature: 'Hot',
  preparationTime: '',
  sizes: '3',
  featured: false,
  popular: false,
  available: true,
  image: null,
  notes: ''
});

const imagePreview = ref(null);
const newCategory = ref('');
const showNewCategory = ref(false);
const newSize = ref('');
const showNewSize = ref(false);
const loading = ref(true);
const originalItem = ref(null);

// Use centralized category system
const { 
  getFormSelectOptions, 
  addCategory, 
  getCategoryLabel,
  getCategoryByLabel,
  allCategories 
} = useMenuCategories();

// Category options from centralized system
const categoryOptions = ref(getFormSelectOptions());

// Temperature options
const temperatureOptions = [
  { value: 'Hot', label: 'Hot' },
  { value: 'Cold', label: 'Cold' },
  { value: 'Both', label: 'Hot & Cold' }
];

// Size options
const sizeOptions = ref([
  { value: '1', label: '1 Size (Regular)' },
  { value: '2', label: '2 Sizes (Small, Large)' },
  { value: '3', label: '3 Sizes (Small, Medium, Large)' },
  { value: '4', label: '4 Sizes (XS, S, M, L)' },
  { value: '5', label: '5 Sizes (XS, S, M, L, XL)' }
]);

// Sample data (in a real app, this would come from your backend)
const sampleMenuItems = [
  {
    id: 1,
    name: 'Iced Brown Sugar Oatmilk Shaken Espresso',
    category: 'Cold Drinks',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAWsgIO1J1Vy9qVoM5CgDrJPOWPDgBANVPmkuXgLP4EqL2L8-oGTFU2DQTpsyAWN1HmnYOBEN3z4XdfReS-rIZjs2S1M_J83gKc5AeabAa9DEPvq8aTTyAZmTSWOI9I_q0J3ioJ6be5cPi1BMyyf2ZkhMhGaUmVVfBqqnHuQtdb2u1rVuZzunYb5GUkm1Vxd-L4zgy_wJfd9xsGT130RGiGgkzubfU2x9m9BUktQ7lnphNIVlvs4tY740wqaw_9cIPyLlfgNn2-X0M',
    description: 'Rich espresso with brown sugar syrup and oat milk, shaken with ice',
    price: 5.45,
    temperature: 'Cold',
    available: true,
    featured: true,
    sizes: 3,
    preparationTime: '3-5 minutes',
    notes: 'Popular cold drink'
  },
  {
    id: 2,
    name: 'Pistachio Cream Cold Brew',
    category: 'Cold Drinks',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCaB3cG7MngQrvip_shrbfauPLR2ZDR5t1BvoFgE292K03RnduyBPQUm04uktCdHywSzDNx2XwHX6uDIWKZazO7LDzXaaKp7JwY9LeZG6SNsBcVQVrh1P4yzhbZrPwBdrkL3wgNMfRc4X-5smWQv7yHHm6-T8To2b6K_86KOdxiJNYwkPy008xzojrHGBzM53o-tHtq4jvuBDi4gh4gMBMiQON-21R_En8G4LpMhBwG5ry4bDAaAbR3Eqnf4cRd8p-C6rhg_C9d-YI',
    description: 'Smooth cold brew coffee with creamy pistachio flavor',
    price: 4.95,
    temperature: 'Cold',
    available: true,
    featured: false,
    popular: true,
    sizes: 2,
    preparationTime: '2-3 minutes',
    notes: 'Customer favorite'
  }
];

// Methods
const loadMenuItem = () => {
  // In a real app, you'd make an API call here
  const item = sampleMenuItems.find(item => item.id == props.id);
  
  if (!item) {
    alert('Menu item not found!');
    router.get('/admin/menu');
    return;
  }
  
  originalItem.value = { ...item };
  
  // Map category from display name to value using centralized system
  const categoryData = getCategoryByLabel(item.category);
  const categoryValue = categoryData?.value || 'hot_drinks';
  
  // Populate form with existing data
  form.value = {
    name: item.name,
    description: item.description,
    category: categoryValue,
    price: item.price.toString(),
    temperature: item.temperature,
    preparationTime: item.preparationTime || '',
    sizes: item.sizes.toString(),
    featured: item.featured || false,
    popular: item.popular || false,
    available: item.available !== false,
    image: null,
    notes: item.notes || ''
  };
  
  // Set image preview if available
  if (item.image) {
    imagePreview.value = item.image;
  }
  
  loading.value = false;
};

const handleCategoryChange = (value) => {
  if (value === 'add_new') {
    showNewCategory.value = true;
    form.value.category = '';
  } else {
    showNewCategory.value = false;
    form.value.category = value;
  }
};

const addNewCategory = () => {
  if (newCategory.value.trim()) {
    const newCategoryData = {
      label: newCategory.value.trim(),
      icon: 'restaurant_menu', // Default icon
      description: `Custom category: ${newCategory.value.trim()}`
    };
    
    const addedCategory = addCategory(newCategoryData);
    if (addedCategory) {
      form.value.category = addedCategory.value;
      categoryOptions.value = getFormSelectOptions(); // Refresh options
      showNewCategory.value = false;
      newCategory.value = '';
    } else {
      alert('Category already exists!');
    }
  }
};

const cancelNewCategory = () => {
  showNewCategory.value = false;
  newCategory.value = '';
  form.value.category = originalItem.value ? 
    getCategoryByLabel(originalItem.value.category)?.value || 'hot_drinks' : 
    'hot_drinks';
};

const handleSizeChange = (value) => {
  if (value === 'add_new') {
    showNewSize.value = true;
    form.value.sizes = '';
  } else {
    showNewSize.value = false;
    form.value.sizes = value;
  }
};

const addNewSize = () => {
  if (newSize.value.trim()) {
    const newValue = (sizeOptions.value.length + 1).toString();
    form.value.sizes = newValue;
    sizeOptions.value.push({
      value: newValue,
      label: newSize.value.trim()
    });
    showNewSize.value = false;
    newSize.value = '';
  }
};

const cancelNewSize = () => {
  showNewSize.value = false;
  newSize.value = '';
  form.value.sizes = originalItem.value ? originalItem.value.sizes.toString() : '3';
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
  
  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
  form.value.image = file;
};

const clearImage = () => {
  imagePreview.value = originalItem.value?.image || null;
  form.value.image = null;
  document.getElementById('image-upload').value = '';
};

const submitForm = () => {
  // Validate required fields
  if (!form.value.name || !form.value.category || !form.value.price || !form.value.description) {
    alert('Please fill in all required fields.');
    return;
  }

  // Validate price
  const price = parseFloat(form.value.price);
  if (isNaN(price) || price <= 0) {
    alert('Please enter a valid price.');
    return;
  }

  const updatedMenuItem = {
    id: props.id,
    name: form.value.name,
    description: form.value.description,
    category: getCategoryLabel(form.value.category),
    price: price,
    temperature: form.value.temperature,
    preparationTime: form.value.preparationTime || '3-5 minutes',
    sizes: parseInt(form.value.sizes),
    sizeConfiguration: sizeOptions.value.find(option => option.value === form.value.sizes)?.label || `${form.value.sizes} sizes`,
    featured: form.value.featured,
    popular: form.value.popular,
    available: form.value.available,
    image: imagePreview.value || 'https://via.placeholder.com/400x300?text=Menu+Item',
    notes: form.value.notes,
    updatedAt: new Date().toISOString().split('T')[0]
  };

  console.log('Updated menu item:', updatedMenuItem);
  alert(`Menu item "${form.value.name}" has been updated successfully!`);
  
  // Navigate back to menu page
  router.get('/admin/menu');
};

const saveDraft = () => {
  localStorage.setItem('menuItemEditDraft', JSON.stringify(form.value));
  alert('Draft saved successfully!');
};

const goBack = () => {
  router.get('/admin/menu');
};

const deleteMenuItem = () => {
  if (confirm(`Are you sure you want to delete "${originalItem.value?.name}"? This action cannot be undone.`)) {
    console.log('Deleting menu item:', props.id);
    alert(`Menu item "${originalItem.value?.name}" has been deleted successfully!`);
    router.get('/admin/menu');
  }
};

// Load data when component mounts
onMounted(() => {
  loadMenuItem();
});
</script>

<template>
  <AdminLayout 
    title="Edit Menu Item"
    page-title="Edit Menu Item"
    page-subtitle="Update your menu item details"
  >
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-64">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#ec7813] mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-400">Loading menu item...</p>
      </div>
    </div>

    <!-- Edit Form -->
    <div v-else>
      <!-- Header Actions -->
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
          <!-- Basic Information -->
          <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
            <FormSection 
              title="Basic Information"
              subtitle="Essential details about your menu item"
              icon="restaurant_menu"
            />
            
            <!-- Item Image Upload -->
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
                          <span>Change Image</span>
                        </div>
                      </label>
                      <button 
                        type="button" 
                        @click="clearImage"
                        class="px-6 py-3 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 font-medium"
                      >
                        <span class="flex items-center gap-2">
                          <span class="material-symbols-outlined text-lg">refresh</span>
                          Reset
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

          <!-- Category & Details -->
          <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
            <FormSection 
              title="Category & Details"
              subtitle="Categorization and serving information"
              icon="category"
            />
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div class="space-y-2">
                <FormSelect
                  v-model="form.category"
                  label="Category"
                  placeholder="Select category"
                  :options="categoryOptions"
                  allow-custom
                  custom-option-label="+ Add New Category"
                  required
                  @add-custom="handleCategoryChange('add_new')"
                  @change="(e) => handleCategoryChange(e.target.value)"
                />
                
                <!-- New Category Input -->
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
                  v-model="form.sizes"
                  label="Available Sizes"
                  placeholder="Select size options"
                  :options="sizeOptions"
                  allow-custom
                  custom-option-label="+ Add Custom Size Configuration"
                  required
                  @add-custom="handleSizeChange('add_new')"
                  @change="(e) => handleSizeChange(e.target.value)"
                />
                
                <!-- New Size Configuration Input -->
                <div v-show="showNewSize" class="space-y-3 mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                  <FormInput
                    v-model="newSize"
                    type="text"
                    placeholder="Enter size configuration (e.g., 6 Sizes - Tiny, XS, S, M, L, XL)"
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
                    <p>• "6 Sizes (Tiny, XS, S, M, L, XL)"</p>
                    <p>• "Custom Portions (Kids, Regular, Large, Family)"</p>
                    <p>• "Temperature Variants (Hot Only, Iced Only)"</p>
                  </div>
                </div>
              </div>
              
              <FormInput
                v-model="form.preparationTime"
                label="Preparation Time"
                type="text"
                placeholder="e.g., 3-5 minutes"
              />
            </div>
          </CardWrapper>

          <!-- Settings & Status -->
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

          <!-- Form Actions -->
          <CardWrapper rounded="xl" padding="lg" shadow="sm">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
              <!-- Left side - Delete Button -->
              <button 
                type="button" 
                @click="deleteMenuItem"
                class="w-full sm:w-auto px-8 py-3 rounded-xl bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-800 transition-all duration-200 font-medium"
              >
                <span class="flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-lg">delete</span>
                  Delete Item
                </span>
              </button>

              <!-- Right side - Action Buttons -->
              <div class="flex flex-col sm:flex-row items-center gap-3">
                <button 
                  type="button" 
                  @click="goBack"
                  class="w-full sm:w-auto px-8 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200 font-medium"
                >
                  <span class="flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">close</span>
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
                    <span class="material-symbols-outlined text-lg">save</span>
                    Update Menu Item
                  </span>
                </button>
              </div>
            </div>
          </CardWrapper>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
/* Component-specific styles if needed */
</style>
