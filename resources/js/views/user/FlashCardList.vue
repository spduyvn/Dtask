<template>
    <div class="flash-card-list">
        <div class="toolbar">
            <div class="toolbar-search">
                <input
                    v-model="searchQuery"
                    type="text"
                    class="search-input"
                    placeholder="Search by question..."
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
            <div class="toolbar-actions">
                <button
                    class="btn-back"
                    @click="router.push({ name: 'FlashCardGroups' })"
                >
                    ← Back to Groups
                </button>
                <button
                    class="btn-study"
                    @click="
                        router.push({
                            name: 'FlashCardStudy',
                            query: { group_id: groupId },
                        })
                    "
                >
                    Study Now
                </button>
                <button class="btn-primary" @click="openCreate">
                    + Create card
                </button>
            </div>
        </div>

        <!-- Desktop: table -->
        <div class="table-wrap list-desktop">
            <table class="table">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Answer</th>
                        <th>Level</th>
                        <th>Last Reviewed</th>
                        <th>Next Review</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="6" class="loading-cell">Loading...</td>
                    </tr>
                    <tr v-else-if="!cards.length">
                        <td colspan="6" class="empty-cell">
                            No flash cards in this group yet.
                        </td>
                    </tr>
                    <tr v-else v-for="c in cards" :key="c.id" class="card-row">
                        <td class="question-cell">
                            <router-link
                                :to="{
                                    name: 'FlashCardDetail',
                                    params: { id: c.id },
                                }"
                                class="card-link"
                            >
                                {{ truncate(c.question) }}
                            </router-link>
                        </td>
                        <td>{{ truncate(c.answer) }}</td>
                        <td>
                            <span class="level-badge">Lv.{{ c.level }}</span>
                        </td>
                        <td>{{ formatDateTime(c.last_reviewed_at) }}</td>
                        <td>{{ formatDateTime(c.next_review_at) }}</td>
                        <td>
                            <button
                                class="btn-sm btn-edit"
                                @click="openEdit(c)"
                            >
                                <img
                                    src="../../assets/images/icons/edit.png"
                                    width="20px"
                                    alt="Edit"
                                />
                            </button>
                            <button
                                class="btn-sm btn-delete"
                                @click="confirmDelete(c)"
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
            <div v-else-if="!cards.length" class="list-cards-empty">
                No flash cards in this group yet.
            </div>
            <div
                v-else
                v-for="c in cards"
                :key="c.id"
                class="list-card list-card--flashcard"
            >
                <div
                    class="list-card-main"
                    @click="
                        router.push({
                            name: 'FlashCardDetail',
                            params: { id: c.id },
                        })
                    "
                >
                    <div class="list-card-head">
                        <h3 class="list-card-title">
                            {{ truncate(c.question, 60) }}
                        </h3>
                    </div>
                    <p class="list-card-meta">
                        Lv.{{ c.level }} ·
                        {{ formatDateTime(c.updated_at) }}
                    </p>
                </div>
                <div class="list-card-actions">
                    <button
                        type="button"
                        class="btn-card-action"
                        @click.stop="openEdit(c)"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        class="btn-card-action btn-card-action--danger"
                        @click="confirmDelete(c)"
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
                    {{ editingId ? "Update card" : "Create card" }}
                </h3>
                <form @submit.prevent="submitForm" class="form">
                    <div v-if="formError" class="form-error">
                        {{ formError }}
                    </div>
                    <div class="field">
                        <label>Question</label>
                        <textarea
                            v-model="form.question"
                            rows="4"
                            required
                            class="field-textarea--large"
                            placeholder="Front of card..."
                        ></textarea>
                    </div>
                    <div class="field">
                        <label>Answer</label>
                        <textarea
                            v-model="form.answer"
                            rows="6"
                            required
                            class="field-textarea--large"
                            placeholder="Back of card..."
                        ></textarea>
                    </div>
                    <div class="field">
                        <div
                            class="drop-zone"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="onDrop"
                            @paste="onPaste"
                            :class="{ 'is-dragging': isDragging }"
                        >
                            <input
                                type="file"
                                @change="onFileChange"
                                accept="image/*"
                                ref="fileInputRef"
                                class="file-input"
                            />
                            <div class="drop-hint">
                                Drop image here or paste (Ctrl+V)
                            </div>

                            <div
                                v-if="
                                    form.imagePreview ||
                                    (editingId &&
                                        form.existingImage &&
                                        !form.removeImage)
                                "
                                class="image-preview-container"
                            >
                                <ProtectedImage
                                    :src="
                                        form.imagePreview || form.existingImage
                                    "
                                    class="image-preview"
                                />
                                <button
                                    type="button"
                                    class="btn-sm btn-delete btn-remove-image"
                                    @click="removeImage"
                                >
                                    Remove Image
                                </button>
                            </div>
                        </div>
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
                <h3 class="modal-title">Delete card?</h3>
                <p class="modal-text">
                    Are you sure you want to delete this card? This action
                    cannot be undone.
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
import { useRouter, useRoute } from "vue-router";
import "../../styles/flash-card-list.css";
import api from "../../api/axios";
import ProtectedImage from "../../components/flashcards/ProtectedImage.vue";

