<script setup>
// 提出修改 / 建立新條目. Nobody edits text here: the visitor describes the
// change and cites sources, and the AI editor drafts and submits it while the
// next page shows its progress.
// Needs a browser signed in to a schedule (the closest thing to an account).
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../../Layouts/AppLayout.vue'
import Button from '../../../Components/Button.vue'
import FieldError from '../../../Components/FieldError.vue'
import Icon from '../../../Components/Icon.vue'
import { focusFirstInvalid } from '../../../Composables/useFormErrorFocus'
import useTurnstile from '../../../Composables/useTurnstile'
import { WIKI_PRIMARY_LINK } from '../../../Components/Wiki/linkClasses'

const props = defineProps({
  viewModel: {
    type: Object,
    required: true,
  },
})

const isNewPage = computed(() => props.viewModel.page === null)

const heading = computed(() =>
  isNewPage.value ? '建立新條目' : `提出修改：${props.viewModel.page.title}`
)

const canSubmit = computed(
  () =>
    props.viewModel.signedIn &&
    !props.viewModel.blocked &&
    !props.viewModel.isProtected
)

const form = useForm({
  instruction: '',
  source_urls: [''],
  screenshots: [],
  page_slug: props.viewModel.page?.slug ?? null,
  requested_slug: props.viewModel.requestedSlug,
  requested_title: props.viewModel.requestedTitle ?? '',
  'cf-turnstile-response': '',
})

// URLs and screenshots share one cap. An empty URL box is not a reference.
const referenceCount = computed(
  () =>
    form.source_urls.filter(url => url.trim() !== '').length +
    form.screenshots.length
)

const canAddReference = computed(
  () =>
    form.source_urls.length + form.screenshots.length <
    props.viewModel.maxReferences
)

function addSource() {
  if (canAddReference.value) {
    form.source_urls.push('')
  }
}

const screenshotInput = ref(null)
const screenshotPreviews = ref([])
const screenshotNotice = ref('')

function addScreenshots(event) {
  const room = props.viewModel.maxReferences - referenceCount.value
  const files = Array.from(event.target.files ?? [])
  const maxBytes = props.viewModel.screenshotMaxKb * 1024
  const tooBig = files.filter(file => file.size > maxBytes)
  const accepted = files.filter(file => file.size <= maxBytes).slice(0, room)

  screenshotNotice.value =
    tooBig.length > 0
      ? `每張截圖最大 ${Math.floor(props.viewModel.screenshotMaxKb / 1024)} MB，已略過 ${tooBig.length} 張。`
      : accepted.length < files.length
        ? `最多 ${props.viewModel.maxReferences} 項，已略過 ${files.length - accepted.length} 張。`
        : ''

  for (const file of accepted) {
    form.screenshots.push(file)
    screenshotPreviews.value.push(URL.createObjectURL(file))
  }

  event.target.value = ''
}

function removeScreenshot(index) {
  URL.revokeObjectURL(screenshotPreviews.value[index])
  screenshotPreviews.value.splice(index, 1)
  form.screenshots.splice(index, 1)
}

const screenshotError = index =>
  form.errors[`screenshots.${index}`] ??
  (index === 0 ? form.errors.screenshots : '')

function removeSource(index) {
  form.source_urls.splice(index, 1)

  if (form.source_urls.length === 0) {
    form.source_urls.push('')
  }
}

const sourceError = index =>
  form.errors[`source_urls.${index}`] ??
  (index === 0 ? form.errors.source_urls : '')

const {
  container: turnstileContainer,
  render: renderTurnstileWidget,
  remove: removeTurnstileWidget,
} = useTurnstile()

async function renderTurnstile() {
  if (!props.viewModel.turnstileSiteKey) {
    return
  }

  await renderTurnstileWidget(props.viewModel.turnstileSiteKey, {
    language: 'zh-TW',
    onSuccess: token => {
      form['cf-turnstile-response'] = token
    },
    onInvalid: () => {
      form['cf-turnstile-response'] = ''
    },
  })
}

onMounted(async () => {
  if (canSubmit.value) {
    await nextTick()
    renderTurnstile()
  }
})

onUnmounted(() => {
  screenshotPreviews.value.forEach(url => URL.revokeObjectURL(url))
  removeTurnstileWidget()
})

