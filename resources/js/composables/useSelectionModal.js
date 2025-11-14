import { ref } from 'vue'

export function useSelectionModal(transform = (x) => x) {
  const selected = ref(null)
  const isOpen = ref(false)

  const open = (item) => {
    selected.value = transform(item)
    isOpen.value = true
  }

  const close = () => {
    isOpen.value = false
    selected.value = null
  }

  return { selected, isOpen, open, close }
}