const router = useRouter();
const route = useRoute();
const groupId = Number(route.params.groupId);

const cards = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1, per_page: 50 });
const searchQuery = ref("");
let searchDebounceId = null;
const showModal = ref(false);
const editingId = ref(null);
const form = reactive({
    question: "",
    answer: "",
    imageFile: null,
    imagePreview: null,
    existingImage: null,
    removeImage: false,
});
const fileInputRef = ref(null);
const formError = ref("");
const saving = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);
const isDragging = ref(false);

function handleFile(file) {
    if (!file || !file.type.startsWith("image/")) return;
    form.imageFile = file;
    form.imagePreview = URL.createObjectURL(file);
    form.removeImage = false;
}

function onPaste(e) {
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    for (const item of items) {
        if (item.type.indexOf("image") !== -1) {
            const blob = item.getAsFile();
            handleFile(blob);
        }
    }
}

function onDrop(e) {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    handleFile(file);
}

function truncate(content, max = 50) {
    if (!content) return "—";
    return content.length > max ? content.slice(0, max) + "…" : content;
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
    fetchCards(1);
}

function clearSearch() {
    searchQuery.value = "";
    pagination.value.current_page = 1;
    fetchCards(1);
}

async function fetchCards(page = 1) {
    loading.value = true;
    try {
        const params = { page, per_page: 50, group_id: groupId };
        if (searchQuery.value && searchQuery.value.trim()) {
            params.search = searchQuery.value.trim();
        }
        const { data } = await api.get("/me/flash-cards", { params });
        cards.value = data.data || [];
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            per_page: data.per_page,
        };
    } catch (e) {
        cards.value = [];
        formError.value = e.response?.data?.message || "Failed to load list.";
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editingId.value = null;
    form.question = "";
    form.answer = "";
    form.imageFile = null;
    form.imagePreview = null;
    form.existingImage = null;
    form.removeImage = false;
    if (fileInputRef.value) fileInputRef.value.value = "";
    formError.value = "";
    showModal.value = true;
}

function openEdit(c) {
    editingId.value = c.id;
    form.question = c.question;
    form.answer = c.answer;
    form.imageFile = null;
    form.imagePreview = null;
    form.existingImage = c.image_url;
    form.removeImage = false;
    if (fileInputRef.value) fileInputRef.value.value = "";
    formError.value = "";
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingId.value = null;
}

function onFileChange(e) {
    const file = e.target.files[0];
    handleFile(file);
}

function removeImage() {
    form.imageFile = null;
    form.imagePreview = null;
    form.removeImage = true;
    if (fileInputRef.value) fileInputRef.value.value = "";
}

async function submitForm() {
    formError.value = "";
    saving.value = true;
    try {
        const formData = new FormData();
        formData.append("question", form.question);
        formData.append("answer", form.answer);
        formData.append("flash_card_group_id", groupId);

        if (form.imageFile) {
            formData.append("image", form.imageFile);
        }
        if (form.removeImage) {
            formData.append("remove_image", "true");
        }

        if (editingId.value) {
            formData.append("_method", "PUT");
            await api.post(`/me/flash-cards/${editingId.value}`, formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
        } else {
            await api.post("/me/flash-cards", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
        }
        closeModal();
        await fetchCards(pagination.value.current_page);
    } catch (e) {
        const msg = e.response?.data?.errors;
        formError.value = msg
            ? Object.values(msg).flat().join(" ")
            : e.response?.data?.message || "An error occurred.";
    } finally {
        saving.value = false;
    }
}

function confirmDelete(c) {
    deleteTarget.value = c;
}

async function doDelete() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    try {
        await api.delete(`/me/flash-cards/${deleteTarget.value.id}`);
        deleteTarget.value = null;
        await fetchCards(pagination.value.current_page);
    } catch (e) {
        formError.value = e.response?.data?.message || "Failed to delete.";
    } finally {
        deleting.value = false;
    }
}

function goPage(page) {
    fetchCards(page);
}

onMounted(() => fetchCards());
</script>
