// Module-level (singleton) state powering a single, app-wide toast
// notification (snackbar). Success/error feedback for actions goes
// here; form validation errors stay inline in their dialog instead.
import { ref } from 'vue'

interface NotifyState {
  visible: boolean
  message: string
  color: 'success' | 'error' | 'info' | 'warning'
}

const state = ref<NotifyState>({ visible: false, message: '', color: 'success' })

function show(message: string, color: NotifyState['color']): void {
  state.value = { visible: true, message, color }
}

export function useNotify() {
  return {
    state,
    success: (message: string) => show(message, 'success'),
    error: (message: string) => show(message, 'error'),
    info: (message: string) => show(message, 'info'),
    warning: (message: string) => show(message, 'warning'),
  }
}
