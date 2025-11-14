<script setup>
import { computed } from 'vue'

const props = defineProps({
  order: {
    type: Object,
    default: () => ({})
  },
  items: {
    type: Array,
    default: () => []
  },
  totals: {
    type: Object,
    default: () => ({
      subtotal: 0,
      tax: 0,
      fees: 0,
      total: 0
    })
  },
  status: {
    type: Object,
    default: () => ({
      text: 'Pending',
      color: 'gray'
    })
  },
  payment: {
    type: Object,
    default: () => ({
      status: 'Unpaid',
      method: '',
      color: 'gray'
    })
  },
  showOrderInfo: {
    type: Boolean,
    default: true
  },
  showTimeline: {
    type: Boolean,
    default: false
  },
  theme: {
    type: String,
    default: 'light', // 'light' or 'dark'
    validator: (value) => ['light', 'dark'].includes(value)
  }
})

const emit = defineEmits(['item-click'])

// Format price helper
const formatPrice = (price) => {
  return `₱${Number(price || 0).toFixed(2)}`
}

// Status styling
const getStatusClasses = (color) => {
  const colorMap = {
    yellow: 'bg-yellow-100 text-yellow-700',
    green: 'bg-green-100 text-green-700',
    blue: 'bg-blue-100 text-blue-700',
    purple: 'bg-purple-100 text-purple-700',
    orange: 'bg-orange-100 text-orange-700',
    red: 'bg-red-100 text-red-700',
    gray: 'bg-gray-100 text-gray-700'
  }
  return colorMap[color] || colorMap.gray
}

const getStatusDotClasses = (color) => {
  const colorMap = {
    yellow: 'bg-yellow-500',
    green: 'bg-green-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
    orange: 'bg-orange-500',
    red: 'bg-red-500',
    gray: 'bg-gray-500'
  }
  return colorMap[color] || 'bg-gray-500'
}

// Computed properties
const hasItems = computed(() => props.items && props.items.length > 0)
const itemsCount = computed(() => props.items?.length || 0)
const isEmpty = computed(() => !hasItems.value)

// Theme classes
const containerClass = computed(() => {
  return props.theme === 'dark' 
    ? 'bg-gray-800/50 border-gray-700' 
    : 'bg-gray-50 border-gray-200'
})

const textPrimaryClass = computed(() => {
  return props.theme === 'dark' ? 'text-white' : 'text-gray-900'
})

const textSecondaryClass = computed(() => {
  return props.theme === 'dark' ? 'text-white/60' : 'text-gray-600'
})

const itemBgClass = computed(() => {
  return props.theme === 'dark' 
    ? 'bg-gray-900/50 border-gray-700' 
    : 'bg-white border-gray-200'
})
</script>

