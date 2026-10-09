// Minimal QR Code generator (ISO/IEC 18004), byte mode, error correction
// level M, versions 1-40. Written in-house so the 2FA setup screen can show
// a scannable code without adding a dependency.
//
// Produces the module matrix only; rendering is up to the caller
// (see qrToPath() and the QrCode component).

// Error-correction codewords per block and number of blocks, indexed by
// version (index 0 is unused), for level M.
const ECC_CODEWORDS_PER_BLOCK = [
  -1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26, 26, 28, 28, 28, 28, 28, 28, 28,
  28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28,
]
const NUM_ERROR_CORRECTION_BLOCKS = [
  -1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33,
  35, 37, 38, 40, 43, 45, 47, 49,
]

const MIN_VERSION = 1
const MAX_VERSION = 40
// Format-information bits for level M.
const FORMAT_ECC_BITS = 0

export class QrCodeTooLongError extends Error {
  constructor() {
    super('The text is too long to fit in a QR code.')
    this.name = 'QrCodeTooLongError'
  }
}

function getBit(value: number, index: number): boolean {
  return ((value >>> index) & 1) !== 0
}

// Number of data + error-correction modules available in a version.
function numRawDataModules(version: number): number {
  let result = (16 * version + 128) * version + 64
  if (version >= 2) {
    const numAlign = Math.floor(version / 7) + 2
    result -= (25 * numAlign - 10) * numAlign - 55
    if (version >= 7) result -= 36
  }
  return result
}

function numDataCodewords(version: number): number {
  return (
    Math.floor(numRawDataModules(version) / 8) -
    ECC_CODEWORDS_PER_BLOCK[version] * NUM_ERROR_CORRECTION_BLOCKS[version]
  )
}

// --- Reed-Solomon over GF(256), polynomial 0x11D ------------------------------

function gfMultiply(x: number, y: number): number {
  let z = 0
  for (let i = 7; i >= 0; i--) {
    z = (z << 1) ^ ((z >>> 7) * 0x11d)
    z ^= ((y >>> i) & 1) * x
  }
  return z
}

function reedSolomonDivisor(degree: number): number[] {
  const result: number[] = new Array<number>(degree).fill(0)
  result[degree - 1] = 1
  let root = 1
  for (let i = 0; i < degree; i++) {
    for (let j = 0; j < degree; j++) {
      result[j] = gfMultiply(result[j], root)
      if (j + 1 < degree) result[j] ^= result[j + 1]
    }
    root = gfMultiply(root, 0x02)
  }
  return result
}

function reedSolomonRemainder(data: number[], divisor: number[]): number[] {
  const result: number[] = new Array<number>(divisor.length).fill(0)
  for (const byte of data) {
    const factor = byte ^ (result.shift() as number)
    result.push(0)
    divisor.forEach((coefficient, i) => {
      result[i] ^= gfMultiply(coefficient, factor)
    })
  }
  return result
}

// --- Data encoding ------------------------------------------------------------

function encodeData(bytes: Uint8Array, version: number): number[] {
  const bits: number[] = []
  const append = (value: number, length: number): void => {
    for (let i = length - 1; i >= 0; i--) bits.push((value >>> i) & 1)
  }

  append(0b0100, 4) // byte mode
  append(bytes.length, version <= 9 ? 8 : 16)
  bytes.forEach((byte) => append(byte, 8))

  const capacityBits = numDataCodewords(version) * 8
  append(0, Math.min(4, capacityBits - bits.length)) // terminator
  append(0, (8 - (bits.length % 8)) % 8) // byte alignment

  const codewords: number[] = []
  for (let i = 0; i < bits.length; i += 8) {
    codewords.push(parseInt(bits.slice(i, i + 8).join(''), 2))
  }
  for (let pad = 0xec; codewords.length < numDataCodewords(version); pad ^= 0xec ^ 0x11) {
    codewords.push(pad)
  }
  return codewords
}

// Splits the data into blocks, appends error correction, and interleaves.
function addEccAndInterleave(data: number[], version: number): number[] {
  const numBlocks = NUM_ERROR_CORRECTION_BLOCKS[version]
  const blockEccLength = ECC_CODEWORDS_PER_BLOCK[version]
  const rawCodewords = Math.floor(numRawDataModules(version) / 8)
  const numShortBlocks = numBlocks - (rawCodewords % numBlocks)
  const shortBlockLength = Math.floor(rawCodewords / numBlocks)

  const blocks: number[][] = []
  const divisor = reedSolomonDivisor(blockEccLength)
  for (let i = 0, offset = 0; i < numBlocks; i++) {
    const length = shortBlockLength - blockEccLength + (i < numShortBlocks ? 0 : 1)
    const blockData = data.slice(offset, offset + length)
    offset += length
    const ecc = reedSolomonRemainder(blockData, divisor)
    if (i < numShortBlocks) blockData.push(0) // placeholder, skipped below
    blocks.push(blockData.concat(ecc))
  }

  const result: number[] = []
  for (let i = 0; i < blocks[0].length; i++) {
    blocks.forEach((block, j) => {
      if (i !== shortBlockLength - blockEccLength || j >= numShortBlocks) result.push(block[i])
    })
  }
  return result
}