function submit() {
  form
    .transform(data => ({
      ...data,
      source_urls: data.source_urls.map(url => url.trim()).filter(Boolean),
    }))
    .post('/wiki/edit-requests', {
      forceFormData: true,
      onError: () => {
        focusFirstInvalid()
        removeTurnstileWidget()
        form['cf-turnstile-response'] = ''
        renderTurnstile()
      },
    })
}

const inputClasses =
  'w-full rounded-md border border-theme-300 bg-white px-3 py-2 text-theme-900 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100'
const labelClasses =
  'mb-1 block text-sm font-medium text-theme-700 dark:text-zinc-300'
</script>

<template>
  <Head :title="`${heading} - 知識庫 - NOU 小幫手`" />

  <AppLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <nav aria-label="麵包屑" class="text-sm">
        <Link
          :href="viewModel.page ? viewModel.page.url : '/wiki'"
          class="inline-flex items-center gap-1 text-theme-700 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-zinc-100"
        >
          <Icon name="chevron-left" class="size-3" />
          {{ viewModel.page ? viewModel.page.title : '知識庫' }}
        </Link>
      </nav>

      <header class="space-y-2">
        <h2 class="text-3xl font-bold text-theme-900 dark:text-zinc-100">
          {{ heading }}
        </h2>
        <p class="text-theme-800 dark:text-zinc-300">
          要{{ isNewPage ? '建立新條目' : '編輯條目' }}非常簡單，只需要說明{{
            isNewPage ? '新條目需要有什麼內容' : '需要修改什麼內容'
          }}，NOU 小幫手的 AI 編輯會自動撰寫內文。如果有來源資料，請附上網址。
        </p>
      </header>

      <div
        v-if="!viewModel.signedIn"
        class="space-y-3 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="wiki-needs-schedule"
      >
        <p class="text-theme-900 dark:text-zinc-100">
          提出修改前，請先在這台裝置登入你的課表。這是我們辨識編輯者、防止濫用的方式，不需要另外註冊帳號。
        </p>
        <div class="flex flex-wrap gap-2">
          <Link href="/schedules/my" :class="WIKI_PRIMARY_LINK"
            >登入我的課表</Link
          >
          <Link
            href="/schedules/create"
            class="inline-flex items-center px-3 py-2 text-sm font-semibold text-theme-900 underline hover:no-underline dark:text-zinc-100"
            >還沒有課表？建立一個</Link
          >
        </div>
      </div>

      <div
        v-else-if="viewModel.blocked || viewModel.isProtected"
        role="status"
        class="rounded-lg border border-amber-300 bg-amber-50 p-6 text-amber-900 dark:border-amber-700 dark:bg-amber-950/50 dark:text-amber-100"
      >
        {{
          viewModel.isProtected
            ? '這個條目受到保護，目前只有管理員可以修改。'
            : '這台裝置目前無法提出知識庫修改。'
        }}
      </div>

      <form
        v-else
        class="space-y-6 rounded-lg border border-theme-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
        data-testid="wiki-edit-request-form"
        @submit.prevent="submit"
      >
        <div v-if="isNewPage">
          <label for="requested_title" :class="labelClasses"
            >建議標題（選填）</label
          >
          <input
            id="requested_title"
            v-model="form.requested_title"
            type="text"
            :maxlength="80"
            placeholder="例如：空大考試要去哪裡考？怎麼考？是全遠距嗎？"
            :aria-invalid="form.errors.requested_title ? 'true' : null"
            :aria-describedby="
              form.errors.requested_title ? 'requested_title-error' : null
            "
            :class="inputClasses"
          />
          <FieldError
            id="requested_title-error"
            :message="form.errors.requested_title || form.errors.requested_slug"
          />
        </div>

        <div>
          <label for="instruction" :class="labelClasses">
            {{ isNewPage ? '條目內容說明' : '要改什麼？為什麼？' }}
            <span class="text-red-500" aria-hidden="true">*</span>
          </label>
          <textarea
            id="instruction"
            v-model="form.instruction"
            rows="6"
            required
            :maxlength="viewModel.instructionMaxLength"
            :placeholder="
              isNewPage
                ? '例如：說明空大期中考、期末考的考場、時間和應考規定，以及哪些課程是線上考試。'
                : '例如：第二段的考試時間已經改成上午九點半，請更新，來源是 115 學年度的考試須知。'
            "
            :aria-invalid="form.errors.instruction ? 'true' : null"
            aria-describedby="instruction-hint instruction-error"
            :class="inputClasses"
            data-testid="wiki-instruction"
          ></textarea>
          <p
            id="instruction-hint"
            class="mt-1 text-xs text-theme-700 dark:text-zinc-400"
          >
            {{ form.instruction.length }}/{{ viewModel.instructionMaxLength }}
          </p>
          <FieldError
            id="instruction-error"
            :message="form.errors.instruction"
          />
        </div>

        <fieldset class="space-y-2">
          <legend :class="labelClasses">參考資料（選填）</legend>
          <p class="text-xs text-theme-700 dark:text-zinc-400">
            若有資料來源，請附上連結或截圖，合計最多
            {{ viewModel.maxReferences }}
            項。目前可使用的網址為：{{ viewModel.sourceDomains.join('、') }}。
          </p>
          <div
            v-for="(url, index) in form.source_urls"
            :key="index"
            class="space-y-1"
          >
            <div class="flex gap-2">
              <label :for="`source-url-${index}`" class="sr-only"
                >來源網址 {{ index + 1 }}</label
              >
              <input
                :id="`source-url-${index}`"
                v-model="form.source_urls[index]"
                type="url"
                inputmode="url"
                placeholder="https://www.nou.edu.tw/…"
                :aria-invalid="sourceError(index) ? 'true' : null"
                :aria-describedby="
                  sourceError(index) ? `source-url-${index}-error` : null
                "
                :class="inputClasses"
                :data-testid="`wiki-source-url-${index}`"
              />
              <Button
                v-if="form.source_urls.length > 1"
                variant="ghost"
                size="sm"
                :aria-label="`移除來源網址 ${index + 1}`"
                @click="removeSource(index)"
              >
                <Icon name="x-mark" class="size-4" />
              </Button>
            </div>
            <FieldError
              :id="`source-url-${index}-error`"
              :message="sourceError(index)"
            />
          </div>
          <div class="flex flex-wrap gap-2">
            <Button v-if="canAddReference" size="sm" @click="addSource">
              <Icon name="plus" class="size-4" />
              新增來源
            </Button>
            <Button
              v-if="canAddReference"
              size="sm"
              data-testid="wiki-add-screenshot"
              @click="screenshotInput.click()"
            >
              <Icon name="photo" class="size-4" />
              上傳截圖
            </Button>
          </div>
          <input
            ref="screenshotInput"
            type="file"
            multiple
            accept="image/png,image/jpeg,image/webp"
            class="sr-only"
            tabindex="-1"
            aria-label="上傳截圖"
            data-testid="wiki-screenshot-input"
            @change="addScreenshots"
          />
          <p
            v-if="screenshotNotice"
            role="status"
            class="text-xs text-amber-800 dark:text-amber-300"
          >
            {{ screenshotNotice }}
          </p>
          <ul
            v-if="form.screenshots.length"
            class="flex flex-wrap gap-3"
            data-testid="wiki-screenshot-previews"
          >
            <li
              v-for="(file, index) in form.screenshots"
              :key="screenshotPreviews[index]"
              class="w-24 space-y-1"
            >
              <div class="relative">
                <img
                  :src="screenshotPreviews[index]"
                  :alt="`截圖：${file.name}`"
                  class="size-24 rounded-md border border-theme-200 object-cover dark:border-zinc-700"
                />
                <Button
                  variant="ghost"
                  size="sm"
                  class="absolute top-0 right-0 bg-white/80 dark:bg-zinc-900/80"
                  :aria-label="`移除截圖 ${file.name}`"
                  @click="removeScreenshot(index)"
                >
                  <Icon name="x-mark" class="size-4" />
                </Button>
              </div>
              <FieldError
                :id="`screenshot-${index}-error`"
                :message="screenshotError(index)"
              />
            </li>
          </ul>
          <FieldError
            v-if="form.screenshots.length === 0"
            id="screenshots-error"
            :message="form.errors.screenshots"
          />
          <p class="text-xs text-theme-700 dark:text-zinc-400">
            截圖會顯示在修改紀錄上，請勿包含個人資料。
          </p>
        </fieldset>

        <div>
          <div ref="turnstileContainer"></div>
          <FieldError
            id="turnstile-error"
            :message="form.errors['cf-turnstile-response']"
          />
        </div>

        <Button
          type="submit"
          variant="primary"
          :disabled="form.processing"
          data-testid="wiki-edit-request-submit"
        >
          <Icon name="paper-airplane" class="size-4" />
          送出
        </Button>
      </form>
    </div>
  </AppLayout>
</template>
