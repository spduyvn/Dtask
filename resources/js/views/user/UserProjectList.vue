<template>
    <div class="user-project-list">
      <div class="toolbar">
        <div class="toolbar-filters">
          <select v-model="filterTypeId" class="filter-select">
            <option :value="null">All types</option>
            <option v-for="t in projectTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
          <input
            v-model="filterName"
            type="text"
            class="filter-input"
            placeholder="Search by name..."
            @keydown.enter="applyFilters"
          />
          <button type="button" class="btn-secondary btn-sm" @click="applyFilters">Filter</button>
          <button type="button" class="btn-secondary btn-sm" @click="clearFilters">Clear</button>
        </div>
        <button class="btn-primary" @click="openCreate">+ Create project</button>
      </div>
  
      <!-- Desktop: table -->
      <div class="table-wrap list-desktop">
        <table class="table">
          <thead>
            <tr>
              <th>Project name</th>
              <th>Description</th>
              <th>Start date</th>
              <th>End date</th>
              <th>Type</th>
              <th>Total tasks</th>
              <th>Open tasks</th>
              <th width="120">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="loading-cell">Loading...</td>
            </tr>
            <tr v-else-if="!projects.length">
              <td colspan="8" class="empty-cell">No projects yet.</td>
            </tr>
            <tr
              v-else
              v-for="p in projects"
              :key="p.id"
              class="task-row task-row--blue"
            >
              <td class="task-title task-title--link" @click="openDetail(p)">{{ p.name }}</td>
              <td class="task-desc">{{ p.description || '—' }}</td>
              <td>{{ formatDate(p.start_date) }}</td>
              <td>{{ formatDate(p.end_date) }}</td>
              <td>
                <span
                  v-if="p.project_type"
                  class="badge type-tag"
                  :style="p.project_type.color ? { backgroundColor: p.project_type.color + '33', color: p.project_type.color } : {}"
                >
                  {{ p.project_type.name }}
                </span>
                <span v-else class="type-tag-empty">—</span>
              </td>
              <td>{{ p.tasks_count ?? 0 }}</td>
              <td>{{ p.open_tasks_count ?? 0 }}</td>
              <td>
                <button class="btn-sm btn-edit" @click="openEdit(p)"><img
                  src="../../assets/images/icons/edit.png" width="20px" alt="Edit" /></button>
                <button class="btn-sm btn-delete" @click="confirmDelete(p)"><img
                  src="../../assets/images/icons/delete.png" width="20px" alt="Delete" /></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile: cards -->
      <div class="list-cards list-mobile">
        <div v-if="loading" class="list-cards-loading">Loading...</div>
        <div v-else-if="!projects.length" class="list-cards-empty">No projects yet.</div>
        <a
          v-else
          v-for="p in projects"
          :key="p.id"
          href="javascript:void(0)"
          class="list-card list-card--project"
          @click.prevent="openDetail(p)"
        >
          <div class="list-card-main">
            <h3 class="list-card-title">{{ p.name }}</h3>
            <p v-if="p.description" class="list-card-desc">{{ p.description }}</p>
            <div class="list-card-badges">
              <span
                v-if="p.project_type"
                class="badge type-tag"
                :style="p.project_type.color ? { backgroundColor: p.project_type.color + '33', color: p.project_type.color } : {}"
              >
                {{ p.project_type.name }}
              </span>
              <span class="list-card-meta">{{ p.tasks_count ?? 0 }} tasks · {{ p.open_tasks_count ?? 0 }} open</span>
            </div>
          </div>
          <div class="list-card-actions" @click.stop>
            <button type="button" class="btn-card-action" @click="openEdit(p)">Edit</button>
            <button type="button" class="btn-card-action btn-card-action--danger" @click="confirmDelete(p)">Delete</button>
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
          <h3 class="modal-title">{{ editingId ? 'Update project' : 'Create project' }}</h3>
          <form @submit.prevent="submitForm" class="form">
            <div v-if="formError" class="form-error">{{ formError }}</div>
            <div class="field">
              <label>Title</label>
              <input v-model="form.title" type="text" required placeholder="Project name" />
            </div>
            <div class="field">
              <label>Description</label>
              <textarea v-model="form.description" rows="3" placeholder="Optional description"></textarea>
            </div>
            <div class="field">
              <label>Color (optional)</label>
              <div class="color-picker">
                <button
                  v-for="c in projectColorOptions"
                  :key="c.value"
                  type="button"
                  class="color-swatch"
                  :class="{ 'color-swatch--selected': form.color === c.value }"
                  :style="c.value ? { backgroundColor: c.value } : {}"
                  :title="c.label"
                  @click="form.color = c.value"
                >
                  <span v-if="!c.value" class="color-swatch-none">None</span>
                  <span v-else-if="form.color === c.value" class="color-swatch-check">✓</span>
                </button>
                <div class="color-custom">
                  <input
                    v-model="form.color"
                    type="color"
                    class="color-input-native"
                    title="Custom color"
                  />
                  <span class="color-custom-label">Custom</span>
                </div>
              </div>
            </div>
            <div class="field">
              <label>Start date</label>
              <input v-model="form.start_date" type="date" />
            </div>
            <div class="field">
              <label>End date</label>
              <input v-model="form.end_date" type="date" />
            </div>
            <div class="field">
              <label>Project type</label>
              <div class="type-tags-wrap">
                <button
                  v-for="t in projectTypes"
                  :key="t.id"
                  type="button"
                  class="type-tag type-tag--selectable"
                  :class="{ 'type-tag--selected': form.project_type_id === t.id }"
                  :style="t.color ? { borderColor: t.color, color: t.color } : {}"
                  @click="form.project_type_id = t.id"
                >
                  {{ t.name }}
                </button>
                <button type="button" class="type-tag-manage" @click="showTypesModal = true">
                  Manage types
                </button>
              </div>
            </div>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="closeModal">Cancel</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
            </div>
          </form>
        </div>
      </div>
  
      <!-- Modal Manage project types -->
      <div v-if="showTypesModal" class="modal-overlay" @click.self="showTypesModal = false">
        <div class="modal modal--wide">
          <h3 class="modal-title">Manage project types</h3>
          <div class="form type-form-inline">
            <div class="field field-inline">
              <input v-model="newTypeName" type="text" placeholder="New type name" class="input-sm" />
              <input v-model="newTypeColor" type="color" class="color-input-inline" title="Color" />
              <button type="button" class="btn-primary btn-sm" :disabled="!newTypeName.trim() || savingType" @click="addType">
                {{ savingType ? 'Adding...' : 'Add' }}
              </button>
            </div>
          </div>
          <ul class="type-list">
            <li v-for="t in projectTypes" :key="t.id" class="type-list-item">
              <span class="type-list-name" :style="t.color ? { color: t.color } : {}">{{ t.name }}</span>
              <span v-if="t.color" class="type-list-swatch" :style="{ backgroundColor: t.color }"></span>
              <template v-if="editingTypeId === t.id">
                <input v-model="editTypeName" type="text" class="input-sm" @keydown.enter="saveEditType" />
                <input v-model="editTypeColor" type="color" class="color-input-inline" />
                <button type="button" class="btn-primary btn-sm" :disabled="savingType" @click="saveEditType">Save</button>
                <button type="button" class="btn-secondary btn-sm" @click="editingTypeId = null">Cancel</button>
              </template>
              <template v-else>
                <button type="button" class="btn-sm btn-edit" @click="startEditType(t)">Edit</button>
                <button type="button" class="btn-sm btn-delete" @click="confirmDeleteType(t)">Delete</button>
              </template>
            </li>
          </ul>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="showTypesModal = false">Close</button>
          </div>
        </div>
      </div>

      <!-- Confirm Delete type -->
      <div v-if="deleteTypeTarget" class="modal-overlay" @click.self="deleteTypeTarget = null">
        <div class="modal modal-sm">
          <h3 class="modal-title">Delete type?</h3>
          <p class="modal-text">Delete <strong>{{ deleteTypeTarget?.name }}</strong>? Projects using it will need another type first.</p>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="deleteTypeTarget = null">Cancel</button>
            <button type="button" class="btn-danger" :disabled="deletingType" @click="doDeleteType">{{ deletingType ? 'Deleting...' : 'Delete' }}</button>
          </div>
        </div>
      </div>

      <!-- Confirm Delete -->
      <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
        <div class="modal modal-sm">
          <h3 class="modal-title">Delete project?</h3>
          <p class="modal-text">Are you sure you want to delete <strong>{{ deleteTarget.name }}</strong>? This action cannot be undone.</p>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="deleteTarget = null">Cancel</button>
            <button type="button" class="btn-danger" :disabled="deleting" @click="doDelete">{{ deleting ? 'Deleting...' : 'Delete' }}</button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, reactive, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import '../../styles/user-project-list.css'
  import api from '../../api/axios'
  
  const router = useRouter()

  const projects = ref([])
  const loading = ref(true)
  const pagination = ref({ current_page: 1, last_page: 1, per_page: 50 })
  const showModal = ref(false)
  const editingId = ref(null)
  const form = reactive({
    title: '',
    description: '',
    color: '',
    start_date: '',
    end_date: '',
    project_type_id: null,
  })
  const formError = ref('')
  const saving = ref(false)
  const deleteTarget = ref(null)
  const deleting = ref(false)

  const projectTypes = ref([])
  const showTypesModal = ref(false)
  const newTypeName = ref('')
  const newTypeColor = ref('#3b82f6')
  const editingTypeId = ref(null)
  const editTypeName = ref('')
  const editTypeColor = ref('')
  const deleteTypeTarget = ref(null)
  const savingType = ref(false)
  const deletingType = ref(false)

  const projectColorOptions = [
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
 
  function formatDate(str) {
    if (!str) return '—'
    const s = typeof str === 'string' ? str.split('T')[0] : str
    const d = new Date(s + 'T00:00:00')
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleDateString('en-GB')
  }

  async function fetchProjectTypes() {
    try {
      const { data } = await api.get('/me/project-types')
      projectTypes.value = data.data || []
    } catch {
      projectTypes.value = []
    }
  }

  const filterTypeId = ref(null)
  const filterName = ref('')

  function getProjectParams(page = 1) {
    const params = { page, per_page: 50 }
    if (filterTypeId.value != null && filterTypeId.value !== '') {
      params.project_type_id = filterTypeId.value
    }
    if (filterName.value && filterName.value.trim()) {
      params.name = filterName.value.trim()
    }
    return params
  }

  function applyFilters() {
    pagination.value.current_page = 1
    fetchProjects(1)
  }

  function clearFilters() {
    filterTypeId.value = null
    filterName.value = ''
    pagination.value.current_page = 1
    fetchProjects(1)
  }

  async function fetchProjects(page = 1) {
    loading.value = true
    try {
      const params = getProjectParams(page)
      const { data } = await api.get('/me/projects', { params })
      projects.value = data.data || []
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        per_page: data.per_page,
      }
    } catch (e) {
      projects.value = []
      formError.value = e.response?.data?.message || 'Failed to load list.'
    } finally {
      loading.value = false
    }
  }
  
  function openCreate() {
    editingId.value = null
    form.title = ''
    form.description = ''
    form.color = ''
    form.start_date = ''
    form.end_date = ''
    form.project_type_id = projectTypes.value[0]?.id ?? null
    formError.value = ''
    fetchProjectTypes()
    showModal.value = true
  }

  function openEdit(t) {
    editingId.value = t.id
    form.title = t.name
    form.description = t.description || ''
    form.color = t.color || ''
    form.start_date = t.start_date || ''
    form.end_date = t.end_date || ''
    form.project_type_id = t.project_type_id ?? t.project_type?.id ?? projectTypes.value[0]?.id ?? null
    formError.value = ''
    fetchProjectTypes()
    showModal.value = true
  }

  function openDetail(p) {
    router.push({ name: 'UserProjectDetail', params: { id: p.id } })
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
        name: form.title,
        description: form.description || null,
        color: form.color || null,
        start_date: form.start_date || null,
        end_date: form.end_date || null,
        project_type_id: form.project_type_id || null,
      }
      if (editingId.value) {
        await api.put(`/me/projects/${editingId.value}`, payload)
      } else {
        await api.post('/me/projects', payload)
      }
      closeModal()
      await fetchProjects(pagination.value.current_page)
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
      await api.delete(`/me/projects/${deleteTarget.value.id}`)
      deleteTarget.value = null
      await fetchProjects(pagination.value.current_page)
    } catch (e) {
      formError.value = e.response?.data?.message || 'Failed to delete.'
    } finally {
      deleting.value = false
    }
  }
  
  function goPage(page) {
    fetchProjects(page)
  }
  
  async function addType() {
    const name = newTypeName.value.trim()
    if (!name) return
    savingType.value = true
    try {
      await api.post('/me/project-types', { name, color: newTypeColor.value || null })
      newTypeName.value = ''
      newTypeColor.value = '#3b82f6'
      await fetchProjectTypes()
      form.project_type_id = form.project_type_id || projectTypes.value[projectTypes.value.length - 1]?.id
    } catch (e) {
      formError.value = e.response?.data?.message || 'Failed to add type.'
    } finally {
      savingType.value = false
    }
  }

  function startEditType(t) {
    editingTypeId.value = t.id
    editTypeName.value = t.name
    editTypeColor.value = t.color || '#3b82f6'
  }

  async function saveEditType() {
    if (!editingTypeId.value) return
    savingType.value = true
    try {
      await api.put(`/me/project-types/${editingTypeId.value}`, {
        name: editTypeName.value.trim(),
        color: editTypeColor.value || null,
      })
      editingTypeId.value = null
      await fetchProjectTypes()
    } catch (e) {
      formError.value = e.response?.data?.message || 'Failed to update type.'
    } finally {
      savingType.value = false
    }
  }

  function confirmDeleteType(t) {
    deleteTypeTarget.value = t
  }

  async function doDeleteType() {
    if (!deleteTypeTarget.value) return
    const id = deleteTypeTarget.value.id
    deletingType.value = true
    try {
      await api.delete(`/me/project-types/${id}`)
      await fetchProjectTypes()
      if (form.project_type_id === id) {
        form.project_type_id = projectTypes.value[0]?.id ?? null
      }
    } catch (e) {
      formError.value = e.response?.data?.message || 'Cannot delete: projects use this type.'
    } finally {
      deletingType.value = false
      deleteTypeTarget.value = null
    }
  }

  onMounted(() => {
    fetchProjects()
    fetchProjectTypes()
  })
  </script>
  