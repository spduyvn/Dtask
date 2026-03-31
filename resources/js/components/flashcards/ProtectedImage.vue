<template>
    <img
        v-if="imageUrl"
        :src="imageUrl"
        v-bind="$attrs"
        alt="Protected image"
    />
    <div v-else-if="loading" class="image-loading-placeholder">
        <div class="mini-spinner"></div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from "vue";
import api from "../../api/axios";

const props = defineProps({
    src: {
        type: String,
        required: true,
    },
});

const imageUrl = ref(null);
const loading = ref(false);

const fetchImage = async (url) => {
    if (!url) {
        imageUrl.value = null;
        return;
    }

    // If it's already a blob URL (preview), just use it
    if (url.startsWith("blob:")) {
        imageUrl.value = url;
        return;
    }

    loading.value = true;
    try {
        const response = await api.get(url, {
            responseType: "blob",
        });
        const newUrl = URL.createObjectURL(response.data);

        // Cleanup old URL
        if (imageUrl.value && imageUrl.value.startsWith("blob:")) {
            URL.revokeObjectURL(imageUrl.value);
        }

        imageUrl.value = newUrl;
    } catch (e) {
        console.error("Failed to load protected image", e);
        imageUrl.value = null;
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.src,
    (newSrc) => {
        fetchImage(newSrc);
    },
);

onMounted(() => {
    fetchImage(props.src);
});

onBeforeUnmount(() => {
    if (imageUrl.value && imageUrl.value.startsWith("blob:")) {
        URL.revokeObjectURL(imageUrl.value);
    }
});
</script>

<style scoped>
.image-loading-placeholder {
    width: 100%;
    height: 150px;
    background: rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

[data-theme="dark"] .image-loading-placeholder {
    background: rgba(255, 255, 255, 0.05);
}

.mini-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #ccc;
    border-top-color: #e94560;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
