import axios from 'axios';
window.axios = axios;

window.axios.defaults.baseURL = 'http://127.0.0.1:8000';
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