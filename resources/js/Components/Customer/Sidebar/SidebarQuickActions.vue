<script setup>
import QuickActionButton from './Buttons/QuickActionButton.vue'
</script>

<template>
    <div class="mt-8">
        <p class="text-label text-xs text-surface-500 mb-3">Quick Actions
        </p>
        <div class="space-y-2">
            <QuickActionButton :href="route('order.status')" icon-class="fas fa-clipboard-check" title="Track Order" subtitle="Live status" />
            <QuickActionButton href="#" icon-class="fas fa-gift" title="Today's Deals" subtitle="Special offers" />
            <QuickActionButton href="#" icon-class="fas fa-heart" title="Favorites" subtitle="Your saved items" />
            <QuickActionButton href="#" icon-class="fas fa-history" title="Order History" subtitle="Previous orders" />
        </div>
    </div>
</template>