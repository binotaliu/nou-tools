#!/usr/bin/env node
// Accessibility scan runner for public pages, driven by axe-core + Playwright.
//
// Usage:
//   node scripts/a11y-scan/scan.mjs
//   node scripts/a11y-scan/scan.mjs --schedule=yXvIIh6VQS-Tl4UKKscGyw
//   node scripts/a11y-scan/scan.mjs --schedule="https://nou-tools.test/schedules/yXvI..." --only=home,study-room-authed
//   node scripts/a11y-scan/scan.mjs --base-url=https://nou-tools.test --tags=wcag2a,wcag2aa,wcag21aa
//   node scripts/a11y-scan/scan.mjs --no-html   # skip HTML report generation, JSON only
//
// Writes results/index.html (an aggregated overview linking each page's own
// axe-html-reporter report under results/html/, and its Playwright
// ariaSnapshot() tree under results/a11y-tree/) unless --no-html is passed.
// Each page's raw ariaSnapshot() is also written to results/{name}.snapshot.txt.
//
// Pages are declared in scripts/a11y-scan/pages.json:
//   - plain entries just need a `path`.
//   - `needsSchedule: true` entries have `{schedule}` in their path substituted
//     with --schedule/A11Y_SCHEDULE_TOKEN, or (with `auth: true`) require a
//     `student_schedule` cookie, which this script obtains by driving the real
//     POST /schedules/my "remember this schedule" flow — the cookie Laravel
//     issues is encrypted, so it cannot be forged locally.
//
// Requires --schedule (or A11Y_SCHEDULE_TOKEN) to reach any `needsSchedule`
// page; without it those entries are skipped with a warning. Get a token by
// grabbing any schedule's share link/token from a seeded dev record, e.g.:
//   php artisan tinker --execute 'echo App\Models\StudentSchedule::first()?->getRouteKey();'

import { chromium } from 'playwright'
import { createHtmlReport } from 'axe-html-reporter'
import fs from 'node:fs'
import path from 'node:path'
import { createRequire } from 'node:module'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const require = createRequire(import.meta.url)
const axeSourcePath = require.resolve('axe-core/axe.min.js')

function parseArgs(argv) {
  const args = {
    baseUrl: process.env.A11Y_BASE_URL || 'https://nou-tools.test',
    schedule: process.env.A11Y_SCHEDULE_TOKEN || null,
    out: path.join(__dirname, 'results'),
    only: null,
    tags: ['wcag2a', 'wcag2aa', 'wcag21aa'],
    html: true,
  }

  for (const arg of argv) {
    const [key, ...rest] = arg.replace(/^--/, '').split('=')
    const value = rest.join('=')
    if (key === 'base-url') args.baseUrl = value
    else if (key === 'schedule') args.schedule = value
    else if (key === 'out') args.out = path.resolve(value)
    else if (key === 'only') args.only = value.split(',').map(s => s.trim())
    else if (key === 'tags') args.tags = value.split(',').map(s => s.trim())
    else if (key === 'no-html') args.html = false
  }

  return args
}

async function establishScheduleCookie(context, baseUrl, token) {
  await context.request.get(`${baseUrl}/schedules/my`)

  const csrfCookie = (await context.cookies()).find(
    c => c.name === 'XSRF-TOKEN'
  )
  if (!csrfCookie) {
    throw new Error('Could not obtain a CSRF cookie from GET /schedules/my')
  }

  await context.request.post(`${baseUrl}/schedules/my`, {
    headers: { 'X-XSRF-TOKEN': decodeURIComponent(csrfCookie.value) },
    form: { url: token },
  })

  const hasScheduleCookie = (await context.cookies()).some(
    c => c.name === 'student_schedule'
  )
  if (!hasScheduleCookie) {
    throw new Error(
      `Remembering schedule "${token}" via POST /schedules/my did not set a student_schedule cookie — is the token/share link valid?`
    )
  }
}

function loadPages(only) {
  const all = JSON.parse(
    fs.readFileSync(path.join(__dirname, 'pages.json'), 'utf8')
  )
  return only ? all.filter(p => only.includes(p.name)) : all
}

