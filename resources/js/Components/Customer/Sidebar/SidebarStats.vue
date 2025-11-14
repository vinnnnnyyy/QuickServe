<script setup>
import StatCard from '../../Shared/DataDisplay/StatCard.vue'

defineProps({
    hours: {
        type: String,
        default: '8 AM - 10 PM'
    },
    rating: {
        type: Number,
        default: 4.8 }
})
</script>

<template>
    <div class="grid grid-cols-2 gap-3 mb-8">
        <!-- Hours Stat -->
        <StatCard icon="fas fa-clock" label="Hours" :value="hours" theme="primary" />

        <!-- Rating Stat -->
        <StatCard icon="fas fa-star" label="Rating" :value="`${Number(rating).toFixed(1)} ★`" theme="green" />
    </div>
</template>