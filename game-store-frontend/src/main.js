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

// ── Глобальный error handler (debug helper) ──────────────────
// Показывает любые runtime-ошибки прямо на странице, чтобы белый
// экран превращался в видимое сообщение со стеком. Полезно для
// диагностики продакшна — DevTools'ом пользоваться не всегда удобно.
// Можно убрать когда стабилизируется. Но пока пусть висит.
function showFatalError(label, err) {
  try {
    let panel = document.getElementById('__fatal_error_panel__');
    if (!panel) {
      panel = document.createElement('div');
      panel.id = '__fatal_error_panel__';
      panel.style.cssText = [
        'position:fixed', 'inset:0', 'z-index:2147483647',
        'background:#1a0a08', 'color:#ffd1a0',
        'padding:24px', 'overflow:auto',
        'font:14px/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace',
        'white-space:pre-wrap',
      ].join(';');
      document.body.appendChild(panel);
    }
    const stack = err && err.stack ? err.stack : (err && err.message) || String(err);
    const block = document.createElement('div');
    block.style.cssText = 'border-top:2px solid #d32a18;margin-top:14px;padding-top:14px';
    block.innerHTML = '';
    const h = document.createElement('div');
    h.style.cssText = 'color:#ff8433;font-weight:700;font-size:15px;margin-bottom:8px';
    h.textContent = '⚠ ' + label;
    block.appendChild(h);
    const pre = document.createElement('pre');
    pre.style.cssText = 'margin:0;color:#ffd1a0;white-space:pre-wrap';
    pre.textContent = stack;
    block.appendChild(pre);
    panel.appendChild(block);
  } catch (_) { /* совсем уж тяжёлый случай — молчим */ }
}

window.addEventListener('error', (e) => {
  showFatalError('window.error: ' + (e.message || ''), e.error || e);
});
window.addEventListener('unhandledrejection', (e) => {
  showFatalError('unhandledrejection', e.reason);
});

const app = createApp(App)
const head = createHead()

app.config.errorHandler = (err, instance, info) => {
  showFatalError('Vue errorHandler [' + info + ']', err);
  // Дополнительно лог в консоль
  console.error('[Vue]', info, err);
};

app.use(createPinia())
app.use(router)
app.use(head)

app.mount('#app')