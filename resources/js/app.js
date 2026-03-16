import { createApp } from 'vue'
import '../css/app.css';

import App from './App.vue'
import './bootstrap' // Setup axios with baseURL and interceptors
import router from './router' // This imports the file you just created!

// global UI components
import Loader from './components/Loader.vue'

const app = createApp(App)

app.component('Loader', Loader)

app.use(router) // This "plugs in" the router
app.mount('#app')