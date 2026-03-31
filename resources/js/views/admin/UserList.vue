<template>
  <div class="user-list">
    <div class="toolbar">
      <button class="btn-primary" @click="openCreate">+ Add user</button>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Full name</th>
            <th>Email</th>
            <th>Created at</th>
            <th width="140">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="loading-cell">Loading...</td>
          </tr>
          <tr v-else-if="!users.length">
            <td colspan="5" class="empty-cell">No users yet.</td>
          </tr>
          <tr v-else v-for="u in users" :key="u.id">
            <td>{{ u.id }}</td>
            <td>{{ u.name }}</td>
            <td>{{ u.email }}</td>
            <td>{{ formatDate(u.created_at) }}</td>
            <td>
              <button class="btn-sm btn-edit" @click="openEdit(u)"><img
                src="../../assets/images/icons/edit.png" width="20px" alt="Edit" /></button>
              <button class="btn-sm btn-delete" @click="confirmDelete(u)"><img
                src="../../assets/images/icons/delete.png" width="20px" alt="Delete" /></button>
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
        <h3 class="modal-title">{{ editingId ? 'Update user' : 'Add user' }}</h3>
        <form @submit.prevent="submitForm" class="form">
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="field">
            <label>Full name</label>
            <input v-model="form.name" type="text" required placeholder="John Doe" />
          </div>
          <div class="field">
            <label>Email</label>
            <input v-model="form.email" type="email" required placeholder="email@example.com" :readonly="!!editingId" />
          </div>
          <div class="field">
            <label>{{ editingId ? 'New password (leave blank to keep current)' : 'Password' }}</label>
            <input
              v-model="form.password"
              type="password"
              :placeholder="editingId ? '••••••••' : 'Enter password'"
            />
          </div>
          <div v-if="!editingId" class="field">
            <label>Confirm password</label>
            <input v-model="form.password_confirmation" type="password" placeholder="Re-enter password" />
          </div>
          <div v-else-if="form.password" class="field">
            <label>Confirm new password</label>
            <input v-model="form.password_confirmation" type="password" placeholder="Re-enter password" />
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
        <h3 class="modal-title">Delete user?</h3>
        <p class="modal-text">Are you sure you want to delete <strong>{{ deleteTarget.name }}</strong> ({{ deleteTarget.email }})? This action cannot be undone.</p>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="deleteTarget = null">Cancel</button>
          <button type="button" class="btn-danger" :disabled="deleting" @click="doDelete">{{ deleting ? 'Deleting...' : 'Delete' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import '../../styles/user-list.css'
import { ref, reactive, onMounted } from 'vue'
import api from '../../api/axios'

const users = ref([])
const loading = ref(true)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15 })
const showModal = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const formError = ref('')
const saving = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/users', { params: { page, per_page: 15 } })
    users.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
    }
  } catch (e) {
    users.value = []
    formError.value = e.response?.data?.message || 'Failed to load list.'
  } finally {
    loading.value = false
  }
}

function formatDate(str) {
  if (!str) return '—'
  const d = new Date(str)
  return d.toLocaleDateString('en-GB')
}

function openCreate() {
  editingId.value = null
  form.name = ''
  form.email = ''
  form.password = ''
  form.password_confirmation = ''
  formError.value = ''
  showModal.value = true
}

function openEdit(u) {
  editingId.value = u.id
  form.name = u.name
  form.email = u.email
  form.password = ''
  form.password_confirmation = ''
  formError.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingId.value = null
}

async function submitForm() {
  formError.value = ''
  if (!editingId.value && form.password !== form.password_confirmation) {
    formError.value = 'Password and confirmation do not match.'
    return
  }
  if (editingId.value && form.password && form.password !== form.password_confirmation) {
    formError.value = 'New password and confirmation do not match.'
    return
  }

  saving.value = true
  try {
    if (editingId.value) {
      const payload = { name: form.name }
      if (form.password) {
        payload.password = form.password
        payload.password_confirmation = form.password_confirmation
      }
      await api.put(`/users/${editingId.value}`, payload)
    } else {
      await api.post('/users', {
        name: form.name,
        email: form.email,
        password: form.password,
        password_confirmation: form.password_confirmation,
      })
    }
    closeModal()
    await fetchUsers(pagination.value.current_page)
  } catch (e) {
    const msg = e.response?.data?.errors
    formError.value = msg
      ? Object.values(msg).flat().join(' ')
      : (e.response?.data?.message || 'An error occurred.')
  } finally {
    saving.value = false
  }
}

function confirmDelete(u) {
  deleteTarget.value = u
}

async function doDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await api.delete(`/users/${deleteTarget.value.id}`)
    deleteTarget.value = null
    await fetchUsers(pagination.value.current_page)
  } catch (e) {
    formError.value = e.response?.data?.message || 'Failed to delete.'
  } finally {
    deleting.value = false
  }
}

function goPage(page) {
  fetchUsers(page)
}

onMounted(() => fetchUsers())
</script>
