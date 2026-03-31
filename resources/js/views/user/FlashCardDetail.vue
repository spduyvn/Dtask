<template>
    <div class="flash-card-study">
        <div class="study-header">
            <h2>Card Detail</h2>
            <button class="btn-secondary" @click="goBack">Back to List</button>
        </div>

        <div v-if="loading" class="study-message">
            <div class="spinner"></div>
            <p>Loading card...</p>
        </div>

        <div v-else-if="!card" class="study-message done-message">
            <h3>Card not found</h3>
            <p>
                The flash card you are looking for does not exist or was
                deleted.
            </p>
            <div class="done-actions">
                <button
                    class="btn-primary"
                    @click="router.push({ name: 'FlashCardGroups' })"
                >
                    Go to Groups
                </button>
            </div>
        </div>

        <div v-else class="study-card-container">
            <div class="card-viewer-container">
                <FlashCard
                    :card="card"
                    :forceFlipped="forceFlipped"
                    @flipped="onCardFlipped"
                />
            </div>

            <p class="flip-hint" v-if="!forceFlipped">
                Tap the card to reveal the answer
            </p>
            <p class="flip-hint" v-else>Tap the card to hide the answer</p>

            <div class="card-info">
                <p><strong>Level:</strong> {{ card.level }}</p>
                <p>
                    <strong>Last Reviewed:</strong>
                    {{ formatDateTime(card.last_reviewed_at) }}
                </p>
                <p>
                    <strong>Next Review:</strong>
                    {{ formatDateTime(card.next_review_at) }}
                </p>
            </div>

            <div class="study-controls">
                <button
                    class="btn-primary edit-button"
                    @click="isEditing = true"
                >
                    Edit Card
                </button>
            </div>
        </div>

        <!-- Modal Edit -->
        <div
            v-if="isEditing"
            class="modal-overlay"
            @click.self="isEditing = false"
        >
            <div class="modal modal--wide">
                <h3 class="modal-title">Update card</h3>
                <form @submit.prevent="submitEdit" class="form">
                    <div v-if="formError" class="form-error">
                        {{ formError }}
                    </div>
                    <div class="field">
                        <label>Question</label>
                        <textarea
                            v-model="form.question"
                            rows="3"
                            required
                            class="field-textarea--large"
                            placeholder="Front of card..."
                        ></textarea>
                    </div>
                    <div class="field">
                        <label>Answer</label>
                        <textarea
                            v-model="form.answer"
                            rows="4"
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
                                    (form.existingImage && !form.removeImage)
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
                            @click="isEditing = false"
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
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import api from "../../api/axios";
import FlashCard from "../../components/flashcards/FlashCard.vue";
import ProtectedImage from "../../components/flashcards/ProtectedImage.vue";
import "../../styles/flash-card-list.css"; // Reuse for modal styles

const router = useRouter();
const route = useRoute();
const cardId = Number(route.params.id);

const card = ref(null);
const loading = ref(true);
const forceFlipped = ref(false);

const isEditing = ref(false);
const saving = ref(false);
const formError = ref("");
const form = reactive({
    question: "",
    answer: "",
    imageFile: null,
    imagePreview: null,
    existingImage: null,
    removeImage: false,
});
const fileInputRef = ref(null);
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

function goBack() {
    if (card.value && card.value.flash_card_group_id) {
        router.push({
            name: "FlashCardList",
            params: { groupId: card.value.flash_card_group_id },
        });
    } else {
        router.push({ name: "FlashCardGroups" });
    }
}

async function fetchCard() {
    loading.value = true;
    try {
        const { data } = await api.get(`/me/flash-cards/${cardId}`);
        card.value = data.flashCard;
        // prep form
        form.question = card.value.question;
        form.answer = card.value.answer;
        form.existingImage = card.value.image_url;
        form.imageFile = null;
        form.imagePreview = null;
        form.removeImage = false;
        if (fileInputRef.value) fileInputRef.value.value = "";
    } catch (e) {
        console.error("Failed to load card", e);
    } finally {
        loading.value = false;
    }
}

function onCardFlipped() {
    forceFlipped.value = !forceFlipped.value;
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

async function submitEdit() {
    formError.value = "";
    saving.value = true;
    try {
        const formData = new FormData();
        formData.append("question", form.question);
        formData.append("answer", form.answer);
        formData.append("flash_card_group_id", card.value.flash_card_group_id);

        if (form.imageFile) {
            formData.append("image", form.imageFile);
        }
        if (form.removeImage) {
            formData.append("remove_image", "true");
        }
        formData.append("_method", "PUT");

        const { data } = await api.post(`/me/flash-cards/${cardId}`, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        card.value = data.flashCard;
        isEditing.value = false;
        forceFlipped.value = false;
    } catch (e) {
        const msg = e.response?.data?.errors;
        formError.value = msg
            ? Object.values(msg).flat().join(" ")
            : e.response?.data?.message || "An error occurred.";
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    fetchCard();
});
</script>

<style scoped>
.flash-card-study {
    padding: 16px;
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 120px);
}

.study-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.btn-secondary {
    padding: 10px 18px;
    background: #f3f4f6;
    color: #111827;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
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
}

.study-message {
    text-align: center;
    padding: 4rem 2rem;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.done-message h3 {
    font-size: 1.5rem;
    color: #ef4444;
    margin-bottom: 1rem;
}

.done-message p {
    margin-bottom: 2rem;
    color: #6b7280;
}

.done-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
    max-width: 200px;
}

.study-card-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.card-viewer-container {
    width: 100%;
    max-width: 400px; /* Keep it nicely constrained like a real card */
    margin: 20px auto;
}

.flip-hint {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 10px;
    text-align: center;
}

.card-info {
    margin-top: 30px;
    width: 100%;
    max-width: 400px;
    background: var(--bg-card, #ffffff);
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
}

.card-info p {
    margin: 8px 0;
    font-size: 0.95rem;
    color: #374151;
    display: flex;
    justify-content: space-between;
}

.card-info strong {
    color: #111827;
}

.study-controls {
    margin-top: auto;
    display: flex;
    justify-content: center;
    padding: 20px 0;
    width: 100%;
}

.edit-button {
    width: 100%;
    max-width: 400px;
}

/* Dark mode overrides */
[data-theme="dark"] .card-info {
    background: #1f2937;
    border-color: #374151;
}

[data-theme="dark"] .card-info p {
    color: #d1d5db;
}

[data-theme="dark"] .card-info strong {
    color: #e5e7eb;
}

[data-theme="dark"] .flip-hint {
    color: #9ca3af;
}

[data-theme="dark"] .study-message {
    background: #1f2937;
    border-color: #374151;
}

[data-theme="dark"] .spinner {
    border-color: #374151;
    border-top-color: #3b82f6;
}
</style>