<template>
  <div :class="showOrderInfo ? 'grid grid-cols-1 lg:grid-cols-3 gap-6' : 'space-y-5'">
    <!-- Left Column: Order Info (Optional) -->
    <div v-if="showOrderInfo" class="lg:col-span-1 space-y-5">
      <div :class="['rounded-xl p-5 border shadow-sm', containerClass]">
        <h4 :class="['text-base font-bold mb-4 flex items-center gap-2', textPrimaryClass]">
          <i class="fas fa-info-circle text-primary-600"></i>
          Order Details
        </h4>
        
        <div class="space-y-3">
          <div v-if="order.id">
            <label :class="['block text-xs font-medium mb-1', textSecondaryClass]">Order ID</label>
            <p :class="['text-sm font-semibold', textPrimaryClass]">{{ order.id }}</p>
          </div>
          <div v-if="order.sessionId">
            <label :class="['block text-xs font-medium mb-1', textSecondaryClass]">Session</label>
            <p :class="['text-sm', textPrimaryClass]">{{ order.sessionId }}</p>
          </div>
          <div v-if="order.tableType">
            <label :class="['block text-xs font-medium mb-1', textSecondaryClass]">Table/Location</label>
            <p :class="['text-sm', textPrimaryClass]">{{ order.tableType }}</p>
          </div>
          <div v-if="order.orderType">
            <label :class="['block text-xs font-medium mb-1', textSecondaryClass]">Order Type</label>
            <p :class="['text-sm', textPrimaryClass]">{{ order.orderType }}</p>
          </div>
          <div v-if="order.deviceInfo">
            <label :class="['block text-xs font-medium mb-1', textSecondaryClass]">Device</label>
            <p :class="['text-xs', textPrimaryClass]">{{ order.deviceInfo.type }}</p>
          </div>
          <div>
            <label :class="['block text-xs font-medium mb-1', textSecondaryClass]">Status</label>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" :class="getStatusClasses(status.color)">
              <span class="w-1 h-1 rounded-full" :class="getStatusDotClasses(status.color)"></span>
              {{ status.text }}
            </span>
          </div>
          <div>
            <label :class="['block text-xs font-medium mb-1', textSecondaryClass]">Payment</label>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" :class="getStatusClasses(payment.color)">
              <span class="w-1 h-1 rounded-full" :class="getStatusDotClasses(payment.color)"></span>
              {{ payment.status }}
            </span>
          </div>
          <div v-if="order.time">
            <label :class="['block text-xs font-medium mb-1', textSecondaryClass]">Time</label>
            <p :class="['text-xs', textPrimaryClass]">{{ order.time }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Middle Column: Order Items -->
    <div :class="showOrderInfo ? 'lg:col-span-1' : ''">
      <div class="space-y-5">
        <div :class="['rounded-xl p-5 border shadow-sm', containerClass]">
          <h4 :class="['text-base font-bold mb-4 flex items-center gap-2', textPrimaryClass]">
            <i class="fas fa-list-alt text-primary-600"></i>
            Order Items
          </h4>
          
          <!-- Empty State -->
          <div v-if="isEmpty" class="text-center py-6">
            <i class="fas fa-shopping-cart text-2xl mb-2 text-gray-400"></i>
            <p :class="['text-sm', textSecondaryClass]">No items in your order</p>
          </div>
          
          <!-- Items List -->
          <div v-else class="space-y-3 max-h-64 overflow-y-auto">
            <div 
              v-for="(item, index) in items" 
              :key="`item-${item.id || index}`"
              :class="['rounded-lg p-4 border cursor-pointer hover:shadow-md hover:border-primary-200 transition-all duration-200', itemBgClass]"
              @click="emit('item-click', item, index)"
            >
              <div class="flex items-start justify-between gap-4">
                <!-- Item Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-start gap-3">
                    <!-- Item Image (if available) -->
                    <div v-if="item.image" class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0 ring-1 ring-gray-200">
                      <img :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                    </div>
                    
                    <!-- Item Details -->
                    <div class="flex-1 min-w-0">
                      <h5 :class="['font-semibold text-sm leading-tight mb-1', textPrimaryClass]">{{ item.name }}</h5>
                      
                      <!-- Quantity Badge -->
                      <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                          {{ item.quantity || 1 }}x
                        </span>
                      </div>
                      
                      <!-- Customizations -->
                      <div v-if="item.customizations || item.selectedSize || item.isCustomized" class="space-y-1">
                        <div class="flex flex-wrap gap-1">
                          <span v-if="item.selectedSize" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                            {{ item.selectedSize }}
                          </span>
                          <span v-if="item.customizations?.temperature" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">
                            {{ item.customizations.temperature }}
                          </span>
                          <span v-if="item.customizations?.milk && item.customizations.milk !== 'regular'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                            {{ item.customizations.milk }}
                          </span>
                          <span v-if="item.isCustomized && !item.selectedSize && !item.customizations" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
                            <i class="fas fa-cog mr-1"></i>
                            Custom
                          </span>
                        </div>
                        
                        <!-- Extras -->
                        <div v-if="item.customizations?.extras && item.customizations.extras.length > 0" class="flex flex-wrap gap-1">
                          <span 
                            v-for="extra in item.customizations.extras" 
                            :key="extra"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700"
                          >
                            {{ extra.replace('-', ' ').replace('_', ' ') }}
                          </span>
                        </div>
                        
                        <!-- Special Instructions -->
                        <div v-if="item.customizations?.specialInstructions" class="mt-1 p-1.5 bg-yellow-50 rounded-md border-l-2 border-yellow-200">
                          <p :class="['text-xs italic', textSecondaryClass]">
                            "{{ item.customizations.specialInstructions }}"
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Price -->
                <div class="text-right flex-shrink-0 ml-3">
                  <div :class="['text-lg font-bold', textPrimaryClass]">
                    {{ formatPrice((item.displayPrice || item.finalPrice || item.price) * (item.quantity || 1)) }}
                  </div>
                  <div v-if="item.displayPrice || item.finalPrice" :class="['text-xs mt-0.5', textSecondaryClass]">
                    {{ formatPrice(item.displayPrice || item.finalPrice) }} each
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Order Totals & Timeline -->
    <div :class="showOrderInfo ? 'lg:col-span-1' : ''">
      <div class="space-y-5">
        <!-- Order Totals -->
        <div v-if="!isEmpty" :class="['rounded-xl p-5 border shadow-sm', containerClass]">
          <h4 :class="['text-base font-bold mb-4 flex items-center gap-2', textPrimaryClass]">
            <i class="fas fa-calculator text-primary-600"></i>
            Order Total
          </h4>
          
          <div class="space-y-2">
            <div class="flex justify-between items-center py-1.5">
              <span :class="['text-sm', textSecondaryClass]">Subtotal ({{ itemsCount }} items):</span>
              <span :class="['font-semibold', textPrimaryClass]">{{ formatPrice(totals.subtotal) }}</span>
            </div>
            
            <div v-if="totals.tax > 0" class="flex justify-between items-center py-1.5">
              <span :class="['text-sm', textSecondaryClass]">Tax (12%):</span>
              <span :class="['font-semibold', textPrimaryClass]">{{ formatPrice(totals.tax) }}</span>
            </div>
            
            <div v-if="totals.fees > 0" class="flex justify-between items-center py-1.5">
              <span :class="['text-sm', textSecondaryClass]">Service Fee:</span>
              <span :class="['font-semibold', textPrimaryClass]">{{ formatPrice(totals.fees) }}</span>
            </div>
            
            <div class="flex justify-between items-center pt-3 border-t-2 border-primary-200">
              <span :class="['text-lg font-bold', textPrimaryClass]">Total:</span>
              <span class="text-xl font-bold text-primary-600">{{ formatPrice(totals.total) }}</span>
            </div>
          </div>
        </div>

        <!-- Order Timeline (optional) -->
        <div v-if="showTimeline" :class="['rounded-xl p-5 border shadow-sm', containerClass]">
          <h4 :class="['text-base font-bold mb-4 flex items-center gap-2', textPrimaryClass]">
            <i class="fas fa-clock text-primary-600"></i>
            Order Timeline
          </h4>
          
          <div class="space-y-3">
            <div :class="['flex items-center gap-3 p-3 rounded-lg border transition-all duration-200', itemBgClass]">
              <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center ring-2 ring-green-200">
                <i class="fas fa-check text-green-600 text-sm"></i>
              </div>
              <div class="flex-1">
                <p :class="['font-semibold text-sm', textPrimaryClass]">Order Placed</p>
                <p :class="['text-xs', textSecondaryClass]">{{ order.time || 'Just now' }}</p>
              </div>
              <div class="text-green-600">
                <i class="fas fa-check-circle text-sm"></i>
              </div>
            </div>
            
            <div v-if="status.text !== 'Pending'" :class="['flex items-center gap-3 p-3 rounded-lg border transition-all duration-200', itemBgClass]">
              <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center ring-2 ring-blue-200">
                <i class="fas fa-check-circle text-blue-600 text-sm"></i>
              </div>
              <div class="flex-1">
                <p :class="['font-semibold text-sm', textPrimaryClass]">Order Confirmed</p>
                <p :class="['text-xs', textSecondaryClass]">Being prepared...</p>
              </div>
              <div class="text-blue-600">
                <i class="fas fa-spinner fa-spin text-sm"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions Slot -->
        <div v-if="$slots.actions" class="flex justify-end">
          <slot name="actions" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
