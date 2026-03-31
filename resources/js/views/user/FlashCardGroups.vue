<template>
    <div class="flash-card-groups">
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
                + Create group
            </button>
        </div>

        <!-- Desktop: table -->
        <div class="table-wrap list-desktop">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Cards</th>
                        <th>Created at</th>
                        <th>Updated at</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="6" class="loading-cell">Loading...</td>
                    </tr>
                    <tr v-else-if="!groups.length">
                        <td colspan="6" class="empty-cell">No groups yet.</td>
                    </tr>
                    <tr
                        v-else
                        v-for="g in groups"
                        :key="g.id"
                        class="group-row"
                    >
                        <td class="group-name">
                            <router-link
                                :to="{
                                    name: 'FlashCardList',
                                    params: { groupId: g.id },
                                }"
                                class="group-link"
                            >
                                {{ g.name }}
                            </router-link>
                        </td>
                        <td>
                            <span class="desc-text">{{
                                g.description || "—"
                            }}</span>
                        </td>
                        <td>
                            <span class="count-badge">{{
                                g.flash_cards_count || 0
                            }}</span>
                        </td>
                        <td>{{ formatDateTime(g.created_at) }}</td>
                        <td>{{ formatDateTime(g.updated_at) }}</td>
                        <td>
                            <button
                                class="btn-sm btn-edit"
                                @click="openEdit(g)"
                            >
                                <img
                                    src="../../assets/images/icons/edit.png"
                                    width="20px"
                                    alt="Edit"
                                />
                            </button>
                            <button
                                class="btn-sm btn-delete"
                                @click="confirmDelete(g)"
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
            <div v-else-if="!groups.length" class="list-cards-empty">
                No groups yet.
            </div>
            <div
                v-else
                v-for="g in groups"
                :key="g.id"
                class="list-card list-card--group"
            >
                <div
                    class="list-card-main"
                    @click="
                        router.push({
                            name: 'FlashCardList',
                            params: { groupId: g.id },
                        })
                    "
                >
                    <div class="list-card-head">
                        <h3 class="list-card-title">{{ g.name }}</h3>
                    </div>
                    <p class="list-card-meta">
                        {{ g.flash_cards_count || 0 }} cards ·
                        {{ formatDateTime(g.updated_at) }}
                    </p>
                </div>
                <div class="list-card-actions">
                    <button
                        type="button"
                        class="btn-card-action"
                        @click.stop="openEdit(g)"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        class="btn-card-action btn-card-action--danger"
                        @click="confirmDelete(g)"
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
                    {{ editingId ? "Update group" : "Create group" }}
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
                            placeholder="Group name"
                        />
                    </div>
                    <div class="field">
                        <label>Description (optional)</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            placeholder="Write a description..."
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
                <h3 class="modal-title">Delete group?</h3>
                <p class="modal-text">
                    Are you sure you want to delete
                    <strong>{{ deleteTarget.name }}</strong
                    >? All cards inside will be removed. This action cannot be
                    undone.
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
import "../../styles/flash-card-groups.css";
import api from "../../api/axios";

const router = useRouter();
const groups = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1, per_page: 50 });
const searchQuery = ref("");
let searchDebounceId = null;
const showModal = ref(false);
const editingId = ref(null);
const form = reactive({
    name: "",
    description: "",
});
const formError = ref("");
const saving = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);

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
    fetchGroups(1);
}

function clearSearch() {
    searchQuery.value = "";
    pagination.value.current_page = 1;
    fetchGroups(1);
}

async function fetchGroups(page = 1) {
    loading.value = true;
    try {
        const params = { page, per_page: 50 };
        if (searchQuery.value && searchQuery.value.trim()) {
            params.search = searchQuery.value.trim();
        }
        const { data } = await api.get("/me/flash-card-groups", { params });
        groups.value = data.data || [];
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            per_page: data.per_page,
        };
    } catch (e) {
        groups.value = [];
        formError.value = e.response?.data?.message || "Failed to load list.";
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editingId.value = null;
    form.name = "";
    form.description = "";
    formError.value = "";
    showModal.value = true;
}

function openEdit(g) {
    editingId.value = g.id;
    form.name = g.name;
    form.description = g.description || "";
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
            description: form.description || null,
        };
        if (editingId.value) {
            await api.put(`/me/flash-card-groups/${editingId.value}`, payload);
        } else {
            await api.post("/me/flash-card-groups", payload);
        }
        closeModal();
        await fetchGroups(pagination.value.current_page);
    } catch (e) {
        const msg = e.response?.data?.errors;
        formError.value = msg
            ? Object.values(msg).flat().join(" ")
            : e.response?.data?.message || "An error occurred.";
    } finally {
        saving.value = false;
    }
}

function confirmDelete(g) {
    deleteTarget.value = g;
}

async function doDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        await api.delete(`/me/flash-card-groups/${deleteTarget.value.id}`);
        deleteTarget.value = null;
        await fetchGroups(pagination.value.current_page);
    } catch (e) {
        formError.value = e.response?.data?.message || "Failed to delete.";
    } finally {
        deleting.value = false;
    }
}

function goPage(page) {
    fetchGroups(page);
}

onMounted(() => fetchGroups());
</script>
