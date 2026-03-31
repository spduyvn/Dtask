<template>
    <div class="user-tasks-page">
        <div class="tabs-header">
            <button
                :class="['tab-btn', { active: activeTab === 'list' }]"
                @click="setTab('list')"
            >
                List View
            </button>
            <button
                :class="['tab-btn', { active: activeTab === 'calendar' }]"
                @click="setTab('calendar')"
            >
                Calendar View
            </button>
        </div>

        <div class="tab-content">
            <UserTaskList v-if="activeTab === 'list'" />
            <UserTaskCalendar v-if="activeTab === 'calendar'" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import UserTaskList from "./UserTaskList.vue";
import UserTaskCalendar from "./UserTaskCalendar.vue";

const route = useRoute();
const router = useRouter();

const activeTab = ref("list");

function setTab(tab) {
    activeTab.value = tab;
    router.replace({ query: { ...route.query, tab } });
}

onMounted(() => {
    if (route.query.tab === "calendar") {
        activeTab.value = "calendar";
    }
});

watch(
    () => route.query.tab,
    (newTab) => {
        if (newTab === "calendar" || newTab === "list") {
            activeTab.value = newTab;
        }
    },
);
</script>

<style scoped>
.user-tasks-page {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.tabs-header {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    border-bottom: 1px solid #e1e4e8;
    padding-bottom: 10px;
}
.tab-btn {
    background: transparent;
    border: 1px solid transparent;
    font-size: 15px;
    font-weight: 600;
    color: #586069;
    cursor: pointer;
    padding: 8px 20px;
    border-radius: 6px;
    transition: all 0.2s;
}
.tab-btn:hover {
    background: #f3f4f6;
    color: #24292e;
}
.tab-btn.active {
    background: #e94560;
    color: white;
    border-color: #e94560;
}

/* Dark mode compatibility */
:global(body.dark) .tabs-header {
    border-bottom-color: #333;
}
:global(body.dark) .tab-btn {
    color: #a0a0a0;
}
:global(body.dark) .tab-btn:hover {
    background: #2a2a2a;
    color: #fff;
}
:global(body.dark) .tab-btn.active {
    background: #e94560;
    color: #ffffff;
    border-color: #e94560;
}
</style>
