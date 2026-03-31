<template>
    <div
        class="flash-card-container"
        :class="{ flipped: isFlipped }"
        @click="flipCard"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="flipCard"
        @keydown.space.prevent="flipCard"
    >
        <div class="flash-card-inner">
            <div class="flash-card-front">
                <div class="card-content">
                    <span class="label">QUESTION</span>
                    <p class="text">{{ card.question }}</p>
                </div>
                <div class="hint">Tap to reveal answer</div>
            </div>
            <div class="flash-card-back">
                <div class="card-content">
                    <span class="label">ANSWER</span>
                    <p class="text">{{ card.answer }}</p>
                    <ProtectedImage
                        v-if="card.image_url"
                        :src="card.image_url"
                        class="card-image"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import ProtectedImage from "./ProtectedImage.vue";

const props = defineProps({
    card: {
        type: Object,
        required: true,
    },
    forceFlipped: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["flipped"]);

const isFlipped = ref(props.forceFlipped);

watch(
    () => props.forceFlipped,
    (newVal) => {
        isFlipped.value = newVal;
    },
);

watch(
    () => props.card,
    () => {
        isFlipped.value = false;
    },
);

const flipCard = () => {
    isFlipped.value = !isFlipped.value;
    emit("flipped", isFlipped.value);
};
</script>

<style scoped>
.flash-card-container {
    background-color: transparent;
    width: 100%;
    max-width: 560px;
    height: clamp(360px, 62vh, 560px);
    perspective: 1000px;
    cursor: pointer;
    margin: 0 auto;
    -webkit-tap-highlight-color: transparent;
}

.flash-card-container:active .flash-card-inner {
    transform: translateY(1px);
}

.flash-card-container.flipped:active .flash-card-inner {
    transform: rotateY(180deg) translateY(1px);
}

.flash-card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    text-align: center;
    transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1);
    transform-style: preserve-3d;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border-radius: 16px;
}

.flash-card-container:focus-visible {
    outline: 2px solid rgba(59, 130, 246, 0.6);
    outline-offset: 4px;
    border-radius: 18px;
}

.flash-card-container.flipped .flash-card-inner {
    transform: rotateY(180deg);
}

.flash-card-front,
.flash-card-back {
    position: absolute;
    width: 100%;
    height: 100%;
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 24px;
    border-radius: 16px;
    box-sizing: border-box;
    background: #ffffff;
    border: 1px solid #e5e7eb;
}

.flash-card-back {
    transform: rotateY(180deg);
    background: #f8fafc;
}

.card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 100%;
    min-height: 0;
    max-height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
    word-break: break-word;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

.label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #9ca3af;
    letter-spacing: 0.1em;
    margin-bottom: 12px;
}

.text {
    font-size: clamp(1.05rem, 2.2vw, 1.5rem);
    line-height: 1.5;
    color: #1f2937;
    margin: 0;
    width: 100%;
    max-height: 100%;
    overflow-y: auto;
    word-break: break-word;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
}

.card-image {
    width: 100%;
    max-width: 100%;
    max-height: 220px;
    object-fit: contain;
    margin-top: 14px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.hint {
    font-size: 0.875rem;
    color: #9ca3af;
    margin-top: auto;
    animation: pulse 2s infinite;
}

@media (max-width: 480px) {
    .flash-card-container {
        height: clamp(320px, 60vh, 520px);
    }

    .flash-card-front,
    .flash-card-back {
        padding: 18px;
    }

    .hint {
        font-size: 0.8125rem;
    }
}

@media (min-width: 768px) {
    .flash-card-front,
    .flash-card-back {
        padding: 32px;
    }

    .card-image {
        max-height: 260px;
    }
}

@keyframes pulse {
    0% {
        opacity: 0.6;
    }
    50% {
        opacity: 1;
    }
    100% {
        opacity: 0.6;
    }
}

/* Dark mode adjustments (assume parent class or general dark theme variables exist) */
:root[data-theme="dark"] .flash-card-front,
:root[data-theme="dark"] .flash-card-back {
    background: #1f2937;
    border-color: #374151;
}

:root[data-theme="dark"] .text {
    color: #f3f4f6;
}
</style>
