<template>
    <div class="user-task-calendar">
        <!-- ========= Desktop: Calendar Toolbar ========= -->
        <div class="calendar-toolbar">
            <div class="calendar-nav">
                <button
                    class="calendar-nav-btn"
                    title="Previous month"
                    @click="prevMonth"
                >
                    &larr;
                </button>
                <span class="calendar-month-label">{{ monthLabel }}</span>
                <button
                    class="calendar-nav-btn"
                    title="Next month"
                    @click="nextMonth"
                >
                    &rarr;
                </button>
            </div>
            <button class="calendar-today-btn" @click="goToday">Today</button>
        </div>

        <!-- ========= Loading ========= -->
        <div v-if="loading" class="calendar-loading">
            <span class="calendar-loading-spinner"></span>
            <span>Loading calendar...</span>
        </div>

        <!-- ========= Desktop: Grid ========= -->
        <div v-else class="calendar-grid">
            <div class="calendar-weekdays">
                <div v-for="wd in WEEKDAYS" :key="wd" class="calendar-weekday">
                    {{ wd }}
                </div>
            </div>
            <div class="calendar-days">
                <div
                    v-for="(day, idx) in calendarDays"
                    :key="idx"
                    :class="dayClasses(day)"
                    @click="selectDay(day)"
                >
                    <span class="calendar-day-number">{{ day.date }}</span>
                    <div class="calendar-day-tasks">
                        <div
                            v-for="task in day.tasks.slice(
                                0,
                                MAX_VISIBLE_TASKS,
                            )"
                            :key="task.id"
                            :class="[
                                'calendar-task-pill',
                                taskPillClass(task, day),
                            ]"
                            :title="task.title"
                            @click.stop="openDetail(task)"
                        >
                            <span class="calendar-task-pill-dot"></span>
                            <span>{{ task.title }}</span>
                        </div>
                        <div
                            v-if="day.tasks.length > MAX_VISIBLE_TASKS"
                            class="calendar-more-tasks"
                            @click.stop="selectDay(day)"
                        >
                            +{{ day.tasks.length - MAX_VISIBLE_TASKS }} more
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========= Mobile: List View ========= -->
        <div v-if="!loading" class="calendar-mobile-list">
            <div class="calendar-mobile-month-nav">
                <button class="calendar-nav-btn" @click="prevMonth">
                    &larr;
                </button>
                <span class="calendar-mobile-month-label">{{
                    monthLabel
                }}</span>
                <button class="calendar-nav-btn" @click="nextMonth">
                    &rarr;
                </button>
            </div>

            <div
                v-if="daysWithTasks.length === 0"
                class="calendar-mobile-no-tasks"
            >
                No tasks in this month.
            </div>

            <div
                v-for="day in daysWithTasks"
                :key="day.key"
                class="calendar-mobile-day-group"
            >
                <div class="calendar-mobile-day-header">
                    <span
                        :class="[
                            'calendar-mobile-day-label',
                            { 'calendar-mobile-day-label--today': day.isToday },
                        ]"
                    >
                        {{ day.date }} {{ MONTHS_SHORT[currentMonth - 1] }}
                        <span
                            v-if="day.isToday"
                            style="
                                margin-left: 6px;
                                font-size: 11px;
                                color: #e94560;
                            "
                            >● Today</span
                        >
                    </span>
                    <span class="calendar-mobile-day-weekday">{{
                        WEEKDAYS[day.dayOfWeek]
                    }}</span>
                </div>
                <div class="calendar-mobile-day-tasks">
                    <div
                        v-for="task in day.tasks"
                        :key="task.id"
                        class="calendar-mobile-task"
                        @click="openDetail(task)"
                    >
                        <span
                            :class="[
                                'calendar-mobile-task-dot',
                                'calendar-mobile-task-dot--' +
                                    getStatusColor(task.status),
                            ]"
                        ></span>
                        <span class="calendar-mobile-task-title">{{
                            task.title
                        }}</span>
                        <span
                            :class="[
                                'calendar-mobile-task-status',
                                'badge',
                                'badge--' + getStatusColor(task.status),
                            ]"
                        >
                            {{ getStatusLabel(task.status) }}
                        </span>
                    </div>
                    <div
                        v-if="day.tasks.length === 0"
                        class="calendar-mobile-day-empty"
                    >
                        No tasks
                    </div>
                </div>
                <button
                    v-if="!day.isPast"
                    class="calendar-mobile-create-btn"
                    @click="openCreateForDate(day.fullDate)"
                >
                    + Create task
                </button>
            </div>
        </div>

        <!-- Legend -->
        <div v-if="!loading" class="calendar-legend">
            <div
                v-for="opt in STATUS_OPTIONS"
                :key="opt.value"
                class="calendar-legend-item"
            >
                <span
                    :class="[
                        'calendar-legend-dot',
                        'calendar-legend-dot--' + opt.color,
                    ]"
                ></span>
                <span>{{ opt.label }}</span>
            </div>
            <div class="calendar-legend-item">
                <span
                    class="calendar-legend-dot calendar-legend-dot--red"
                ></span>
                <span>Overdue</span>
            </div>
        </div>

        <!-- ========= Day Detail Panel ========= -->
        <div
            v-if="selectedDay"
            class="calendar-detail-overlay"
            @click.self="selectedDay = null"
        >
            <div class="calendar-detail-panel">
                <div class="calendar-detail-header">
                    <div>
                        <span class="calendar-detail-date"
                            >{{ selectedDay.date }}
                            {{ MONTHS_SHORT[currentMonth - 1] }}</span
                        >
                        <span class="calendar-detail-date-sub">{{
                            selectedDay.isToday
                                ? "Today"
                                : WEEKDAYS[selectedDay.dayOfWeek]
                        }}</span>
                    </div>
                    <button
                        class="calendar-detail-close"
                        @click="selectedDay = null"
                    >
                        &times;
                    </button>
                </div>

                <div class="calendar-detail-body">
                    <div
                        v-if="selectedDay.isPast"
                        class="calendar-detail-past-notice"
                    >
                        <span class="calendar-detail-past-notice-icon">🔒</span>
                        <span
                            >This date is in the past. Tasks are
                            read-only.</span
                        >
                    </div>
                    <div
                        v-if="selectedDay.tasks.length === 0"
                        class="calendar-detail-empty"
                    >
                        <div class="calendar-detail-empty-icon">📋</div>
                        <div>No tasks for this date</div>
                    </div>
                    <div
                        v-for="task in selectedDay.tasks"
                        :key="task.id"
                        class="calendar-detail-task"
                        @click="openDetail(task)"
                    >
                        <span
                            :class="[
                                'calendar-detail-task-status-dot',
                                'calendar-detail-task-status-dot--' +
                                    getTaskDotColor(task, selectedDay),
                            ]"
                        ></span>
                        <div class="calendar-detail-task-info">
                            <div class="calendar-detail-task-title">
                                {{ task.title }}
                            </div>
                            <div class="calendar-detail-task-meta">
                                <span
                                    :class="[
                                        'calendar-detail-task-badge',
                                        'badge',
                                        'badge--' + getStatusColor(task.status),
                                    ]"
                                >
                                    {{ getStatusLabel(task.status) }}
                                </span>
                                <span
                                    v-if="task.due_at || task.due_date"
                                    class="calendar-detail-task-time"
                                >
                                    Due:
                                    {{
                                        formatDateTime(
                                            task.due_at || task.due_date,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!selectedDay.isPast" class="calendar-detail-actions">
                    <button
                        class="calendar-detail-create-btn"
                        @click="openCreateForDate(selectedDay.fullDate)"
                    >
                        + Create task for this day
                    </button>
                </div>
            </div>
        </div>

        <!-- ========= Create Task Modal ========= -->
        <div
            v-if="showCreateModal"
            class="modal-overlay"
            @click.self="closeCreateModal"
        >
            <div class="modal">
                <h3 class="modal-title">Create task</h3>
                <form
                    class="form calendar-create-form"
                    @submit.prevent="submitCreate"
                >
                    <div v-if="formError" class="form-error">
                        {{ formError }}
                    </div>
                    <div class="field">
                        <label>Title</label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="Task name"
                        />
                    </div>
                    <div class="field">
                        <label>Description</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Optional description"
                        ></textarea>
                    </div>
                    <div class="field">
                        <label>Project</label>
                        <select
                            v-model.number="form.project_id"
                            class="field-select"
                        >
                            <option :value="null">No project</option>
                            <option
                                v-for="p in projects"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.name }}
                            </option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Start time</label>
                        <input v-model="form.start_at" type="datetime-local" />
                    </div>
                    <div class="field">
                        <label>Due time</label>
                        <input
                            v-model="form.due_at"
                            type="datetime-local"
                            :min="createDateMin"
                        />
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select
                            v-model.number="form.status"
                            class="field-select"
                        >
                            <option
                                v-for="opt in STATUS_OPTIONS"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button
                            type="button"
                            class="btn-secondary"
                            @click="closeCreateModal"
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
import { ref, reactive, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import "../../styles/user-task-calendar.css";
import "../../styles/user-task-list.css";
import api from "../../api/axios";

const router = useRouter();

// ───────── Constants ─────────
const WEEKDAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
const MONTHS = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];
const MONTHS_SHORT = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
];
const STATUS_OPTIONS = [
    { value: 0, label: "Not started", color: "gray" },
    { value: 1, label: "In progress", color: "green" },
    { value: 2, label: "Paused", color: "orange" },
    { value: 3, label: "Completed", color: "blue" },
];
const MAX_VISIBLE_TASKS = 3;

