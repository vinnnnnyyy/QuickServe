<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import FormInput from '@/Components/Admin/Forms/FormInput.vue';
import FormSelect from '@/Components/Admin/Forms/FormSelect.vue';
import FormTextarea from '@/Components/Admin/Forms/FormTextarea.vue';
import FormSection from '@/Components/Admin/Forms/FormSection.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  id: {
    type: Number,
    required: true
  }
});

// Form data initialized with props
const form = ref({
  name: props.item.name || '',
  description: props.item.description || '',
  category: props.item.category || '',
  stock: props.item.stock || '',
  unitPrice: props.item.unit_price || '',
  minStockLevel: props.item.min_stock_level || '',
  supplier: props.item.supplier || '',
  supplierEmail: props.item.supplier_email || '',
  supplierPhone: props.item.supplier_phone || '',
  sku: props.item.sku || '',
  location: props.item.location || '',
  notes: props.item.notes || '',
  unit: props.item.unit || 'pcs',
  recipeUnit: props.item.recipe_unit || '',
  conversionFactor: props.item.conversion_factor || ''
});

const isSubmitting = ref(false);
const newCategory = ref('');
const showNewCategory = ref(false);

// Category options (This should ideally be dynamic or passed from backend, but using static for now matching Create.vue)
const categoryOptions = ref([
  { value: 'supplies', label: 'Supplies' },
  { value: 'ingredients', label: 'Ingredients' },
  { value: 'packaging', label: 'Packaging' },
  { value: 'cleaning', label: 'Cleaning Supplies' },
  { value: 'equipment', label: 'Equipment' },
  { value: 'beverages', label: 'Beverages' },
  { value: 'food', label: 'Food Items' },
  { value: 'disposables', label: 'Disposables' }
]);

const unitOptions = [
  { value: 'pcs', label: 'Pieces (pcs)' },
  { value: 'g', label: 'Grams (g)' },
  { value: 'kg', label: 'Kilograms (kg)' },
  { value: 'ml', label: 'Milliliters (ml)' },
  { value: 'l', label: 'Liters (l)' },
  { value: 'pump', label: 'Pumps' },
  { value: 'cup', label: 'Cups' }
];

// Location options
const locationOptions = ref([
  { value: 'storage_room', label: 'Storage Room' },
  { value: 'kitchen', label: 'Kitchen' },
  { value: 'counter', label: 'Counter Area' },
  { value: 'freezer', label: 'Freezer' },
  { value: 'refrigerator', label: 'Refrigerator' },
  { value: 'pantry', label: 'Pantry' },
  { value: 'office', label: 'Office' }
]);

// Computed properties
const totalValue = computed(() => {
  const stock = parseFloat(form.value.stock) || 0;
  const unitPrice = parseFloat(form.value.unitPrice) || 0;
  return (stock * unitPrice).toFixed(2);
});

const stockStatus = computed(() => {
  const stock = parseInt(form.value.stock) || 0;
  const minLevel = parseInt(form.value.minStockLevel) || 0;
  
  if (stock === 0) return { text: 'Out of Stock', color: 'red' };
  if (stock <= minLevel) return { text: 'Low Stock', color: 'yellow' };
  return { text: 'In Stock', color: 'green' };
});

// Methods
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
    const categoryValue = newCategory.value.toLowerCase().replace(/\s+/g, '_');
    const categoryLabel = newCategory.value.trim();
    
    categoryOptions.value.push({
      value: categoryValue,
      label: categoryLabel
    });
    
    form.value.category = categoryValue;
    newCategory.value = '';
    showNewCategory.value = false;
  }
};

const generateSKU = () => {
  const prefix = form.value.category.toUpperCase().slice(0, 3) || 'INV';
  const timestamp = Date.now().toString().slice(-6);
  const random = Math.floor(Math.random() * 100).toString().padStart(2, '0');
  form.value.sku = `${prefix}-${timestamp}${random}`;
};

