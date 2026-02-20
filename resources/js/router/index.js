import { createRouter, createWebHistory } from 'vue-router';
import AdminLayout from '../components/layouts/AdminLayout.vue';
import UserLayout from '../components/layouts/UserLayout.vue';
import AdminDashboard from '../views/AdminDashboard.vue';
import UserDashboard from '../views/UserDashboard.vue';
import AssetList from '../pages/AssetList.vue';
import ConsumableList from '../pages/ConsumableList.vue';
import PeopleList from '../pages/PeopleList.vue';
import AccessoryList from '../pages/AccessoryList.vue';
import LicenseList from '../pages/LicenseList.vue';
import ComponentList from '../pages/ComponentList.vue';
import MaintenanceList from '../pages/MaintenanceList.vue';
import ActivityLogList from '../pages/ActivityLogList.vue';
import ReportsList from '../pages/ReportsList.vue';
import Settings from '../pages/Settings.vue';
import TransferList from '../pages/TransferList.vue';
import TicketList from '../pages/TicketList.vue';
import UserAssetList from '../pages/UserAssetList.vue';

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
    },

    // Admin dashboard Section
    {
        path: '/dashboard/admin',
        component: AdminLayout,
        meta: { requiresAuth: true, requiresAdmin: true },
        children: [
            { path: '', name: 'dashboard-admin', component: AdminDashboard },
            { path: 'assets', name: 'assets-list', component: AssetList },
            { path: 'consumables', name: 'consumables-list', component: ConsumableList },
            { path: 'people', name: 'people-list', component: PeopleList },
            { path: 'accessories', name: 'accessories-list', component: AccessoryList },
            { path: 'licenses', name: 'licenses-list', component: LicenseList },
            { path: 'components', name: 'components-list', component: ComponentList },
            { path: 'maintenances', name: 'maintenances-list', component: MaintenanceList },
            { path: 'logs', name: 'activitylogs-list', component: ActivityLogList },
            { path: 'reports', name: 'reports-list', component: ReportsList },
            { path: 'settings', name: 'settings', component: Settings },
            { path: 'transfers', redirect: { name: 'transfer-assets-list' } },
            { path: 'transfers/assets', name: 'transfer-assets-list', component: TransferList, props: { mode: 'transfer', title: 'Asset Transfer Requests' } },
            { path: 'transfers/returns', name: 'return-assets-list', component: TransferList, props: { mode: 'return', title: 'Asset Return Requests' } },
            { path: 'tickets', name: 'ticket-list', component: TicketList },
        ]
    },

    // User dashboard Section
    {
        path: '/dashboard/user',
        component: UserLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', name: 'dashboard-user', component: UserDashboard },
            { path: 'my-assets', name: 'user-assets', component: UserAssetList },
            { path: 'my-tickets', name: 'user-tickets', component: TicketList },
            {
                path: 'report-issue',
                name: 'user.report-issue',
                component: () =>
                    import ('../pages/IssueReportForm.vue'),
                meta: { layout: 'UserLayout' }
            },
            {
                path: 'profile',
                name: 'user.profile',
                component: () =>
                    import ('../pages/UserProfile.vue'),
                meta: { layout: 'UserLayout' }
            },
            {
                path: 'settings',
                name: 'user.settings',
                component: () =>
                    import ('../pages/UserSettings.vue'),
                meta: { layout: 'UserLayout' }
            },
            {
                path: 'request-transfer',
                name: 'user.request-transfer',
                // This assumes you will create a TransferRequestForm.vue in your pages folder
                component: () =>
                    import ('../pages/TransferRequestForm.vue'),
                meta: {
                    layout: 'UserLayout',
                    requiresAuth: true
                }
            },
            {
                path: 'request-return',
                name: 'user.request-return',
                component: () =>
                    import ('../pages/ReturnAssetForm.vue'),
                meta: {
                    layout: 'UserLayout',
                    requiresAuth: true
                }
            },
            {
                path: 'inbound-verifications',
                name: 'user.inbound-verifications',
                component: () =>
                    import ('../pages/InboundTransfer.vue'),
                meta: {
                    layout: 'UserLayout',
                    requiresAuth: true
                }
            },
        ]
    },

    {
        path: '/dashboard',
        name: 'dashboard',
        meta: { requiresAuth: true, isDashboard: true }
    }
];

const router = createRouter({
    history: createWebHistory(
        import.meta.env.BASE_URL),
    routes
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('user_token');
    const userData = localStorage.getItem('user_data');

    if (to.meta.requiresAuth && !token) return next({ name: 'login' });

    if (to.meta.requiresAuth && userData) {
        try {
            const user = JSON.parse(userData);
            const userRole = user.role || 'user';

            if (to.meta.isDashboard) {
                return next({ name: userRole === 'admin' ? 'dashboard-admin' : 'dashboard-user' });
            }

            if (to.meta.requiresAdmin && userRole !== 'admin') {
                return next({ name: 'dashboard-user' });
            }

            next();
        } catch (err) {
            localStorage.removeItem('user_token');
            localStorage.removeItem('user_data');
            return next({ name: 'login' });
        }
    } else next();
});

export default router;