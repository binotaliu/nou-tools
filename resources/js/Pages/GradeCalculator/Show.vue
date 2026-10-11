<script setup>
// Scores and weights live in `courses` (a copy of the ViewModel) and are
// calculated in the browser by useGradeCalculation; saving PUTs them back.
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Icon from '../../Components/Icon.vue'
import Greeting from '../../Components/Greeting.vue'
import {
  PASSING_SCORE,
  neededFinal,
  parseScore,
  semesterScore,
  weightTotal,
  weightsAreValid,
} from '../../Composables/useGradeCalculation'
import { toSemesterDisplay } from '../../Composables/semesterDisplay'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
  greeting: {
    type: Object,
    required: true,
  },
})

const courses = reactive(
  props.viewModel.courses.map(course => ({
    ...course,
    scores: { ...course.scores },
    weights: { ...course.weights },
  }))
)

const isSummer = computed(() => props.viewModel.isSummer)
const items = computed(() =>
  isSummer.value
    ? [
        { key: 'regular', label: '平時成績' },
        { key: 'final', label: '期末考成績' },
      ]
    : [
        { key: 'regular', label: '平時成績' },
        { key: 'midterm', label: '期中考成績' },
        { key: 'final', label: '期末考成績' },
      ]
)

const semesterLabel = computed(() => toSemesterDisplay(props.viewModel.term))

const pageTitle = computed(() => {
  const suffix = props.viewModel.scheduleName
    ? ` - ${props.viewModel.scheduleName}`
    : ''

  return `成績計算機 - ${semesterLabel.value}${suffix} - NOU 小幫手`
})

const hasUnsavedChanges = ref(false)
const allWeightsValid = computed(() =>
  courses.every(course => weightsAreValid(course.weights))
)

watch(courses, () => (hasUnsavedChanges.value = true), { deep: true })

function result(course) {
  const score = semesterScore(course.scores, course.weights)

  if (score !== null) {
    return { kind: 'score', score, passed: score >= PASSING_SCORE }
  }

  const needed = neededFinal(course.scores, course.weights)

  return needed ? { kind: 'needed', ...needed } : { kind: 'empty' }
}

function isDefaultWeights(course) {
  return Object.keys(props.viewModel.defaultWeights).every(
    key =>
      parseScore(course.weights[key]) === props.viewModel.defaultWeights[key]
  )
}

function resetWeights(course) {
  Object.assign(course.weights, props.viewModel.defaultWeights)
}

const form = useForm({})

function save() {
  form
    .transform(() => ({
      scores: Object.fromEntries(
        courses.map(course => [
          course.id,
          Object.fromEntries(
            Object.entries(course.scores).map(([key, value]) => [
              key,
              parseScore(value),
            ])
          ),
        ])
      ),
      weights: Object.fromEntries(
        courses.map(course => [
          course.id,
          Object.fromEntries(
            Object.entries(course.weights).map(([key, value]) => [
              key,
              parseScore(value) ?? 0,
            ])
          ),
        ])
      ),
    }))
    .put(
      `/schedules/${props.viewModel.scheduleUuid}/${props.viewModel.term}/grade-calculator`,
      {
        preserveScroll: true,
        onSuccess: () => {
          hasUnsavedChanges.value = false
        },
      }
    )
}

function warnBeforeUnload(event) {
  if (hasUnsavedChanges.value) {
    event.preventDefault()
    event.returnValue = ''
  }
}

onMounted(() => {
  window.addEventListener('beforeunload', warnBeforeUnload)
})

onUnmounted(() => {
  window.removeEventListener('beforeunload', warnBeforeUnload)
})

const inputClass =
  'w-full rounded-md border border-theme-200 bg-white px-3 py-2 text-base text-theme-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100'
</script>

