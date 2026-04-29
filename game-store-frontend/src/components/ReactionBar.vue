<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import api from '../api/axios';

/**
 * ReactionBar — панель реакций под постом или комментом.
 *
 * Phase 2 / Batch E.
 *
 * Props:
 *   reactableType: 'post' | 'comment'
 *   reactableId:   number
 *   summary:       array из { palette_emoji_id, emoji_char, name, count, reacted_by_me }
 *   palette:       array из { id, emoji_char, name, is_default } — все доступные эмодзи
 *
 * Emits:
 *   updated(newSummary, totalCount) — после toggle, чтобы родитель обновил счётчики
 *
 * UX:
 *   - Видны эмодзи где count > 0 как chips, кликабельны для toggle
 *   - "+ ☻" кнопка открывает popup-палитру со всеми emoji
 *   - Уже поставленные мной — подсвечены ember-glow
 *   - Гость → редирект на /login
 *   - Frozen → 403 toast
 */

const props = defineProps({
  reactableType: { type: String, required: true },     // 'post' | 'comment'
  reactableId:   { type: Number, required: true },
  summary:       { type: Array, default: () => [] },
  palette:       { type: Array, default: () => [] },
});

const emit = defineEmits(['updated']);

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const popupOpen = ref(false);
const submitting = ref(false);

// Локальная копия summary для оптимистичного UI
const localSummary = ref([...props.summary]);

// Если props.summary меняется (родитель перезагрузил) — синхронизируем
import { watch } from 'vue';
watch(() => props.summary, (s) => { localSummary.value = [...s]; }, { deep: true });

const visibleChips = computed(() => localSummary.value.filter(r => r.count > 0));

const togglePopup = () => {
  if (!authStore.isLoggedIn) {
    router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } });
    return;
  }
  popupOpen.value = !popupOpen.value;
};

const handleToggle = async (paletteEmojiId) => {
  if (submitting.value) return;
  if (!authStore.isLoggedIn) {
    router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } });
    return;
  }

  submitting.value = true;
  try {
    const { data } = await api.post('/reactions/toggle', {
      reactable_type:   props.reactableType,
      reactable_id:     props.reactableId,
      palette_emoji_id: paletteEmojiId,
    });
    localSummary.value = data.summary;
    emit('updated', data.summary, data.total_count);
    popupOpen.value = false;
  } catch (e) {
    if (e.response?.status === 403) {
      toast.error(e.response.data?.message || 'Реакции недоступны.');
    } else if (e.response?.status === 429) {
      toast.warning('Слишком много кликов, подождите.');
    } else {
      toast.error('Не удалось.');
    }
  } finally {
    submitting.value = false;
  }
};

const closePopup = () => { popupOpen.value = false; };
</script>

<template>
  <div class="reaction-bar">
    <!-- Видимые реакции — clickable chips -->
    <button
      v-for="r in visibleChips"
      :key="r.palette_emoji_id"
      class="reaction-chip"
      :class="{ active: r.reacted_by_me }"
      :title="r.name"
      :disabled="submitting"
      @click="handleToggle(r.palette_emoji_id)"
    >
      <span class="reaction-emoji">{{ r.emoji_char }}</span>
      <span class="reaction-count">{{ r.count }}</span>
    </button>

    <!-- Кнопка «добавить реакцию» -->
    <div class="add-reaction-wrap">
      <button
        class="add-reaction"
        :disabled="submitting"
        @click="togglePopup"
        title="Добавить реакцию"
      >
        ☺︎ <span class="ar-plus">+</span>
      </button>

      <!-- Popup палитра -->
      <Transition name="palette">
        <div v-if="popupOpen" class="palette-popup" @click.stop>
          <div class="palette-arrow"></div>
          <div class="palette-grid">
            <button
              v-for="p in palette"
              :key="p.id"
              class="palette-item"
              :class="{ active: localSummary.find(r => r.palette_emoji_id === p.id && r.reacted_by_me) }"
              :disabled="submitting"
              :title="p.description || p.name"
              @click="handleToggle(p.id)"
            >
              <span class="palette-emoji">{{ p.emoji_char }}</span>
              <span class="palette-name">{{ p.name }}</span>
            </button>
          </div>
        </div>
      </Transition>

      <!-- Backdrop для закрытия popup при клике мимо -->
      <div v-if="popupOpen" class="palette-backdrop" @click="closePopup"></div>
    </div>
  </div>
