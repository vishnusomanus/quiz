import { useRef, useState, type ChangeEvent } from 'react'
import { uploadOptionImage } from '@/api/quizzes'
import { validationMessage } from '@/api/client'
import { Checkbox } from '@/components/ui/Checkbox'
import { Input, Textarea } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Button } from '@/components/ui/Button'
import { MsIcon } from '@/components/ui/MsIcon'
import { useToastStore } from '@/store/toastStore'
import type { QuestionType, QuizDetail, QuizPayload } from '@/types/api'
import { countryLabel } from '@/utils/format'

const COUNTRIES = ['IN', 'US', 'GB', 'AE', 'SG', 'AU', 'CA', 'DE', 'FR', 'JP']
const IMAGE_ACCEPT = 'image/jpeg,image/png,image/gif,image/webp'
const IMAGE_TYPES = new Set(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
const MAX_IMAGE_BYTES = 2 * 1024 * 1024
const MAX_IMAGE_LABEL = '2 MB'

function formatFileSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function imageFileError(file: File) {
  const namedImage = /\.(jpe?g|png|gif|webp)$/i.test(file.name)
  if (file.type && !IMAGE_TYPES.has(file.type) && !namedImage) {
    return 'Images must be jpeg, png, gif, or webp.'
  }
  if (file.size > MAX_IMAGE_BYTES) {
    return `This image is too large (${formatFileSize(file.size)}). Maximum size is ${MAX_IMAGE_LABEL}.`
  }
  return null
}

function countryChipClass(active: boolean) {
  return active
    ? 'rounded-full bg-primary px-3 py-1 text-[11px] font-semibold text-on-primary'
    : 'rounded-full bg-surface-container-low px-3 py-1 text-[11px] font-semibold text-on-surface-variant'
}

type DraftOption = {
  key: string
  id?: string
  label: string
  image_url: string
  is_correct: boolean
}

type DraftQuestion = {
  key: string
  id?: string
  prompt: string
  type: QuestionType
  points: number
  time_limit_seconds: string
  options: DraftOption[]
}

type DraftQuiz = {
  title: string
  description: string
  time_limit_seconds: string
  per_question_time_seconds: string
  is_published: boolean
  countries: string[]
  questions: DraftQuestion[]
}

function optionDraft(): DraftOption {
  return { key: crypto.randomUUID(), label: '', image_url: '', is_correct: false }
}

function questionDraft(): DraftQuestion {
  return {
    key: crypto.randomUUID(),
    prompt: '',
    type: 'single_choice',
    points: 1,
    time_limit_seconds: '',
    options: [optionDraft(), optionDraft()],
  }
}

function fromQuiz(quiz?: QuizDetail | null): DraftQuiz {
  if (!quiz) {
    return {
      title: '',
      description: '',
      time_limit_seconds: '180',
      per_question_time_seconds: '30',
      is_published: true,
      countries: [],
      questions: [questionDraft()],
    }
  }

  return {
    title: quiz.title,
    description: quiz.description ?? '',
    time_limit_seconds: quiz.time_limit_seconds ? String(quiz.time_limit_seconds) : '',
    per_question_time_seconds: quiz.per_question_time_seconds ? String(quiz.per_question_time_seconds) : '',
    is_published: quiz.is_published,
    countries: quiz.countries,
    questions: quiz.questions.map((question) => ({
      key: question.id,
      id: question.id,
      prompt: question.prompt,
      type: question.type,
      points: question.points,
      time_limit_seconds: question.time_limit_seconds ? String(question.time_limit_seconds) : '',
      options: question.options.map((option) => ({
        key: option.id,
        id: option.id,
        label: option.label,
        image_url: option.image_url ?? '',
        is_correct: Boolean(option.is_correct),
      })),
    })),
  }
}

function toNumber(value: string) {
  if (!value.trim()) return null
  const parsed = Number(value)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null
}

export function QuizForm({
  quiz,
  busy,
  onSubmit,
}: {
  quiz?: QuizDetail | null
  busy?: boolean
  onSubmit: (payload: QuizPayload) => void
}) {
  const [draft, setDraft] = useState<DraftQuiz>(() => fromQuiz(quiz))
  const [error, setError] = useState<string | null>(null)
  const [optionErrors, setOptionErrors] = useState<Record<string, string>>({})
  const [uploadingKey, setUploadingKey] = useState<string | null>(null)
  const fileInputRef = useRef<HTMLInputElement>(null)
  const uploadTargetRef = useRef<{ questionKey: string; optionKey: string } | null>(null)
  const push = useToastStore((state) => state.push)

  function showUploadError(optionKey: string, message: string, title: string) {
    setOptionErrors((current) => ({ ...current, [optionKey]: message }))
    setError(message)
    push({ title, description: message, tone: 'danger' })
  }

  function clearOptionError(optionKey: string) {
    setOptionErrors((current) => {
      if (!current[optionKey]) return current
      const next = { ...current }
      delete next[optionKey]
      return next
    })
  }

  function updateQuestion(key: string, patch: Partial<DraftQuestion>) {
    setDraft((current) => ({
      ...current,
      questions: current.questions.map((question) => (question.key === key ? { ...question, ...patch } : question)),
    }))
  }

  function updateOption(questionKey: string, optionKey: string, patch: Partial<DraftOption>) {
    setDraft((current) => ({
      ...current,
      questions: current.questions.map((question) =>
        question.key === questionKey
          ? {
              ...question,
              options: question.options.map((option) => (option.key === optionKey ? { ...option, ...patch } : option)),
            }
          : question,
      ),
    }))
  }

  function pickImage(questionKey: string, optionKey: string) {
    uploadTargetRef.current = { questionKey, optionKey }
    fileInputRef.current?.click()
  }

  async function handleImageFile(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0]
    const target = uploadTargetRef.current
    event.target.value = ''
    if (!file || !target) return
    const fileError = imageFileError(file)
    if (fileError) {
      const tooLarge = file.size > MAX_IMAGE_BYTES
      showUploadError(target.optionKey, fileError, tooLarge ? 'Image too large' : 'Invalid image')
      return
    }
    setUploadingKey(target.optionKey)
    clearOptionError(target.optionKey)
    setError(null)
    try {
      const response = await uploadOptionImage(file)
      updateOption(target.questionKey, target.optionKey, { image_url: response.data.url })
    } catch (err) {
      const message = validationMessage(err)
      const tooLarge = /larger than|too large|2 mb/i.test(message)
      showUploadError(target.optionKey, message, tooLarge ? 'Image too large' : 'Upload failed')
    } finally {
      setUploadingKey(null)
      uploadTargetRef.current = null
    }
  }

  function handleSubmit() {
    if (!draft.title.trim()) {
      setError('Title is required.')
      return
    }
    if (draft.questions.length === 0) {
      setError('Add at least one question.')
      return
    }
    for (const [index, question] of draft.questions.entries()) {
      if (!question.prompt.trim()) {
        setError(`Question ${index + 1} needs a prompt.`)
        return
      }
      if (question.options.length < 2) {
        setError(`Question ${index + 1} needs at least two options.`)
        return
      }
      if (question.options.some((option) => !option.label.trim() && !option.image_url.trim())) {
        setError(`Question ${index + 1} has an empty option. Add a label or an image.`)
        return
      }
      const correct = question.options.filter((option) => option.is_correct).length
      if (question.type === 'single_choice' && correct !== 1) {
        setError(`Question ${index + 1} must have exactly one correct option.`)
        return
      }
      if (question.type === 'multiple_choice' && correct < 1) {
        setError(`Question ${index + 1} must have at least one correct option.`)
        return
      }
    }

    setError(null)
    onSubmit({
      title: draft.title.trim(),
      description: draft.description.trim() || null,
      time_limit_seconds: toNumber(draft.time_limit_seconds),
      per_question_time_seconds: toNumber(draft.per_question_time_seconds),
      is_published: draft.is_published,
      countries: draft.countries,
      questions: draft.questions.map((question, index) => ({
        id: question.id,
        prompt: question.prompt.trim(),
        type: question.type,
        points: question.points || 1,
        time_limit_seconds: toNumber(question.time_limit_seconds),
        sort_order: index + 1,
        options: question.options.map((option, optionIndex) => ({
          id: option.id,
          label: option.label.trim(),
          image_url: option.image_url.trim() || null,
          is_correct: option.is_correct,
          sort_order: optionIndex + 1,
        })),
      })),
    })
  }

  const allCountriesSelected = COUNTRIES.every((code) => draft.countries.includes(code))

  return (
    <div className="space-y-4">
      <input
        ref={fileInputRef}
        type="file"
        accept={IMAGE_ACCEPT}
        className="hidden"
        onChange={(event) => void handleImageFile(event)}
      />
      {error ? <p className="rounded-xl bg-error-container px-3 py-2 text-xs text-on-error-container">{error}</p> : null}
      <section className="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
        <h2 className="text-sm font-semibold text-on-surface">Quiz details</h2>
        <div className="mt-3 grid gap-3 md:grid-cols-2">
          <label className="text-xs font-semibold text-on-surface-variant md:col-span-2">
            Title
            <Input className="mt-1" value={draft.title} onChange={(event) => setDraft({ ...draft, title: event.target.value })} />
          </label>
          <label className="text-xs font-semibold text-on-surface-variant md:col-span-2">
            Description
            <Textarea
              className="mt-1"
              value={draft.description}
              onChange={(event) => setDraft({ ...draft, description: event.target.value })}
            />
          </label>
          <label className="text-xs font-semibold text-on-surface-variant">
            Overall time (seconds)
            <Input
              className="mt-1"
              type="number"
              min={1}
              value={draft.time_limit_seconds}
              onChange={(event) => setDraft({ ...draft, time_limit_seconds: event.target.value })}
            />
          </label>
          <label className="text-xs font-semibold text-on-surface-variant">
            Per-question time (seconds)
            <Input
              className="mt-1"
              type="number"
              min={1}
              value={draft.per_question_time_seconds}
              onChange={(event) => setDraft({ ...draft, per_question_time_seconds: event.target.value })}
            />
          </label>
        </div>
        <label className="mt-3 flex items-center gap-2 text-sm text-on-surface">
          <Checkbox
            checked={draft.is_published}
            onChange={(event) => setDraft({ ...draft, is_published: event.target.checked })}
          />
          Published
        </label>
        <div className="mt-4">
          <p className="text-xs font-semibold text-on-surface-variant">Countries</p>
          <p className="mt-0.5 text-[11px] text-on-surface-variant">
            Choose All, pick specific countries, or leave none selected for a worldwide quiz.
          </p>
          <div className="mt-2 flex flex-wrap gap-2">
            <button
              type="button"
              onClick={() =>
                setDraft((current) => ({
                  ...current,
                  countries: allCountriesSelected ? [] : [...COUNTRIES],
                }))
              }
              className={countryChipClass(allCountriesSelected)}
            >
              All
            </button>
            {COUNTRIES.map((code) => {
              const selected = draft.countries.includes(code)
              return (
                <button
                  key={code}
                  type="button"
                  onClick={() =>
                    setDraft((current) => ({
                      ...current,
                      countries: selected
                        ? current.countries.filter((item) => item !== code)
                        : [...current.countries, code],
                    }))
                  }
                  className={countryChipClass(selected)}
                >
                  {countryLabel(code)}
                </button>
              )
            })}
          </div>
        </div>
      </section>

      {draft.questions.map((question, index) => (
        <section key={question.key} className="rounded-2xl bg-surface-container-lowest p-4 shadow-sm sm:p-5">
          <div className="flex items-center justify-between gap-2">
            <h3 className="text-sm font-semibold text-on-surface">Question {index + 1}</h3>
            <Button
              size="sm"
              disabled={draft.questions.length <= 1}
              onClick={() =>
                setDraft((current) => ({
                  ...current,
                  questions: current.questions.filter((item) => item.key !== question.key),
                }))
              }
            >
              <MsIcon name="delete" className="text-[14px]" />
              Remove
            </Button>
          </div>
          <label className="mt-3 block text-xs font-semibold text-on-surface-variant">
            Prompt
            <Textarea
              className="mt-1"
              value={question.prompt}
              onChange={(event) => updateQuestion(question.key, { prompt: event.target.value })}
            />
          </label>
          <div className="mt-3 grid gap-3 sm:grid-cols-3">
            <label className="text-xs font-semibold text-on-surface-variant">
              Type
              <Select
                className="mt-1"
                value={question.type}
                onChange={(event) => {
                  const type = event.target.value as QuestionType
                  const options =
                    type === 'single_choice'
                      ? question.options.map((option, optionIndex) => ({
                          ...option,
                          is_correct: optionIndex === question.options.findIndex((item) => item.is_correct),
                        }))
                      : question.options
                  updateQuestion(question.key, { type, options })
                }}
              >
                <option value="single_choice">Single choice</option>
                <option value="multiple_choice">Multiple choice</option>
              </Select>
            </label>
            <label className="text-xs font-semibold text-on-surface-variant">
              Points
              <Input
                className="mt-1"
                type="number"
                min={1}
                value={question.points}
                onChange={(event) => updateQuestion(question.key, { points: Number(event.target.value) || 1 })}
              />
            </label>
            <label className="text-xs font-semibold text-on-surface-variant">
              Time limit (seconds)
              <Input
                className="mt-1"
                type="number"
                min={1}
                value={question.time_limit_seconds}
                onChange={(event) => updateQuestion(question.key, { time_limit_seconds: event.target.value })}
              />
            </label>
          </div>
          <div className="mt-4 space-y-3">
            {question.options.map((option, optionIndex) => (
              <div key={option.key} className="rounded-xl border border-outline-variant/30 bg-surface-container-low/40 p-3">
                <div className="flex items-start gap-2">
                  <label className="mt-2 flex items-center gap-2 text-xs text-on-surface-variant">
                    <Checkbox
                      checked={option.is_correct}
                      onChange={(event) => {
                        const checked = event.target.checked
                        updateQuestion(question.key, {
                          options: question.options.map((item) => {
                            if (item.key !== option.key) {
                              return question.type === 'single_choice' ? { ...item, is_correct: false } : item
                            }
                            return { ...item, is_correct: checked }
                          }),
                        })
                      }}
                    />
                    Correct
                  </label>
                  <div className="min-w-0 flex-1 space-y-2">
                    <Input
                      value={option.label}
                      placeholder={`Option ${optionIndex + 1} label (optional with image)`}
                      onChange={(event) => updateOption(question.key, option.key, { label: event.target.value })}
                    />
                    <div className="flex flex-wrap items-center gap-2">
                      <Button size="sm" disabled={uploadingKey === option.key} onClick={() => pickImage(question.key, option.key)}>
                        <MsIcon name="image" className="text-[14px]" />
                        {uploadingKey === option.key ? 'Uploading…' : option.image_url ? 'Replace image' : 'Upload image'}
                      </Button>
                      {option.image_url ? (
                        <Button size="sm" onClick={() => updateOption(question.key, option.key, { image_url: '' })}>
                          <MsIcon name="close" className="text-[14px]" />
                          Remove image
                        </Button>
                      ) : null}
                    </div>
                    <p className="text-[11px] text-on-surface-variant">JPEG, PNG, GIF, or WebP. Max {MAX_IMAGE_LABEL}.</p>
                    {optionErrors[option.key] ? (
                      <p role="alert" className="text-xs font-medium text-error">
                        {optionErrors[option.key]}
                      </p>
                    ) : null}
                  </div>
                  <Button
                    size="sm"
                    disabled={question.options.length <= 2}
                    onClick={() =>
                      updateQuestion(question.key, {
                        options: question.options.filter((item) => item.key !== option.key),
                      })
                    }
                  >
                    <MsIcon name="close" className="text-[14px]" />
                  </Button>
                </div>
                {option.image_url ? (
                  <img
                    src={option.image_url}
                    alt={option.label || `Option ${optionIndex + 1} preview`}
                    className="mt-3 max-h-24 rounded-lg border border-outline-variant/30 bg-surface-container-lowest object-contain p-1"
                  />
                ) : null}
              </div>
            ))}
            <Button
              size="sm"
              onClick={() => updateQuestion(question.key, { options: [...question.options, optionDraft()] })}
            >
              <MsIcon name="add" className="text-[14px]" />
              Add option
            </Button>
          </div>
        </section>
      ))}

      <div className="flex flex-wrap gap-2">
        <Button onClick={() => setDraft((current) => ({ ...current, questions: [...current.questions, questionDraft()] }))}>
          <MsIcon name="add" className="text-[16px]" />
          Add question
        </Button>
        <Button variant="primary" disabled={busy} onClick={handleSubmit}>
          {busy ? 'Saving…' : quiz ? 'Save changes' : 'Create quiz'}
        </Button>
      </div>
    </div>
  )
}
