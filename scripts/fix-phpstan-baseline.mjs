#!/usr/bin/env node
// One-off, idempotent edit of phpstan-baseline.neon, made in place so it works
// on whatever the baseline currently contains (it is not a replacement file).
//
//   node scripts/fix-phpstan-baseline.mjs [path/to/phpstan-baseline.neon]
//
// 1. Removes `Application::$router` entries of providers that no longer use it
//    (the extension providers moved to the SDK v2 route registrar).
// 2. UserServiceProvider now reads `$this->app->router` twice: count 1 -> 2.
// 3. Records the redundant instanceof in ExtensionBlockRegistry until the
//    check is deleted (then remove this entry: PHPStan reports it unmatched).

import { readFileSync, writeFileSync } from 'node:fs'

const STALE_ROUTER_PATHS = new Set([
  'app/Extensions/CinemaMovie/Providers/CinemaMovieServiceProvider.php',
  'app/Extensions/Files/Providers/FilesServiceProvider.php',
  'app/Extensions/Gallery/Providers/GalleryServiceProvider.php',
  'app/Extensions/Translations/Providers/TranslationsServiceProvider.php',
  'app/Extensions/Tuleap/Providers/TuleapServiceProvider.php',
])
const USERS_PROVIDER = 'app/Core/Users/Providers/UserServiceProvider.php'

const BLOCK_REGISTRY_PATH = 'app/Core/Extensions/Capabilities/Registry/ExtensionBlockRegistry.php'
const BLOCK_REGISTRY_ENTRY = [
  '\t\t-',
  "\t\t\tmessage: '#^Instanceof between App\\\\Core\\\\Extensions\\\\Contracts\\\\ExtensionInterface and App\\\\Core\\\\Extensions\\\\Contracts\\\\ExtensionInterface will always evaluate to true\\.$#'",
  '\t\t\tidentifier: instanceof.alwaysTrue',
  '\t\t\tcount: 1',
  `\t\t\tpath: ${BLOCK_REGISTRY_PATH}`,
  '',
]

const strip = (line) => line.replace(/\r$/, '')

/** Splits the file into header lines and entries (each entry: its lines, blank separator included). */
export function parseBaseline(text) {
  const lines = text.split('\n')
  const starts = lines.flatMap((line, index) => (strip(line) === '\t\t-' ? [index] : []))
  const header = lines.slice(0, starts[0] ?? lines.length)
  const entries = starts.map((start, i) => lines.slice(start, starts[i + 1] ?? lines.length))

  return { header, entries }
}

const field = (entry, name) => {
  const line = entry.find((candidate) => strip(candidate).startsWith(`\t\t\t${name}: `))
  return line === undefined ? undefined : strip(line).slice(`\t\t\t${name}: `.length)
}

const isRouterEntry = (entry) => (field(entry, 'message') ?? '').includes('$router')

export function fixBaseline(text) {
  const { header, entries } = parseBaseline(text)
  const changes = []
  const kept = []

  for (const entry of entries) {
    const path = field(entry, 'path')

    if (isRouterEntry(entry) && STALE_ROUTER_PATHS.has(path)) {
      changes.push(`removed stale router entry: ${path}`)
      continue
    }

    if (isRouterEntry(entry) && path === USERS_PROVIDER && field(entry, 'count') === '1') {
      kept.push(entry.map((line) => (strip(line) === '\t\t\tcount: 1' ? line.replace('count: 1', 'count: 2') : line)))
      changes.push(`${USERS_PROVIDER}: count 1 -> 2`)
      continue
    }

    kept.push(entry)
  }

  const alreadyRecorded = kept.some(
    (entry) => field(entry, 'path') === BLOCK_REGISTRY_PATH && field(entry, 'identifier') === 'instanceof.alwaysTrue',
  )
  if (!alreadyRecorded) {
    const at = kept.findIndex((entry) => (field(entry, 'path') ?? '') > BLOCK_REGISTRY_PATH)
    kept.splice(at === -1 ? kept.length : at, 0, BLOCK_REGISTRY_ENTRY)
    changes.push(`added instanceof.alwaysTrue entry: ${BLOCK_REGISTRY_PATH}`)
  }

  return { text: [...header, ...kept.flat()].join('\n'), changes }
}

function main(argv) {
  const file = argv[0] ?? 'phpstan-baseline.neon'
  const { text, changes } = fixBaseline(readFileSync(file, 'utf8'))

  if (changes.length === 0) {
    console.log(`${file}: already up to date.`)
    return 0
  }

  writeFileSync(file, text)
  console.log(`${file}: ${changes.length} change(s)`)
  changes.forEach((change) => console.log(`  - ${change}`))
  return 0
}

import { fileURLToPath } from 'node:url'
if (process.argv[1] === fileURLToPath(import.meta.url)) process.exit(main(process.argv.slice(2)))
