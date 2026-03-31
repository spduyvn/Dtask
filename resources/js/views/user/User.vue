<template>
  <div class="user-task-list">
    <div class="toolbar">
      <button class="btn-primary" @click="openCreate">+ Tạo task</button>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Tiêu đề</th>
            <th>Mô tả</th>
            <th>Bắt đầu</th>
            <th>Hết hạn</th>
            <th>Trạng thái</th>
            <th width="200">Quản lý trạng thái</th>
            <th width="120">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="loading-cell">Đang tải...</td>
          </tr>
          <tr v-else-if="!tasks.length">
            <td colspan="7" class="empty-cell">Chưa có task nào.</td>
          </tr>
          <tr
            v-else
            v-for="t in tasks"
            :key="t.id"
            :class="['task-row', 'task-row--' + getTaskRowColor(t)]"
          >
            <td class="task-title">{{ t.title }}</td>
            <td class="task-desc">{{ t.description || '—' }}</td>
            <td>{{ formatDate(t.start_date) }}</td>
            <td>{{ formatDate(t.due_date) }}</td>
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
              <button class="btn-sm btn-edit" @click="openEdit(t)">Sửa</button>
              <button class="btn-sm btn-delete" @click="confirmDelete(t)">Xóa</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="pagination.last_page > 1" class="pagination">
      <button :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">Trước</button>
      <span>{{ pagination.current_page }} / {{ pagination.last_page }}</span>
      <button :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">Sau</button>
    </div>

    <!-- Modal Create/Edit -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <h3 class="modal-title">{{ editingId ? 'Cập nhật task' : 'Tạo task' }}</h3>
        <form @submit.prevent="submitForm" class="form">
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="field">
            <label>Tiêu đề</label>
            <input v-model="form.title" type="text" required placeholder="Tên task" />
          </div>
          <div class="field">
            <label>Mô tả</label>
            <textarea v-model="form.description" rows="3" placeholder="Mô tả (tùy chọn)"></textarea>
          </div>
          <div class="field">
            <label>Ngày bắt đầu</label>
            <input v-model="form.start_date" type="date" />
          </div>
          <div class="field">
            <label>Ngày hết hạn</label>
            <input v-model="form.due_date" type="date" />
          </div>
          <div class="field">
            <label>Trạng thái</label>
            <select v-model.number="form.status" class="field-select">
              <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="closeModal">Hủy</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Đang lưu...' : 'Lưu' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete -->
    <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
      <div class="modal modal-sm">
        <h3 class="modal-title">Xóa task?</h3>
        <p class="modal-text">Bạn có chắc muốn xóa <strong>{{ deleteTarget.title }}</strong>? Hành động này không thể hoàn tác.</p>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="deleteTarget = null">Hủy</button>
          <button type="button" class="btn-danger" :disabled="deleting" @click="doDelete">{{ deleting ? 'Đang xóa...' : 'Xóa' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import '../../styles/user-task-list.css'
import api from '../../api/axios'

const STATUS_OPTIONS = [
  { value: 0, label: 'Chưa bắt đầu', icon: '○', color: 'gray' },
  { value: 1, label: 'Đang làm', icon: '▶', color: 'green' },
  { value: 2, label: 'Tạm dừng', icon: '⏸', color: 'orange' },
  { value: 3, label: 'Hoàn thành', icon: '✓', color: 'blue' },
]

const tasks = ref([])
const loading = ref(true)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 50 })
const showModal = ref(false)
const editingId = ref(null)
const form = reactive({ title: '', description: '', start_date: '', due_date: '', status: 0 })
const formError = ref('')
const saving = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)
const updatingStatusId = ref(null)

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

/** Màu dòng: đỏ nếu quá hạn và chưa hoàn thành; còn lại theo status */
function getTaskRowColor(task) {
  const status = task.status ?? 0
  const today = todayStr()
  const due = task.due_date ? (task.due_date.split && task.due_date.split('T')[0]) : null
  if (due && due <= today && status !== 3) return 'red'
  return getStatusColor(status)
}

function formatDate(str) {
  if (!str) return '—'
  const s = typeof str === 'string' ? str.split('T')[0] : str
  const d = new Date(s + 'T00:00:00')
  return d.toLocaleDateString('vi-VN')
}

async function fetchTasks(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/me/tasks', { params: { page, per_page: 50 } })
    tasks.value = data.data || []
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
    }
  } catch (e) {
    tasks.value = []
    formError.value = e.response?.data?.message || 'Không tải được danh sách.'
  } finally {
    loading.value = false
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
    formError.value = e.response?.data?.message || 'Không cập nhật được trạng thái.'
  } finally {
    updatingStatusId.value = null
  }
}

