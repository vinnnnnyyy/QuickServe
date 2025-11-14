<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Admin/UI/Pagination.vue';
import AdminModal from '@/Components/Admin/UI/AdminModal.vue';
import CardWrapper from '@/Components/Admin/UI/CardWrapper.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

// Accept props from Inertia
const props = defineProps({
  staff: {
    type: Array,
    default: () => []
  }
});

// Convert props to reactive ref for local manipulation
const staff = ref(props.staff || []);

// Add avatar generation for staff members (computed property for reactivity)
const processedStaff = computed(() => {
  const staffArray = Array.isArray(staff.value) ? staff.value : [];

  return staffArray.map(member => {
    const processedMember = { ...member };

    // Generate avatar if not present
    if (!processedMember.avatar) {
      const names = processedMember.name.split(' ');
      processedMember.avatar = names.length > 1
        ? names[0].charAt(0) + names[names.length - 1].charAt(0)
        : names[0].charAt(0) + names[0].charAt(1);
    }

    // Generate avatar color if not present
    if (!processedMember.avatarColor) {
      const colors = [
        'bg-gradient-to-br from-blue-500 to-blue-600',
        'bg-gradient-to-br from-green-500 to-green-600',
        'bg-gradient-to-br from-purple-500 to-purple-600',
        'bg-gradient-to-br from-red-500 to-red-600',
        'bg-gradient-to-br from-teal-500 to-teal-600',
        'bg-gradient-to-br from-indigo-500 to-indigo-600',
        'bg-gradient-to-br from-pink-500 to-pink-600',
        'bg-gradient-to-br from-amber-500 to-amber-600'
      ];
      processedMember.avatarColor = colors[processedMember.id % colors.length];
    }

    // Add missing fields with defaults
    if (!processedMember.department) {
      processedMember.department = processedMember.role?.toLowerCase().includes('manager') ? 'manager' : 'barista';
    }
    if (!processedMember.skills) {
      processedMember.skills = ['Customer Service'];
    }
    if (!processedMember.shift) {
      processedMember.shift = '8:00 AM - 4:00 PM';
    }
    if (!processedMember.hire_date) {
      processedMember.hire_date = new Date().toISOString().split('T')[0];
    }

    return processedMember;
  });
});

// Remove hardcoded staff data - keeping old structure for reference but commented out
/*
const staff = ref([
*/

// Pagination logic
const currentPage = ref(1);
const itemsPerPage = ref(6); // 6 staff members per page (2 rows of 3 cards each)

const searchTerm = ref('');
const activeFilter = ref('all');
const selectedStaff = ref(null);
const showStaffModal = ref(false);

// Computed properties
const filteredStaff = computed(() => {
  let filtered = processedStaff.value;
  
  // Apply department filter
  if (activeFilter.value !== 'all') {
    if (activeFilter.value === 'part_time') {
      filtered = filtered.filter(member => {
        const rate = parseFloat(member.hourly_rate) || 0;
        return rate < 15; // Assuming part-time staff have lower hourly rates
      });
    } else {
      filtered = filtered.filter(member => member.department === activeFilter.value);
    }
  }
  
  // Apply search filter
  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase();
    filtered = filtered.filter(member => 
      member.name.toLowerCase().includes(search) ||
      member.role.toLowerCase().includes(search) ||
      (member.department && member.department.toLowerCase().includes(search)) ||
      (member.email && member.email.toLowerCase().includes(search))
    );
  }
  
  return filtered;
});

// Paginated staff computed property
const paginatedStaff = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredStaff.value.slice(start, end);
});

// Total filtered items (for pagination)
const totalFilteredStaff = computed(() => filteredStaff.value.length);

// Handle page change
const handlePageChange = (page) => {
  currentPage.value = page;
  console.log('Staff page changed to:', page);
};

