<template>
  <div class="review-form-box">
    <span class="rf-rivet rf-rivet--tl" aria-hidden="true"></span>
    <span class="rf-rivet rf-rivet--tr" aria-hidden="true"></span>
    <span class="rf-rivet rf-rivet--bl" aria-hidden="true"></span>
    <span class="rf-rivet rf-rivet--br" aria-hidden="true"></span>

    <div class="form-head">
      <span class="tribal-eyebrow">
        <span class="eb-spike"></span>
        Суд воина
        <span class="eb-spike"></span>
      </span>
      <h3 class="form-title">Оставьте свой отзыв</h3>
    </div>

    <form @submit.prevent="submitReview" class="form-grid">

      <div class="rating-field">
        <label class="label">Общая оценка</label>
        <div class="control rating-control">
          <span v-for="star in 5" :key="star" class="star"
                :class="{ 'is-filled': star <= reviewData.rating }"
                @click="setRating(star)"
                @mouseover="hoverRating = star"
                @mouseleave="hoverRating = 0"
                :style="{ color: star <= hoverRating ? 'var(--ember-gold)' : '' }">
            &#9733;
          </span>
          <span class="rating-hint" v-if="reviewData.rating">{{ reviewData.rating }} / 5</span>
        </div>
      </div>

      <div class="title-field">
        <label for="reviewTitle" class="label">Заголовок (необязательно)</label>
        <div class="control">
          <input type="text" id="reviewTitle" class="input" v-model="reviewData.title" placeholder="Кратко, о чём ваш отзыв">
        </div>
      </div>

      <div class="body-field">
        <label for="reviewBody" class="label">Текст отзыва</label>
        <div class="control">
          <textarea id="reviewBody" class="textarea" v-model="reviewData.body" placeholder="Поделитесь впечатлениями от клинка…"></textarea>
        </div>
      </div>

      <div v-if="error" class="error-message" role="alert">
        {{ error }}
      </div>

      <div class="submit-field">
        <button type="submit" class="submit-btn" :disabled="isSubmitting">
          <span v-if="isSubmitting" class="btn-spin" aria-hidden="true"></span>
          <span class="btn-label">{{ isSubmitting ? 'Куём…' : 'Опубликовать суд' }}</span>
        </button>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import api from '../api/axios';

const props = defineProps({ gameId: { type: [String, Number], required: true } });
const emit = defineEmits(['review-submitted']);

const reviewData = reactive({
  rating: 0,
  title: '',
  body: '',
});
const hoverRating = ref(0);

const isSubmitting = ref(false);
const error = ref('');

const setRating = (star) => {
  reviewData.rating = star;
};

const submitReview = async () => {
  if (reviewData.rating === 0) {
    error.value = 'Поставьте оценку от 1 до 5 звёзд.';
    return;
  }
  if (reviewData.body.trim() === '') {
    error.value = 'Напишите текст отзыва.';
    return;
  }

  isSubmitting.value = true;
  error.value = '';

  try {
    await api.post(`/games/${props.gameId}/reviews`, reviewData);
    reviewData.rating = 0;
    reviewData.title = '';
    reviewData.body = '';
    emit('review-submitted');
  } catch (err) {
    error.value = err.response?.data?.message || 'Произошла ошибка при отправке отзыва.';
    console.error(err);
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.review-form-box {
  position: relative;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  padding: 30px 30px 26px;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
}

.rf-rivet {
  position: absolute;
  width: 8px; height: 8px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  z-index: 2;
  box-shadow: inset -1px -1px 1px rgba(0, 0, 0, 0.7);
}
.rf-rivet--tl { top: 12px; left: 12px; }
.rf-rivet--tr { top: 12px; right: 12px; }
.rf-rivet--bl { bottom: 12px; left: 12px; }
.rf-rivet--br { bottom: 12px; right: 12px; }

.form-head {
  margin-bottom: 22px;
  padding-bottom: 14px;
  border-bottom: 1px dashed var(--iron-dark);
}
.tribal-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 2.8px;
  text-transform: uppercase;
  color: var(--bronze);
  margin-bottom: 8px;
}
.eb-spike {
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 6px solid var(--bronze);
  filter: drop-shadow(0 0 3px rgba(199, 154, 94, 0.5));
}
.form-title {
  font-family: var(--font-display);
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--text-bright);
  margin: 0;
  letter-spacing: 0.3px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.45);
}

