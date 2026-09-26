import { nextTick } from 'vue'

// After a failed submit, move focus to the first field marked
// `aria-invalid="true"` so keyboard and screen-reader users land on the
// problem (its message is announced through FieldError's role="alert" and
// the field's aria-describedby). Call it from the submit's `onError`.
export function focusFirstInvalid(root = document) {
  return nextTick(() => {
    const field = root.querySelector('[aria-invalid="true"]')

    if (field) {
      field.focus()
    }
  })
}

// Attributes for a field with a possible error message.
export function fieldErrorAttrs(id, message, hintId = null) {
  return {
    'aria-invalid': message ? 'true' : null,
    'aria-describedby':
      [hintId, message ? id : null].filter(Boolean).join(' ') || null,
  }
}

export default function useFormErrorFocus() {
  return { focusFirstInvalid, fieldErrorAttrs }
}
