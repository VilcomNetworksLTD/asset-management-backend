import { createApp } from 'vue'
import App from './App.vue'
import router from './router' // This imports the file you just created!

const app = createApp(App)

app.use(router) // This "plugs in" the router
app.mount('#app')