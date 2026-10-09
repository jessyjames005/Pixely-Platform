#!/usr/bin/env node
// Merges PHPUnit/Pest Clover reports and enforces a minimum statement coverage.
//
// The backend test jobs run in separate CI jobs (unit on SQLite, functional on
// MySQL), so no single report describes the whole suite. A line counts as
// covered when ANY report covers it. Dependency-free on purpose: it needs
// neither phpcov nor a lockfile change.
//
// Usage: node scripts/coverage-gate.mjs <directory-with-clover-xml> [--min=80]
//
// Exit codes: 0 gate passed (or no minimum), 1 below the minimum, 2 bad input.
// When GITHUB_STEP_SUMMARY is set, a Markdown report is appended to it.

import { appendFileSync, existsSync, readdirSync, readFileSync, statSync } from 'node:fs'
import { join } from 'node:path'
import { fileURLToPath } from 'node:url'

const ATTRIBUTE = /([A-Za-z_:][\w:.-]*)="([^"]*)"/g

function attributes(source) {
  const result = {}
  for (const [, name, value] of source.matchAll(ATTRIBUTE)) result[name] = value
  return result
}

/**
 * Statement lines of one Clover document, as Map<file, Map<line, covered>>.
 * Only `type="stmt"` lines count; method declaration lines are ignored.
 */
export function parseClover(xml) {
  const files = new Map()

  for (const match of xml.matchAll(/<file\b([^>]*)>([\s\S]*?)<\/file>/g)) {
    const { name } = attributes(match[1])
    if (!name) continue

    const lines = files.get(name) ?? new Map()
    for (const line of match[2].matchAll(/<line\b([^>]*?)\/?>/g)) {
      const attrs = attributes(line[1])
      if (attrs.type !== 'stmt') continue
      const number = Number(attrs.num)
      lines.set(number, (lines.get(number) ?? false) || Number(attrs.count) > 0)
    }
    files.set(name, lines)
  }

  return files
}

/** Union of several parsed reports: a line is covered if any report covers it. */
export function mergeCoverage(reports) {
  const merged = new Map()

  for (const report of reports) {
    for (const [file, lines] of report) {
      const target = merged.get(file) ?? new Map()
      for (const [number, covered] of lines) target.set(number, (target.get(number) ?? false) || covered)
      merged.set(file, target)
    }
  }

  return merged
}

// "app/Core/Auth/..." -> "app/Core/Auth"; "app/Models/User.php" -> "app/Models".
export function areaOf(file) {
  const normalised = file.replaceAll('\\', '/')
  const index = normalised.lastIndexOf('/app/')
  const relative = index === -1 ? normalised : normalised.slice(index + 1)
  const parts = relative.split('/')
  const depth = ['Core', 'Extensions'].includes(parts[1]) ? 3 : 2

  return parts.slice(0, depth).join('/')
}

export function summarise(merged) {
  const areas = new Map()

  for (const [file, lines] of merged) {
    const name = areaOf(file)
    const area = areas.get(name) ?? { statements: 0, covered: 0 }
    for (const isCovered of lines.values()) {
      area.statements++
      if (isCovered) area.covered++
    }
    areas.set(name, area)
  }

  const percent = (value, total) => (total === 0 ? 100 : (value / total) * 100)
  let statements = 0
  let covered = 0
  for (const area of areas.values()) {
    statements += area.statements
    covered += area.covered
  }

  return {
    statements,
    covered,
    percent: percent(covered, statements),
    areas: [...areas.entries()]
      .map(([name, { statements: total, covered: hit }]) => ({
        name,
        statements: total,
        covered: hit,
        percent: percent(hit, total),
      }))
      .sort((a, b) => a.percent - b.percent || b.statements - a.statements),
  }
}

export function formatMarkdown(summary, minimum) {
  const status = minimum > 0 ? (summary.percent >= minimum ? 'passed' : 'FAILED') : 'report only'
  const rows = summary.areas.map(
    (area) => `| \`${area.name}\` | ${area.covered}/${area.statements} | ${area.percent.toFixed(1)}% |`,
  )

  return [
    '## PHP coverage',
    '',
    `**${summary.percent.toFixed(2)}%** of ${summary.statements} statements ` +
      `(minimum ${minimum}% — ${status}).`,
    '',
    '| Area | Covered | Coverage |',
    '| --- | ---: | ---: |',
    ...rows,
    '',
  ].join('\n')
}

function cloverFiles(directory) {
  if (!existsSync(directory) || !statSync(directory).isDirectory()) return []

  return readdirSync(directory)
    .filter((name) => name.endsWith('.xml'))
    .map((name) => join(directory, name))
}

function main(argv) {
  const positional = argv.filter((argument) => !argument.startsWith('--'))
  const minArgument = argv.find((argument) => argument.startsWith('--min='))
  const minimum = minArgument === undefined ? 80 : Number(minArgument.slice('--min='.length))
  const directory = positional[0]

  if (directory === undefined || Number.isNaN(minimum) || minimum < 0 || minimum > 100) {
    console.error('Usage: coverage-gate.mjs <directory-with-clover-xml> [--min=0..100]')
    return 2
  }

  const files = cloverFiles(directory)
  if (files.length === 0) {
    console.error(`No Clover XML report found in "${directory}".`)
    return 2
  }

  const summary = summarise(mergeCoverage(files.map((file) => parseClover(readFileSync(file, 'utf8')))))
  if (summary.statements === 0) {
    console.error('The reports contain no statements: is the coverage driver (pcov) enabled?')
    return 2
  }

  const markdown = formatMarkdown(summary, minimum)
  console.log(markdown)

  if (process.env.GITHUB_STEP_SUMMARY) appendFileSync(process.env.GITHUB_STEP_SUMMARY, `${markdown}\n`)

  if (minimum > 0 && summary.percent < minimum) {
    console.error(`Coverage ${summary.percent.toFixed(2)}% is below the required ${minimum}%.`)
    return 1
  }

  return 0
}

if (process.argv[1] === fileURLToPath(import.meta.url)) process.exit(main(process.argv.slice(2)))
