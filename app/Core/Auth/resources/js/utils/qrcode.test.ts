// Tests for the in-house QR code generator. The golden matrix was decoded
// back to its text with an independent QR reader when it was recorded, so it
// guards against regressions in placement, masking and error correction.
import { describe, it, expect } from 'vitest'
import { generateQrMatrix, qrToPath, QrCodeTooLongError } from './qrcode'

// 'HELLO WORLD' (version 1, level M). '#' is a dark module.
const GOLDEN_HELLO_WORLD = [
  '#######.##..#.#######',
  '#.....#....#..#.....#',
  '#.###.#..#.#..#.###.#',
  '#.###.#.#..#..#.###.#',
  '#.###.#.###.#.#.###.#',
  '#.....#.#..#..#.....#',
  '#######.#.#.#.#######',
  '........#..##........',
  '#...#.######.#####..#',
  '...#....#.###....####',
  '..######..##.##.#..#.',
  '#####...##...#.......',
  '#####.#.#.#.#.##..##.',
  '........#.#.####.#.##',
  '#######.###.#.#.##.#.',
  '#.....#..#.###.##..##',
  '#.###.#.##.#.##...##.',
  '#.###.#..#..#...##.##',
  '#.###.#..###...###...',
  '#.....#....#.#.......',
  '#######.#########.#.#',
]

function toRows(matrix: boolean[][]): string[] {
  return matrix.map((row) => row.map((dark) => (dark ? '#' : '.')).join(''))
}

describe('generateQrMatrix', () => {
  it('matches the recorded matrix', () => {
    expect(toRows(generateQrMatrix('HELLO WORLD'))).toEqual(GOLDEN_HELLO_WORLD)
  })

  it('is deterministic', () => {
    expect(generateQrMatrix('same input')).toEqual(generateQrMatrix('same input'))
  })

  it('picks the smallest version that fits (level M, byte mode)', () => {
    expect(generateQrMatrix('a')).toHaveLength(21)
    expect(generateQrMatrix('x'.repeat(14))).toHaveLength(21)
    expect(generateQrMatrix('x'.repeat(15))).toHaveLength(25)
    expect(generateQrMatrix('x'.repeat(2331))).toHaveLength(177)
  })

  it('rejects text longer than version 40 can hold', () => {
    expect(() => generateQrMatrix('x'.repeat(2332))).toThrow(QrCodeTooLongError)
  })

  it('draws the three finder patterns, the timing pattern and the dark module', () => {
    const matrix = generateQrMatrix('otpauth://totp/Pixely:jane%40example.com?secret=GEZDGNBVGY3TQOJQ')
    const size = matrix.length

    const finderAt = (top: number, left: number): string[] =>
      matrix.slice(top, top + 7).map((row) => row.slice(left, left + 7).map((dark) => (dark ? '#' : '.')).join(''))
    const finder = ['#######', '#.....#', '#.###.#', '#.###.#', '#.###.#', '#.....#', '#######']

    expect(finderAt(0, 0)).toEqual(finder)
    expect(finderAt(0, size - 7)).toEqual(finder)
    expect(finderAt(size - 7, 0)).toEqual(finder)

    for (let i = 8; i < size - 8; i++) {
      expect(matrix[6][i]).toBe(i % 2 === 0)
      expect(matrix[i][6]).toBe(i % 2 === 0)
    }
    expect(matrix[size - 8][8]).toBe(true)
  })

  it('encodes non-ASCII text as UTF-8 bytes', () => {
    // 'é' is two bytes, so 8 of them need 16 bytes: version 2.
    expect(generateQrMatrix('é'.repeat(8))).toHaveLength(25)
  })
})

describe('qrToPath', () => {
  it('emits one unit square per dark module, offset by the quiet zone', () => {
    const path = qrToPath([[true, false], [false, true]], 4)

    expect(path).toBe('M4,4h1v1h-1zM5,5h1v1h-1z')
  })
})
