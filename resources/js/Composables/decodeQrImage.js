// Reads a QR code out of a picture file (a screenshot of the backup card).
// BarcodeDetector where the browser has it, else jsQR, lazy-loaded like the
// camera scanner's. Resolves to the decoded text, or null when there is none.
export default async function decodeQrImage(file) {
  const bitmap = await createImageBitmap(file)

  try {
    if ('BarcodeDetector' in window) {
      try {
        const detector = new window.BarcodeDetector({ formats: ['qr_code'] })
        const [code] = await detector.detect(bitmap)

        if (code?.rawValue) {
          return code.rawValue
        }
      } catch {
        // Not supported for qr_code: fall through to jsQR.
      }
    }

    const { default: jsQR } = await import('jsqr')
    const canvas = document.createElement('canvas')
    canvas.width = bitmap.width
    canvas.height = bitmap.height
    const context = canvas.getContext('2d', { willReadFrequently: true })
    context.drawImage(bitmap, 0, 0)
    const image = context.getImageData(0, 0, canvas.width, canvas.height)

    return jsQR(image.data, image.width, image.height)?.data ?? null
  } finally {
    bitmap.close?.()
  }
}
