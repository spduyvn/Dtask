<template>
    <div class="flash-card-study">
        <div class="study-header">
            <h2>Study Session</h2>
            <button class="btn-secondary" @click="goBack">Quit</button>
        </div>

        <FlashCardGroupSelector
            v-if="!selectedGroupId"
            @select="onGroupSelected"
        />

        <div v-else-if="loading" class="study-message">
            <div class="spinner"></div>
            <p>Loading your due cards...</p>
        </div>

        <div
            v-else-if="cards.length === 0 || currentIndex >= cards.length"
            class="study-message done-message"
        >
            <h3>🎉 You're all caught up!</h3>
            <p>No more due cards to study in this group right now.</p>
            <div class="done-actions">
                <button class="btn-primary" @click="goBack">
                    Back to Group
                </button>
            </div>
        </div>

        <div v-else class="study-card-container">
            <div class="progress-bar">
                <div
                    class="progress-fill"
                    :style="{ width: progressPercentage + '%' }"
                ></div>
            </div>
            <p class="progress-text">
                Card {{ currentIndex + 1 }} of {{ cards.length }}
            </p>

            <FlashCardSwiper
                v-model="currentIndex"
                :cards="cards"
                @swiped-left="onSwiped('left')"
                @swiped-right="onSwiped('right')"
                class="swiper-wrapper-container"
            >
                <template #default="{ card, isActive }">
                    <FlashCard
                        :card="card"
                        :forceFlipped="isActive ? showAnswer : false"
                        @flipped="() => onCardFlipped(card.id)"
                    />
                </template>
            </FlashCardSwiper>

            <transition name="fade-up">
                <div v-if="showAnswer" class="study-controls feedback-controls">
                    <p class="feedback-ask">How well did you know this?</p>
                    <div class="feedback-buttons">
                        <button
                            class="btn-feedback btn-again"
                            @click="submitFeedback('Again')"
                            :disabled="submitting"
                        >
                            Again
                            <span class="feedback-hint">&lt; 1m</span>
                        </button>
                        <button
                            class="btn-feedback btn-hard"
                            @click="submitFeedback('Hard')"
                            :disabled="submitting"
                        >
                            Hard
                            <span class="feedback-hint">15m</span>
                        </button>
                        <button
                            class="btn-feedback btn-good"
                            @click="submitFeedback('Good')"
                            :disabled="submitting"
                        >
                            Good
                            <span class="feedback-hint">{{
                                currentCard.level > 0
                                    ? currentCard.level + "d"
                                    : "1d"
                            }}</span>
                        </button>
                        <button
                            class="btn-feedback btn-easy"
                            @click="submitFeedback('Easy')"
                            :disabled="submitting"
                        >
                            Easy
                            <span class="feedback-hint">{{
                                currentCard.level > 0
                                    ? (currentCard.level + 2) * 2 + "d"
                                    : "4d"
                            }}</span>
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import api from "../../api/axios";
import FlashCardGroupSelector from "../../components/flashcards/FlashCardGroupSelector.vue";
import FlashCardSwiper from "../../components/flashcards/FlashCardSwiper.vue";
import FlashCard from "../../components/flashcards/FlashCard.vue";

const router = useRouter();
const route = useRoute();

// If group_id is passed via query, auto-select it
const initialGroupId = route.query.group_id
    ? Number(route.query.group_id)
    : null;

const selectedGroupId = ref(initialGroupId);
const cards = ref([]);
const loading = ref(false);
const currentIndex = ref(0);
const showAnswer = ref(false);
const submitting = ref(false);

const currentCard = computed(() => {
    return cards.value[currentIndex.value] || null;
});

const progressPercentage = computed(() => {
    if (!cards.value.length) return 100;
    return (currentIndex.value / cards.value.length) * 100;
});

