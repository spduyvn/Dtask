<template>
  <div class="user-task-list">
    <div class="toolbar">
      <div class="toolbar-filters">
        <select v-model="filterProjectId" class="filter-select">
          <option :value="null">All projects</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <select v-model="filterStatus" class="filter-select">
          <option :value="null">All statuses</option>
          <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <button type="button" class="btn-secondary btn-sm" @click="applyFilters">Filter</button>
        <button type="button" class="btn-secondary btn-sm" @click="clearFilters">Clear</button>
      </div>
      <button class="btn-primary" @click="openCreate">+ Create task</button>
    </div>

    <!-- Desktop: table -->
    <div class="table-wrap list-desktop">
      <table class="table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Project</th>
            <th>Start</th>
            <th>Due</th>
            <th>Remaining</th>
            <th>Status</th>
            <th width="200">Status actions</th>
            <th width="120">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="9" class="loading-cell">Loading...</td>
          </tr>
          <tr v-else-if="!tasks.length">
            <td colspan="9" class="empty-cell">No tasks yet.</td>
          </tr>
          <tr
            v-else
            v-for="t in tasks"
            :key="t.id"
            :class="['task-row', 'task-row--' + getTaskRowColor(t)]"
          >
            <td class="task-title task-title--link" @click="openDetail(t)">{{ t.title }}</td>
            <td class="task-desc">{{ t.description || '—' }}</td>
              <td>{{ (projects.find(p => p.id === t.project_id) || {}).name || '—' }}</td>
            <td>{{ formatDateTime(t.start_at || t.start_date) }}</td>
            <td>{{ formatDateTime(t.due_at || t.due_date) }}</td>
            <td>
              <span :class="['countdown-pill', getCountdownClass(t)]">
                <span class="countdown-pill-dot"></span>
                <span class="countdown-pill-text">
                  {{ formatCountdown(t.due_at || t.due_date, t.status) }}
                </span>
              </span>
            </td>
            <td>
              <span :class="['badge', 'badge--' + getStatusColor(t.status)]">
                {{ getStatusLabel(t.status) }}
              </span>
            </td>
            <td class="status-actions">
              <button
                v-for="opt in STATUS_OPTIONS"
                :key="opt.value"
                type="button"
                :class="['btn-icon', t.status === opt.value ? 'btn-icon--active' : '']"
                :title="opt.label"
                @click="setStatus(t, opt.value)"
              >
                <span class="btn-icon-symbol">{{ opt.icon }}</span>
              </button>
            </td>
            <td>
              <button class="btn-sm btn-edit" @click="openEdit(t)"><img
                src="../../assets/images/icons/edit.png" width="20px" alt="Edit" /></button>
              <button class="btn-sm btn-delete" @click="confirmDelete(t)"><img
                src="../../assets/images/icons/delete.png" width="20px" alt="Delete" /></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile: cards -->
    <div class="list-cards list-mobile">
      <div v-if="loading" class="list-cards-loading">Loading...</div>
      <div v-else-if="!tasks.length" class="list-cards-empty">No tasks yet.</div>
      <a
        v-else
        v-for="t in tasks"
        :key="t.id"
        href="javascript:void(0)"
        class="list-card list-card--task"
        :class="'list-card--' + getTaskRowColor(t)"
        @click.prevent="openDetail(t)"
      >
        <div class="list-card-main">
          <h3 class="list-card-title">{{ t.title }}</h3>
          <p class="list-card-meta">{{ (projects.find(p => p.id === t.project_id) || {}).name || '—' }}</p>
          <div class="list-card-badges">
            <span :class="['badge', 'badge--' + getStatusColor(t.status)]">{{ getStatusLabel(t.status) }}</span>
            <span :class="['countdown-pill', getCountdownClass(t)]">
              {{ formatCountdown(t.due_at || t.due_date, t.status) }}
            </span>
          </div>
        </div>
        <div class="list-card-actions" @click.stop>
          <button type="button" class="btn-card-action" title="Edit" @click="openEdit(t)">Edit</button>
          <button type="button" class="btn-card-action btn-card-action--danger" title="Delete" @click="confirmDelete(t)">Delete</button>
        </div>
      </a>
    </div>

    <div v-if="pagination.last_page > 1" class="pagination">
      <button :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">Trước</button>
      <span>{{ pagination.current_page }} / {{ pagination.last_page }}</span>
      <button :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">Sau</button>
    </div>

    <!-- Modal Create/Edit -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <h3 class="modal-title">{{ editingId ? 'Update task' : 'Create task' }}</h3>
        <form @submit.prevent="submitForm" class="form">
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="field">
            <label>Title</label>
            <input v-model="form.title" type="text" required placeholder="Task name" />
          </div>
          <div class="field">
            <label>Description</label>
            <textarea v-model="form.description" rows="3" placeholder="Optional description"></textarea>
          </div>
          <div class="field">
            <label>Project</label>
            <select v-model.number="form.project_id" class="field-select">
              <option :value="null">No project</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>
          <div class="field">
            <label>Start time</label>
            <input v-model="form.start_at" type="datetime-local" />
          </div>
          <div class="field">
            <label>Due time</label>
            <div class="due-quick">
              <button type="button" class="btn-due-quick" @click="setDueQuick('today')">Today</button>
              <button type="button" class="btn-due-quick" @click="setDueQuick('this_week')">This week</button>
              <button type="button" class="btn-due-quick" @click="setDueQuick('next_week')">Next week</button>
              <button type="button" class="btn-due-quick btn-due-quick--clear" @click="setDueQuick('clear')">Clear</button>
            </div>
            <input v-model="form.due_at" type="datetime-local" />
          </div>
          <div class="field">
            <label>Status</label>
            <select v-model.number="form.status" class="field-select">
              <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="closeModal">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete -->
    <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
      <div class="modal modal-sm">
        <h3 class="modal-title">Delete task?</h3>
        <p class="modal-text">Are you sure you want to delete <strong>{{ deleteTarget.title }}</strong>? This action cannot be undone.</p>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="deleteTarget = null">Cancel</button>
          <button type="button" class="btn-danger" :disabled="deleting" @click="doDelete">{{ deleting ? 'Deleting...' : 'Delete' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import '../../styles/user-task-list.css'
import api from '../../api/axios'
 
const router = useRouter()

const STATUS_OPTIONS = [
  { value: 0, label: 'Not started', icon: '○', color: 'gray' },
  { value: 1, label: 'In progress', icon: '▶', color: 'green' },
  { value: 2, label: 'Paused', icon: '⏸', color: 'orange' },
  { value: 3, label: 'Completed', icon: '✓', color: 'blue' },
]

const tasks = ref([])
const projects = ref([])
const loading = ref(true)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 50 })
const filterProjectId = ref(null)
const filterStatus = ref(null)
const showModal = ref(false)
const editingId = ref(null)
const form = reactive({ title: '', description: '', start_at: '', due_at: '', status: 0, project_id: null })
const formError = ref('')
const saving = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)
const updatingStatusId = ref(null)
const now = ref(new Date())
let countdownTimerId = null

