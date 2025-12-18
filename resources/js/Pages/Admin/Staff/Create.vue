<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import FormInput from '@/Components/Admin/Forms/FormInput.vue';
import FormSelect from '@/Components/Admin/Forms/FormSelect.vue';
import FormSection from '@/Components/Admin/Forms/FormSection.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

// Form data
const form = ref({
  fullName: '',
  email: '',
  password: '',
  phone: '',
  role: '',
  experience: '',
  workShift: '',
  hourlyRate: 15.00,
  accessLevel: 'staff',
  notes: '',
  profilePicture: null
});

const profilePreview = ref(null);
const newJobTitle = ref('');
const showNewJobTitle = ref(false);

// Job title options
const jobTitleOptions = [
  { value: 'head_barista', label: 'Head Barista' },
  { value: 'barista', label: 'Barista' },
  { value: 'senior_barista', label: 'Senior Barista' },
  { value: 'assistant_manager', label: 'Assistant Manager' },
  { value: 'manager', label: 'Manager' },
  { value: 'cashier', label: 'Cashier' },
  { value: 'supervisor', label: 'Supervisor' }
];

// Work shift options
const shiftOptions = [
  { value: '6am_2pm', label: '6:00 AM - 2:00 PM' },
  { value: '8am_4pm', label: '8:00 AM - 4:00 PM' },
  { value: '10am_6pm', label: '10:00 AM - 6:00 PM' },
  { value: '2pm_10pm', label: '2:00 PM - 10:00 PM' },
  { value: 'flexible', label: 'Flexible' }
];

// Methods
const handleJobTitleChange = (value) => {
  if (value === 'add_new') {
    showNewJobTitle.value = true;
    form.value.role = '';
  } else {
    showNewJobTitle.value = false;
    form.value.role = value;
  }
};

const addNewJobTitle = () => {
  if (newJobTitle.value.trim()) {
    const newValue = newJobTitle.value.trim().toLowerCase().replace(/\s+/g, '_');
    form.value.role = newValue;
    jobTitleOptions.push({
      value: newValue,
      label: newJobTitle.value.trim()
    });
    showNewJobTitle.value = false;
    newJobTitle.value = '';
  }
};

const cancelNewJobTitle = () => {
  showNewJobTitle.value = false;
  newJobTitle.value = '';
  form.value.role = '';
};

const handleProfileUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  
  // Validate file type
  const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
  if (!validTypes.includes(file.type)) {
    alert('Please select a valid image file (JPG, PNG, or GIF)');
    return;
  }
  
  // Validate file size (5MB max)
  const maxSize = 5 * 1024 * 1024;
  if (file.size > maxSize) {
    alert('File size must be less than 5MB');
    return;
  }
  
  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    profilePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
  form.value.profilePicture = file;
};

const clearProfilePhoto = () => {
  profilePreview.value = null;
  form.value.profilePicture = null;
  document.getElementById('profile-upload').value = '';
};

const isSubmitting = ref(false);

const submitForm = async () => {
  // Validate required fields
  if (!form.value.fullName || !form.value.role || !form.value.experience || !form.value.workShift || !form.value.hourlyRate) {
    alert('Please fill in all required fields.');
    return;
  }

  // Validate email if provided
  if (form.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    alert('Please enter a valid email address.');
    return;
  }

  // Validate password
  if (!form.value.password || form.value.password.length < 6) {
    alert('Please enter a password with at least 6 characters.');
    return;
  }

  // Validate experience
  const experience = parseInt(form.value.experience);
  if (isNaN(experience) || experience < 0) {
    alert('Please enter a valid experience value.');
    return;
  }

  // Validate hourly rate
  const hourlyRate = parseFloat(form.value.hourlyRate);
  if (isNaN(hourlyRate) || hourlyRate <= 0) {
    alert('Please enter a valid hourly rate.');
    return;
  }

  isSubmitting.value = true;

  try {
    // Prepare FormData for API (to support file upload)
    const formData = new FormData();
    formData.append('name', form.value.fullName);
    formData.append('email', form.value.email || '');
    formData.append('password', form.value.password);
    formData.append('phone', form.value.phone || '');
    formData.append('role', jobTitleOptions.find(option => option.value === form.value.role)?.label || form.value.role);
    formData.append('shift', shiftOptions.find(option => option.value === form.value.workShift)?.label || form.value.workShift);
    formData.append('hourly_rate', parseFloat(form.value.hourlyRate) || 15.00);
    formData.append('hire_date', new Date().toISOString().split('T')[0]);
    
    // Add image if uploaded
    if (form.value.profilePicture) {
      formData.append('image', form.value.profilePicture);
    }

    // Send to API
    const response = await fetch('/api/staff', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: formData
    });

    if (response.ok) {
      const result = await response.json();
      alert(`Staff member "${form.value.fullName}" has been added successfully!`);
      
      // Navigate back to staff page
      router.get('/admin/staff');
    } else {
      const error = await response.json();
      alert('Error adding staff member: ' + (error.message || 'Unknown error'));
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error adding staff member. Please try again.');
  } finally {
    isSubmitting.value = false;
  }
};

const saveDraft = () => {
  localStorage.setItem('staffDraft', JSON.stringify(form.value));
  alert('Draft saved successfully!');
};

const goBack = () => {
  router.get('/admin/staff');
};
</script>

