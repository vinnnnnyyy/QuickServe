<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import FormInput from '@/Components/Admin/Forms/FormInput.vue';
import FormSelect from '@/Components/Admin/Forms/FormSelect.vue';
import FormTextarea from '@/Components/Admin/Forms/FormTextarea.vue';
import FormSection from '@/Components/Admin/Forms/FormSection.vue';
import { ref, computed, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
  addon: {
    type: Object,
    required: true
  },
  categories: {
    type: Array,
    default: () => ['Extras', 'Milk', 'Toppings', 'Syrups']
  }
});

const form = useForm({
  name: '',
  description: '',
  price: '',
  category: '',
  available: true,
  max_quantity: 1
});

const newCategory = ref('');
const showNewCategory = ref(false);

onMounted(() => {
  form.name = props.addon.name;
  form.description = props.addon.description || '';
  form.price = props.addon.price_formatted;
  form.category = props.addon.category;
  form.available = props.addon.available;
  form.max_quantity = props.addon.max_quantity;
});

const categoryOptions = computed(() => {
  const cats = [...props.categories];
  if (!cats.includes(props.addon.category)) {
    cats.push(props.addon.category);
  }
  return cats.map(cat => ({
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
  { value: 10, label: '10' }
];

const handleCategoryChange = (value) => {
  if (value === 'add_new') {
    showNewCategory.value = true;
    form.category = '';
  } else {
    showNewCategory.value = false;
    form.category = value;
  }
};

const addNewCategory = () => {
  if (newCategory.value.trim()) {
    form.category = newCategory.value.trim();
    showNewCategory.value = false;
    newCategory.value = '';
  }
};

const cancelNewCategory = () => {
  showNewCategory.value = false;
  newCategory.value = '';
  form.category = props.addon.category;
};

const submitForm = () => {
  if (!form.name || !form.category || !form.price) {
    alert('Please fill in all required fields.');
    return;
  }

  form.put(route('admin.addons.update', props.addon.id), {
    onError: (errors) => {
      console.error(errors);
      alert('Error: ' + Object.values(errors).join('\n'));
    }
  });
};

const deleteAddon = () => {
  if (confirm(`Are you sure you want to delete "${props.addon.name}"? This action cannot be undone.`)) {
    router.delete(route('admin.addons.destroy', props.addon.id));
  }
};

const goBack = () => {
  router.get(route('admin.addons.index'));
};
</script>

<template>
  <AdminLayout 
    title="Edit Add-on"
    page-title="Edit Add-on"
    page-subtitle="Update customization option details"
  >
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <button 
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
        >
          <span class="material-symbols-outlined">arrow_back</span>
          <span>Back to Add-ons</span>
        </button>
      </div>
    </div>

    <div class="max-w-4xl mx-auto">
      <form @submit.prevent="submitForm" class="space-y-8">
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Add-on Information"
            subtitle="Basic details about the customization option"
            icon="extension"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <FormInput
              v-model="form.name"
              label="Add-on Name"
              type="text"
              placeholder="e.g., Extra Shot, Oat Milk, Whipped Cream"
              required
            />
            
            <FormInput
              v-model="form.price"
              label="Additional Price"
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
              placeholder="Describe the add-on option..."
              rows="3"
              max-length="255"
              show-char-count
            />
          </div>
        </CardWrapper>

        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Category & Settings"
            subtitle="Categorization and availability settings"
            icon="settings"
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
              v-model="form.max_quantity"
              label="Max Quantity per Item"
              :options="maxQuantityOptions"
              required
            />
          </div>

          <div class="mt-6">
            <label class="flex items-center gap-3 cursor-pointer p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 w-fit">
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
        </CardWrapper>

        <CardWrapper rounded="xl" padding="lg" shadow="sm">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <button 
              type="button" 
              @click="deleteAddon"
              class="w-full sm:w-auto px-8 py-3 rounded-xl bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-800 transition-all duration-200 font-medium"
            >
              <span class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">delete</span>
                Delete Add-on
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
                type="submit"
                :disabled="form.processing"
                class="w-full sm:w-auto px-8 py-3 rounded-xl bg-[#ec7813] text-white hover:bg-[#ea580c] hover:shadow-lg transition-all duration-200 font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-lg">{{ form.processing ? 'hourglass_empty' : 'save' }}</span>
                  {{ form.processing ? 'Updating...' : 'Update Add-on' }}
                </span>
              </button>
            </div>
          </div>
        </CardWrapper>
      </form>
    </div>
  </AdminLayout>
</template>
