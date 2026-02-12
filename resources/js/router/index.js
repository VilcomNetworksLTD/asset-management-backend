import { createRouter, createWebHistory } from 'vue-router'

const routes = [{
        path: '/',
        name: 'login',
        component: () =>
            import ('../views/LoginView.vue')
    },
    {
        path: '/register',
        name: 'register',
        component: () =>
            import ('../views/RegisterView.vue')
    },
    {
        path: '/verify-otp',
        name: 'verify-otp',
        component: () =>
            import ('../views/VerifyOtpView.vue')
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () =>
            import ('../views/ForgotPasswordView.vue')
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () =>
            import ('../views/ResetPasswordView.vue')
    }
]

const router = createRouter({
    history: createWebHistory(
        import.meta.env.BASE_URL),
    routes
})

export default router