const submitForm = async () => {
  isSubmitting.value = true;
  
  try {
    // Validate required fields
    if (!form.value.name || !form.value.category || form.value.stock === '' || form.value.unitPrice === '') {
      alert('Please fill in all required fields');
      return;
    }

    router.put(`/admin/inventory/${props.id}`, {
      name: form.value.name,
      description: form.value.description || '',
      category: form.value.category,
      stock: parseInt(form.value.stock, 10),
      unitPrice: parseFloat(form.value.unitPrice),
      minStockLevel: form.value.minStockLevel ? parseInt(form.value.minStockLevel, 10) : 0,
      supplier: form.value.supplier || '',
      supplierEmail: form.value.supplierEmail || '',
      supplierPhone: form.value.supplierPhone || '',
      sku: form.value.sku || '',
      location: form.value.location || '',
      notes: form.value.notes || '',
      unit: form.value.unit || 'pcs',
      recipe_unit: form.value.recipeUnit || null,
      conversion_factor: form.value.conversionFactor ? parseFloat(form.value.conversionFactor) : null
    }, {
      preserveScroll: true,
      onSuccess: () => {
        // Handle success if needed, though inertia typically handles redirect
      }
    });
    
  } catch (error) {
    console.error('Error updating inventory item:', error);
    alert('Error updating inventory item. Please try again.');
  } finally {
    isSubmitting.value = false;
  }
};

const goBack = () => {
  router.get('/admin/inventory');
};

const deleteItem = () => {
    if(!confirm('Are you sure you want to delete this item?')) return;
    router.delete(`/admin/inventory/${props.id}`);
}

</script>