// ───────── State ─────────
const today = new Date();
const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;

const currentMonth = ref(today.getMonth() + 1); // 1-12
const currentYear = ref(today.getFullYear());

const tasks = ref([]);
const projects = ref([]);
const loading = ref(true);
const selectedDay = ref(null);

// Create modal
const showCreateModal = ref(false);
const form = reactive({
    title: "",
    description: "",
    start_at: "",
    due_at: "",
    status: 0,
    project_id: null,
});
const formError = ref("");
const saving = ref(false);
const createDateMin = ref("");

// ───────── Computed ─────────
const monthLabel = computed(
    () => `${MONTHS[currentMonth.value - 1]} ${currentYear.value}`,
);

const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value - 1; // JS months 0-based

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    const startDayOfWeek = firstDay.getDay(); // 0=Sun
    const daysInMonth = lastDay.getDate();

    // Previous month padding
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    const days = [];

    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const d = prevMonthLastDay - i;
        days.push(buildDay(d, month - 1, year, true));
    }

    // Current month
    for (let d = 1; d <= daysInMonth; d++) {
        days.push(buildDay(d, month, year, false));
    }

    // Next month padding (fill to 42 cells = 6 rows)
    const remaining = 42 - days.length;
    for (let d = 1; d <= remaining; d++) {
        days.push(buildDay(d, month + 1, year, true));
    }

    return days;
});