function openCreate() {
  editingId.value = null
  form.title = ''
  form.description = ''
  form.start_date = ''
  form.due_date = ''
  form.status = 0
  formError.value = ''
  showModal.value = true
}

function openEdit(t) {
  editingId.value = t.id
  form.title = t.title
  form.description = t.description || ''
  form.start_date = t.start_date ? (t.start_date.split && t.start_date.split('T')[0]) : ''
  form.due_date = t.due_date ? (t.due_date.split && t.due_date.split('T')[0]) : ''
  form.status = typeof t.status === 'number' ? t.status : 0
  formError.value = ''
  showModal.value = true
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
      start_date: form.start_date || null,
      due_date: form.due_date || null,
      status: form.status,
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
      : (e.response?.data?.message || 'Có lỗi xảy ra.')
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
    formError.value = e.response?.data?.message || 'Không xóa được.'
  } finally {
    deleting.value = false
  }
}

function goPage(page) {
  fetchTasks(page)
}

onMounted(() => fetchTasks())
</script>

<style scoped>
.user-task-list {
  max-width: 100%;
}

.toolbar {
  margin-bottom: 20px;
}

.btn-primary {
  padding: 10px 20px;
  background: #e94560;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #d63d56;
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.table-wrap {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  overflow: hidden;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 14px 18px;
  text-align: left;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.table th {
  background: rgba(0, 0, 0, 0.2);
  color: #94a3b8;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.table td {
  color: #e2e8f0;
  font-size: 14px;
}

.task-row--red {
  background: rgba(239, 68, 68, 0.12);
  border-left: 4px solid #ef4444;
}

.task-row--gray {
  background: rgba(100, 116, 139, 0.15);
  border-left: 4px solid #64748b;
}

.task-row--green {
  background: rgba(34, 197, 94, 0.12);
  border-left: 4px solid #22c55e;
}

.task-title {
  font-weight: 600;
}

.task-desc {
  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}

.badge--red {
  background: rgba(239, 68, 68, 0.25);
  color: #fca5a5;
}

.badge--gray {
  background: rgba(100, 116, 139, 0.3);
  color: #94a3b8;
}

.badge--green {
  background: rgba(34, 197, 94, 0.25);
  color: #587c64;
}

.badge--orange {
  background: rgba(249, 115, 22, 0.25);
  color: #fdba74;
}

.badge--blue {
  background: rgba(59, 130, 246, 0.25);
  color: #93c5fd;
}

.task-row--orange {
  background: rgba(249, 115, 22, 0.1);
  border-left: 4px solid #f97316;
}

.task-row--blue {
  background: rgba(59, 130, 246, 0.1);
  border-left: 4px solid #3b82f6;
}

.status-actions {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.btn-icon {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.05);
  color: #94a3b8;
  cursor: pointer;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.btn-icon:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #e2e8f0;
}

.btn-icon--active {
  background: rgba(233, 69, 96, 0.2);
  color: #e94560;
  border-color: rgba(233, 69, 96, 0.4);
}

.btn-icon-symbol {
  font-size: 14px;
  line-height: 1;
}

.loading-cell,
.empty-cell {
  text-align: center;
  color: #64748b;
  padding: 40px;
}

.btn-sm {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  margin-right: 8px;
  border: none;
  transition: opacity 0.2s;
}

.btn-edit {
  background: rgba(59, 130, 246, 0.2);
  color: #60a5fa;
}

.btn-delete {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
}

.pagination {
  margin-top: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.pagination button {
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.08);
  color: #e2e8f0;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
}

.pagination button:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.12);
}

.pagination button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 28px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
}

.modal-sm {
  max-width: 400px;
}

.modal-title {
  margin: 0 0 20px;
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.modal-text {
  margin: 0 0 24px;
  color: #6b7280;
  font-size: 14px;
  line-height: 1.5;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-error {
  padding: 10px 14px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: 8px;
  color: #b91c1c;
  font-size: 13px;
}

.field label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.field input,
.field textarea {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #ffffff;
  color: #111827;
  font-size: 14px;
}

.field textarea {
  resize: vertical;
  min-height: 80px;
}

.field input:focus,
.field textarea:focus,
.field select:focus {
  outline: none;
  border-color: #e94560;
  box-shadow: 0 0 0 2px rgba(233, 69, 96, 0.2);
}

.field-select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #ffffff;
  color: #111827;
  font-size: 14px;
  cursor: pointer;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 8px;
}

.btn-secondary {
  padding: 10px 18px;
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-danger {
  padding: 10px 18px;
  background: rgba(239, 68, 68, 0.12);
  color: #b91c1c;
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
}

.btn-danger:hover {
  background: rgba(239, 68, 68, 0.2);
}

.btn-danger:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
