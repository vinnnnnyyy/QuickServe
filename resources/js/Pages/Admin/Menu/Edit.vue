<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import FormInput from '@/Components/Admin/Forms/FormInput.vue';
import FormSelect from '@/Components/Admin/Forms/FormSelect.vue';
import FormTextarea from '@/Components/Admin/Forms/FormTextarea.vue';
import FormSection from '@/Components/Admin/Forms/FormSection.vue';
import AddonModal from '@/Components/Admin/Modals/AddonModal.vue';
import { ref, onMounted, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
  menuItem: {
    type: Object,
    required: true
  },
  categories: {
    type: Array,
    default: () => []
  },
  addons: {
    type: Array,
    default: () => []
  },
  inventoryItems: {
    type: Array,
    default: () => []
  }
});

const sizePresetMap = {
  '1': ['Regular'],
  '2': ['Small', 'Large'],
  '3': ['Small', 'Medium', 'Large'],
  '4': ['XS', 'S', 'M', 'L'],
  '5': ['XS', 'S', 'M', 'L', 'XL'],
};

const findSizePreset = (labels) => {
  if (!labels || !Array.isArray(labels)) return '3';
  for (const [key, value] of Object.entries(sizePresetMap)) {
    if (JSON.stringify(value) === JSON.stringify(labels)) {
      return key;
    }
  }
  return '3';
};

const form = useForm({
  name: props.menuItem.name || '',
  description: props.menuItem.description || '',
  category_id: props.menuItem.category_id || null,
  price: props.menuItem.price_formatted || '',
  temperature: props.menuItem.temperature || 'Hot',
  prep_time: props.menuItem.prep_time || '',
  size_preset: findSizePreset(props.menuItem.size_labels),
  size_labels: props.menuItem.size_labels || ['Small', 'Medium', 'Large'],
  featured: props.menuItem.featured || false,
  popular: props.menuItem.popular || false,
  available: props.menuItem.available !== false,
  image: null,
  notes: props.menuItem.notes || '',
  addon_ids: props.menuItem.addons ? props.menuItem.addons.map(a => a.id) : [],
  // Hydrate ingredients with inventory details
  ingredients: props.menuItem.ingredients ? props.menuItem.ingredients.map(ing => {
      const inventoryItem = props.inventoryItems.find(i => i.id === ing.id);
      return {
          id: ing.id,
          quantity: parseFloat(ing.pivot.quantity),
          unit_price: inventoryItem ? inventoryItem.unit_price : 0,
          unit: inventoryItem ? inventoryItem.unit : '',
          recipe_unit: inventoryItem ? inventoryItem.recipe_unit : '',
          conversion_factor: inventoryItem ? inventoryItem.conversion_factor : 1,
          use_recipe_unit: false // Default to false (stock unit) when loading
      };
  }) : []
});

const imagePreview = ref(props.menuItem.image_url || null);
const newCategory = ref('');
const showNewCategory = ref(false);
const newSize = ref('');
const showNewSize = ref(false);
const addonSearch = ref('');
const showAddonModal = ref(false);
const localAddons = ref([]);

onMounted(() => {
  localAddons.value = [...props.addons];
});

const allAddons = computed(() => localAddons.value);

const addonCategories = computed(() => {
  const categories = [...new Set(allAddons.value.map(a => a.category))];
  if (categories.length === 0) {
    return ['Extras', 'Milk', 'Toppings', 'Syrups'];
  }
  return categories;
});

const categoryOptions = computed(() => {
  return props.categories.map(cat => ({
    value: cat.id,
    label: cat.name
  }));
});

const temperatureOptions = [
  { value: 'Hot', label: 'Hot' },
  { value: 'Cold', label: 'Cold' },
  { value: 'Both', label: 'Hot & Cold' }
];

const sizeOptions = ref([
  { value: '1', label: '1 Size (Regular)' },
  { value: '2', label: '2 Sizes (Small, Large)' },
  { value: '3', label: '3 Sizes (Small, Medium, Large)' },
  { value: '4', label: '4 Sizes (XS, S, M, L)' },
  { value: '5', label: '5 Sizes (XS, S, M, L, XL)' }
]);

watch(() => form.size_preset, (newPreset) => {
  if (sizePresetMap[newPreset]) {
    form.size_labels = sizePresetMap[newPreset];
  }
});

const groupedAddons = computed(() => {
  const groups = {};
  allAddons.value.forEach(addon => {
    if (!groups[addon.category]) {
      groups[addon.category] = [];
    }
    groups[addon.category].push(addon);
  });
  return groups;
});

