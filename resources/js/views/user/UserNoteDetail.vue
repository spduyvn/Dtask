<template>
  <div class="user-note-detail">
    <button class="btn-back" @click="goBack">← Back to notes</button>

    <div v-if="loading" class="loading">Loading note...</div>
    <div v-else-if="!note" class="empty">Note not found.</div>
    <div v-else class="note-detail-card">
      <header class="note-detail-header">
        <div class="note-detail-title-row">
          <span v-if="note.color" class="note-detail-dot" :style="{ backgroundColor: note.color }"></span>
          <input
            v-model="form.name"
            type="text"
            class="note-detail-title-input"
            placeholder="Note title"
          />
        </div>
        <div class="note-detail-meta">
          <span class="note-detail-date">{{ formatDateTime(note.updated_at) }}</span>
          <div class="note-detail-color-picker">
            <button
              v-for="c in noteColorOptions"
              :key="c.value"
              type="button"
              class="color-swatch-btn"
              :class="{ 'color-swatch-btn--selected': form.color === c.value }"
              :style="c.value ? { backgroundColor: c.value } : {}"
              :title="c.label"
              @click="form.color = c.value"
            >
              <span v-if="!c.value" class="color-swatch-none">None</span>
              <span v-else-if="form.color === c.value" class="color-swatch-check">✓</span>
            </button>
            <input v-model="form.color" type="color" class="color-input-native" title="Custom color" />
          </div>
        </div>
      </header>

      <div class="note-editor-section">
        <div class="note-editor-toolbar" ref="toolbarRef">
          <button
            type="button"
            class="toolbar-btn"
            :class="{ active: toolbarStates.bold }"
            title="Bold"
            @mousedown.prevent="applyFormat('bold')"
          >
            <b>B</b>
          </button>
          <button
            type="button"
            class="toolbar-btn"
            :class="{ active: toolbarStates.italic }"
            title="Italic"
            @mousedown.prevent="applyFormat('italic')"
          >
            <i>I</i>
          </button>
          <button
            type="button"
            class="toolbar-btn"
            :class="{ active: toolbarStates.underline }"
            title="Underline"
            @mousedown.prevent="applyFormat('underline')"
          >
            <u>U</u>
          </button>
          <span class="toolbar-divider"></span>
          <button
            type="button"
            class="toolbar-btn"
            :class="{ active: toolbarStates.highlight }"
            title="Highlight"
            @mousedown.prevent="applyFormat('highlight')"
          >
            <span class="toolbar-icon-highlight">H</span>
          </button>
          <button
            type="button"
            class="toolbar-btn"
            :class="{ active: toolbarStates.code }"
            title="Code"
            @mousedown.prevent="applyFormat('code')"
          >
            <code class="toolbar-icon-code">&lt;/&gt;</code>
          </button>
          <span class="toolbar-divider"></span>
          <button
            type="button"
            class="toolbar-btn"
            title="Unordered list"
            @mousedown.prevent="applyFormat('insertUnorderedList')"
          >
            •
          </button>
          <button
            type="button"
            class="toolbar-btn"
            title="Ordered list"
            @mousedown.prevent="applyFormat('insertOrderedList')"
          >
            1.
          </button>
        </div>
        <div
          ref="editorRef"
          class="note-editor-content"
          contenteditable="true"
          data-placeholder="Write your note here..."
          @input="onEditorInput"
          @focus="onEditorFocus"
          @blur="onEditorBlur"
        ></div>
      </div>

      <div class="note-detail-actions">
        <div v-if="formError" :class="['form-message', formSuccess ? 'form-success' : 'form-error']">{{ formError }}</div>
        <div class="actions-row">
          <button type="button" class="btn-secondary" @click="goBack">Cancel</button>
          <button type="button" class="btn-primary" :disabled="saving" @click="saveNote">
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
          <button type="button" class="btn-danger" @click="confirmDelete">Delete</button>
        </div>
      </div>
    </div>

    <!-- Confirm Delete -->
    <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = false">
      <div class="modal modal-sm">
        <h3 class="modal-title">Delete note?</h3>
        <p class="modal-text">
          Are you sure you want to delete <strong>{{ note?.name }}</strong>? This action cannot be undone.
        </p>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="showDeleteConfirm = false">Cancel</button>
          <button type="button" class="btn-danger" :disabled="deleting" @click="doDelete">
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import '../../styles/user-note-detail.css'
import { ref, reactive, onMounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/axios'

const route = useRoute()
const router = useRouter()

const note = ref(null)
const loading = ref(true)
const editorRef = ref(null)
const toolbarRef = ref(null)
const form = reactive({
  name: '',
  color: '',
  content: '',
})
const formError = ref('')
const formSuccess = ref(false)
const saving = ref(false)
const showDeleteConfirm = ref(false)
const deleting = ref(false)
const toolbarStates = reactive({
  bold: false,
  italic: false,
  underline: false,
  highlight: false,
  code: false,
})

const noteColorOptions = [
  { value: '', label: 'None' },
  { value: '#e94560', label: 'Red' },
  { value: '#3b82f6', label: 'Blue' },
  { value: '#22c55e', label: 'Green' },
  { value: '#f59e0b', label: 'Amber' },
  { value: '#8b5cf6', label: 'Purple' },
  { value: '#ec4899', label: 'Pink' },
  { value: '#14b8a6', label: 'Teal' },
  { value: '#f97316', label: 'Orange' },
  { value: '#6366f1', label: 'Indigo' },
  { value: '#6b7280', label: 'Gray' },
]

async function fetchNote() {
  loading.value = true
  formError.value = ''
  try {
    const { data } = await api.get(`/me/notes/${route.params.id}`)
    note.value = data.note
    form.name = data.note.name || ''
    form.color = data.note.color || ''
    form.content = data.note.content || ''
  } catch {
    note.value = null
  } finally {
    loading.value = false
    await nextTick()
    if (note.value && editorRef.value) {
      editorRef.value.innerHTML = form.content || ''
    }
  }
}

function setEditorContent(html) {
  if (editorRef.value) {
    editorRef.value.innerHTML = html || ''
  }
}

function getEditorContent() {
  return editorRef.value ? editorRef.value.innerHTML : ''
}

function onEditorInput() {
  form.content = getEditorContent()
}

function onEditorFocus() {
  updateToolbarStates()
}

function onEditorBlur() {
  setTimeout(() => {
    toolbarStates.bold = false
    toolbarStates.italic = false
    toolbarStates.underline = false
    toolbarStates.highlight = false
    toolbarStates.code = false
  }, 150)
}

function updateToolbarStates() {
  const sel = window.getSelection()
  if (!sel.rangeCount || !editorRef.value?.contains(sel.anchorNode)) return
  try {
    toolbarStates.bold = document.queryCommandState('bold')
    toolbarStates.italic = document.queryCommandState('italic')
    toolbarStates.underline = document.queryCommandState('underline')
    toolbarStates.highlight = document.queryCommandState('hiliteColor')
    const node = sel.anchorNode
    let el = node.nodeType === 3 ? node.parentElement : node
    while (el && el !== editorRef.value) {
      if (el.tagName === 'CODE' || el.tagName === 'MARK') {
        toolbarStates.code = el.tagName === 'CODE'
        toolbarStates.highlight = el.tagName === 'MARK' || toolbarStates.highlight
        break
      }
      el = el.parentElement
    }
  } catch {}
}

function applyFormat(cmd) {
  editorRef.value?.focus()
  const sel = window.getSelection()
  if (!sel.rangeCount) return

  if (cmd === 'bold' || cmd === 'italic' || cmd === 'underline') {
    document.execCommand(cmd, false, null)
  } else if (cmd === 'insertUnorderedList' || cmd === 'insertOrderedList') {
    document.execCommand(cmd, false, null)
  } else if (cmd === 'highlight') {
    document.execCommand('hiliteColor', false, '#fef08a')
  } else if (cmd === 'code') {
    wrapSelectionWithTag('code', 'note-code')
  }

  form.content = getEditorContent()
  updateToolbarStates()
}

function wrapSelectionWithTag(tagName, className) {
  const sel = window.getSelection()
  if (!sel.rangeCount) return
  const range = sel.getRangeAt(0)
  if (range.collapsed) return

  const selectedText = range.toString()
  const wrapper = document.createElement(tagName)
  if (className) wrapper.className = className
  wrapper.textContent = selectedText

  range.deleteContents()
  range.insertNode(wrapper)
  sel.removeAllRanges()
  const newRange = document.createRange()
  newRange.selectNodeContents(wrapper)
  sel.addRange(newRange)

  form.content = getEditorContent()
}

function formatDateTime(str) {
  if (!str) return '—'
  const d = new Date(str)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleString('en-GB', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function goBack() {
  router.push({ name: 'UserNoteList' })
}

async function saveNote() {
  formError.value = ''
  saving.value = true
  try {
    const payload = {
      name: form.name,
      color: form.color || null,
      content: getEditorContent() || null,
    }
    await api.put(`/me/notes/${route.params.id}`, payload)
    note.value = { ...note.value, ...payload }
    formSuccess.value = true
    formError.value = 'Saved.'
    setTimeout(() => {
      formError.value = ''
      formSuccess.value = false
    }, 2000)
  } catch (e) {
    formSuccess.value = false
    formError.value = e.response?.data?.message || 'Failed to save.'
  } finally {
    saving.value = false
  }
}

function confirmDelete() {
  showDeleteConfirm.value = true
}

async function doDelete() {
  deleting.value = true
  try {
    await api.delete(`/me/notes/${route.params.id}`)
    router.push({ name: 'UserNoteList' })
  } catch (e) {
    formError.value = e.response?.data?.message || 'Failed to delete.'
  } finally {
    deleting.value = false
  }
}

watch(() => route.params.id, fetchNote, { immediate: false })
onMounted(() => fetchNote())
</script>