<template>
  <AdminLayout 
    title="Edit Inventory Item"
    page-title="Edit Inventory Item"
    page-subtitle="Update inventory item details"
  >
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <button 
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
        >
          <span class="material-symbols-outlined">arrow_back</span>
          <span>Back to Inventory</span>
        </button>
      </div>
    </div>

    <div class="max-w-7xl mx-auto">
      <form @submit.prevent="submitForm" class="space-y-12">
        <!-- Basic Information -->
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Basic Information"
            subtitle="Essential details about the inventory item"
            icon="inventory"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <FormInput
              v-model="form.name"
              label="Item Name"
              type="text"
              placeholder="Enter item name (e.g., Paper Cups 16oz)"
              required
              help-text="Descriptive name for the inventory item"
            />
            
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
                  help-text="Create a new category for this item"
                />
                <div class="flex gap-3">
                  <button
                    type="button"
                    @click="addNewCategory"
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-all text-sm"
                  >
                    Add Category
                  </button>
                  <button
                    type="button"
                    @click="showNewCategory = false"
                    class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all text-sm"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </div>

            <div class="lg:col-span-2">
              <FormTextarea
                v-model="form.description"
                label="Description"
                placeholder="Enter item description..."
                rows="3"
                help-text="Brief description of the inventory item"
              />
            </div>
          </div>
        </CardWrapper>

        <!-- Stock & Pricing Information -->
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Stock & Pricing"
            subtitle="Quantity and pricing information"
            icon="monitoring"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <FormInput
              v-model="form.stock"
              label="Current Stock"
              type="number"
              placeholder="0"
              min="0"
              required
              help-text="Current quantity in stock"
            />
            
            <FormSelect
              v-model="form.unit"
              label="Unit of Measurement"
              :options="unitOptions"
              required
              help-text="e.g., ml for liquids, g for weights"
            />

            <FormInput
              v-model="form.unitPrice"
              label="Unit Price"
              type="number"
              placeholder="0.00"
              step="0.01"
              min="0"
              required
              help-text="Price per unit (₱)"
            />

            <FormInput
              v-model="form.minStockLevel"
              label="Minimum Stock Level"
              type="number"
              placeholder="0"
              min="0"
              help-text="Alert when stock falls below this level"
            />

            <div class="lg:col-span-3">
              <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Value</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">₱{{ totalValue }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Stock Status</p>
                    <span 
                      class="inline-flex items-center gap-1.5 text-sm font-medium"
                      :class="{
                        'text-green-600 dark:text-green-400': stockStatus.color === 'green',
                        'text-yellow-600 dark:text-yellow-400': stockStatus.color === 'yellow',
                        'text-red-600 dark:text-red-400': stockStatus.color === 'red'
                      }"
                    >
                      <span 
                        class="w-1.5 h-1.5 rounded-full"
                        :class="{
                          'bg-green-500': stockStatus.color === 'green',
                          'bg-yellow-500': stockStatus.color === 'yellow',
                          'bg-red-500': stockStatus.color === 'red'
                        }"
                      ></span>
                      {{ stockStatus.text }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-8 border-t border-gray-100 dark:border-gray-800 pt-6">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">scale</span>
              Recipe Unit Conversion
            </h4>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-4">
              <p class="text-sm text-blue-800 dark:text-blue-200">
                <span class="font-bold">Tip:</span> Enable this if you use different units for recipes (e.g., "Scoops") than for purchasing (e.g., "Grams").
              </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <FormInput
                v-model="form.recipeUnit"
                label="Recipe Unit Name"
                type="text"
                placeholder="e.g., Scoop, Pump, Slice"
                help-text="The unit name used in recipes"
              />

              <div class="space-y-4">
                <FormInput
                  v-model="form.conversionFactor"
                  label="Conversion Factor"
                  type="number"
                  placeholder="0"
                  step="0.0001"
                  min="0"
                  help-text="How much of the base unit constitutes 1 recipe unit?"
                />
                
                <div v-if="form.recipeUnit && form.conversionFactor" class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700">
                  <span class="font-medium text-gray-900 dark:text-white">Conversion Logic:</span>
                  1 {{ form.recipeUnit }} = {{ form.conversionFactor }} {{ form.unit }}
                </div>
              </div>
            </div>
          </div>
        </CardWrapper>

        <!-- Additional Information -->
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Additional Information"
            subtitle="Optional details for better inventory management"
            icon="info"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <FormInput
              v-model="form.sku"
              label="SKU (Stock Keeping Unit)"
              type="text"
              placeholder="Auto-generated or enter custom SKU"
              help-text="Unique identifier for this item"
            />

            <FormSelect
              v-model="form.location"
              label="Storage Location"
              placeholder="Select storage location"
              :options="locationOptions"
              help-text="Where this item is stored"
            />

            <FormInput
              v-model="form.supplier"
              label="Supplier"
              type="text"
              placeholder="Enter supplier name"
              help-text="Primary supplier for this item"
            />

            <FormInput
              v-model="form.supplierEmail"
              label="Supplier Email"
              type="email"
              placeholder="Enter supplier email"
              help-text="Contact email for the supplier"
            />

            <FormInput
              v-model="form.supplierPhone"
              label="Supplier Phone"
              type="tel"
              placeholder="Enter supplier phone number"
              help-text="Contact phone number for the supplier"
            />

            <div class="flex items-end">
              <button
                type="button"
                @click="generateSKU"
                class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
              >
                <span class="material-symbols-outlined">refresh</span>
                <span>Generate SKU</span>
              </button>
            </div>

            <div class="lg:col-span-2">
              <FormTextarea
                v-model="form.notes"
                label="Notes"
                placeholder="Additional notes about this inventory item..."
                rows="3"
                help-text="Any additional information or special instructions"
              />
            </div>
          </div>
        </CardWrapper>

        <!-- Form Actions -->
        <CardWrapper rounded="xl" padding="lg">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
             <button
              type="button"
              @click="deleteItem"
               class="flex items-center gap-2 px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-all"
            >
              <span class="material-symbols-outlined">delete</span>
              <span>Delete Item</span>
            </button>
            
            <div class="flex items-center gap-3">
              <button
                type="button"
                @click="goBack"
                class="px-6 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="flex items-center gap-2 px-6 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isSubmitting" class="material-symbols-outlined animate-spin">refresh</span>
                <span v-else class="material-symbols-outlined">save</span>
                <span>{{ isSubmitting ? 'Updating...' : 'Update Item' }}</span>
              </button>
            </div>
          </div>
        </CardWrapper>
      </form>
    </div>
  </AdminLayout>
</template>
