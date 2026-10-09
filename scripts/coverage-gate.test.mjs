// Run with: node --test scripts/*.test.mjs
import assert from 'node:assert/strict'
import { spawnSync } from 'node:child_process'
import { mkdtempSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { test } from 'node:test'
import { fileURLToPath } from 'node:url'
import { areaOf, mergeCoverage, parseClover, summarise } from './coverage-gate.mjs'

const SCRIPT = fileURLToPath(new URL('./coverage-gate.mjs', import.meta.url))

// Shape produced by PHPUnit/Pest --coverage-clover.
function clover(files) {
  const body = Object.entries(files)
    .map(([name, lines]) => {
      const rows = lines
        .map(([num, count, type = 'stmt']) =>
          type === 'method'
            ? `<line num="${num}" type="method" name="m${num}" visibility="public" complexity="1" crap="1" count="${count}"/>`
            : `<line num="${num}" type="stmt" count="${count}"/>`,
        )
        .join('')
      return `<file name="${name}"><class name="X" namespace="App"><metrics methods="1"/></class>${rows}<metrics statements="${lines.length}"/></file>`
    })
    .join('')
  return `<?xml version="1.0" encoding="UTF-8"?><coverage generated="1"><project timestamp="1">${body}</project></coverage>`
}

function run(files, extra = []) {
  const directory = mkdtempSync(join(tmpdir(), 'cov-'))
  Object.entries(files).forEach(([name, xml]) => writeFileSync(join(directory, name), xml))
  return spawnSync('node', [SCRIPT, directory, ...extra], { encoding: 'utf8' })
}

test('parseClover keeps statement lines only', () => {
  const report = parseClover(
    clover({ '/w/app/Core/Auth/A.php': [[10, 0, 'method'], [11, 3], [12, 0]] }),
  )

  assert.deepEqual([...report.get('/w/app/Core/Auth/A.php')], [[11, true], [12, false]])
})

test('mergeCoverage counts a line as covered when any report covers it', () => {
  const unit = parseClover(clover({ '/w/app/Models/User.php': [[1, 1], [2, 0], [3, 0]] }))
  const functional = parseClover(clover({ '/w/app/Models/User.php': [[1, 0], [2, 5], [3, 0]] }))

  const merged = mergeCoverage([unit, functional])

  assert.deepEqual([...merged.get('/w/app/Models/User.php')], [[1, true], [2, true], [3, false]])
})

test('areaOf groups Core and Extensions by module, other code by top folder', () => {
  assert.equal(areaOf('/home/runner/work/p/p/app/Core/Auth/Services/X.php'), 'app/Core/Auth')
  assert.equal(areaOf('/home/runner/work/p/p/app/Extensions/Gallery/Models/Photo.php'), 'app/Extensions/Gallery')
  assert.equal(areaOf('/home/runner/work/p/p/app/Models/User.php'), 'app/Models')
  assert.equal(areaOf('C:\\repo\\app\\Core\\Users\\X.php'), 'app/Core/Users')
})

test('summarise totals and orders areas worst first', () => {
  const merged = mergeCoverage([
    parseClover(
      clover({
        '/w/app/Core/Auth/A.php': [[1, 1], [2, 1]],
        '/w/app/Models/User.php': [[1, 1], [2, 0], [3, 0], [4, 0]],
      }),
    ),
  ])

  const summary = summarise(merged)

  assert.equal(summary.statements, 6)
  assert.equal(summary.covered, 3)
  assert.equal(summary.percent, 50)
  assert.deepEqual(summary.areas.map((area) => area.name), ['app/Models', 'app/Core/Auth'])
})

test('exits 0 when merged coverage reaches the minimum', () => {
  const unit = clover({ '/w/app/A.php': [[1, 1], [2, 0]] })
  const functional = clover({ '/w/app/A.php': [[1, 0], [2, 1]] })

  const result = run({ 'unit.xml': unit, 'functional.xml': functional }, ['--min=100'])

  assert.equal(result.status, 0, result.stderr)
  assert.match(result.stdout, /100\.00%/)
})

test('exits 1 below the minimum and names the shortfall', () => {
  const result = run({ 'unit.xml': clover({ '/w/app/A.php': [[1, 1], [2, 0], [3, 0], [4, 0]] }) }, ['--min=80'])

  assert.equal(result.status, 1)
  assert.match(result.stderr, /25\.00% is below the required 80%/)
})

test('--min=0 reports without failing', () => {
  const result = run({ 'unit.xml': clover({ '/w/app/A.php': [[1, 0]] }) }, ['--min=0'])

  assert.equal(result.status, 0)
  assert.match(result.stdout, /report only/)
})

test('defaults to an 80% minimum', () => {
  const result = run({ 'unit.xml': clover({ '/w/app/A.php': [[1, 1], [2, 0]] }) })

  assert.equal(result.status, 1)
})

test('exits 2 when there is no report or no statement', () => {
  assert.equal(run({}).status, 2)
  assert.equal(run({ 'empty.xml': clover({}) }).status, 2)
})

test('exits 2 on invalid arguments', () => {
  assert.equal(spawnSync('node', [SCRIPT], { encoding: 'utf8' }).status, 2)
  assert.equal(run({ 'a.xml': clover({ '/w/app/A.php': [[1, 1]] }) }, ['--min=abc']).status, 2)
  assert.equal(run({ 'a.xml': clover({ '/w/app/A.php': [[1, 1]] }) }, ['--min=150']).status, 2)
})

test('appends a Markdown report to GITHUB_STEP_SUMMARY', () => {
  const directory = mkdtempSync(join(tmpdir(), 'cov-'))
  writeFileSync(join(directory, 'unit.xml'), clover({ '/w/app/Core/Auth/A.php': [[1, 1]] }))
  const summary = join(directory, 'summary.md')

  const result = spawnSync('node', [SCRIPT, directory, '--min=50'], {
    encoding: 'utf8',
    env: { ...process.env, GITHUB_STEP_SUMMARY: summary },
  })

  assert.equal(result.status, 0, result.stderr)
  assert.match(spawnSync('cat', [summary], { encoding: 'utf8' }).stdout, /\| `app\/Core\/Auth` \| 1\/1 \| 100\.0% \|/)
})