// Delete staff member function
const deleteStaffMember = async (staffId) => {
  if (!confirm('Are you sure you want to delete this staff member?')) {
    return;
  }
  
  try {
    const response = await fetch(`/api/staff/${staffId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    });
    
    if (response.ok) {
      // Remove staff member from local array
      staff.value = staff.value.filter(member => member.id !== staffId);
      alert('Staff member deleted successfully!');
      
      // Close modal if the deleted staff member was selected
      if (selectedStaff.value && selectedStaff.value.id === staffId) {
        closeStaffModal();
      }
    } else {
      const error = await response.json();
      alert('Error deleting staff member: ' + (error.message || 'Unknown error'));
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error deleting staff member. Please try again.');
  }
};

// Toggle staff status function
const toggleStaffStatus = async (member) => {
  const statusCycle = {
    'active': 'break',
    'break': 'off_duty', 
    'off_duty': 'active'
  };
  
  const newStatus = statusCycle[member.status] || 'active';
  
  try {
    const response = await fetch(`/api/staff/${member.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        status: newStatus
      })
    });
    
    if (response.ok) {
      const updatedMember = await response.json();
      // Update local staff member
      const index = staff.value.findIndex(s => s.id === member.id);
      if (index !== -1) {
        staff.value[index] = { ...staff.value[index], ...updatedMember };
      }
      // Update selected staff if modal is open
      if (selectedStaff.value && selectedStaff.value.id === member.id) {
        selectedStaff.value = { ...selectedStaff.value, ...updatedMember };
      }
    } else {
      const error = await response.json();
      alert('Error updating staff status: ' + (error.message || 'Unknown error'));
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error updating staff status. Please try again.');
  }
};

// Refresh data function
const refreshData = () => {
  router.reload({ only: ['staff'] });
};

const staffStats = computed(() => ({
  total: processedStaff.value.length,
  active: processedStaff.value.filter(member => member.status === 'active').length,
  onBreak: processedStaff.value.filter(member => member.status === 'break').length,
  offDuty: processedStaff.value.filter(member => member.status === 'off_duty').length,
  onLeave: processedStaff.value.filter(member => member.status === 'on_leave').length
}));

const departmentCounts = computed(() => ({
  all: processedStaff.value.length,
  barista: processedStaff.value.filter(member => member.department === 'barista').length,
  manager: processedStaff.value.filter(member => member.department === 'manager').length,
  cashier: processedStaff.value.filter(member => member.department === 'cashier').length,
  part_time: processedStaff.value.filter(member => {
    const rate = parseFloat(member.hourly_rate) || 0;
    return rate < 15; // Assuming part-time staff have lower hourly rates
  }).length
}));

const todaysSchedule = computed(() => {
  const morningShift = processedStaff.value.filter(member =>
    member.status === 'active' &&
    (member.shift?.includes('6:00 AM') || member.shift?.includes('8:00 AM') || member.shift?.includes('Morning'))
  );

  const eveningShift = processedStaff.value.filter(member =>
    member.status === 'active' &&
    (member.shift?.includes('2:00 PM') || member.shift?.includes('10:00 PM') || member.shift?.includes('Evening') || member.shift?.includes('12:00 PM'))
  );

  return {
    morning: morningShift,
    evening: eveningShift,
    morningCount: morningShift.length,
    eveningCount: eveningShift.length
  };
});

// Methods
const setActiveFilter = (filter) => {
  activeFilter.value = filter;
};

const getStatusConfig = (status) => {
  const configs = {
    active: {
      text: 'Active',
      color: 'text-green-600 dark:text-green-400',
      dot: 'bg-green-500'
    },
    break: {
      text: 'Break',
      color: 'text-yellow-600 dark:text-yellow-400',
      dot: 'bg-yellow-500'
    },
    off_duty: {
      text: 'Off Duty',
      color: 'text-red-600 dark:text-red-400',
      dot: 'bg-red-500'
    },
    on_leave: {
      text: 'On Leave',
      color: 'text-gray-600 dark:text-gray-400',
      dot: 'bg-gray-500'
    }
  };
  return configs[status] || configs.off_duty;
};

const openStaffModal = (member) => {
  selectedStaff.value = member;
  showStaffModal.value = true;
  document.body.style.overflow = 'hidden';
};

const closeStaffModal = () => {
  showStaffModal.value = false;
  selectedStaff.value = null;
  document.body.style.overflow = '';
};

const viewProfile = (staffMember) => {
  openStaffModal(staffMember);
};

const editStaff = (staffId) => {
  router.get(`/admin/staff/${staffId}/edit`);
};

const deleteStaff = (staffId) => {
  deleteStaffMember(staffId);
};