const daysWithTasks = computed(() => {
    return calendarDays.value.filter(
        (d) => !d.otherMonth && d.tasks.length > 0,
    );
});

// ───────── Helpers ─────────
function buildDay(date, jsMonth, year, otherMonth) {
    // Handle month overflow
    let actualYear = year;
    let actualMonth = jsMonth;
    if (jsMonth < 0) {
        actualMonth = 11;
        actualYear = year - 1;
    }
    if (jsMonth > 11) {
        actualMonth = 0;
        actualYear = year + 1;
    }

    const fullDate = `${actualYear}-${String(actualMonth + 1).padStart(2, "0")}-${String(date).padStart(2, "0")}`;
    const dayOfWeek = new Date(actualYear, actualMonth, date).getDay();
    const isToday = fullDate === todayStr;
    const isPast = fullDate < todayStr;

    // Match tasks to this day
    const dayTasks = tasks.value.filter((t) => {
        const dueDate = extractDate(t.due_at || t.due_date);
        const startDate = extractDate(t.start_at || t.start_date);
        return dueDate === fullDate || startDate === fullDate;
    });

    return {
        date,
        fullDate,
        dayOfWeek,
        otherMonth,
        isToday,
        isPast,
        tasks: dayTasks,
        key: fullDate,
    };
}

function extractDate(value) {
    if (!value) return null;
    if (typeof value === "string") {
        return value.substring(0, 10);
    }
    return null;
}

function dayClasses(day) {
    return [
        "calendar-day",
        {
            "calendar-day--other-month": day.otherMonth,
            "calendar-day--today": day.isToday,
            "calendar-day--past": day.isPast && !day.isToday,
            "calendar-day--selected":
                selectedDay.value &&
                selectedDay.value.fullDate === day.fullDate,
        },
    ];
}

