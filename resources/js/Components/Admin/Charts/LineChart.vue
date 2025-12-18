<script setup>
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import { Line } from 'vue-chartjs'
import { computed } from 'vue'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const props = defineProps({
  data: {
    type: Array, // Expected: [{date: 'Y-m-d', total: 100}]
    required: true
  },
  label: {
    type: String,
    default: 'Revenue'
  },
  color: {
    type: String,
    default: '#ec7813'
  }
})

const chartData = computed(() => {
  return {
    labels: props.data.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }),
    datasets: [
      {
        label: props.label,
        backgroundColor: (context) => {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 300);
          gradient.addColorStop(0, `${props.color}40`); // 25% opacity
          gradient.addColorStop(1, `${props.color}00`); // 0% opacity
          return gradient;
        },
        borderColor: props.color,
        data: props.data.map(item => item.total),
        fill: true,
        tension: 0.4,
        pointRadius: 0,
        pointHoverRadius: 6,
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
          label: function(context) {
              let label = context.dataset.label || '';
              if (label) {
                  label += ': ';
              }
              if (context.parsed.y !== null) {
                  label += new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(context.parsed.y);
              }
              return label;
          }
      }
    }
  },
  scales: {
    x: {
      grid: {
        display: false,
        drawBorder: false
      },
      ticks: {
        maxTicksLimit: 7
      }
    },
    y: {
      grid: {
        color: '#f3f4f6', 
        drawBorder: false,
        borderDash: [5, 5]
      },
      ticks: {
          callback: function(value, index, values) {
              if (value >= 1000) {
                  return '₱' + (value / 1000) + 'k';
              }
              return '₱' + value;
          }
      }
    }
  },
  interaction: {
      mode: 'nearest',
      axis: 'x',
      intersect: false
  }
}
</script>

<template>
  <Line :data="chartData" :options="chartOptions" />
</template>
