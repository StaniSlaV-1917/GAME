import { ref } from 'vue';

// Модульный синглтон — один экземпляр на всё приложение
const toasts = ref([]);
let nextId = 0;

function add(message, type = 'info', duration = 4000) {
  const id = ++nextId;
  const toast = { id, message, type, visible: false };
  toasts.value.push(toast);

  // Небольшая задержка чтобы CSS-transition успел сработать
  setTimeout(() => { toast.visible = true; }, 30);

  // Автоудаление
  if (duration > 0) {
    setTimeout(() => remove(id), duration);
  }

  return id;
}

function remove(id) {
  const idx = toasts.value.findIndex(t => t.id === id);
  if (idx === -1) return;
  toasts.value[idx].visible = false;
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 300);
}

export function useToast() {
  return {
    toasts,
    remove,
    success: (msg, dur)  => add(msg, 'success', dur),
    error:   (msg, dur)  => add(msg, 'error',   dur ?? 6000),
    warning: (msg, dur)  => add(msg, 'warning', dur),
    info:    (msg, dur)  => add(msg, 'info',    dur),
  };
}