async function scanPage(context, axeSource, tags, baseUrl, page) {
  const tab = await context.newPage()
  try {
    // 'load' rather than 'networkidle': pages with a live map (Leaflet tile
    // requests) or other persistent polling never go network-idle, so
    // networkidle would time out on those pages. A short settle delay after
    // 'load' covers Vue/Inertia hydration instead.
    await tab.goto(new URL(page.url, baseUrl).toString(), {
      waitUntil: 'load',
      timeout: 30000,
    })
    await tab.waitForTimeout(500)
    await tab.evaluate(axeSource)
    const results = await tab.evaluate(
      async tags =>
        window.axe.run(document, { runOnly: { type: 'tag', values: tags } }),
      tags
    )
    const snapshot = await tab.locator('body').ariaSnapshot()
    return { ok: true, results, snapshot }
  } catch (error) {
    return { ok: false, error: error.message }
  } finally {
    await tab.close()
  }
}

function summarizeContrastPairs(perPageResults) {
  const pairs = new Map()

  for (const [pageName, { results }] of perPageResults) {
    if (!results) {
      continue
    }
    for (const violation of results.violations) {
      if (violation.id !== 'color-contrast') {
        continue
      }
      for (const node of violation.nodes) {
        const data = node.any[0]?.data
        if (!data) {
          continue
        }
        const key = `${data.fgColor} on ${data.bgColor} @ ${data.fontSize} ${data.fontWeight} (need ${data.expectedContrastRatio}, got ${data.contrastRatio})`
        if (!pairs.has(key)) {
          pairs.set(key, {
            count: 0,
            pages: new Set(),
            sample: node.target.join(' '),
          })
        }
        const entry = pairs.get(key)
        entry.count += 1
        entry.pages.add(pageName)
      }
    }
  }

  return [...pairs.entries()].sort((a, b) => b[1].count - a[1].count)
}