.label {
  display: block;
  font-family: var(--font-ui);
  font-weight: 700;
  font-size: 0.74rem;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--bronze);
  margin-bottom: 8px;
}

.form-grid {
  display: grid;
  gap: 20px;
}

/* ── рейтинг ── */
.rating-control {
  display: flex;
  align-items: center;
  gap: 6px;
}
.rating-control .star {
  font-size: 2rem;
  color: var(--iron-warm);
  cursor: pointer;
  transition: color 0.2s var(--ease-smoke), transform 0.15s var(--ease-forge), filter 0.2s var(--ease-smoke);
}
.rating-control .star:hover { transform: scale(1.18); }
.rating-control .star.is-filled {
  color: var(--ember-gold);
  filter: drop-shadow(0 0 6px rgba(255, 201, 121, 0.55));
}
.rating-hint {
  margin-left: 10px;
  font-family: var(--font-ui);
  font-size: 0.86rem;
  color: var(--ember-spark);
  letter-spacing: 0.5px;
}

/* ── поля ── */
.input, .textarea {
  width: 100%;
  box-sizing: border-box;
  background: linear-gradient(180deg,
    rgba(8, 6, 10, 0.75) 0%,
    rgba(18, 16, 13, 0.85) 100%);
  color: var(--text-bone);
  border: 1px solid var(--iron-mid);
  padding: 13px 16px;
  font-family: var(--font-body);
  font-size: 0.98rem;
  box-shadow: var(--inset-iron-top);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}
.input::placeholder, .textarea::placeholder { color: var(--text-void); }
.input:focus, .textarea:focus {
  outline: none;
  border-color: var(--ember-flame);
  box-shadow: var(--inset-iron-top), 0 0 0 3px rgba(226, 67, 16, 0.15), 0 0 12px rgba(255, 122, 43, 0.22);
}
.textarea {
  min-height: 130px;
  resize: vertical;
}

/* ── ошибка ── */
.error-message {
  background: linear-gradient(180deg, rgba(138, 31, 24, 0.25), rgba(90, 20, 18, 0.35));
  color: #ffb4a8;
  border: 1px solid rgba(194, 40, 26, 0.45);
  padding: 11px 14px;
  text-align: center;
  font-family: var(--font-body);
  font-size: 0.9rem;
  box-shadow: var(--inset-iron-top);
}

/* ── submit ── */
.submit-btn {
  position: relative;
  width: 100%;
  border: 1px solid var(--ember-heart);
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: 1.3px;
  text-transform: uppercase;
  padding: 15px 14px;
  cursor: pointer;
  background: var(--grad-ember);
  color: var(--text-bright);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.65);
  clip-path: var(--clip-forged-sm);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: transform 0.18s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
}
.submit-btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%, rgba(255, 201, 121, 0.4) 50%, transparent 100%);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35), var(--glow-ember-strong);
}
.submit-btn:hover:not(:disabled)::after { transform: translateX(120%); }
.submit-btn:disabled {
  cursor: not-allowed;
  background: var(--ash-stone);
  border-color: var(--iron-mid);
  color: var(--text-smoke);
  box-shadow: var(--inset-iron-top);
  text-shadow: none;
}
.btn-label { position: relative; z-index: 1; }

.btn-spin {
  width: 14px; height: 14px;
  border: 2px solid rgba(255, 248, 234, 0.3);
  border-top-color: var(--text-bright);
  border-radius: 50%;
  animation: rfSpin 0.8s linear infinite;
  flex-shrink: 0;
}
@keyframes rfSpin { to { transform: rotate(360deg); } }
</style>
