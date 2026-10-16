<script setup>
// 提出修改 / 建立新條目. Nobody edits text here: the visitor describes the
// change and cites sources, and the AI editor drafts it on the next page.
// Needs a browser signed in to a schedule (the closest thing to an account).
import { computed, nextTick, onMounted, onUnmounted } from 'vue'
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
  page_slug: props.viewModel.page?.slug ?? null,
  requested_slug: props.viewModel.requestedSlug,
  requested_title: props.viewModel.requestedTitle ?? '',
  'cf-turnstile-response': '',
})

function addSource() {
  if (form.source_urls.length < props.viewModel.maxSourceUrls) {
    form.source_urls.push('')
  }
}

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
  removeTurnstileWidget()
})

function submit() {
  form
    .transform(data => ({
      ...data,
      source_urls: data.source_urls.map(url => url.trim()).filter(Boolean),
    }))
    .post('/wiki/edit-requests', {
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
          知識庫不能直接編輯。請說明{{
            isNewPage ? '新條目要寫什麼' : '這個條目要怎麼改'
          }}，並附上來源；AI
          編輯會依照知識庫的寫作規則寫出草稿，你確認差異後再送出。
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
            {{ isNewPage ? '這個條目要寫什麼？' : '要改什麼？為什麼？' }}
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
            越具體越好。修改說明會公開顯示在編輯歷史中。{{
              form.instruction.length
            }}/{{ viewModel.instructionMaxLength }}
          </p>
          <FieldError
            id="instruction-error"
            :message="form.errors.instruction"
          />
        </div>

        <fieldset class="space-y-2">
          <legend :class="labelClasses">來源網址（選填）</legend>
          <p class="text-xs text-theme-700 dark:text-zinc-400">
            學校規定、日期、費用等資料請附上來源。只接受這些網站：{{
              viewModel.sourceDomains.join('、')
            }}。
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
          <Button
            v-if="form.source_urls.length < viewModel.maxSourceUrls"
            size="sm"
            @click="addSource"
          >
            <Icon name="plus" class="size-4" />
            新增來源
          </Button>
        </fieldset>

        <div>
          <div ref="turnstileContainer"></div>
          <FieldError
            id="turnstile-error"
            :message="form.errors['cf-turnstile-response']"
          />
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <Button
            type="submit"
            variant="primary"
            :disabled="form.processing"
            data-testid="wiki-edit-request-submit"
          >
            <Icon name="paper-airplane" class="size-4" />
            請 AI 編輯撰寫草稿
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
