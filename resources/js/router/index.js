import { createRouter, createWebHistory } from "vue-router";
import LoginPage from "../views/LoginPage.vue";
import RegisterPage from "../views/RegisterPage.vue";
import AdminDashboardLayout from "../layouts/AdminDashboardLayout.vue";
import UserDashboardLayout from "../layouts/UserDashboardLayout.vue";
import AdminDashboardHome from "../views/admin/AdminDashboardHome.vue";
import UserDashboardHome from "../views/user/UserDashboardHome.vue";
import UserTasks from "../views/user/UserTasks.vue";
import UserTaskDetail from "../views/user/UserTaskDetail.vue";
import UserList from "../views/admin/UserList.vue";
import UserProjectList from "../views/user/UserProjectList.vue";
import UserProjectDetail from "../views/user/UserProjectDetail.vue";
import UserNoteList from "../views/user/UserNoteList.vue";
import UserNoteDetail from "../views/user/UserNoteDetail.vue";
import FlashCardGroups from "../views/user/FlashCardGroups.vue";
import FlashCardList from "../views/user/FlashCardList.vue";
import FlashCardStudy from "../views/user/FlashCardStudy.vue";
import FlashCardDetail from "../views/user/FlashCardDetail.vue";

function getAuthUser() {
    try {
        return JSON.parse(localStorage.getItem("auth_user") || "null");
    } catch {
        return null;
    }
}

function getDefaultRoute() {
    const user = getAuthUser();
    if (user && typeof user.role === "number") {
        return user.role === 0 ? "/admin" : "/user";
    }
    return "/login";
}

const routes = [
    {
        path: "/login",
        name: "Login",
        component: LoginPage,
        meta: { guest: true },
    },
    {
        path: "/register",
        name: "Register",
        component: RegisterPage,
        meta: { guest: true },
    },
    {
        path: "/admin",
        component: AdminDashboardLayout,
        meta: { requiresAuth: true, requiresAdmin: true },
        children: [
            {
                path: "",
                name: "AdminDashboard",
                component: AdminDashboardHome,
            },
            {
                path: "users",
                name: "UserList",
                component: UserList,
            },
        ],
    },
    {
        path: "/user",
        component: UserDashboardLayout,
        meta: { requiresAuth: true, requiresUser: true },
        children: [
            {
                path: "",
                name: "UserDashboard",
                component: UserDashboardHome,
            },
            {
                path: "tasks",
                name: "UserTasks",
                component: UserTasks,
            },
            {
                path: "tasks/:id",
                name: "UserTaskDetail",
                component: UserTaskDetail,
            },
            {
                path: "projects",
                name: "UserProjectList",
                component: UserProjectList,
            },
            {
                path: "projects/:id",
                name: "UserProjectDetail",
                component: UserProjectDetail,
            },
            {
                path: "notes",
                name: "UserNoteList",
                component: UserNoteList,
            },
            {
                path: "notes/:id",
                name: "UserNoteDetail",
                component: UserNoteDetail,
            },
            {
                path: "flash-cards",
                name: "FlashCardGroups",
                component: FlashCardGroups,
            },
            {
                path: "flash-cards/groups/:groupId",
                name: "FlashCardList",
                component: FlashCardList,
            },
            {
                path: "flash-cards/cards/:id",
                name: "FlashCardDetail",
                component: FlashCardDetail,
            },
            {
                path: "flash-cards/study",
                name: "FlashCardStudy",
                component: FlashCardStudy,
            },
        ],
    },
    {
        path: "/dashboard",
        redirect: () => getDefaultRoute(),
    },
    {
        path: "/home",
        redirect: () => getDefaultRoute(),
    },
    {
        path: "/",
        redirect: "/user",
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem("auth_token");
    const user = getAuthUser();

    // If not logged in, only allow login and register
    if (!token && to.path !== "/login" && to.path !== "/register") {
        next({ path: "/login", query: { redirect: to.fullPath } });
        return;
    }

    // If already logged in, prevent going back to login page
    if (to.meta.guest && token) {
        next(getDefaultRoute());
        return;
    }

    if (
        to.meta.requiresAdmin &&
        token &&
        typeof user?.role === "number" &&
        user.role !== 0
    ) {
        next("/user");
        return;
    }

    if (
        to.meta.requiresUser &&
        token &&
        typeof user?.role === "number" &&
        user.role !== 1
    ) {
        next("/admin");
        return;
    }

    next();
});

export default router;