<template>
  <AdminLayout 
    title="Add New Staff Member"
    page-title="Add New Staff Member"
    page-subtitle="Create a new staff member profile and assign roles"
  >
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <button 
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
        >
          <span class="material-symbols-outlined">arrow_back</span>
          <span>Back to Staff</span>
        </button>
      </div>
    </div>

    <div class="max-w-7xl mx-auto">
      <form @submit.prevent="submitForm" class="space-y-12">
        <!-- Personal Information -->
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Personal Information"
            subtitle="Basic information about the staff member"
            icon="person"
          />
          
          <!-- Profile Picture Upload -->
          <div class="mb-8">
            <div class="bg-white dark:bg-gray-900/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
              <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-6">Profile Picture</label>
              <div class="flex flex-col sm:flex-row items-center gap-6">
                <div class="flex-shrink-0">
                  <div class="w-32 h-32 rounded-full bg-gray-100 dark:bg-gray-800 border-4 border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden">
                    <div v-if="profilePreview" class="w-full h-full">
                      <img :src="profilePreview" alt="Profile Preview" class="w-full h-full object-cover rounded-full">
                    </div>
                    <div v-else class="w-full h-full flex items-center justify-center">
                      <span class="material-symbols-outlined text-4xl text-gray-400">person</span>
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
                        id="profile-upload" 
                        @change="handleProfileUpload"
                      >
                      <div class="flex items-center justify-center gap-2 px-6 py-3 rounded-xl border-2 border-dashed border-[#ec7813]/30 text-[#ec7813] hover:border-[#ec7813]/50 hover:bg-[#ec7813]/5 transition-all duration-200 font-medium">
                        <span class="material-symbols-outlined">upload</span>
                        <span>Upload Photo</span>
                      </div>
                    </label>
                    <button 
                      type="button" 
                      @click="clearProfilePhoto"
                      class="px-6 py-3 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 font-medium"
                    >
                      <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">delete</span>
                        Remove
                      </span>
                    </button>
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    <p>• Recommended: 400x400px or larger</p>
                    <p>• Supported formats: JPG, PNG, GIF</p>
                    <p>• Maximum file size: 5MB</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
            <FormInput
              v-model="form.fullName"
              label="Full Name"
              type="text"
              placeholder="Enter full name (e.g., Sarah Johnson)"
              required
            />
            
            <FormInput
              v-model="form.email"
              label="Email Address"
              type="email"
              placeholder="Enter email address"
            />
            
            <FormInput
              v-model="form.password"
              label="Password"
              type="password"
              placeholder="Enter password (min 6 characters)"
              required
            />
            
            <FormInput
              v-model="form.phone"
              label="Phone Number"
              type="tel"
              placeholder="Enter phone number"
            />
            
            <FormInput
              v-model="form.experience"
              label="Experience (Years)"
              type="number"
              placeholder="Enter years of experience"
              min="0"
              max="50"
              required
            />
            
            <FormInput
              v-model="form.hourlyRate"
              label="Hourly Rate ($)"
              type="number"
              placeholder="Enter hourly rate"
              min="0"
              step="0.01"
              required
            />
          </div>
        </CardWrapper>

        <!-- Job Information -->
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Job Information"
            subtitle="Role and employment details"
            icon="work"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-2">
              <FormSelect
                v-model="form.role"
                label="Job Title"
                placeholder="Select job title"
                :options="jobTitleOptions"
                allow-custom
                custom-option-label="+ Add New Job Title"
                required
                @add-custom="handleJobTitleChange('add_new')"
                @change="(e) => handleJobTitleChange(e.target.value)"
              />
              
              <!-- New Job Title Input -->
              <div v-show="showNewJobTitle" class="space-y-3 mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <FormInput
                  v-model="newJobTitle"
                  type="text"
                  placeholder="Enter new job title"
                />
                <div class="flex gap-3">
                  <button 
                    type="button" 
                    @click="addNewJobTitle"
                    class="px-4 py-2 bg-[#ec7813] text-white rounded-lg hover:bg-[#ea580c] transition-all text-sm font-medium"
                  >
                    <span class="flex items-center gap-2">
                      <span class="material-symbols-outlined text-sm">add</span>
                      Add
                    </span>
                  </button>
                  <button 
                    type="button" 
                    @click="cancelNewJobTitle"
                    class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all text-sm font-medium"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </div>
            
            <FormSelect
              v-model="form.workShift"
              label="Work Shift"
              placeholder="Select shift"
              :options="shiftOptions"
              required
            />
          </div>
        </CardWrapper>

        <!-- Permissions & Access -->
        <CardWrapper rounded="xl" padding="lg" shadow="hover" hover>
          <FormSection 
            title="Permissions & Access"
            subtitle="System access levels and specific permissions"
            icon="security"
          />
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <FormSelect
              v-model="form.accessLevel"
              label="Access Level"
              :options="[
                { value: 'staff', label: 'Staff (Basic Access)' },
                { value: 'supervisor', label: 'Supervisor' },
                { value: 'manager', label: 'Manager' },
                { value: 'admin', label: 'Administrator' }
              ]"
            />
            
            <div class="space-y-2">
              <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3">Notes</label>
              <textarea 
                v-model="form.notes"
                rows="2" 
                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ec7813] focus:border-[#ec7813] transition-all duration-200 text-base resize-none" 
                placeholder="Additional notes about this staff member (optional)"
              ></textarea>
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
              :disabled="isSubmitting"
              class="w-full sm:w-auto px-8 py-3 rounded-xl bg-[#ec7813] text-white hover:bg-[#ea580c] hover:shadow-lg transition-all duration-200 font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">{{ isSubmitting ? 'hourglass_empty' : 'person_add' }}</span>
                {{ isSubmitting ? 'Adding...' : 'Add Staff Member' }}
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
