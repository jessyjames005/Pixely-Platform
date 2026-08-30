// Module-level (singleton) state powering a single, app-wide confirmation
// dialog. Any component can call confirm({...}) and await the result,
// instead of using the native, unstyled window.confirm().
import { ref } from 'vue'

interface ConfirmOptions {
  title?: string
  message: string
  confirmText?: string
  cancelText?: string
  color?: string // e.g. 'error' for destructive actions (the default)
}

const isOpen = ref(false)
const options = ref<ConfirmOptions>({ message: '' })
let resolvePromise: ((value: boolean) => void) | null = null

// Opens the confirmation dialog and resolves once the user answers.
function confirm(opts: ConfirmOptions): Promise<boolean> {
  options.value = opts
  isOpen.value = true

  return new Promise((resolve) => {
    resolvePromise = resolve
  })
}

function handleConfirm(): void {
  isOpen.value = false
  resolvePromise?.(true)
  resolvePromise = null
}

function handleCancel(): void {
  isOpen.value = false
  resolvePromise?.(false)
  resolvePromise = null
}

export function useConfirmDialog() {
  return { isOpen, options, confirm, handleConfirm, handleCancel }
}