function todayStr() {
  const d = new Date()
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')
}

function getStatusLabel(status) {
  const opt = STATUS_OPTIONS.find((o) => o.value === status)
  return opt ? opt.label : '—'
}

function getStatusColor(status) {
  const opt = STATUS_OPTIONS.find((o) => o.value === status)
  return opt ? opt.color : 'gray'
}

function getCountdownClass(task) {
  const status = task.status
  const value = task.due_at || task.due_date

  if (!value) return 'countdown--none'
  if (status === 3) return 'countdown--done'

  const due = parseDateTime(value)
  if (!due) return 'countdown--none'

  const current = now.value instanceof Date ? now.value : new Date()
  const diffMs = due.getTime() - current.getTime()
  const totalMinutes = Math.floor(Math.abs(diffMs) / 60000)

  if (diffMs < 0) return 'countdown--over'
  if (totalMinutes <= 60) return 'countdown--urgent'
  if (totalMinutes <= 24 * 60) return 'countdown--soon'
  return 'countdown--normal'
}

/** Màu dòng: đỏ nếu quá hạn và chưa hoàn thành; còn lại theo status */
function getTaskRowColor(task) {
  const status = task.status ?? 0
  const base = task.due_at || task.due_date
  if (!base) return getStatusColor(status)

  const now = new Date()
  const due = parseDateTime(base)
  if (due && due.getTime() <= now.getTime() && status !== 3) return 'red'
  return getStatusColor(status)
}

function parseDateTime(value) {
  if (!value) return null
  if (value instanceof Date) return value
  if (typeof value !== 'string') return null
  // ISO string from API
  if (value.includes('T')) {
    return new Date(value)
  }
  // fallback date only
  return new Date(value + 'T00:00:00')
}