function taskPillClass(task, day) {
    if (isTaskOverdue(task, day)) return "calendar-task-pill--overdue";
    const status = task.status ?? 0;
    const map = {
        0: "not-started",
        1: "in-progress",
        2: "paused",
        3: "completed",
    };
    return `calendar-task-pill--${map[status] || "not-started"}`;
}

function isTaskOverdue(task) {
    if (task.status === 3) return false;
    const dueStr = extractDate(task.due_at || task.due_date);
    if (!dueStr) return false;
    return dueStr < todayStr;
}

function getStatusColor(status) {
    const opt = STATUS_OPTIONS.find((o) => o.value === status);
    return opt ? opt.color : "gray";
}

function getStatusLabel(status) {
    const opt = STATUS_OPTIONS.find((o) => o.value === status);
    return opt ? opt.label : "—";
}

function getTaskDotColor(task, day) {
    if (isTaskOverdue(task, day)) return "red";
    return getStatusColor(task.status);
}

function formatDateTime(value) {
    if (!value) return "—";
    const d = new Date(value.includes("T") ? value : value + "T00:00:00");
    if (isNaN(d.getTime())) return "—";
    return d.toLocaleString("en-GB", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
    });
}

// ───────── Navigation ─────────
function prevMonth() {
    if (currentMonth.value === 1) {
        currentMonth.value = 12;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
}

function nextMonth() {
    if (currentMonth.value === 12) {
        currentMonth.value = 1;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
}

function goToday() {
    currentMonth.value = today.getMonth() + 1;
    currentYear.value = today.getFullYear();
}

function selectDay(day) {
    if (day.otherMonth) return;
    selectedDay.value = day;
}

function openDetail(task) {
    router.push({ name: "UserTaskDetail", params: { id: task.id } });
}

// ───────── Create Task ─────────
function openCreateForDate(dateStr) {
    // Validate: cannot create tasks in the past
    if (dateStr < todayStr) return;

    form.title = "";
    form.description = "";
    form.project_id = null;
    form.status = 0;
    form.start_at = `${dateStr}T09:00`;
    form.due_at = `${dateStr}T17:00`;
    createDateMin.value = `${todayStr}T00:00`;
    formError.value = "";
    showCreateModal.value = true;
    selectedDay.value = null;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

async function submitCreate() {
    formError.value = "";

    // Frontend validation: due_at must be >= today
    if (form.due_at) {
        const dueDate = form.due_at.substring(0, 10);
        if (dueDate < todayStr) {
            formError.value = "Due date cannot be in the past.";
            return;
        }
    }

    saving.value = true;
    try {
        const payload = {
            title: form.title,
            description: form.description || null,
            start_at: form.start_at
                ? form.start_at.replace("T", " ") + ":00"
                : null,
            due_at: form.due_at ? form.due_at.replace("T", " ") + ":00" : null,
            status: form.status,
            project_id: form.project_id || null,
        };
        await api.post("/me/tasks", payload);
        closeCreateModal();
        await fetchTasks();
    } catch (e) {
        const msg = e.response?.data?.errors;
        formError.value = msg
            ? Object.values(msg).flat().join(" ")
            : e.response?.data?.message || "An error occurred.";
    } finally {
        saving.value = false;
    }
}

// ───────── Data Fetching ─────────
async function fetchTasks() {
    loading.value = true;
    try {
        const { data } = await api.get("/me/tasks/calendar", {
            params: { month: currentMonth.value, year: currentYear.value },
        });
        tasks.value = data.tasks || [];
    } catch (e) {
        tasks.value = [];
        console.error("Failed to load calendar tasks:", e);
    } finally {
        loading.value = false;
    }
}

async function fetchProjects() {
    try {
        const { data } = await api.get("/me/projects", {
            params: { per_page: 100 },
        });
        projects.value = data.data || [];
    } catch {
        projects.value = [];
    }
}

// ───────── Watchers ─────────
watch([currentMonth, currentYear], () => {
    selectedDay.value = null;
    fetchTasks();
});

// ───────── Lifecycle ─────────
onMounted(() => {
    fetchTasks();
    fetchProjects();
});
</script>
