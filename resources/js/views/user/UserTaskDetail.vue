<template>
    <div class="task-detail">
        <button class="btn-back" @click="goBack">← Back to list</button>

        <div v-if="loading" class="loading">Loading task...</div>
        <div v-else-if="!task" class="empty">Task not found.</div>
        <div v-else class="detail-card">
            <header class="detail-header">
                <div>
                    <h2 class="detail-title">{{ task.title }}</h2>
                    <p class="detail-project" v-if="task.project">
                        Project: <span>{{ task.project.name }}</span>
                    </p>
                </div>
                <div class="detail-status">
                    <span
                        :class="[
                            'badge',
                            'badge--' + getStatusColor(task.status),
                        ]"
                    >
                        {{ getStatusLabel(task.status) }}
                    </span>
                </div>
            </header>

            <section class="detail-section countdown-section">
                <div class="countdown-main">
                    <div class="countdown-label">Time remaining</div>
                    <div :class="['countdown-big', getCountdownClass(task)]">
                        <div class="countdown-big-value">
                            {{ bigCountdown.value }}
                        </div>
                        <div class="countdown-big-sub">
                            {{ bigCountdown.sub }}
                        </div>
                    </div>
                </div>
                <div class="time-grid">
                    <div class="time-item">
                        <div class="time-label">Start</div>
                        <div class="time-value">
                            {{
                                formatDateTime(task.start_at || task.start_date)
                            }}
                        </div>
                    </div>
                    <div class="time-item">
                        <div class="time-label">Due</div>
                        <div class="time-value">
                            {{ formatDateTime(task.due_at || task.due_date) }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="detail-section">
                <h3 class="section-title">Description</h3>
                <p class="detail-description">
                    {{ task.description || "No description yet." }}
                </p>
            </section>
        </div>
    </div>
</template>

<script setup>
import "../../styles/user-task-detail.css";
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "../../api/axios";

const route = useRoute();
const router = useRouter();

const STATUS_OPTIONS = [
    { value: 0, label: "Not started", color: "gray" },
    { value: 1, label: "In progress", color: "green" },
    { value: 2, label: "Paused", color: "orange" },
    { value: 3, label: "Completed", color: "blue" },
];

const task = ref(null);
const loading = ref(true);
const now = ref(new Date());
let timerId = null;

onMounted(async () => {
    await fetchTask();
    timerId = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timerId) {
        clearInterval(timerId);
        timerId = null;
    }
});

async function fetchTask() {
    loading.value = true;
    try {
        const id = route.params.id;
        const { data } = await api.get(`/me/tasks/${id}`);
        task.value = data.task;
    } catch (e) {
        task.value = null;
    } finally {
        loading.value = false;
    }
}

function goBack() {
    router.push({ name: "UserTasks" });
}

function getStatusLabel(status) {
    const opt = STATUS_OPTIONS.find((o) => o.value === status);
    return opt ? opt.label : "—";
}

function getStatusColor(status) {
    const opt = STATUS_OPTIONS.find((o) => o.value === status);
    return opt ? opt.color : "gray";
}

function parseDateTime(value) {
    if (!value) return null;
    if (value instanceof Date) return value;
    if (typeof value !== "string") return null;
    if (value.includes("T")) {
        return new Date(value);
    }
    return new Date(value + "T00:00:00");
}

function formatDateTime(value) {
    const d = parseDateTime(value);
    if (!d || Number.isNaN(d.getTime())) return "—";
    return d.toLocaleString("en-GB", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function getCountdownClass(t) {
    if (!t) return "countdown--none";
    const status = t.status;
    const value = t.due_at || t.due_date;

    if (!value) return "countdown--none";
    if (status === 3) return "countdown--done";

    const due = parseDateTime(value);
    if (!due) return "countdown--none";

    const current = now.value instanceof Date ? now.value : new Date();
    const diffMs = due.getTime() - current.getTime();
    const totalMinutes = Math.floor(Math.abs(diffMs) / 60000);

    if (diffMs < 0) return "countdown--over";
    if (totalMinutes <= 60) return "countdown--urgent";
    if (totalMinutes <= 24 * 60) return "countdown--soon";
    return "countdown--normal";
}

const bigCountdown = computed(() => {
    if (!task.value) {
        return { value: "—", sub: "No deadline yet" };
    }

    const status = task.value.status;
    const value = task.value.due_at || task.value.due_date;

    if (status === 3) {
        return { value: "Completed", sub: "This task has been completed." };
    }

    if (!value) {
        return { value: "—", sub: "No due time set" };
    }

    const due = parseDateTime(value);
    if (!due) {
        return { value: "—", sub: "Invalid time value" };
    }

    const current = now.value instanceof Date ? now.value : new Date();
    const diffMs = due.getTime() - current.getTime();
    const past = diffMs < 0;
    const absMs = Math.abs(diffMs);

    const totalSeconds = Math.floor(absMs / 1000);
    const days = Math.floor(totalSeconds / (24 * 3600));
    const hours = Math.floor((totalSeconds % (24 * 3600)) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    let main = "";
    if (days) {
        main = `${days} days ${hours.toString().padStart(2, "0")}h`;
    } else if (hours) {
        main = `${hours}h ${minutes.toString().padStart(2, "0")}m`;
    } else {
        main = `${minutes.toString().padStart(2, "0")}m ${seconds.toString().padStart(2, "0")}s`;
    }

    const prefix = past ? "Overdue" : "Remaining";
    const sub = past
        ? "This task is overdue, please resolve it as soon as possible."
        : "Time remaining until this task is due.";

    return {
        value: `${prefix} ${main}`,
        sub,
    };
});
</script>
