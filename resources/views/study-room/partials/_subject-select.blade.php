{{--
    科目選單：開始計時前的表單、以及計時中「變更活動」對話框共用。
--}}
@php
    $subjectModel ??= 'selectedSubjectCourseId';
    $subjectSelectTestid ??= 'study-room-subject-select';
@endphp

<label class="block">
    <span
        class="mb-1 block text-xs font-medium text-warm-600 dark:text-zinc-400"
        >科目</span
    >
    <x-select
        x-model="{{ $subjectModel }}"
        data-testid="{{ $subjectSelectTestid }}"
    >
        @foreach ($viewModel->subjects as $subject)
            <option value="{{ $subject->id ?? '' }}">
                {{ $subject->name }}
            </option>
        @endforeach
    </x-select>
</label>
