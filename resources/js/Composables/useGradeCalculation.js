// Pure grade maths for the 成績計算機. Scores and weights are on a 0-100
// scale; an item whose weight is 0 (the summer midterm) is not required.

export const PASSING_SCORE = 60

const ITEMS = ['regular', 'midterm', 'final']

export function parseScore(value) {
  if (value === null || value === undefined || value === '') {
    return null
  }

  const number = Number(value)

  return Number.isFinite(number) ? number : null
}

export function weightTotal(weights) {
  return ITEMS.reduce((sum, item) => sum + (parseScore(weights[item]) ?? 0), 0)
}

export function weightsAreValid(weights) {
  return Math.abs(weightTotal(weights) - 100) < 0.001
}

function round(value) {
  return Math.round(value * 10) / 10
}

function weightedSum(scores, weights, items) {
  return items.reduce(
    (sum, item) => sum + (parseScore(scores[item]) ?? 0) * weights[item],
    0
  )
}

function requiredItems(weights) {
  return ITEMS.filter(item => (parseScore(weights[item]) ?? 0) > 0)
}

// 學期成績, or null while a weighted item is still empty.
export function semesterScore(scores, weights) {
  const required = requiredItems(weights)

  if (required.some(item => parseScore(scores[item]) === null)) {
    return null
  }

  return round(weightedSum(scores, weights, required) / 100)
}

// With the final empty: what it takes to reach 60.
// status is 'needed' | 'alreadyPassed' | 'impossible', or null when the
// other items are not all filled in (or the final carries no weight).
export function neededFinal(scores, weights) {
  const finalWeight = parseScore(weights.final) ?? 0

  if (parseScore(scores.final) !== null || finalWeight <= 0) {
    return null
  }

  const others = requiredItems(weights).filter(item => item !== 'final')

  if (others.some(item => parseScore(scores[item]) === null)) {
    return null
  }

  const needed =
    (PASSING_SCORE * 100 - weightedSum(scores, weights, others)) / finalWeight

  if (needed <= 0) {
    return { status: 'alreadyPassed', score: 0 }
  }

  if (needed > 100) {
    return { status: 'impossible', score: round(needed) }
  }

  return { status: 'needed', score: Math.ceil(needed * 10) / 10 }
}
