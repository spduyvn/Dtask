<template>
    <div class="group-selector">
        <h3>Select a Group to Study</h3>
        <div v-if="loading" class="loading">Loading groups...</div>
        <div v-else-if="!groups.length" class="empty">
            No groups found. Create one in the Flash Cards list!
        </div>
        <div v-else class="group-list">
            <button
                v-for="group in groups"
                :key="group.id"
                class="group-card"
                @click="emit('select', group.id)"
            >
                <h4>{{ group.name }}</h4>
                <p v-if="group.description">{{ group.description }}</p>
                <div class="group-meta">
                    <span>{{ group.flash_cards_count || 0 }} cards</span>
                </div>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "../../api/axios";

const emit = defineEmits(["select"]);
const groups = ref([]);
const loading = ref(true);

const fetchGroups = async () => {
    loading.value = true;
    try {
        const { data } = await api.get("/me/flash-card-groups/all");
        groups.value = data.data || [];
    } catch (e) {
        console.error("Failed to load groups", e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchGroups();
});
</script>

<style scoped>
.group-selector {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
}

.group-selector h3 {
    margin-bottom: 24px;
    font-size: 1.5rem;
    color: var(--text-main);
}

.group-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.group-card {
    background: var(--bg-card, #ffffff);
    border: 1px solid var(--border-color, #e5e7eb);
    padding: 16px;
    border-radius: 12px;
    cursor: pointer;
    text-align: left;
    transition:
        transform 0.2s,
        box-shadow 0.2s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.group-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.group-card h4 {
    margin: 0 0 8px 0;
    font-size: 1.25rem;
    color: var(--text-main, #111827);
}

.group-card p {
    margin: 0 0 12px 0;
    font-size: 0.875rem;
    color: var(--text-muted, #6b7280);
}

.group-meta {
    font-size: 0.75rem;
    color: #3b82f6;
    font-weight: 500;
}
</style>