const filteredAddons = computed(() => {
  if (!addonSearch.value.trim()) return allAddons.value;
  const query = addonSearch.value.toLowerCase();
  return allAddons.value.filter(addon => 
    addon.name.toLowerCase().includes(query) || 
    addon.category.toLowerCase().includes(query)
  );
});

const filteredGroupedAddons = computed(() => {
  const groups = {};
  filteredAddons.value.forEach(addon => {
    if (!groups[addon.category]) {
      groups[addon.category] = [];
    }
    groups[addon.category].push(addon);
  });
  return groups;
});

const isAddonSelected = (addonId) => {
  return form.addon_ids.includes(addonId);
};

const toggleAddon = (addonId) => {
  const index = form.addon_ids.indexOf(addonId);
  if (index === -1) {
    form.addon_ids.push(addonId);
  } else {
    form.addon_ids.splice(index, 1);
  }
};

const selectAllAddons = () => {
  form.addon_ids = allAddons.value.map(a => a.id);
};

const clearAllAddons = () => {
  form.addon_ids = [];
};

const getCategoryIcon = (category) => {
  const icons = {
    'Milk': 'water_drop',
    'Extras': 'add_circle',
    'Toppings': 'cake',
    'Syrups': 'local_cafe',
    'Sweeteners': 'nutrition'
  };
  return icons[category] || 'extension';
};

const handleCategoryChange = (value) => {
  if (value === 'add_new') {
    showNewCategory.value = true;
    form.category_id = null;
  } else {
    showNewCategory.value = false;
    form.category_id = value;
  }
};

const addNewCategory = async () => {
  const categoryName = newCategory.value.trim();
  if (!categoryName) return;

  try {
    const response = await fetch(route('api.categories.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        name: categoryName,
        scope: 'menu'
      })
    });

    if (response.ok) {
      const newCategoryData = await response.json();
      props.categories.push({
        id: newCategoryData.id,
        name: newCategoryData.name
      });
      form.category_id = newCategoryData.id;
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
  form.category_id = props.menuItem.category_id;
};

const handleSizeChange = (value) => {
  if (value === 'add_new') {
    showNewSize.value = true;
    form.size_preset = null;
  } else {
    showNewSize.value = false;
    form.size_preset = value;
  }
};

