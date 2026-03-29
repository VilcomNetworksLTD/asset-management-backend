import axios from 'axios';
window.axios = axios;

window.axios.defaults.baseURL =
    import.meta.env.VITE_API_BASE_URL;
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add token to requests if it exists
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('user_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});