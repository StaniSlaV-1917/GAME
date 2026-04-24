import './assets/main.css'
import './assets/themes.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createHead } from '@vueuse/head'
import App from './App.vue'
// NOTE: legacy ./assets/style.css отключён — конфликтовал с Ashenforge
// (старые background: #111827 на body, зелёная палитра auth-форм и т.п.)
// Файл оставлен на диске; если что-то нужное обнаружится — перенесём в новые компоненты.
import router from './router'

const app = createApp(App)
const head = createHead()

app.use(createPinia())
app.use(router)
app.use(head)

app.mount('#app')