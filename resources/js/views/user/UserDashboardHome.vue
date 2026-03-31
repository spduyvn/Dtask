<template>
    <div class="dashboard-home">
        <div class="welcome-block">
            <h2>Welcome back, {{ user?.name }}!</h2>
            <p>Here is the overview of your tasks.</p>
        </div>

        <div v-if="loading" class="loading-state">Loading metrics...</div>

        <div v-else class="cards">
            <div class="card card--active">
                <div class="card-icon card-icon--blue">
                    <span>📋</span>
                </div>
                <div class="card-content">
                    <div class="card-value">{{ summary.activeTasks }}</div>
                    <div class="card-label">Active Tasks</div>
                </div>
            </div>

            <div class="card card--due-today">
                <div class="card-icon card-icon--orange"><span>⏱</span></div>
                <div class="card-content">
                    <div class="card-value">{{ summary.dueToday }}</div>
                    <div class="card-label">Due Today</div>
                </div>
            </div>

            <div class="card card--overdue">
                <div class="card-icon card-icon--red"><span>🛑</span></div>
                <div class="card-content">
                    <div class="card-value">{{ summary.overdue }}</div>
                    <div class="card-label">Overdue Tasks</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useAuth } from "../../composables/useAuth";
import api from "../../api/axios";
import "../../styles/user-dashboard-home.css";

const { user } = useAuth();
const summary = ref({ activeTasks: 0, dueToday: 0, overdue: 0 });
const loading = ref(true);

async function fetchSummary() {
    try {
        const { data } = await api.get("/me/tasks/summary");
        summary.value = data;
    } catch (err) {
        console.error("Failed to load summary", err);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    fetchSummary();
});
</script>
