<template>
    <div class="dashboard" :class="{ 'sidebar-open': sidebarOpen }">
        <div
            class="sidebar-overlay"
            aria-hidden="true"
            @click="sidebarOpen = false"
        ></div>
        <aside class="sidebar">
            <div class="sidebar-header">
                <router-link
                    to="/user"
                    class="logo"
                    @click="sidebarOpen = false"
                    ><img
                        src="../assets/images/icons/dtask.png"
                        width="50%"
                        alt="Dtask"
                /></router-link>
            </div>
            <nav class="sidebar-nav">
                <router-link
                    to="/user"
                    class="nav-item"
                    exact-active-class="active"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon">◉</span>
                    <span>Overview</span>
                </router-link>
                <router-link
                    to="/user/tasks"
                    class="nav-item"
                    active-class="active"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon"
                        ><img
                            src="../assets/images/icons/task.png"
                            width="20px"
                    /></span>
                    <span>Tasks</span>
                </router-link>
                <router-link
                    to="/user/projects"
                    class="nav-item"
                    active-class="active"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon"
                        ><img
                            src="../assets/images/icons/project.png"
                            width="20px"
                    /></span>
                    <span>Projects</span>
                </router-link>
                <router-link
                    to="/user/notes"
                    class="nav-item"
                    active-class="active"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon"
                        ><img
                            src="../assets/images/icons/note.png"
                            width="20px"
                    /></span>
                    <span>Notes</span>
                </router-link>
                <router-link
                    to="/user/flash-cards"
                    class="nav-item"
                    active-class="active"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon">📇</span>
                    <span>Flash Cards</span>
                </router-link>
            </nav>
        </aside>
        <div class="main-wrap">
            <header class="header">
                <button
                    type="button"
                    class="btn-menu"
                    aria-label="Open menu"
                    @click="sidebarOpen = true"
                >
                    <span class="btn-menu-bar"></span>
                    <span class="btn-menu-bar"></span>
                    <span class="btn-menu-bar"></span>
                </button>
                <h1 class="page-title">{{ pageTitle }}</h1>
                <div class="header-actions">
                    <button
                        type="button"
                        class="btn-theme"
                        :title="isDark ? 'Light mode' : 'Dark mode'"
                        @click="toggleTheme"
                    >
                        {{ isDark ? "☀" : "🌙" }}
                    </button>
                    <span class="user-name">{{ user?.name }}</span>
                    <span class="role-badge user">User</span>
                    <button class="btn-logout" @click="handleLogout">
                        Log out
                    </button>
                </div>
            </header>
            <main class="content">
                <router-view v-slot="{ Component }">
                    <transition name="fade" mode="out-in">
                        <component :is="Component" />
                    </transition>
                </router-view>
            </main>
        </div>
    </div>
</template>

<script setup>
import "../styles/common.css";
import "../styles/user-dashboard-layout.css";
import { computed, ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuth } from "../composables/useAuth";
import { useTheme } from "../composables/useTheme";

const router = useRouter();
const route = useRoute();
const { user, logout } = useAuth();
const { isDark, toggle: toggleTheme } = useTheme();
const sidebarOpen = ref(false);

const pageTitle = computed(() => {
    const titles = {
        UserDashboard: "Overview",
        UserTasks: "Tasks",
        UserProjectList: "Projects",
        UserProjectDetail: "Project detail",
        UserTaskDetail: "Task detail",
        UserNoteList: "Notes",
        UserNoteDetail: "Note detail",
        FlashCardGroups: "Flash Cards",
        FlashCardList: "Flash Cards",
        FlashCardDetail: "Card detail",
        FlashCardStudy: "Study Flash Cards",
    };
    return titles[route.name] || "Overview";
});

async function handleLogout() {
    await logout();
    router.push("/login");
}
</script>