const IMPACT_COLOR = {
  critical: '#b42318',
  serious: '#b54708',
  moderate: '#854a0e',
  minor: '#475467',
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

function renderSnapshotHtml({ pageName, url, snapshot }) {
  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>${escapeHtml(pageName)} — accessibility tree</title>
<style>
  :root { color-scheme: light dark; }
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 2rem auto; max-width: 60rem; padding: 0 1rem; }
  .meta { color: #667085; margin-bottom: 1.5rem; }
  pre { border: 1px solid #d0d5dd; border-radius: 8px; padding: 1rem; overflow-x: auto; white-space: pre; font-size: 0.85rem; line-height: 1.5; }
  @media (prefers-color-scheme: dark) {
    body { background: #101828; color: #eaecf0; }
    pre { border-color: #344054; background: #1d2939; }
    .meta { color: #98a2b3; }
  }
</style>
</head>
<body>
  <h1>${escapeHtml(pageName)}</h1>
  <p class="meta"><code>${escapeHtml(url)}</code> — Playwright <code>ariaSnapshot()</code> of &lt;body&gt;</p>
  <pre>${escapeHtml(snapshot)}</pre>
</body>
</html>
`
}

function renderIndexHtml({ summary, contrastPairs, baseUrl, generatedAt }) {
  const totalViolationInstances = summary
    .filter(s => s.ok)
    .flatMap(s => s.violations)
    .reduce((sum, v) => sum + v.count, 0)
  const failingPages = summary.filter(s => s.ok && s.violations.length > 0)
  const erroredPages = summary.filter(s => !s.ok)

  const pageRows = summary
    .map(s => {
      if (!s.ok) {
        return `<tr class="error-row">
          <td>${escapeHtml(s.page)}</td>
          <td><code>${escapeHtml(s.url)}</code></td>
          <td colspan="3" class="error-cell">${escapeHtml(s.error)}</td>
        </tr>`
      }
      const impactBadges = s.violations
        .map(
          v =>
            `<span class="badge" style="background:${IMPACT_COLOR[v.impact] ?? '#475467'}">${escapeHtml(v.id)} ×${v.count}</span>`
        )
        .join(' ')
      return `<tr>
        <td><a href="html/${encodeURIComponent(s.page)}.html">${escapeHtml(s.page)}</a></td>
        <td><code>${escapeHtml(s.url)}</code></td>
        <td>${s.violations.length}</td>
        <td>${impactBadges || '<span class="ok">—</span>'}</td>
        <td><a href="a11y-tree/${encodeURIComponent(s.page)}.html">tree</a></td>
      </tr>`
    })
    .join('\n')

  const contrastRows = contrastPairs
    .map(
      ([key, v]) => `<tr>
        <td><code>${escapeHtml(key)}</code></td>
        <td>${v.count}</td>
        <td>${escapeHtml([...v.pages].join(', '))}</td>
        <td><code class="sample">${escapeHtml(v.sample)}</code></td>
      </tr>`
    )
    .join('\n')

  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Accessibility scan report</title>
<style>
  :root { color-scheme: light dark; }
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 2rem auto; max-width: 72rem; padding: 0 1rem; line-height: 1.5; }
  h1 { margin-bottom: 0.25rem; }
  .meta { color: #667085; margin-bottom: 2rem; }
  .stats { display: flex; gap: 1.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
  .stat { border: 1px solid #d0d5dd; border-radius: 8px; padding: 0.75rem 1.25rem; min-width: 10rem; }
  .stat strong { display: block; font-size: 1.5rem; }
  table { border-collapse: collapse; width: 100%; margin-bottom: 2.5rem; }
  th, td { border: 1px solid #d0d5dd; padding: 0.5rem 0.75rem; text-align: left; vertical-align: top; font-size: 0.9rem; }
  th { background: #f9fafb; }
  code { font-size: 0.85em; word-break: break-word; }
  code.sample { display: block; max-width: 32rem; }
  .badge { display: inline-block; color: #fff; border-radius: 999px; padding: 0.1rem 0.6rem; font-size: 0.75rem; margin: 0.1rem; }
  .ok { color: #067647; }
  .error-row td { background: #fef3f2; }
  .error-cell { color: #b42318; }
  @media (prefers-color-scheme: dark) {
    body { background: #101828; color: #eaecf0; }
    .stat { border-color: #344054; }
    th, td { border-color: #344054; }
    th { background: #1d2939; }
    .meta { color: #98a2b3; }
    .error-row td { background: #3b1d1a; }
  }
</style>
</head>
<body>
  <h1>Accessibility scan report</h1>
  <p class="meta">${escapeHtml(baseUrl)} — generated ${escapeHtml(generatedAt)} — axe-core WCAG 2.0/2.1 A/AA rules</p>

  <div class="stats">
    <div class="stat"><strong>${summary.length}</strong>pages scanned</div>
    <div class="stat"><strong>${failingPages.length}</strong>pages with violations</div>
    <div class="stat"><strong>${totalViolationInstances}</strong>violation instances</div>
    <div class="stat"><strong>${erroredPages.length}</strong>pages failed to scan</div>
  </div>

  <h2>Pages</h2>
  <table>
    <thead><tr><th>Page</th><th>URL</th><th>Violation types</th><th>Details</th><th>A11y tree</th></tr></thead>
    <tbody>
${pageRows}
    </tbody>
  </table>

  ${
    contrastPairs.length > 0
      ? `<h2>Color-contrast pairs (deduped across pages)</h2>
  <table>
    <thead><tr><th>Color pair</th><th>Count</th><th>Pages</th><th>Example selector</th></tr></thead>
    <tbody>
${contrastRows}
    </tbody>
  </table>`
      : ''
  }
</body>
</html>
`
}

async function main() {
  const args = parseArgs(process.argv.slice(2))
  const pages = loadPages(args.only).map(p => ({
    ...p,
    url: p.needsSchedule
      ? p.path.replace('{schedule}', args.schedule ?? '')
      : p.path,
  }))

  const skipped = []
  const runnable = pages.filter(p => {
    if (p.needsSchedule && !args.schedule) {
      skipped.push(p.name)
      return false
    }
    return true
  })

  if (skipped.length > 0) {
    console.log(
      `Skipping ${skipped.length} page(s) that need --schedule/A11Y_SCHEDULE_TOKEN: ${skipped.join(', ')}`
    )
  }

  fs.mkdirSync(args.out, { recursive: true })

  const browser = await chromium.launch({ headless: true })
  const context = await browser.newContext({ ignoreHTTPSErrors: true })

  const needsAuth = runnable.some(p => p.auth)
  if (needsAuth) {
    console.log(
      `Establishing student_schedule cookie via POST ${args.baseUrl}/schedules/my ...`
    )
    await establishScheduleCookie(context, args.baseUrl, args.schedule)
  }

  const axeSource = fs.readFileSync(axeSourcePath, 'utf8')
  const perPageResults = []

  for (const p of runnable) {
    const outcome = await scanPage(
      context,
      axeSource,
      args.tags,
      args.baseUrl,
      p
    )
    perPageResults.push([p.name, outcome])

    if (!outcome.ok) {
      console.log(`✘ ${p.name} (${p.url}): ${outcome.error}`)
      continue
    }

    fs.writeFileSync(
      path.join(args.out, `${p.name}.json`),
      JSON.stringify(outcome.results, null, 2)
    )
    fs.writeFileSync(
      path.join(args.out, `${p.name}.snapshot.txt`),
      outcome.snapshot
    )

    if (args.html) {
      createHtmlReport({
        results: outcome.results,
        options: {
          projectKey: p.name,
          outputDirPath: args.out,
          outputDir: 'html',
          reportFileName: `${p.name}.html`,
          doNotCreateReportFile: false,
        },
      })

      const snapshotDir = path.join(args.out, 'a11y-tree')
      fs.mkdirSync(snapshotDir, { recursive: true })
      fs.writeFileSync(
        path.join(snapshotDir, `${p.name}.html`),
        renderSnapshotHtml({
          pageName: p.name,
          url: p.url,
          snapshot: outcome.snapshot,
        })
      )
    }

    console.log(
      `✔ ${p.name} (${p.url}) — ${outcome.results.violations.length} violation type(s)`
    )
  }

  await browser.close()

  const summary = perPageResults.map(([name, outcome]) => ({
    page: name,
    url: runnable.find(p => p.name === name).url,
    ok: outcome.ok,
    error: outcome.ok ? undefined : outcome.error,
    violations: outcome.ok
      ? outcome.results.violations.map(v => ({
          id: v.id,
          impact: v.impact,
          help: v.help,
          count: v.nodes.length,
        }))
      : undefined,
  }))
  fs.writeFileSync(
    path.join(args.out, 'summary.json'),
    JSON.stringify(summary, null, 2)
  )

  console.log('\n=== SUMMARY ===')
  for (const s of summary) {
    if (!s.ok) {
      console.log(`\n${s.page}: ERROR — ${s.error}`)
      continue
    }
    console.log(
      `\n${s.page} (${s.url}): ${s.violations.length} violation type(s)`
    )
    for (const v of s.violations) {
      console.log(`  [${v.impact}] ${v.id} (${v.count}x) — ${v.help}`)
    }
  }

  const contrastPairs = summarizeContrastPairs(
    perPageResults.filter(([, o]) => o.ok)
  )
  if (contrastPairs.length > 0) {
    console.log('\n=== COLOR-CONTRAST PAIRS (deduped across pages) ===')
    for (const [key, v] of contrastPairs) {
      console.log(`${v.count}x  ${key}`)
      console.log(`     pages: ${[...v.pages].join(', ')}`)
      console.log(`     e.g. ${v.sample}`)
    }
  }

  if (args.html) {
    const indexHtml = renderIndexHtml({
      summary,
      contrastPairs,
      baseUrl: args.baseUrl,
      generatedAt: new Date().toISOString(),
    })
    fs.writeFileSync(path.join(args.out, 'index.html'), indexHtml)
    console.log(`\nHTML report: ${path.join(args.out, 'index.html')}`)
  }

  console.log(`\nFull per-page results written to ${args.out}`)
}

main().catch(error => {
  console.error(error)
  process.exitCode = 1
})
