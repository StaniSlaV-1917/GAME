<template>
  <div class="review-form-box">
    <h3 class="title is-4">Оставить отзыв</h3>
    <form @submit.prevent="submitReview">
      <div class="field">
        <label class="label">Оценка</label>
        <div class="control rating-control">
          <span v-for="star in 5" :key="star" class="star" :class="{ 'is-filled': star <= rating }" @click="setRating(star)">
            &#9733;
          </span>
        </div>
      </div>
      <div class="field">
        <label class="label">Ваш отзыв</label>
        <div class="control">
          <textarea v-model="comment" class="textarea" placeholder="Расскажите о своих впечатлениях..."></textarea>
        </div>
      </div>
      <div class="field">
        <div class="control">
          <button type="submit" class="button is-primary" :disabled="isSubmitting">
            {{ isSubmitting ? 'Отправка...' : 'Отправить отзыв' }}
          </button>
        </div>
      </div>
      <p v-if="error" class="help is-danger">{{ error }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '../api/axios';

const props = defineProps({ gameId: { type: [String, Number], required: true } });
const emit = defineEmits(['review-submitted']);

const rating = ref(0);
const comment = ref('');
const isSubmitting = ref(false);
const error = ref('');

const setRating = (star) => {
  rating.value = star;
};

const submitReview = async () => {
  if (rating.value === 0 || comment.value.trim() === '') {
    error.value = 'Пожалуйста, поставьте оценку и напишите текст отзыва.';
    return;
  }

  isSubmitting.value = true;
  error.value = '';

  try {
    const payload = {
      game_id: props.gameId,
      rating: rating.value,
      comment: comment.value,
    };
    await api.post('/reviews', payload);
    
    // Сброс формы
    rating.value = 0;
    comment.value = '';

    // Сообщаем родительскому компоненту об успехе
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
    background: rgba(31, 41, 55, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 24px;
    border-radius: 12px;
}
.rating-control .star {
  font-size: 2rem;
  color: #6b7280; /* grey */
  cursor: pointer;
  transition: color 0.2s;
}

.rating-control .star.is-filled,
.rating-control .star:hover {
  color: #f59e0b; /* amber-500 */
}
</style>