// Recent activity data
const recentActivity = ref([
  {
    id: 1,
    action: 'Sarah Johnson clocked in',
    time: '2 minutes ago',
    type: 'clock_in',
    icon: 'check_circle',
    color: 'text-green-600 dark:text-green-400',
    bgColor: 'bg-green-100 dark:bg-green-900/20'
  },
  {
    id: 2,
    action: 'New staff member added',
    time: '1 hour ago',
    type: 'staff_added',
    icon: 'person_add',
    color: 'text-blue-600 dark:text-blue-400',
    bgColor: 'bg-blue-100 dark:bg-blue-900/20'
  },
  {
    id: 3,
    action: 'Schedule updated for next week',
    time: '3 hours ago',
    type: 'schedule_update',
    icon: 'schedule',
    color: 'text-yellow-600 dark:text-yellow-400',
    bgColor: 'bg-yellow-100 dark:bg-yellow-900/20'
  }
]);

// Close modal on escape key
const handleKeydown = (e) => {
  if (e.key === 'Escape') {
    closeStaffModal();
  }
};

// Mount/unmount event listeners
import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <AdminLayout
    title="Staff Management"
    page-title="Staff Management"
    page-subtitle="Manage your team members and their roles"
  >
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 mb-6">
      <div class="flex items-center gap-3">
        <div class="relative">
          <input
            v-model="searchTerm"
            type="search"
            placeholder="Search staff..."
            class="pl-10 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary"
          >
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
        </div>
        <div class="flex items-center gap-2">
          <button 
            @click="refreshData"
            class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
            title="Refresh data"
          >
            <span class="material-symbols-outlined">refresh</span>
          </button>
          <Link
            href="/admin/staff/add"
            class="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all"
          >
            <span class="material-symbols-outlined">person_add</span>
            <span>Add Staff</span>
          </Link>
        </div>
      </div>
    </div>

    <!-- Staff Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-blue-500/20 to-blue-400/10">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">group</span>
          </div>
          <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400">+2 this week</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">{{ staffStats.total }}</p>
        <p class="text-sm text-black/60 dark:text-white/60">Total Staff</p>
      </CardWrapper>

      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-green-500/20 to-green-400/10">
            <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-2xl">schedule</span>
          </div>
          <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400">On time</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">{{ staffStats.active }}</p>
        <p class="text-sm text-black/60 dark:text-white/60">Currently Working</p>
      </CardWrapper>

      <CardWrapper>
        <div class="flex items-center justify-between mb-4">
          <div class="p-3 rounded-lg bg-gradient-to-br from-yellow-500/20 to-yellow-400/10">
            <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-2xl">event</span>
          </div>
          <span class="text-xs font-medium px-2 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400">This week</span>
        </div>
        <p class="text-3xl font-bold text-black dark:text-white mb-1">{{ staffStats.onLeave }}</p>
        <p class="text-sm text-black/60 dark:text-white/60">On Leave</p>
      </CardWrapper>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2">
      <button
        @click="setActiveFilter('all')"
        :class="activeFilter === 'all' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-4 py-2 rounded-full whitespace-nowrap font-medium transition-all"
      >
        All Staff <span class="ml-1 px-2 py-0.5 rounded-full text-xs" :class="activeFilter === 'all' ? 'bg-white/20' : 'text-gray-500'">{{ departmentCounts.all }}</span>
      </button>
      <button
        @click="setActiveFilter('barista')"
        :class="activeFilter === 'barista' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        Baristas <span class="ml-1 text-gray-500">{{ departmentCounts.barista }}</span>
      </button>
      <button
        @click="setActiveFilter('manager')"
        :class="activeFilter === 'manager' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        Managers <span class="ml-1 text-gray-500">{{ departmentCounts.manager }}</span>
      </button>
      <button
        @click="setActiveFilter('part_time')"
        :class="activeFilter === 'part_time' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-4 py-2 rounded-full whitespace-nowrap transition-all"
      >
        Part-time <span class="ml-1 text-gray-500">{{ departmentCounts.part_time }}</span>
      </button>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
      <!-- Staff List -->
      <div class="xl:col-span-3 space-y-6">
        <CardWrapper overflow>
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
              <div>
                <h3 class="text-lg font-bold text-black dark:text-white">Team Members</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  <span v-if="activeFilter === 'all' && !searchTerm">
                    Showing all {{ processedStaff.length }} staff members
                  </span>
                  <span v-else>
                    Showing {{ filteredStaff.length }} of {{ processedStaff.length }} staff members
                    <span v-if="activeFilter !== 'all'" class="font-medium">in {{ activeFilter }}</span>
                    <span v-if="searchTerm" class="font-medium">matching "{{ searchTerm }}"</span>
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <select class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-black/20 text-sm text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                  <option>Sort by: Name</option>
                  <option>Sort by: Role</option>
                  <option>Sort by: Join Date</option>
                  <option>Sort by: Hourly Rate</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="p-8">
            <!-- Empty State -->
            <div v-if="processedStaff.length === 0" class="text-center py-16">
              <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                <span class="material-symbols-outlined text-4xl text-gray-400">group</span>
              </div>
              <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">No staff members found</h3>
              <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">Get started by adding your first team member to manage your staff.</p>
              <Link
                href="/admin/staff/add"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-primary text-white hover:bg-primary/90 transition-all font-medium"
              >
                <span class="material-symbols-outlined">person_add</span>
                <span>Add First Staff Member</span>
              </Link>
            </div>
            
            <!-- Filtered Empty State -->
            <div v-else-if="filteredStaff.length === 0" class="text-center py-12">
              <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-400 text-2xl">person_search</span>
              </div>
              <h3 class="text-lg font-medium text-black dark:text-white mb-2">No staff members found</h3>
              <p class="text-gray-500 dark:text-gray-400 mb-4">
                {{ searchTerm ? `No staff members match "${searchTerm}"` : `No staff members in this category` }}
              </p>
              <button
                @click="searchTerm = ''; activeFilter = 'all'"
                class="px-4 py-2 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-all"
              >
                Clear Filters
              </button>
            </div>
            
            <!-- Staff Grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- Staff Cards -->
              <div
                v-for="member in paginatedStaff"
                :key="member.id"
                class="staff-card bg-white dark:bg-gray-900/50 rounded-lg p-4 border-2 border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300 min-h-[220px] flex flex-col"
                :class="member.status === 'active' ? 'border-green-200 dark:border-green-800' :
                        member.status === 'break' ? 'border-yellow-200 dark:border-yellow-800' :
                        member.status === 'off_duty' ? 'border-red-200 dark:border-red-800' : 'border-blue-200 dark:border-blue-800'"
              >
                <!-- Staff Card Header -->
                <div class="flex items-start justify-between mb-3">
                  <div class="flex items-center gap-4 flex-1 min-w-0">
                    <div
                      :class="member.avatarColor"
                      class="w-14 h-14 rounded-full flex items-center justify-center text-white font-bold text-xl flex-shrink-0 shadow-sm"
                    >
                      {{ member.avatar }}
                    </div>
                    <div class="min-w-0 flex-1">
                      <h4 class="font-bold text-black dark:text-white text-base mb-1 leading-tight">
                        {{ member.name }}
                      </h4>
                      <p class="text-sm text-black/60 dark:text-white/60 font-medium">
                        {{ member.role }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                    <span :class="getStatusConfig(member.status).dot" class="w-2.5 h-2.5 rounded-full"></span>
                    <span :class="getStatusConfig(member.status).color" class="text-xs font-semibold whitespace-nowrap">
                      {{ getStatusConfig(member.status).text }}
                    </span>
                  </div>
                </div>

                <!-- Staff Details -->
                <div class="space-y-2 mb-4 flex-grow">
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-black/60 dark:text-white/60 font-medium">Hourly Rate:</span>
                    <span class="text-black dark:text-white font-semibold">${{ Number(member.hourly_rate || 0).toFixed(2) }}/hr</span>
                  </div>
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-black/60 dark:text-white/60 font-medium">Shift:</span>
                    <span class="text-black dark:text-white font-medium">{{ member.shift }}</span>
                  </div>
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-black/60 dark:text-white/60 font-medium">Department:</span>
                    <span class="text-black dark:text-white font-medium capitalize">{{ member.department }}</span>
                  </div>
                </div>

                <!-- Staff Actions -->
                <div class="flex items-center gap-2 mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                  <button
                    @click="viewProfile(member)"
                    class="flex-1 px-3 py-2 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-all text-sm font-medium"
                  >
                    View Profile
                  </button>
                  <button
                    @click="editStaff(member.id)"
                    class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
                    title="Edit Staff"
                  >
                    <span class="material-symbols-outlined text-base">edit</span>
                  </button>
                  <button
                    @click="toggleStaffStatus(member)"
                    class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
                    title="Toggle Status"
                  >
                    <span class="material-symbols-outlined text-base">sync</span>
                  </button>
                  <button
                    @click="deleteStaff(member.id)"
                    class="p-2 rounded-lg border border-red-200 dark:border-red-900 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                    title="Delete Staff"
                  >
                    <span class="material-symbols-outlined text-base">delete</span>
                  </button>
                </div>
              </div>

              <!-- Add Staff Card (only show if there are existing staff) -->
              <Link
                v-if="processedStaff.length > 0"
                href="/admin/staff/add"
                class="staff-card bg-gray-50 dark:bg-gray-900/20 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg min-h-[220px] flex items-center justify-center hover:border-primary dark:hover:border-primary transition-all duration-300 cursor-pointer group"
              >
                <div class="text-center p-4">
                  <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center group-hover:bg-primary/10 dark:group-hover:bg-primary/20 transition-all">
                    <span class="material-symbols-outlined text-3xl text-gray-400 group-hover:text-primary">person_add</span>
                  </div>
                  <p class="font-semibold text-gray-600 dark:text-gray-400 group-hover:text-primary mb-2 text-base">Add Staff Member</p>
                  <p class="text-sm text-gray-500 dark:text-gray-500 leading-relaxed">Click to add new team member</p>
                </div>
              </Link>
            </div>
            
            <!-- Pagination (only show if there are items to paginate) -->
            <div v-if="totalFilteredStaff > itemsPerPage" class="mt-8">
              <Pagination 
                :current-page="currentPage"
                :total-items="totalFilteredStaff"
                :items-per-page="itemsPerPage"
                items-text="staff members"
                @page-change="handlePageChange"
              />
            </div>
          </div>
        </CardWrapper>
      </div>

      <!-- Sidebar -->
      <div class="xl:col-span-1 sidebar-container space-y-6">
        <!-- Schedule Overview -->
        <CardWrapper>
          <div class="flex items-center gap-2 mb-6">
            <span class="material-symbols-outlined text-primary">schedule</span>
            <h3 class="text-lg font-bold text-black dark:text-white">Today's Schedule</h3>
          </div>
          
          <div class="space-y-6">
            <!-- Morning Shift -->
            <div class="pb-2">
              <div class="flex items-center justify-between p-3 bg-background-light dark:bg-background-dark rounded-lg mb-3 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                  <span class="text-sm text-black dark:text-white font-semibold">Morning Shift</span>
                </div>
                <span class="text-xs text-black/60 dark:text-white/60 font-medium">6:00 AM - 2:00 PM</span>
              </div>

              <div class="pl-4 space-y-2">
                <template v-if="todaysSchedule.morning.length > 0">
                  <div
                    v-for="member in todaysSchedule.morning.slice(0, 3)"
                    :key="`morning-${member.id}`"
                    class="flex items-start gap-2 py-1 schedule-item"
                  >
                    <span class="text-gray-400 mt-0.5 flex-shrink-0">•</span>
                    <div class="flex-1 min-w-0">
                      <span class="text-sm text-black dark:text-white font-medium">{{ member.name }}</span>
                      <span class="text-xs text-black/60 dark:text-white/60 ml-1">({{ member.role }})</span>
                    </div>
                  </div>
                  <div
                    v-if="todaysSchedule.morning.length > 3"
                    class="flex items-center gap-2 py-1"
                  >
                    <span class="text-gray-400">•</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400 italic">{{ todaysSchedule.morning.length - 3 }} more staff</span>
                  </div>
                </template>
                <div v-else class="flex items-center gap-2 py-1">
                  <span class="text-gray-400">•</span>
                  <span class="text-sm text-gray-500 dark:text-gray-500 italic">No staff assigned</span>
                </div>
                <!-- Spacing line -->
                <div class="h-2"></div>
              </div>
            </div>

            <!-- Evening Shift -->
            <div class="pb-2">
              <div class="flex items-center justify-between p-3 bg-background-light dark:bg-background-dark rounded-lg mb-3 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                  <span class="text-sm text-black dark:text-white font-semibold">Evening Shift</span>
                </div>
                <span class="text-xs text-black/60 dark:text-white/60 font-medium">2:00 PM - 10:00 PM</span>
              </div>

              <div class="pl-4 space-y-2">
                <template v-if="todaysSchedule.evening.length > 0">
                  <div
                    v-for="member in todaysSchedule.evening.slice(0, 4)"
                    :key="`evening-${member.id}`"
                    class="flex items-start gap-2 py-1 schedule-item"
                  >
                    <span class="text-gray-400 mt-0.5 flex-shrink-0">•</span>
                    <div class="flex-1 min-w-0">
                      <span class="text-sm text-black dark:text-white font-medium">{{ member.name }}</span>
                      <span class="text-xs text-black/60 dark:text-white/60 ml-1">({{ member.role }})</span>
                    </div>
                  </div>
                  <div
                    v-if="todaysSchedule.evening.length > 4"
                    class="flex items-center gap-2 py-1"
                  >
                    <span class="text-gray-400">•</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400 italic">{{ todaysSchedule.evening.length - 4 }} more staff</span>
                  </div>
                </template>
                <div v-else class="flex items-center gap-2 py-1">
                  <span class="text-gray-400">•</span>
                  <span class="text-sm text-gray-500 dark:text-gray-500 italic">No staff assigned</span>
                </div>
              </div>
            </div>
          </div>
        </CardWrapper>

        <!-- Quick Actions -->
        <CardWrapper>
          <div class="flex items-center gap-2 mb-6">
            <span class="material-symbols-outlined text-primary">bolt</span>
            <h3 class="text-lg font-bold text-black dark:text-white">Quick Actions</h3>
          </div>

          <div class="space-y-3">
            <button class="w-full flex items-center gap-4 p-4 rounded-lg bg-background-light dark:bg-background-dark hover:bg-gray-50 dark:hover:bg-gray-800 hover:shadow-sm transition-all text-left border border-gray-100 dark:border-gray-700 group">
              <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0 group-hover:bg-primary/20 transition-colors">
                <span class="material-symbols-outlined text-primary text-xl">schedule</span>
              </div>
              <div class="flex-1">
                <p class="text-sm font-semibold text-black dark:text-white mb-1">View Schedules</p>
                <p class="text-xs text-black/60 dark:text-white/60">Check who's working today</p>
              </div>
            </button>

            <button class="w-full flex items-center gap-4 p-4 rounded-lg bg-background-light dark:bg-background-dark hover:bg-gray-50 dark:hover:bg-gray-800 hover:shadow-sm transition-all text-left border border-gray-100 dark:border-gray-700 group">
              <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/20 flex items-center justify-center flex-shrink-0 group-hover:bg-green-200 dark:group-hover:bg-green-800/30 transition-colors">
                <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-xl">badge</span>
              </div>
              <div class="flex-1">
                <p class="text-sm font-semibold text-black dark:text-white mb-1">Staff Roles</p>
                <p class="text-xs text-black/60 dark:text-white/60">Manage permissions</p>
              </div>
            </button>

            <button class="w-full flex items-center gap-4 p-4 rounded-lg bg-background-light dark:bg-background-dark hover:bg-gray-50 dark:hover:bg-gray-800 hover:shadow-sm transition-all text-left border border-gray-100 dark:border-gray-700 group">
              <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-200 dark:group-hover:bg-blue-800/30 transition-colors">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-xl">assignment</span>
              </div>
              <div class="flex-1">
                <p class="text-sm font-semibold text-black dark:text-white mb-1">Coffee Stations</p>
                <p class="text-xs text-black/60 dark:text-white/60">Assign baristas to stations</p>
              </div>
            </button>
          </div>
        </CardWrapper>

        <!-- Recent Activity -->
        <CardWrapper>
          <div class="flex items-center gap-2 mb-6">
            <span class="material-symbols-outlined text-primary">history</span>
            <h3 class="text-lg font-bold text-black dark:text-white">Recent Activity</h3>
          </div>

          <div class="space-y-3">
            <div
              v-for="activity in recentActivity"
              :key="activity.id"
              class="flex items-start gap-3 p-4 rounded-lg bg-background-light dark:bg-background-dark border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 hover:shadow-sm transition-all"
            >
              <div :class="activity.bgColor" class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0">
                <span :class="activity.color" class="material-symbols-outlined text-lg">{{ activity.icon }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-black dark:text-white font-semibold leading-tight">{{ activity.action }}</p>
                <p class="text-xs text-black/60 dark:text-white/60 mt-1 font-medium">{{ activity.time }}</p>
              </div>
            </div>
          </div>
        </CardWrapper>
      </div>
    </div>

    <!-- Staff Details Modal -->
    <AdminModal
      :show="showStaffModal"
      :title="selectedStaff?.name"
      subtitle="Staff member profile"
      icon="badge"
      max-width="4xl"
      animation-type="scale"
      @close="closeStaffModal"
    >
      <!-- Modal Content -->
      <div v-if="selectedStaff" class="space-y-8">
        <!-- Staff Information -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">person</span>
            Personal Information
          </h4>
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="flex items-center gap-4">
              <div 
                :class="selectedStaff.avatarColor"
                class="w-20 h-20 rounded-full flex items-center justify-center text-white text-2xl font-bold flex-shrink-0"
              >
                {{ selectedStaff.avatar }}
              </div>
              <div class="space-y-2">
                <div>
                  <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Full Name</label>
                  <p class="text-base font-semibold text-black dark:text-white">{{ selectedStaff.name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Role</label>
                  <p class="text-base text-black dark:text-white">{{ selectedStaff.role }}</p>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Hourly Rate</label>
                  <p class="text-base font-medium text-black dark:text-white">${{ Number(selectedStaff.hourly_rate || 0).toFixed(2) }}/hr</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Status</label>
                  <div class="flex items-center gap-2">
                    <span :class="getStatusConfig(selectedStaff.status).dot" class="w-2 h-2 rounded-full"></span>
                    <span :class="getStatusConfig(selectedStaff.status).color" class="text-sm font-medium">
                      {{ getStatusConfig(selectedStaff.status).text }}
                    </span>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Contact Information</label>
                <div class="space-y-1">
                  <p class="text-sm text-black dark:text-white">{{ selectedStaff.email }}</p>
                  <p class="text-sm text-black dark:text-white">{{ selectedStaff.phone }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Work Details -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h4 class="text-lg font-bold text-black dark:text-white mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">work</span>
            Work Details
          </h4>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div>
              <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Department</label>
              <p class="text-base font-medium text-black dark:text-white capitalize">{{ selectedStaff.department }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Work Shift</label>
              <p class="text-base text-black dark:text-white">{{ selectedStaff.shift }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-1">Join Date</label>
              <p class="text-base text-black dark:text-white">{{ new Date(selectedStaff.hire_date || selectedStaff.joinDate || Date.now()).toLocaleDateString() }}</p>
            </div>
          </div>

          <div class="mt-6">
            <label class="block text-sm font-medium text-black/60 dark:text-white/60 mb-3">Skills & Specialties</label>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="skill in (selectedStaff.skills || ['Customer Service'])"
                :key="skill"
                class="px-3 py-1 rounded-full bg-primary/10 text-primary text-sm font-medium"
              >
                {{ skill }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Custom Footer -->
      <template #footer>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <button
              @click="closeStaffModal"
              class="px-6 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
            >
              Close
            </button>
            <button class="px-6 py-2 rounded-xl bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800 transition-all font-medium">
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">schedule</span>
                View Schedule
              </span>
            </button>
          </div>

          <div class="flex items-center gap-3">
            <button
              @click="toggleStaffStatus(selectedStaff)"
              :class="selectedStaff.status === 'active' 
                ? 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 hover:bg-yellow-200 dark:hover:bg-yellow-800' 
                : 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-800'"
              class="px-6 py-2 rounded-xl transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">{{ selectedStaff.status === 'active' ? 'pause' : 'play_arrow' }}</span>
                {{ selectedStaff.status === 'active' ? 'Set Break' : 'Set Active' }}
              </span>
            </button>
            <button
              @click="editStaff(selectedStaff.id); closeStaffModal();"
              class="px-6 py-2 rounded-xl bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800 transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">edit</span>
                Edit Profile
              </span>
            </button>
            <button
              @click="deleteStaff(selectedStaff.id); closeStaffModal();"
              class="px-6 py-2 rounded-xl bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-800 transition-all font-medium"
            >
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">delete</span>
                Remove Staff
              </span>
            </button>
          </div>
        </div>
      </template>
    </AdminModal>
  </AdminLayout>
</template>

<style>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Staff grid responsive layout handled by Tailwind classes */

/* Staff card styling */
.staff-card {
  min-width: 240px;
  max-width: 100%;
  position: relative;
}

/* Ensure proper text wrapping in schedule */
.schedule-item {
  word-wrap: break-word;
  overflow-wrap: break-word;
}

/* Ensure proper sidebar width in grid layout */
@media (min-width: 1280px) {
  .sidebar-container {
    min-width: 280px;
    max-width: 360px;
  }
}
</style>
