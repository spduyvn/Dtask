<template>
    <div class="user-note-list">
        <div class="toolbar">
            <div class="toolbar-search">
                <input
                    v-model="searchQuery"
                    type="text"
                    class="search-input"
                    placeholder="Search by name..."
                    @input="onSearchInput"
                    @keydown.enter="applySearch"
                />
                <button
                    type="button"
                    class="btn-secondary btn-sm"
                    @click="applySearch"
                >
                    Search
                </button>
                <button
                    v-if="searchQuery"
                    type="button"
                    class="btn-secondary btn-sm"
                    @click="clearSearch"
                >
                    Clear
                </button>
            </div>
            <button class="btn-primary" @click="openCreate">
                + Create note
            </button>
        </div>

        <!-- Desktop: table -->
        <div class="table-wrap list-desktop">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Color</th>
                        <th>Created at</th>
                        <th>Updated at</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="6" class="loading-cell">Loading...</td>
                    </tr>
                    <tr v-else-if="!notes.length">
                        <td colspan="6" class="empty-cell">No notes yet.</td>
                    </tr>
                    <tr v-else v-for="n in notes" :key="n.id" class="note-row">
                        <td class="note-name">
                            <router-link
                                :to="{
                                    name: 'UserNoteDetail',
                                    params: { id: n.id },
                                }"
                                class="note-link"
                            >
                                {{ n.name }}
                            </router-link>
                        </td>
                        <td>
                            <span
                                v-if="n.color"
                                class="color-swatch"
                                :style="{ backgroundColor: n.color }"
                            ></span>
                            <span class="color-text">{{ n.color || "—" }}</span>
                        </td>
                        <td>{{ formatDateTime(n.created_at) }}</td>
                        <td>{{ formatDateTime(n.updated_at) }}</td>
                        <td>
                            <button
                                class="btn-sm btn-edit"
                                @click="
                                    router.push({
                                        name: 'UserNoteDetail',
                                        params: { id: n.id },
                                    })
                                "
                            >
                                <img
                                    src="../../assets/images/icons/edit.png"
                                    width="20px"
                                    alt="Edit"
                                />
                            </button>
                            <button
                                class="btn-sm btn-delete"
                                @click="confirmDelete(n)"
                            >
                                <img
                                    src="../../assets/images/icons/delete.png"
                                    width="20px"
                                    alt="Delete"
                                />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile: cards -->
        <div class="list-cards list-mobile">
            <div v-if="loading" class="list-cards-loading">Loading...</div>
            <div v-else-if="!notes.length" class="list-cards-empty">
                No notes yet.
            </div>
            <div
                v-else
                v-for="n in notes"
                :key="n.id"
                class="list-card list-card--note"
            >
                <div
                    class="list-card-main"
                    @click="
                        router.push({
                            name: 'UserNoteDetail',
                            params: { id: n.id },
                        })
                    "
                >
                    <div class="list-card-head">
                        <span
                            v-if="n.color"
                            class="list-card-dot"
                            :style="{ backgroundColor: n.color }"
                        ></span>
                        <h3 class="list-card-title">{{ n.name }}</h3>
                    </div>
                    <p class="list-card-meta">
                        {{ formatDateTime(n.updated_at) }}
                    </p>
                </div>
                <div class="list-card-actions">
                    <button
                        type="button"
                        class="btn-card-action"
                        @click.stop="
                            router.push({
                                name: 'UserNoteDetail',
                                params: { id: n.id },
                            })
                        "
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        class="btn-card-action btn-card-action--danger"
                        @click="confirmDelete(n)"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <div v-if="pagination.last_page > 1" class="pagination">
            <button
                :disabled="pagination.current_page <= 1"
                @click="goPage(pagination.current_page - 1)"
            >
                Previous
            </button>
            <span
                >{{ pagination.current_page }} /
                {{ pagination.last_page }}</span
            >
            <button
                :disabled="pagination.current_page >= pagination.last_page"
                @click="goPage(pagination.current_page + 1)"
            >
                Next
            </button>
        </div>

        <!-- Modal Create/Edit -->
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal modal--wide">
                <h3 class="modal-title">
                    {{ editingId ? "Update note" : "Create note" }}
                </h3>
                <form @submit.prevent="submitForm" class="form">
                    <div v-if="formError" class="form-error">
                        {{ formError }}
                    </div>
                    <div class="field">
                        <label>Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Note title"
                        />
                    </div>
                    <div class="field">
                        <label>Color (optional)</label>
                        <div class="color-picker">
                            <button
                                v-for="c in noteColorOptions"
                                :key="c.value"
                                type="button"
                                class="color-swatch color-swatch--btn"
                                :class="{
                                    'color-swatch--selected':
                                        form.color === c.value,
                                }"
                                :style="
                                    c.value ? { backgroundColor: c.value } : {}
                                "
                                :title="c.label"
                                @click="form.color = c.value"
                            >
                                <span v-if="!c.value" class="color-swatch-none"
                                    >None</span
                                >
                                <span
                                    v-else-if="form.color === c.value"
                                    class="color-swatch-check"
                                    >✓</span
                                >
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
                        <label>Content</label>
                        <textarea
                            v-model="form.content"
                            rows="12"
                            placeholder="Write your note here..."
                            class="field-textarea--large"
                        ></textarea>
                    </div>
                    <div class="modal-actions">
                        <button
                            type="button"
                            class="btn-secondary"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="btn-primary"
                            :disabled="saving"
                        >
                            {{ saving ? "Saving..." : "Save" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Confirm Delete -->
        <div
            v-if="deleteTarget"
            class="modal-overlay"
            @click.self="deleteTarget = null"
        >
            <div class="modal modal-sm">
                <h3 class="modal-title">Delete note?</h3>
                <p class="modal-text">
                    Are you sure you want to delete
                    <strong>{{ deleteTarget.name }}</strong
                    >? This action cannot be undone.
                </p>
                <div class="modal-actions">
                    <button
                        type="button"
                        class="btn-secondary"
                        @click="deleteTarget = null"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="btn-danger"
                        :disabled="deleting"
                        @click="doDelete"
                    >
                        {{ deleting ? "Deleting..." : "Delete" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import { useRouter } from "vue-router";
import "../../styles/user-note-list.css";
import api from "../../api/axios";

const router = useRouter();
const notes = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1, per_page: 50 });
const searchQuery = ref("");
let searchDebounceId = null;
const showModal = ref(false);
const editingId = ref(null);
const form = reactive({
    name: "",
    color: "",
    content: "",
});
const formError = ref("");
const saving = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);

const noteColorOptions = [
    { value: "", label: "None" },
    { value: "#e94560", label: "Red" },
    { value: "#3b82f6", label: "Blue" },
    { value: "#22c55e", label: "Green" },
    { value: "#f59e0b", label: "Amber" },
    { value: "#8b5cf6", label: "Purple" },
    { value: "#ec4899", label: "Pink" },
    { value: "#14b8a6", label: "Teal" },
    { value: "#f97316", label: "Orange" },
    { value: "#6366f1", label: "Indigo" },
    { value: "#6b7280", label: "Gray" },
];

function notePreview(content) {
    if (!content || typeof content !== "string") return "—";
    const t = content.trim().replace(/\s+/g, " ");
    return t.length > 80 ? t.slice(0, 80) + "…" : t;
}

function formatDateTime(str) {
    if (!str) return "—";
    const d = new Date(str);
    if (Number.isNaN(d.getTime())) return "—";
    return d.toLocaleString("en-GB", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function onSearchInput() {
    if (searchDebounceId) clearTimeout(searchDebounceId);
    searchDebounceId = setTimeout(() => applySearch(), 300);
}

function applySearch() {
    pagination.value.current_page = 1;
    fetchNotes(1);
}

function clearSearch() {
    searchQuery.value = "";
    pagination.value.current_page = 1;
    fetchNotes(1);
}

async function fetchNotes(page = 1) {
    loading.value = true;
    try {
        const params = { page, per_page: 50 };
        if (searchQuery.value && searchQuery.value.trim()) {
            params.search = searchQuery.value.trim();
        }
        const { data } = await api.get("/me/notes", { params });
        notes.value = data.data || [];
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            per_page: data.per_page,
        };
    } catch (e) {
        notes.value = [];
        formError.value = e.response?.data?.message || "Failed to load list.";
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editingId.value = null;
    form.name = "";
    form.color = "";
    form.content = "";
    formError.value = "";
    showModal.value = true;
}

function openEdit(n) {
    editingId.value = n.id;
    form.name = n.name;
    form.color = n.color || "";
    form.content = n.content || "";
    formError.value = "";
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingId.value = null;
}

async function submitForm() {
    formError.value = "";
    saving.value = true;
    try {
        const payload = {
            name: form.name,
            color: form.color || null,
            content: form.content || null,
        };
        if (editingId.value) {
            await api.put(`/me/notes/${editingId.value}`, payload);
        } else {
            await api.post("/me/notes", payload);
        }
        closeModal();
        await fetchNotes(pagination.value.current_page);
    } catch (e) {
        const msg = e.response?.data?.errors;
        formError.value = msg
            ? Object.values(msg).flat().join(" ")
            : e.response?.data?.message || "An error occurred.";
    } finally {
        saving.value = false;
    }
}

function confirmDelete(n) {
    deleteTarget.value = n;
}

async function doDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        await api.delete(`/me/notes/${deleteTarget.value.id}`);
        deleteTarget.value = null;
        await fetchNotes(pagination.value.current_page);
    } catch (e) {
        formError.value = e.response?.data?.message || "Failed to delete.";
    } finally {
        deleting.value = false;
    }
}

function goPage(page) {
    fetchNotes(page);
}

onMounted(() => fetchNotes());
</script>
