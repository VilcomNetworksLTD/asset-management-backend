import { createRouter, createWebHistory } from 'vue-router';

const AdminLayout = () => import('../components/layouts/AdminLayout.vue');
const UserLayout = () => import('../components/layouts/UserLayout.vue');
const AdminDashboard = () => import('../views/AdminDashboard.vue');
const UserDashboard = () => import('../views/UserDashboard.vue');
const AssetList = () => import('../pages/AssetList.vue');
const AssetDetail = () => import('../pages/AssetDetail.vue');
const ConsumableList = () => import('../pages/ConsumableList.vue');
const PeopleList = () => import('../pages/PeopleList.vue');
const AccessoryList = () => import('../pages/AccessoryList.vue');
const LicenseList = () => import('../pages/LicenseList.vue');
const MaintenanceList = () => import('../pages/MaintenanceList.vue');
const ActivityLogList = () => import('../pages/ActivityLogList.vue');
const ReportsList = () => import('../pages/ReportsList.vue');
const Settings = () => import('../pages/Settings.vue');
const TransferList = () => import('../pages/TransferList.vue');
const TicketList = () => import('../pages/TicketList.vue');
const UserAssetList = () => import('../pages/UserAssetList.vue');
const SslCertificateList = () => import('../pages/SslCertificateList.vue');
const Supplier = () => import('../pages/Supplier.vue');
const DepartmentAssets = () => import('../pages/DepartmentAssets.vue');
const HodDepartmentAssets = () => import('../pages/HodDepartmentAssets.vue');
const Support = () => import('../pages/Support.vue');
const Feedback = () => import('../pages/Feedback.vue');
const TonerInventory = () => import('../pages/TonerInventory.vue');
const Scanner = () => import('../pages/Scanner.vue');
const InventoryHub = () => import('../pages/InventoryHub.vue');
const MovementsHub = () => import('../pages/MovementsHub.vue');
const DirectoryHub = () => import('../pages/DirectoryHub.vue');
const OperationsHub = () => import('../pages/OperationsHub.vue');
const MyWorkspaceHub = () => import('../pages/MyWorkspaceHub.vue');
const DefinitionsHub = () => import('../pages/DefinitionsHub.vue');
const ManagementPurchaseRequests = () => import('../pages/ManagementPurchaseRequests.vue');
const AdminPurchaseRequests = () => import('../pages/AdminPurchaseRequests.vue');

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
    {
        path: '/:pathMatch(.*)*',
        redirect: { name: 'login' }
    },

    // Admin dashboard Section
    {
        path: '/dashboard/admin',
        component: AdminLayout,
        meta: { requiresAuth: true, requiresAdmin: true },
        children: [
            { path: '', name: 'dashboard-admin', component: AdminDashboard },
            {
                path: 'scan',
                name: 'admin-scanner',
                component: Scanner,
                props: { targetRoute: 'asset-detail' }
            },
            {
                path: 'assets',
                name: 'assets-list',
                component: AssetList,
            },
            {
                path: 'assets/:id',
                component: AssetDetail,
                name: 'asset-detail',
                props: true
            },
            { path: 'toner-lifecycle', name: 'consumables-list', component: ConsumableList },
            { path: 'toner-inventory', name: 'toner-inventory', component: TonerInventory },
            { path: 'people', name: 'people-list', component: PeopleList },
            // REMOVED: UserHistoryPage route was here
            { path: 'suppliers', name: 'suppliers-list', component: Supplier },
            { path: 'accessories', name: 'accessories-list', component: AccessoryList },
            { path: 'licenses', name: 'licenses-list', component: LicenseList },

            { path: 'maintenances', name: 'maintenances-list', component: MaintenanceList },
            { path: 'logs', name: 'activitylogs-list', component: ActivityLogList },
            { path: 'reports', name: 'reports-list', component: ReportsList },
            {
                path: 'settings',
                name: 'settings',
                component: Settings,
            },
            { path: 'tickets', name: 'ticket-list', component: TicketList },

            // Hub Routes
            { path: 'inventory', name: 'admin-inventory-hub', component: InventoryHub },
            { path: 'movements', name: 'admin-movements-hub', component: MovementsHub },
            { path: 'directory', name: 'admin-directory-hub', component: DirectoryHub },
            { path: 'operations', name: 'admin-operations-hub', component: OperationsHub },

            { path: 'transfers', redirect: { name: 'transfer-assets-list' } },
            {
                path: 'transfers/assets',
                name: 'transfer-assets-list',
                component: TransferList,
                props: { mode: 'transfer', title: 'Asset Transfer Requests' }
            },
            {
                path: 'transfers/returns',
                name: 'return-assets-list',
                component: TransferList,
                props: { mode: 'return', title: 'Asset Return Requests' }
            },
            {
                path: 'ssl-certificates',
                name: 'ssl-certificates',
                component: SslCertificateList,
                meta: { requiresAuth: false }
            },
            { path: 'support-tickets', name: 'admin-tickets', component: TicketList },
            { path: 'support', name: 'admin-support', component: Support },
            { path: 'feedback', name: 'admin-feedback', component: Feedback },
            { path: 'purchase-escalations', name: 'admin-purchase-requests', component: AdminPurchaseRequests },
        ]
    },

    // User dashboard Section
    {
        path: '/dashboard/user',
        component: UserLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', name: 'dashboard-user', component: UserDashboard },
            { path: 'workspace', name: 'user-workspace-hub', component: MyWorkspaceHub },
            {
                path: 'scan',
                name: 'user-scanner',
                component: Scanner,
                props: { targetRoute: 'user-asset-detail' }
            },
            { path: 'my-assets', name: 'user-assets', component: UserAssetList },
            {
                path: 'my-licenses',
                name: 'user-licenses',
                component: () =>
                    import ('../pages/UserLicenseList.vue'),
                meta: { layout: 'UserLayout', requiresAuth: true }
            },

            {
                path: 'my-accessories',
                name: 'user-accessories',
                component: () =>
                    import ('../pages/UserAccessoryList.vue'),
                meta: { layout: 'UserLayout', requiresAuth: true }
            },
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
                component: () =>
                    import ('../pages/TransferRequestForm.vue'),
                meta: { layout: 'UserLayout', requiresAuth: true }
            },
            {
                path: 'request-return',
                name: 'user.request-return',
                component: () =>
                    import ('../pages/ReturnAssetForm.vue'),
                meta: { layout: 'UserLayout', requiresAuth: true }
            },
            {
                path: 'inbound-verifications',
                name: 'user.inbound-verifications',
                component: () =>
                    import ('../pages/InboundTransfer.vue'),
                meta: { layout: 'UserLayout', requiresAuth: true }
            },

            // REMOVED: MyHistory route was here
            {
                path: 'department-assets',
                name: 'department-assets',
                component: DepartmentAssets,
                meta: { layout: 'UserLayout', requiresAuth: true }
            },
            {
                path: 'manage-definitions',
                name: 'hod-definitions',
                component: DefinitionsHub,
                meta: { layout: 'UserLayout', requiresAuth: true }
            },
            {
                path: 'manage-assets',
                name: 'hod-assets-list',
                component: AssetList,
                meta: { layout: 'UserLayout', requiresAuth: true }
            },
            {
                path: 'assets/:id',
                name: 'user-asset-detail',
                component: AssetDetail,
                props: true
            },
            { path: 'support', name: 'user-support', component: Support },
            { path: 'feedback', name: 'user-feedback', component: Feedback },
            
            // New Purchase Request & Management Routes
            { 
              path: 'new-asset-request', 
              name: 'user.new-asset-request', 
              component: () => import('../pages/NewAssetRequestForm.vue'),
              meta: { layout: 'UserLayout', requiresAuth: true }
            },
            {
              path: 'purchase-escalations',
              name: 'management-purchase-requests',
              component: ManagementPurchaseRequests,
              meta: { layout: 'UserLayout', requiresAuth: true,requiresManagement: true }
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
            const userRole = user.role ? user.role.toLowerCase() : 'staff';

            if (to.meta.isDashboard) {
                if (userRole === 'admin') return next({ name: 'dashboard-admin' });
                return next({ name: 'dashboard-user' });
            }

            if (to.meta.requiresAdmin && userRole !== 'admin') {
                return next({ name: 'dashboard-user' });
            }

            if (to.meta.requiresManagement && userRole !== 'management') {
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