function formatDateTime(value) {
  const d = parseDateTime(value)
  if (!d || Number.isNaN(d.getTime())) return '—'
  return d.toLocaleString('en-GB', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatCountdown(value, status) {
  if (status === 3) return 'Completed'
  if (!value) return '—'
  const due = parseDateTime(value)
  if (!due) return '—'

  const current = now.value instanceof Date ? now.value : new Date()
  let diffMs = due.getTime() - current.getTime()
  const past = diffMs < 0
  diffMs = Math.abs(diffMs)

  const totalMinutes = Math.floor(diffMs / 60000)
  const days = Math.floor(totalMinutes / (60 * 24))
  const hours = Math.floor((totalMinutes % (60 * 24)) / 60)
  const minutes = totalMinutes % 60

  const parts = []
  if (days) parts.push(`${days} days`)
  if (hours) parts.push(`${hours} hours`)
  if (!days && !hours) parts.push(`${minutes} minutes`)

  const label = parts.join(' ')
  return past ? `Overdue by ${label}` : `Remaining ${label}`
}

function getTaskParams(page = 1) {
  const params = { page, per_page: 50 }
  if (filterProjectId.value != null && filterProjectId.value !== '') {
    params.project_id = filterProjectId.value
  }
  if (filterStatus.value != null && filterStatus.value !== '') {
    params.status = filterStatus.value
  }
  return params
}

function applyFilters() {
  pagination.value.current_page = 1
  fetchTasks(1)
}

function clearFilters() {
  filterProjectId.value = null
  filterStatus.value = null
  pagination.value.current_page = 1
  fetchTasks(1)
}

async function fetchTasks(page = 1) {
  loading.value = true
  try {
    const params = getTaskParams(page)
    const { data } = await api.get('/me/tasks', { params })
    tasks.value = data.data || []
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
    }
  } catch (e) {
    tasks.value = []
    formError.value = e.response?.data?.message || 'Failed to load list.'
  } finally {
    loading.value = false
  }
}

async function fetchProjects() {
  try {
    const { data } = await api.get('/me/projects', { params: { per_page: 100 } })
    projects.value = data.data || []
  } catch {
    projects.value = []
  }
}

async function setStatus(task, status) {
  if (task.status === status) return
  updatingStatusId.value = task.id
  try {
    await api.patch(`/me/tasks/${task.id}`, { status })
    const idx = tasks.value.findIndex((x) => x.id === task.id)
    if (idx !== -1) tasks.value[idx] = { ...tasks.value[idx], status }
  } catch (e) {
    formError.value = e.response?.data?.message || 'Failed to update status.'
  } finally {
    updatingStatusId.value = null
  }
}

function openDetail(task) {
  router.push({ name: 'UserTaskDetail', params: { id: task.id } })
}

function openCreate() {
  editingId.value = null
  form.title = ''
  form.description = ''
  form.start_at = ''
  form.due_at = ''
  form.status = 0
  form.project_id = null
  formError.value = ''
  showModal.value = true
}

function openEdit(t) {
  editingId.value = t.id
  form.title = t.title
  form.description = t.description || ''
  form.start_at = toLocalInputValue(t.start_at || t.start_date)
  form.due_at = toLocalInputValue(t.due_at || t.due_date)
  form.status = typeof t.status === 'number' ? t.status : 0
  form.project_id = t.project_id ?? null
  formError.value = ''
  showModal.value = true
}

function toLocalInputValue(value) {
  const d = parseDateTime(value)
  if (!d || Number.isNaN(d.getTime())) return ''
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hour = String(d.getHours()).padStart(2, '0')
  const minute = String(d.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hour}:${minute}`
}

function formatDueForInput(d) {
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hour = String(d.getHours()).padStart(2, '0')
  const minute = String(d.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hour}:${minute}`
}

function setDueQuick(key) {
  const now = new Date()
  if (key === 'clear') {
    form.due_at = ''
    return
  }
  if (key === 'today') {
    const d = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59)
    form.due_at = formatDueForInput(d)
    return
  }
  if (key === 'this_week') {
    const day = now.getDay()
    const toSaturday = (6 - day + 7) % 7
    const saturday = new Date(now)
    saturday.setDate(now.getDate() + toSaturday)
    saturday.setHours(23, 59, 0, 0)
    form.due_at = formatDueForInput(saturday)
    return
  }
  if (key === 'next_week') {
    const day = now.getDay()
    const toNextSaturday = (6 - day + 7) % 7 + 7
    const nextSat = new Date(now)
    nextSat.setDate(now.getDate() + toNextSaturday)
    nextSat.setHours(23, 59, 0, 0)
    form.due_at = formatDueForInput(nextSat)
  }
}

function closeModal() {
  showModal.value = false
  editingId.value = null
}

async function submitForm() {
  formError.value = ''
  saving.value = true
  try {
    const payload = {
      title: form.title,
      description: form.description || null,
      start_at: form.start_at ? form.start_at.replace('T', ' ') + ':00' : null,
      due_at: form.due_at ? form.due_at.replace('T', ' ') + ':00' : null,
      status: form.status,
      project_id: form.project_id || null,
    }
    if (editingId.value) {
      await api.put(`/me/tasks/${editingId.value}`, payload)
    } else {
      await api.post('/me/tasks', payload)
    }
    closeModal()
    await fetchTasks(pagination.value.current_page)
  } catch (e) {
    const msg = e.response?.data?.errors
    formError.value = msg
      ? Object.values(msg).flat().join(' ')
      : (e.response?.data?.message || 'An error occurred.')
  } finally {
    saving.value = false
  }
}

function confirmDelete(t) {
  deleteTarget.value = t
}

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await api.delete(`/me/tasks/${deleteTarget.value.id}`)
    deleteTarget.value = null
    await fetchTasks(pagination.value.current_page)
  } catch (e) {
    formError.value = e.response?.data?.message || 'Failed to delete.'
  } finally {
    deleting.value = false
  }
}

function goPage(page) {
  fetchTasks(page)
}

onMounted(() => {
  fetchProjects()
  fetchTasks()
  countdownTimerId = setInterval(() => {
    now.value = new Date()
  }, 60000)
})

onUnmounted(() => {
  if (countdownTimerId) {
    clearInterval(countdownTimerId)
    countdownTimerId = null
  }
})
</script>
