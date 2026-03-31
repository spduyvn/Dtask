<template>
  <div class="project-detail">
    <button class="btn-back" @click="goBack">← Back to project list</button>

    <div v-if="loadingProject" class="loading">Loading project...</div>
    <div v-else-if="!project" class="empty">Project not found.</div>
    <div v-else>
      <section class="project-card">
        <header class="project-header">
          <div>
            <h2 class="project-title">
              <span v-if="project.color" class="project-color-dot" :style="{ backgroundColor: project.color }"></span>
              {{ project.name }}
            </h2>
            <p class="project-sub">
              {{ project.project_type?.name ?? '—' }} •
              <span v-if="project.start_date || project.end_date">
                {{ formatDate(project.start_date) }} - {{ formatDate(project.end_date) }}
              </span>
              <span v-else>No dates set</span>
            </p>
          </div>
          <div class="project-meta">
            <div class="meta-item">
              <div class="meta-label">Total tasks</div>
              <div class="meta-value">{{ project.tasks_count ?? 0 }}</div>
            </div>
            <div class="meta-item">
              <div class="meta-label">Open tasks</div>
              <div class="meta-value meta-value--accent">{{ project.open_tasks_count ?? 0 }}</div>
            </div>
          </div>
        </header>

        <p class="project-description">
          {{ project.description || 'No description for this project yet.' }}
        </p>
      </section>

      <section class="tasks-section">
        <div class="tasks-header">
          <h3 class="section-title">Tasks in project</h3>
          <button class="btn-primary" @click="openCreateTask">+ Add task</button>
        </div>

        <div v-if="loadingTasks" class="loading">Loading tasks...</div>
        <div v-else-if="!tasks.length" class="empty">No tasks in this project yet.</div>
        <div v-else class="task-grid">
          <article v-for="t in tasks" :key="t.id" class="task-card" :class="'task-card--' + getStatusColor(t.status)">
            <header class="task-card-header">
              <h4 class="task-card-title">{{ t.title }}</h4>
              <span :class="['badge', 'badge--' + getStatusColor(t.status)]">
                {{ getStatusLabel(t.status) }}
              </span>
            </header>
            <p class="task-card-desc">{{ t.description || 'No description.' }}</p>
            <div class="task-card-times">
              <div class="task-time">
                <span class="task-time-label">Start</span>
                <span class="task-time-value">{{ formatDateTime(t.start_at || t.start_date) }}</span>
              </div>
              <div class="task-time">
                <span class="task-time-label">Due</span>
                <span class="task-time-value">{{ formatDateTime(t.due_at || t.due_date) }}</span>
              </div>
            </div>
            <div class="task-card-countdown">
              <span :class="['countdown-pill', getCountdownClass(t)]">
                <span class="countdown-pill-dot"></span>
                <span class="countdown-pill-text">
                  {{ formatCountdown(t.due_at || t.due_date, t.status) }}
                </span>
              </span>
            </div>
            <footer class="task-card-footer">
              <button class="btn-sm btn-edit" @click="openEditTask(t)"><img
                src="../../assets/images/icons/edit.png" width="20px" alt="Edit" /></button>
              <button class="btn-sm btn-delete" @click="confirmDeleteTask(t)"><img
                  src="../../assets/images/icons/delete.png" width="20px" alt="Delete" /></button>
            </footer>
          </article>
        </div>
      </section>
    </div>

    <!-- Modal Create/Edit Task -->
    <div v-if="showTaskModal" class="modal-overlay" @click.self="closeTaskModal">
      <div class="modal">
        <h3 class="modal-title">{{ taskEditingId ? 'Update task' : 'Create task' }}</h3>
        <form @submit.prevent="submitTaskForm" class="form">
          <div v-if="taskFormError" class="form-error">{{ taskFormError }}</div>
          <div class="field">
            <label>Title</label>
            <input v-model="taskForm.title" type="text" required placeholder="Task name" />
          </div>
          <div class="field">
            <label>Description</label>
            <textarea v-model="taskForm.description" rows="3" placeholder="Optional description"></textarea>
          </div>
          <div class="field">
            <label>Start time</label>
            <input v-model="taskForm.start_at" type="datetime-local" />
          </div>
          <div class="field">
            <label>Due time</label>
            <input v-model="taskForm.due_at" type="datetime-local" />
          </div>
          <div class="field">
            <label>Status</label>
            <select v-model.number="taskForm.status" class="field-select">
              <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="closeTaskModal">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="taskSaving">
              {{ taskSaving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Task -->
    <div v-if="taskDeleteTarget" class="modal-overlay" @click.self="taskDeleteTarget = null">
      <div class="modal modal-sm">
        <h3 class="modal-title">Delete task?</h3>
        <p class="modal-text">
          Are you sure you want to delete <strong>{{ taskDeleteTarget.title }}</strong>? This action
          cannot be undone.
        </p>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="taskDeleteTarget = null">Cancel</button>
          <button type="button" class="btn-danger" :disabled="taskDeleting" @click="doDeleteTask">
            {{ taskDeleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import '../../styles/user-project-detail.css'
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/axios'

const route = useRoute()
const router = useRouter()

const STATUS_OPTIONS = [
  { value: 0, label: 'Not started', color: 'gray' },
  { value: 1, label: 'In progress', color: 'green' },
  { value: 2, label: 'Paused', color: 'orange' },
  { value: 3, label: 'Completed', color: 'blue' },
]

const project = ref(null)
const loadingProject = ref(true)

const tasks = ref([])
const loadingTasks = ref(true)

const showTaskModal = ref(false)
const taskEditingId = ref(null)
const taskForm = reactive({
  title: '',
  description: '',
  start_at: '',
  due_at: '',
  status: 0,
})
const taskFormError = ref('')
const taskSaving = ref(false)
const taskDeleteTarget = ref(null)
const taskDeleting = ref(false)

const now = ref(new Date())
let timerId = null

onMounted(() => {
  fetchProject()
  fetchTasks()
  timerId = setInterval(() => {
    now.value = new Date()
  }, 60000)
})

onUnmounted(() => {
  if (timerId) {
    clearInterval(timerId)
    timerId = null
  }
})

async function fetchProject() {
  loadingProject.value = true
  try {
    const { data } = await api.get(`/me/projects/${route.params.id}`)
    project.value = data.project
  } catch {
    project.value = null
  } finally {
    loadingProject.value = false
  }
}

async function fetchTasks() {
  loadingTasks.value = true
  try {
    const { data } = await api.get('/me/tasks', {
      params: {
        project_id: route.params.id,
        per_page: 200,
      },
    })
    tasks.value = data.data || []
  } catch {
    tasks.value = []
  } finally {
    loadingTasks.value = false
  }
}

function goBack() {
  router.push({ name: 'UserProjectList' })
}

function getStatusLabel(status) {
  const opt = STATUS_OPTIONS.find((o) => o.value === status)
  return opt ? opt.label : '—'
}

function getStatusColor(status) {
  const opt = STATUS_OPTIONS.find((o) => o.value === status)
  return opt ? opt.color : 'gray'
}

function formatDate(str) {
  if (!str) return '—'
  const s = typeof str === 'string' ? str.split('T')[0] : str
  const d = new Date(s + 'T00:00:00')
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleDateString('en-GB')
}

function parseDateTime(value) {
  if (!value) return null
  if (value instanceof Date) return value
  if (typeof value !== 'string') return null
  if (value.includes('T')) {
    return new Date(value)
  }
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

function openCreateTask() {
  taskEditingId.value = null
  taskForm.title = ''
  taskForm.description = ''
  taskForm.start_at = ''
  taskForm.due_at = ''
  taskForm.status = 0
  taskFormError.value = ''
  showTaskModal.value = true
}

function openEditTask(t) {
  taskEditingId.value = t.id
  taskForm.title = t.title
  taskForm.description = t.description || ''
  taskForm.start_at = toLocalInputValue(t.start_at || t.start_date)
  taskForm.due_at = toLocalInputValue(t.due_at || t.due_date)
  taskForm.status = typeof t.status === 'number' ? t.status : 0
  taskFormError.value = ''
  showTaskModal.value = true
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

function closeTaskModal() {
  showTaskModal.value = false
  taskEditingId.value = null
}

async function submitTaskForm() {
  taskFormError.value = ''
  taskSaving.value = true
  try {
    const payload = {
      title: taskForm.title,
      description: taskForm.description || null,
      start_at: taskForm.start_at ? taskForm.start_at.replace('T', ' ') + ':00' : null,
      due_at: taskForm.due_at ? taskForm.due_at.replace('T', ' ') + ':00' : null,
      status: taskForm.status,
      project_id: route.params.id,
    }
    if (taskEditingId.value) {
      await api.put(`/me/tasks/${taskEditingId.value}`, payload)
    } else {
      await api.post('/me/tasks', payload)
    }
    closeTaskModal()
    await fetchTasks()
    await fetchProject()
  } catch (e) {
    const msg = e.response?.data?.errors
    taskFormError.value = msg
      ? Object.values(msg).flat().join(' ')
      : e.response?.data?.message || 'Có lỗi xảy ra.'
  } finally {
    taskSaving.value = false
  }
}

function confirmDeleteTask(t) {
  taskDeleteTarget.value = t
}

async function doDeleteTask() {
  if (!taskDeleteTarget.value) return
  taskDeleting.value = true
  try {
    await api.delete(`/me/tasks/${taskDeleteTarget.value.id}`)
    taskDeleteTarget.value = null
    await fetchTasks()
    await fetchProject()
  } catch (e) {
    taskFormError.value = e.response?.data?.message || 'Không xóa được.'
  } finally {
    taskDeleting.value = false
  }
}
</script>
