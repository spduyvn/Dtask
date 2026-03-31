<template>
    <div
        class="swiper-container"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
    >
        <div
            class="swiper-wrapper"
            :style="{
                transform: `translateX(calc(-${currentIndex * 100}% + ${dragOffset}px))`,
                transition: isDragging ? 'none' : 'transform 0.3s ease-out',
            }"
        >
            <div
                v-for="(card, index) in cards"
                :key="card.id"
                class="swiper-slide"
            >
                <!-- the card itself via slot or imported component -->
                <slot
                    :card="card"
                    :index="index"
                    :isActive="index === currentIndex"
                ></slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    cards: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["update:modelValue", "swiped-left", "swiped-right"]);

const currentIndex = ref(props.modelValue);
const touchStartX = ref(0);
const touchStartY = ref(0);
const dragOffset = ref(0);
const isDragging = ref(false);

const SWIPE_THRESHOLD = 50; // pixels

watch(
    () => props.modelValue,
    (newVal) => {
        currentIndex.value = newVal;
    },
);

function handleTouchStart(e) {
    if (e.touches.length > 1) return; // ignore multi-touch
    touchStartX.value = e.touches[0].clientX;
    touchStartY.value = e.touches[0].clientY;
    isDragging.value = true;
    dragOffset.value = 0;
}

function handleTouchMove(e) {
    if (!isDragging.value) return;
    const currentX = e.touches[0].clientX;
    const currentY = e.touches[0].clientY;

    // Check if scrolling vertically vs swiping horizontally
    const diffX = currentX - touchStartX.value;
    const diffY = currentY - touchStartY.value;

    if (Math.abs(diffX) > Math.abs(diffY)) {
        // Horizontal swipe, prevent default vertical scroll if needed (e.preventDefault() could be tricky here in passive listeners, but let's just track offset)
        dragOffset.value = diffX;
    }
}

function handleTouchEnd() {
    if (!isDragging.value) return;
    isDragging.value = false;

    if (dragOffset.value > SWIPE_THRESHOLD) {
        // Swiped Right -> Previous Card
        if (currentIndex.value > 0) {
            currentIndex.value--;
            emit("update:modelValue", currentIndex.value);
            emit("swiped-right");
        }
    } else if (dragOffset.value < -SWIPE_THRESHOLD) {
        // Swiped Left -> Next Card
        if (currentIndex.value < props.cards.length - 1) {
            currentIndex.value++;
            emit("update:modelValue", currentIndex.value);
            emit("swiped-left");
        }
    }

    dragOffset.value = 0;
}
</script>

<style scoped>
.swiper-container {
    width: 100%;
    overflow: hidden;
    position: relative;
    touch-action: pan-y; /* Allow vertical scroll but capture horizontal */
}

.swiper-wrapper {
    display: flex;
    width: 100%;
    align-items: center;
}

.swiper-slide {
    flex: 0 0 100%;
    width: 100%;
    padding: 10px;
    box-sizing: border-box;
}
</style>