</template>

<style scoped>
.reaction-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
}

/* Chip */
.reaction-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 11px;
  background: linear-gradient(180deg, rgba(8, 6, 10, 0.5), rgba(18, 16, 13, 0.6));
  border: 1px solid var(--iron-mid);
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.15s var(--ease-smoke);
  font-size: 0.92rem;
}
.reaction-chip:hover:not(:disabled) {
  border-color: var(--bronze);
  background: linear-gradient(180deg, rgba(122, 28, 20, 0.18), rgba(8, 6, 10, 0.5));
}
.reaction-chip.active {
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.18), rgba(168, 35, 24, 0.12));
  border-color: rgba(226, 67, 16, 0.55);
  box-shadow: 0 0 10px rgba(226, 67, 16, 0.25);
}
.reaction-chip:disabled { opacity: 0.6; cursor: wait; }
.reaction-emoji { font-size: 1rem; line-height: 1; }
.reaction-count {
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: var(--fw-semibold);
  color: var(--text-bone);
  letter-spacing: 0.3px;
}
.reaction-chip.active .reaction-count { color: var(--ember-gold); }

/* + кнопка */
.add-reaction-wrap {
  position: relative;
  display: inline-flex;
}
.add-reaction {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 4px 11px;
  background: transparent;
  color: var(--text-ash);
  border: 1px dashed var(--iron-mid);
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 0.95rem;
}
.add-reaction:hover:not(:disabled) {
  color: var(--ember-spark);
  border-color: var(--ember-spark);
  background: rgba(226, 67, 16, 0.08);
}
.add-reaction:disabled { opacity: 0.5; cursor: wait; }
.ar-plus { font-size: 0.78rem; font-weight: var(--fw-bold); }

/* Popup палитра */
.palette-popup {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  z-index: 50;
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--bronze-dark);
  border-radius: 8px;
  box-shadow: var(--shadow-deep), 0 0 18px rgba(226, 67, 16, 0.18);
  padding: 10px;
  min-width: 280px;
}
.palette-arrow {
  position: absolute;
  top: -6px;
  left: 16px;
  width: 12px;
  height: 12px;
  background: var(--ash-stone);
  border-top: 1px solid var(--bronze-dark);
  border-left: 1px solid var(--bronze-dark);
  transform: rotate(45deg);
}
.palette-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 6px;
}
.palette-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  background: rgba(8, 6, 10, 0.4);
  border: 1px solid transparent;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s var(--ease-smoke);
  text-align: left;
}
.palette-item:hover:not(:disabled) {
  background: rgba(122, 28, 20, 0.2);
  border-color: var(--bronze);
}
.palette-item.active {
  background: linear-gradient(180deg, rgba(226, 67, 16, 0.15), rgba(168, 35, 24, 0.08));
  border-color: rgba(226, 67, 16, 0.5);
}
.palette-item:disabled { opacity: 0.6; cursor: wait; }
.palette-emoji { font-size: 1.4rem; line-height: 1; flex-shrink: 0; }
.palette-name {
  font-family: var(--font-display);
  font-size: 0.82rem;
  color: var(--text-bone);
  letter-spacing: 0.3px;
}

/* Backdrop невидимый, ловит клики мимо popup */
.palette-backdrop {
  position: fixed;
  inset: 0;
  z-index: 49;
  background: transparent;
}

/* Анимация palette */
.palette-enter-active, .palette-leave-active {
  transition: all 0.18s var(--ease-smoke);
}
.palette-enter-from, .palette-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* Mobile */
@media (max-width: 480px) {
  .palette-popup { min-width: 240px; }
  .palette-grid { grid-template-columns: 1fr; }
}
</style>