// --- Matrix construction ------------------------------------------------------

class Builder {
  readonly size: number
  readonly modules: boolean[][]
  private readonly version: number
  private readonly isFunction: boolean[][]

  constructor(version: number) {
    this.version = version
    this.size = version * 4 + 17
    this.modules = Array.from({ length: this.size }, () => new Array<boolean>(this.size).fill(false))
    this.isFunction = Array.from({ length: this.size }, () => new Array<boolean>(this.size).fill(false))
  }

  private setFunction(x: number, y: number, dark: boolean): void {
    this.modules[y][x] = dark
    this.isFunction[y][x] = true
  }

  drawFunctionPatterns(): void {
    for (let i = 0; i < this.size; i++) {
      this.setFunction(6, i, i % 2 === 0)
      this.setFunction(i, 6, i % 2 === 0)
    }
    this.drawFinder(3, 3)
    this.drawFinder(this.size - 4, 3)
    this.drawFinder(3, this.size - 4)

    const positions = this.alignmentPositions()
    const last = positions.length - 1
    positions.forEach((cy, i) => {
      positions.forEach((cx, j) => {
        const overlapsFinder = (i === 0 && j === 0) || (i === 0 && j === last) || (i === last && j === 0)
        if (!overlapsFinder) this.drawAlignment(cx, cy)
      })
    })

    this.drawFormatBits(0) // reserve the area; rewritten once the mask is chosen
    this.drawVersion()
  }

  private drawFinder(cx: number, cy: number): void {
    for (let dy = -4; dy <= 4; dy++) {
      for (let dx = -4; dx <= 4; dx++) {
        const distance = Math.max(Math.abs(dx), Math.abs(dy))
        const x = cx + dx
        const y = cy + dy
        if (x >= 0 && x < this.size && y >= 0 && y < this.size) {
          this.setFunction(x, y, distance !== 2 && distance !== 4)
        }
      }
    }
  }

  private drawAlignment(cx: number, cy: number): void {
    for (let dy = -2; dy <= 2; dy++) {
      for (let dx = -2; dx <= 2; dx++) {
        this.setFunction(cx + dx, cy + dy, Math.max(Math.abs(dx), Math.abs(dy)) !== 1)
      }
    }
  }

  private alignmentPositions(): number[] {
    if (this.version === 1) return []
    const count = Math.floor(this.version / 7) + 2
    const step = this.version === 32 ? 26 : Math.ceil((this.version * 4 + 4) / (count * 2 - 2)) * 2
    const result = [6]
    for (let position = this.size - 7; result.length < count; position -= step) result.splice(1, 0, position)
    return result
  }

  drawFormatBits(mask: number): void {
    const data = (FORMAT_ECC_BITS << 3) | mask
    let remainder = data
    for (let i = 0; i < 10; i++) remainder = (remainder << 1) ^ ((remainder >>> 9) * 0x537)
    const bits = ((data << 10) | remainder) ^ 0x5412

    for (let i = 0; i <= 5; i++) this.setFunction(8, i, getBit(bits, i))
    this.setFunction(8, 7, getBit(bits, 6))
    this.setFunction(8, 8, getBit(bits, 7))
    this.setFunction(7, 8, getBit(bits, 8))
    for (let i = 9; i < 15; i++) this.setFunction(14 - i, 8, getBit(bits, i))

    for (let i = 0; i < 8; i++) this.setFunction(this.size - 1 - i, 8, getBit(bits, i))
    for (let i = 8; i < 15; i++) this.setFunction(8, this.size - 15 + i, getBit(bits, i))
    this.setFunction(8, this.size - 8, true) // always-dark module
  }

  private drawVersion(): void {
    if (this.version < 7) return
    let remainder = this.version
    for (let i = 0; i < 12; i++) remainder = (remainder << 1) ^ ((remainder >>> 11) * 0x1f25)
    const bits = (this.version << 12) | remainder

    for (let i = 0; i < 18; i++) {
      const dark = getBit(bits, i)
      const a = this.size - 11 + (i % 3)
      const b = Math.floor(i / 3)
      this.setFunction(a, b, dark)
      this.setFunction(b, a, dark)
    }
  }

  drawCodewords(codewords: number[]): void {
    let index = 0
    for (let right = this.size - 1; right >= 1; right -= 2) {
      if (right === 6) right = 5
      for (let vertical = 0; vertical < this.size; vertical++) {
        for (let j = 0; j < 2; j++) {
          const x = right - j
          const upward = ((right + 1) & 2) === 0
          const y = upward ? this.size - 1 - vertical : vertical
          if (!this.isFunction[y][x] && index < codewords.length * 8) {
            this.modules[y][x] = getBit(codewords[index >>> 3], 7 - (index & 7))
            index++
          }
        }
      }
    }
  }