const addNewSize = () => {
  if (newSize.value.trim()) {
    const newLabel = newSize.value.trim();
    const newValue = `custom_${sizeOptions.value.length + 1}`;
    
    sizeOptions.value.push({
      value: newValue,
      label: newLabel
    });
    
    form.size_preset = newValue;
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
  form.size_preset = findSizePreset(props.menuItem.size_labels);
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  
  const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
  if (!validTypes.includes(file.type)) {
    alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
    return;
  }
  
  const maxSize = 10 * 1024 * 1024;
  if (file.size > maxSize) {
    alert('File size must be less than 10MB');
    return;
  }
  
  form.image = file;
  
  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const clearImage = () => {
  imagePreview.value = props.menuItem.image_url || null;
  form.image = null;
  document.getElementById('image-upload').value = '';
};

const submitForm = () => {
  if (!form.name || !form.category_id || !form.price || !form.description) {
    alert('Please fill in all required fields.');
    return;
  }

  form.transform((data) => ({
    ...data,
    _method: 'PUT',
    ingredients: data.ingredients.map(ing => ({
      id: ing.id,
      quantity: ing.use_recipe_unit 
        ? (parseFloat(ing.quantity) * (parseFloat(ing.conversion_factor) || 1)) 
        : parseFloat(ing.quantity)
    }))
  })).post(route('admin.menu.update', props.menuItem.id), {
    forceFormData: true,
    onError: (errors) => {
      console.error(errors);
      alert('Error: ' + Object.values(errors).join('\n'));
    }
  });
};

const saveDraft = () => {
  localStorage.setItem('menuItemEditDraft', JSON.stringify(form.data()));
  alert('Draft saved successfully!');
};

const goBack = () => {
  router.get(route('admin.menu.index'));
};

const deleteMenuItem = () => {
  if (confirm(`Are you sure you want to delete "${props.menuItem.name}"? This action cannot be undone.`)) {
    router.delete(route('admin.menu.destroy', props.menuItem.id));
  }
};

const handleAddonCreated = (newAddon) => {
  localAddons.value.push(newAddon);
  form.addon_ids.push(newAddon.id);
};

// --- Recipe Builder Logic ---
const availableIngredients = computed(() => {
  return props.inventoryItems.filter(item => 
    !form.ingredients.some(ing => ing.id === item.id)
  );
});

const addIngredient = () => {
    form.ingredients.push({
        id: null,
        quantity: 1,
        unit_price: 0, // for display only
        unit: '', // for display only
        recipe_unit: '',
        conversion_factor: 1,
        use_recipe_unit: false
    });
};

const removeIngredient = (index) => {
    form.ingredients.splice(index, 1);
};

const onIngredientSelect = (index, inventoryId) => {
    const selectedItem = props.inventoryItems.find(i => i.id === inventoryId);
    if (selectedItem) {
        form.ingredients[index].id = selectedItem.id;
        form.ingredients[index].unit_price = selectedItem.unit_price;
        form.ingredients[index].unit = selectedItem.unit;
        form.ingredients[index].recipe_unit = selectedItem.recipe_unit;
        form.ingredients[index].conversion_factor = selectedItem.conversion_factor ? parseFloat(selectedItem.conversion_factor) : 1;
        form.ingredients[index].use_recipe_unit = false;
    }
};

const totalCost = computed(() => {
    return form.ingredients.reduce((total, ing) => {
        // Find the original item to get the price if available
        const item = props.inventoryItems.find(i => i.id === ing.id);
        const price = item ? parseFloat(item.unit_price) : 0;
        const quantity = parseFloat(ing.quantity) || 0;
        const factor = ing.use_recipe_unit ? (parseFloat(ing.conversion_factor) || 1) : 1;
        return total + (price * quantity * factor);
    }, 0);
});

const profitMargin = computed(() => {
    const sellingPrice = parseFloat(form.price) || 0;
    const cost = totalCost.value;
    if (sellingPrice === 0) return 0;
    return ((sellingPrice - cost) / sellingPrice) * 100;
});

</script>

<template>
  <AdminLayout 
    title="Edit Menu Item"
    page-title="Edit Menu Item"
    page-subtitle="Update your menu item details"
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
                    <p>Recommended: 800x600px or larger</p>
                    <p>Supported formats: JPG, PNG, GIF, WebP</p>
                    <p>Maximum file size: 10MB</p>
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
              placeholder="Enter item name"
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
              placeholder="Describe your menu item..."
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
                v-model="form.category_id"
                label="Category"
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
                v-model="form.size_preset"
                label="Available Sizes"
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
            title="Recipe & Costing"
            subtitle="Link inventory items to calculate food cost"
            icon="receipt_long"
          />
          
          <div class="space-y-6">
            <div v-if="form.ingredients.length > 0" class="space-y-4">
                <div v-for="(ing, index) in form.ingredients" :key="index" class="flex flex-col sm:flex-row gap-4 items-start sm:items-end bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Ingredient</label>
                        <select 
                            :value="ing.id"
                            @change="(e) => onIngredientSelect(index, Number(e.target.value))"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-[#ec7813] focus:ring focus:ring-[#ec7813]/20"
                            required
                        >
                            <option :value="null" disabled>Select Item</option>
                            <option v-for="item in props.inventoryItems" :key="item.id" :value="item.id">
                                {{ item.name }} ({{ item.unit || 'units' }})
                            </option>
                        </select>
                    </div>
                    
                    <div class="w-full sm:w-40">
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-xs font-semibold text-gray-500">
                                Quantity ({{ ing.use_recipe_unit ? (ing.recipe_unit || 'Units') : (ing.unit || 'Units') }})
                            </label>
                            
                            <!-- Unit Toggle -->
                            <div v-if="ing.recipe_unit" class="flex items-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="ing.use_recipe_unit" class="sr-only peer">
                                    <div class="w-7 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-[#ec7813]"></div>
                                    <span class="ml-1 text-[10px] font-medium text-gray-500">{{ ing.recipe_unit }}?</span>
                                </label>
                            </div>
                        </div>
                        <input 
                            v-model="ing.quantity"
                            type="number" 
                            step="0.0001"
                            min="0"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-[#ec7813] focus:ring focus:ring-[#ec7813]/20"
                            placeholder="Qty"
                            required
                        >
                        <div v-if="ing.use_recipe_unit" class="mt-1 text-[10px] text-gray-500">
                             ≈ {{ (ing.quantity * (ing.conversion_factor || 1)).toFixed(2) }} {{ ing.unit }}
                        </div>
                    </div>

                    <div class="pb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                       Cost: ₱{{ ((props.inventoryItems.find(i => i.id === ing.id)?.unit_price || 0) * ing.quantity * (ing.use_recipe_unit ? (ing.conversion_factor || 1) : 1)).toFixed(2) }}
                    </div>

                    <button 
                        type="button" 
                        @click="removeIngredient(index)"
                        class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                    >
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>

            <div v-else class="text-center py-6 bg-gray-50 dark:bg-gray-800/30 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 mb-3">No ingredients added yet</p>
            </div>

            <button 
                type="button"
                @click="addIngredient"
                class="flex items-center gap-2 text-sm font-medium text-[#ec7813] hover:text-[#ea580c]"
            >
                <span class="material-symbols-outlined">add</span>
                Add Ingredient
            </button>

            <!-- Cost Summary -->
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-100 dark:border-blue-800 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                   <div class="text-xs text-gray-500 dark:text-gray-400">Total Food Cost</div>
                   <div class="text-lg font-bold text-gray-900 dark:text-white">₱{{ totalCost.toFixed(2) }}</div>
                </div>
                <div>
                   <div class="text-xs text-gray-500 dark:text-gray-400">Selling Price</div>
                   <div class="text-lg font-bold text-gray-900 dark:text-white">₱{{ (parseFloat(form.price)||0).toFixed(2) }}</div>
                </div>
                <div>
                   <div class="text-xs text-gray-500 dark:text-gray-400">Estimated Margin</div>
                   <div :class="['text-lg font-bold', profitMargin > 60 ? 'text-green-600' : 'text-orange-600']">
                       {{ profitMargin.toFixed(1) }}%
                   </div>
                </div>
            </div>

          </div>
        </CardWrapper>

        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Available Add-ons"
            subtitle="Select customization options available for this menu item"
            icon="extension"
          />
          
          <div v-if="allAddons.length === 0" class="text-center py-8 bg-gray-50 dark:bg-gray-900/20 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <span class="material-symbols-outlined text-gray-400 text-2xl">extension</span>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No add-ons available</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Create add-ons first to assign them to menu items</p>
            <button 
              type="button"
              @click="showAddonModal = true"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all"
            >
              <span class="material-symbols-outlined">add</span>
              <span>Create Add-on</span>
            </button>
          </div>

          <div v-else>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
              <div class="relative flex-1 max-w-md">
                <input 
                  v-model="addonSearch"
                  type="search" 
                  placeholder="Search add-ons..." 
                  class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary"
                >
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
              </div>
              <div class="flex items-center gap-2">
                <button 
                  type="button"
                  @click="showAddonModal = true"
                  class="px-3 py-1.5 text-sm rounded-lg bg-[#ec7813] text-white hover:bg-[#ea580c] transition-all flex items-center gap-1"
                >
                  <span class="material-symbols-outlined text-sm">add</span>
                  New Add-on
                </button>
                <button 
                  type="button"
                  @click="selectAllAddons"
                  class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
                >
                  Select All
                </button>
                <button 
                  type="button"
                  @click="clearAllAddons"
                  class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
                >
                  Clear All
                </button>
                <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                  {{ form.addon_ids.length }} selected
                </span>
              </div>
            </div>

            <div class="space-y-6">
              <div v-for="(categoryAddons, category) in filteredGroupedAddons" :key="category">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary">{{ getCategoryIcon(category) }}</span>
                  {{ category }}
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                  <label
                    v-for="addon in categoryAddons"
                    :key="addon.id"
                    class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition-all duration-200"
                    :class="isAddonSelected(addon.id) 
                      ? 'border-primary bg-primary/5 dark:bg-primary/10' 
                      : 'border-gray-200 dark:border-gray-700 hover:border-primary/30 hover:bg-gray-50 dark:hover:bg-gray-800'"
                  >
                    <input
                      type="checkbox"
                      :checked="isAddonSelected(addon.id)"
                      @change="toggleAddon(addon.id)"
                      class="w-5 h-5 rounded text-primary focus:ring-primary focus:ring-2"
                    >
                    <div class="flex-1 min-w-0">
                      <span class="text-sm font-medium text-gray-900 dark:text-white block truncate">{{ addon.name }}</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">+₱{{ addon.price_formatted.toFixed(2) }}</span>
                    </div>
                  </label>
                </div>
              </div>
            </div>

            <div v-if="Object.keys(filteredGroupedAddons).length === 0" class="text-center py-8">
              <p class="text-gray-500 dark:text-gray-400">No add-ons match your search</p>
            </div>
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
          <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
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
                :disabled="form.processing"
                class="w-full sm:w-auto px-8 py-3 rounded-xl bg-[#ec7813] text-white hover:bg-[#ea580c] hover:shadow-lg transition-all duration-200 font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-lg">{{ form.processing ? 'hourglass_empty' : 'save' }}</span>
                  {{ form.processing ? 'Updating...' : 'Update Menu Item' }}
                </span>
              </button>
            </div>
          </div>
        </CardWrapper>
      </form>
    </div>

    <AddonModal
      :show="showAddonModal"
      :existing-categories="addonCategories"
      @close="showAddonModal = false"
      @created="handleAddonCreated"
    />
  </AdminLayout>
</template>
