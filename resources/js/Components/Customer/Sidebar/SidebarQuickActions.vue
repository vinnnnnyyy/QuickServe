<script setup>
import QuickActionButton from './Buttons/QuickActionButton.vue'
import { useOrderHistory } from '../../../composables/useOrderHistory.js'

const { activeOrderCount } = useOrderHistory()
</script>

<template>
    <div class="mt-6 pt-6 border-t border-surface-100">
        <p class="text-xs font-medium text-surface-400 mb-3 uppercase tracking-wider">Quick Actions</p>
        <div class="space-y-0.5">
            <QuickActionButton 
                :href="route('order.history')" 
                icon-class="fas fa-receipt" 
                title="My Orders" 
                :badge="activeOrderCount > 0 ? activeOrderCount : null"
            />
            <QuickActionButton :href="route('order.status')" icon-class="fas fa-clipboard-check" title="Track Order" />
            <QuickActionButton href="#" icon-class="fas fa-heart" title="Favorites" />
        </div>
    </div>
</template>