<template>
  <Head :title="pageTitle" />

  <AppLayout>
    <div class="mx-auto max-w-4xl">
      <div
        class="mb-8 flex flex-col items-start justify-between gap-y-4 md:flex-row bottom-nav:hidden"
        data-testid="grade-calculator-header"
      >
        <div>
          <h2 class="mb-2 text-3xl font-bold text-theme-900 dark:text-zinc-100">
            成績計算機
            <small v-if="viewModel.scheduleName"
              >— {{ viewModel.scheduleName }}</small
            >
          </h2>
          <p class="text-lg text-theme-700 dark:text-zinc-300">
            {{ semesterLabel }}
          </p>
        </div>

        <div class="flex w-full gap-2 md:w-auto">
          <Link
            :href="`/schedules/${viewModel.scheduleUuid}`"
            class="inline-flex w-1/2 items-center justify-center gap-2 rounded-md border border-theme-200 px-4 py-2 text-sm font-medium text-theme-700 transition-colors hover:bg-theme-50 md:w-auto dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-950"
            data-analytics-event="grade_calculator_back"
            data-analytics-feature="grade_calculator"
          >
            <Icon name="arrow-left" class="size-4" />
            回到課表
          </Link>

          <button
            type="button"
            class="inline-flex w-1/2 items-center justify-center gap-2 rounded-md bg-theme-700 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-theme-600 disabled:opacity-50 md:w-auto"
            data-testid="grade-calculator-save"
            data-analytics-event="grade_calculator_save"
            data-analytics-feature="grade_calculator"
            :disabled="form.processing || !allWeightsValid"
            @click="save"
          >
            <Icon name="check" class="size-4" />
            儲存成績
          </button>
        </div>
      </div>

      <Greeting
        class="mb-6"
        :semester-label="greeting.semesterLabel"
        :semester-code="greeting.semesterCode"
        :semester-start="greeting.semesterStart"
        :semester-end="greeting.semesterEnd"
      />

      <p class="mb-6 text-sm text-theme-700 dark:text-zinc-300">
        用於計算學期成績，並可在期末考前推算需要最低及格分數。本頁推算成績僅供參考。
      </p>

      <ul class="space-y-4">
        <li
          v-for="course in courses"
          :key="course.id"
          class="rounded-lg border border-theme-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
          :data-testid="`grade-course-${course.id}`"
        >
          <h3
            class="mb-3 text-lg font-semibold text-theme-900 dark:text-zinc-100"
          >
            {{ course.name }}
            <small
              v-if="course.credits"
              class="font-normal text-theme-700 dark:text-zinc-300"
              >{{ course.credits }} 學分</small
            >
          </h3>

          <div class="grid gap-3 sm:grid-cols-3">
            <div v-for="item in items" :key="item.key">
              <label
                class="mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300"
                :for="`score-${course.id}-${item.key}`"
              >
                {{ item.label }}（{{ course.weights[item.key] }}%）
              </label>
              <input
                :id="`score-${course.id}-${item.key}`"
                v-model="course.scores[item.key]"
                type="number"
                inputmode="decimal"
                min="0"
                max="100"
                step="any"
                :class="inputClass"
                :data-testid="`score-${course.id}-${item.key}`"
              />
            </div>
          </div>

          <p
            class="mt-4 text-base font-medium"
            role="status"
            :data-testid="`result-${course.id}`"
          >
            <template v-if="result(course).kind === 'score'">
              <span class="text-theme-900 dark:text-zinc-100"
                >學期成績
                <strong class="text-xl">{{ result(course).score }}</strong>
                分</span
              >
              <span
                class="ml-2 rounded-full px-2 py-0.5 text-sm"
                :class="
                  result(course).passed
                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100'
                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100'
                "
                >{{ result(course).passed ? '及格' : '不及格' }}</span
              >
            </template>
            <template v-else-if="result(course).kind === 'needed'">
              <template v-if="result(course).status === 'needed'">
                期末考至少要考
                <strong class="text-xl">{{ result(course).score }}</strong>
                分才能及格
              </template>
              <template v-else-if="result(course).status === 'alreadyPassed'">
                期末考不論幾分，學期成績都已達 {{ PASSING_SCORE }} 分
              </template>
              <template v-else>
                期末考即使考 100 分也無法及格（需要
                {{ result(course).score }} 分）
              </template>
            </template>
            <span v-else class="text-theme-700 dark:text-zinc-300"
              >輸入成績後會在這裡顯示學期成績</span
            >
          </p>

          <details class="mt-4">
            <summary
              class="cursor-pointer text-sm font-medium text-theme-700 dark:text-zinc-300"
            >
              調整比例
            </summary>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
              <div v-for="item in items" :key="item.key">
                <label
                  class="mb-1 block text-sm text-theme-700 dark:text-zinc-300"
                  :for="`weight-${course.id}-${item.key}`"
                >
                  {{ item.label }}占比（%）
                </label>
                <input
                  :id="`weight-${course.id}-${item.key}`"
                  v-model="course.weights[item.key]"
                  type="number"
                  inputmode="decimal"
                  min="0"
                  max="100"
                  step="any"
                  :class="inputClass"
                  :data-testid="`weight-${course.id}-${item.key}`"
                />
              </div>
            </div>
            <p
              v-if="!weightsAreValid(course.weights)"
              class="mt-2 text-sm text-red-700 dark:text-red-300"
              role="alert"
              :data-testid="`weight-error-${course.id}`"
            >
              比例總和必須是 100%（目前 {{ weightTotal(course.weights) }}%）
            </p>
            <button
              v-if="!isDefaultWeights(course)"
              type="button"
              class="mt-2 text-sm font-medium text-theme-700 underline dark:text-zinc-300"
              :data-testid="`weight-reset-${course.id}`"
              @click="resetWeights(course)"
            >
              還原預設比例
            </button>
          </details>
        </li>
      </ul>
    </div>
  </AppLayout>
</template>