  // XORs the mask onto the data modules; applying it twice undoes it.
  applyMask(mask: number): void {
    for (let y = 0; y < this.size; y++) {
      for (let x = 0; x < this.size; x++) {
        let invert: boolean
        switch (mask) {
          case 0: invert = (x + y) % 2 === 0; break
          case 1: invert = y % 2 === 0; break
          case 2: invert = x % 3 === 0; break
          case 3: invert = (x + y) % 3 === 0; break
          case 4: invert = (Math.floor(x / 3) + Math.floor(y / 2)) % 2 === 0; break
          case 5: invert = ((x * y) % 2) + ((x * y) % 3) === 0; break
          case 6: invert = (((x * y) % 2) + ((x * y) % 3)) % 2 === 0; break
          default: invert = (((x + y) % 2) + ((x * y) % 3)) % 2 === 0; break
        }
        if (!this.isFunction[y][x] && invert) this.modules[y][x] = !this.modules[y][x]
      }
    }
  }

  // Penalty score per ISO 18004 section 7.8.3 (lower is better).
  penalty(): number {
    const n = this.size
    const m = this.modules
    let score = 0

    // Rule 1: runs of five or more same-coloured modules, rows and columns.
    for (let a = 0; a < n; a++) {
      for (const horizontal of [true, false]) {
        let run = 1
        for (let b = 1; b < n; b++) {
          const current = horizontal ? m[a][b] : m[b][a]
          const previous = horizontal ? m[a][b - 1] : m[b - 1][a]
          if (current === previous) {
            run++
            if (run === 5) score += 3
            else if (run > 5) score += 1
          } else {
            run = 1
          }
        }
      }
    }

    // Rule 2: 2x2 blocks of the same colour.
    for (let y = 0; y < n - 1; y++) {
      for (let x = 0; x < n - 1; x++) {
        if (m[y][x] === m[y][x + 1] && m[y][x] === m[y + 1][x] && m[y][x] === m[y + 1][x + 1]) score += 3
      }
    }

    // Rule 3: finder-like 1:1:3:1:1 patterns with a light margin on one side.
    const pattern = [true, false, true, true, true, false, true]
    const matches = (read: (i: number) => boolean, start: number): boolean =>
      pattern.every((value, i) => read(start + i) === value)
    const lightRun = (read: (i: number) => boolean, start: number): boolean => {
      for (let i = 0; i < 4; i++) if (read(start + i)) return false
      return true
    }
    for (let a = 0; a < n; a++) {
      for (const horizontal of [true, false]) {
        const read = (i: number): boolean => (horizontal ? m[a][i] : m[i][a])
        for (let start = 0; start + 7 <= n; start++) {
          if (!matches(read, start)) continue
          if (start >= 4 && lightRun(read, start - 4)) score += 40
          if (start + 7 + 4 <= n && lightRun(read, start + 7)) score += 40
        }
      }
    }

    // Rule 4: balance of dark and light modules.
    let dark = 0
    for (const row of m) for (const cell of row) if (cell) dark++
    const percent = (dark * 100) / (n * n)
    score += Math.floor(Math.abs(percent - 50) / 5) * 10

    return score
  }
}

/**
 * Encodes text (UTF-8) as a QR code and returns its module matrix, indexed
 * `[row][column]`, `true` for a dark module. The quiet zone is not included.
 *
 * @throws QrCodeTooLongError when the text does not fit in version 40.
 */
export function generateQrMatrix(text: string): boolean[][] {
  const bytes = new TextEncoder().encode(text)

  let version = MIN_VERSION
  for (; ; version++) {
    if (version > MAX_VERSION) throw new QrCodeTooLongError()
    const countBits = version <= 9 ? 8 : 16
    if (4 + countBits + bytes.length * 8 <= numDataCodewords(version) * 8) break
  }

  const codewords = addEccAndInterleave(encodeData(bytes, version), version)

  const builder = new Builder(version)
  builder.drawFunctionPatterns()
  builder.drawCodewords(codewords)

  let bestMask = 0
  let bestPenalty = Number.POSITIVE_INFINITY
  for (let mask = 0; mask < 8; mask++) {
    builder.applyMask(mask)
    builder.drawFormatBits(mask)
    const penalty = builder.penalty()
    if (penalty < bestPenalty) {
      bestPenalty = penalty
      bestMask = mask
    }
    builder.applyMask(mask) // undo
  }

  builder.applyMask(bestMask)
  builder.drawFormatBits(bestMask)
  return builder.modules
}

/**
 * SVG path data (one unit square per dark module) for a module matrix,
 * offset by the quiet zone. The matching viewBox side is
 * `matrix.length + 2 * margin`.
 */
export function qrToPath(matrix: boolean[][], margin = 4): string {
  const segments: string[] = []
  matrix.forEach((row, y) => {
    row.forEach((dark, x) => {
      if (dark) segments.push(`M${x + margin},${y + margin}h1v1h-1z`)
    })
  })
  return segments.join('')
}
