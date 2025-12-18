<script setup>
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import { Doughnut } from 'vue-chartjs'
import { computed } from 'vue'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
  data: {
    type: Array, // Expected: [{category: 'Name', percentage: 20, color: 'class-name'}]
    required: true
  }
})

// Map tailwind classes to approximate hex codes for Chart.js
// Since Chart.js needs hex/rgba, we need a map or passed hex props.
// Ideally backend passes hex, or we map classes here.
// For simplicity, let's assume specific colors map to standard palette or prop includes hex.
// Or we can rely on a fixed palette.

const colorPalette = [
    '#ec7813', // primary
    '#3b82f6', // blue-500
    '#22c55e', // green-500
    '#a855f7', // purple-500
    '#eab308', // yellow-500
    '#ef4444', // red-500
    '#06b6d4', // cyan-500
    '#f97316', // orange-500
];

const chartData = computed(() => {
  return {
    labels: props.data.map(item => item.category),
    datasets: [
      {
        backgroundColor: colorPalette,
        data: props.data.map(item => item.percentage),
        borderWidth: 0,
        hoverOffset: 4
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '75%',
  plugins: {
    legend: {
        display: false // We use custom legend in UI
    },
    tooltip: {
        callbacks: {
            label: function(context) {
                return ` ${context.label}: ${context.parsed}%`;
            }
        }
    }
  }
}
</script>

<template>
  <Doughnut :data="chartData" :options="chartOptions" />
</template>