function goBack() {
    if (selectedGroupId.value) {
        router.push({
            name: "FlashCardList",
            params: { groupId: selectedGroupId.value },
        });
    } else {
        router.push({ name: "FlashCardGroups" });
    }
}

async function onGroupSelected(groupId) {
    selectedGroupId.value = groupId;
    currentIndex.value = 0;
    showAnswer.value = false;
    await fetchStudyCards();
}

async function fetchStudyCards() {
    loading.value = true;
    try {
        const { data } = await api.get(
            `/me/flash-cards/study?limit=50&group_id=${selectedGroupId.value}`,
        );
        cards.value = data.data || [];
    } catch (e) {
        console.error("Failed to load cards", e);
    } finally {
        loading.value = false;
    }
}

function onCardFlipped(cardId) {
    if (currentCard.value && currentCard.value.id === cardId) {
        showAnswer.value = !showAnswer.value;
    }
}

function onSwiped(direction) {
    showAnswer.value = false;
}

async function submitFeedback(rating) {
    if (!currentCard.value || submitting.value) return;

    submitting.value = true;
    try {
        await api.post(`/me/flash-cards/${currentCard.value.id}/review`, {
            rating: rating,
        });

        setTimeout(() => {
            if (currentIndex.value < cards.value.length) {
                currentIndex.value++;
            }
            showAnswer.value = false;
        }, 150);
    } catch (e) {
        console.error("Failed to submit review", e);
        alert("Failed to save your review. Please try again.");
    } finally {
        submitting.value = false;
    }
}

// Auto-load study cards if group_id was passed
onMounted(() => {
    if (selectedGroupId.value) {
        fetchStudyCards();
    }
});
</script>

<style scoped>
.flash-card-study {
    padding: 16px;
    max-width: 960px;
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
    gap: 12px;
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
    color: #22c55e;
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
}

.progress-bar {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    margin-bottom: 8px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #3b82f6;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.75rem;
    color: #6b7280;
    text-align: center;
    margin-bottom: 1.5rem;
}

.swiper-wrapper-container {
    flex: 1;
    display: flex;
    align-items: center;
    padding-bottom: 20px;
}

.study-controls {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-bottom: 1rem;
}

.feedback-controls {
    width: 100%;
}

.feedback-ask {
    margin-bottom: 1rem;
    font-weight: 600;
    color: #374151;
    font-size: 1.1rem;
}

.feedback-buttons {
    display: flex;
    gap: 8px;
    width: 100%;
    justify-content: space-between;
}

@media (max-width: 640px) {
    .flash-card-study {
        padding: 12px;
    }

    .study-header {
        flex-direction: column;
        align-items: stretch;
        margin-bottom: 1.25rem;
    }

    .study-header h2 {
        margin: 0;
        text-align: center;
    }

    .feedback-buttons {
        flex-wrap: wrap;
    }

    .btn-feedback {
        flex: 1 1 calc(50% - 8px);
    }
}

.btn-feedback {
    flex: 1;
    padding: 0.75rem 0.25rem;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    transition:
        transform 0.1s,
        opacity 0.2s;
}

.btn-feedback:active {
    transform: scale(0.95);
}
.btn-feedback:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.feedback-hint {
    font-size: 0.7rem;
    font-weight: 400;
    margin-top: 0.25rem;
    opacity: 0.9;
}

.btn-again {
    background: #ef4444;
}
.btn-again:hover {
    background: #dc2626;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
.btn-hard {
    background: #f59e0b;
}
.btn-hard:hover {
    background: #d97706;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}
.btn-good {
    background: #22c55e;
}
.btn-good:hover {
    background: #16a34a;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}
.btn-easy {
    background: #3b82f6;
}
.btn-easy:hover {
    background: #2563eb;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.fade-up-enter-active,
.fade-up-leave-active {
    transition:
        opacity 0.3s ease,
        transform 0.3s ease;
}
.fade-up-enter-from {
    opacity: 0;
    transform: translateY(20px);
}
.fade-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>
