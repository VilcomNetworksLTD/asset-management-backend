import axios from 'axios';
window.axios = axios;

window.axios.defaults.baseURL =
    import.meta.env.VITE_API_BASE_URL;
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Add token to requests if it exists
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('user_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Handle unauthorized responses by redirecting to login
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('user_token');
            localStorage.removeItem('user_data');
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);