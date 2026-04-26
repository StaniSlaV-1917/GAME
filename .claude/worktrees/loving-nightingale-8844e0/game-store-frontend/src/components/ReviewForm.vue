<template>
  <div class="review-form-box">
    <h3 class="form-title">Ваш отзыв об игре</h3>
    <form @submit.prevent="submitReview" class="form-grid">
      
      <div class="rating-field">
        <label class="label">Общая оценка</label>
        <div class="control rating-control">
          <span v-for="star in 5" :key="star" class="star" 
                :class="{ 'is-filled': star <= reviewData.rating }" 
                @click="setRating(star)"
                @mouseover="hoverRating = star"
                @mouseleave="hoverRating = 0"
                :style="{ color: star <= hoverRating ? '#facc15' : '' }">
            &#9733;
          </span>
        </div>
      </div>

      <div class="title-field">
        <label for="reviewTitle" class="label">Заголовок (необязательно)</label>
        <div class="control">
          <input type="text" id="reviewTitle" class="input" v-model="reviewData.title" placeholder="Кратко, о чем ваш отзыв">
        </div>
      </div>

      <div class="body-field">
        <label for="reviewBody" class="label">Текст отзыва</label>
        <div class="control">
          <textarea id="reviewBody" class="textarea" v-model="reviewData.body" placeholder="Поделитесь вашими впечатлениями от игры..."></textarea>
        </div>
      </div>

      <div v-if="error" class="error-message">
        {{ error }}
      </div>
      
      <div class="submit-field">
        <button type="submit" class="submit-btn" :disabled="isSubmitting">
          {{ isSubmitting ? 'Отправка...' : 'Опубликовать отзыв' }}
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
    error.value = 'Пожалуйста, поставьте оценку от 1 до 5 звезд.';
    return;
  }
  if (reviewData.body.trim() === '') {
    error.value = 'Пожалуйста, напишите текст отзыва.';
    return;
  }

  isSubmitting.value = true;
  error.value = '';

  try {
    // Payload is now just the reactive object
    await api.post(`/games/${props.gameId}/reviews`, reviewData);
    
    // Reset form
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
/* Main container with glassmorphism */
.review-form-box {
    background: rgba(17, 24, 39, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 24px;
    border-radius: 12px;
}

.form-title {
  font-size: 1.5rem; /* Matches section titles */
  font-weight: 700;
  color: #fff;
  margin-bottom: 20px;
  border-bottom: 2px solid #3b82f6;
  padding-bottom: 10px;
}

/* Common label styling */
.label {
  display: block;
  font-weight: 600;
  color: #d1d5db; /* Lighter gray */
  margin-bottom: 8px;
}

/* Grid layout for the form */
.form-grid {
  display: grid;
  gap: 20px;
}

/* Star rating styles */
.rating-control .star {
  font-size: 2.25rem; /* Larger stars */
  color: #6b7280; /* Default gray */
  cursor: pointer;
  transition: color 0.2s, transform 0.1s;
}

.rating-control .star:hover {
  transform: scale(1.15);
}

.rating-control .star.is-filled {
  color: #f59e0b; /* Filled amber */
}


/* Input and Textarea styling */
.input, .textarea {
  width: 100%;
  background-color: #1f2937; /* Dark bg */
  color: #e5e7eb; /* Light text */
  border: 1px solid #4b5563; /* Gray border */
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 1rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.input::placeholder, .textarea::placeholder {
  color: #9ca3af; /* Placeholder text color */
}

.input:focus, .textarea:focus {
  outline: none;
  border-color: #3b82f6; /* Blue border on focus */
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5); /* Blue glow on focus */
}

.textarea {
  min-height: 120px;
  resize: vertical;
}

/* Error message styling */
.error-message {
  background-color: rgba(239, 68, 68, 0.2);
  color: #fca5a5; /* Light red */
  border: 1px solid rgba(239, 68, 68, 0.5);
  padding: 12px 16px;
  border-radius: 8px;
  text-align: center;
}

/* Submit button styling (mirrors .add-to-cart-btn) */
.submit-btn {
  width: 100%;
  border: none;
  font-size: 1.1rem;
  font-weight: 700;
  padding: 16px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
  background-color: #3b82f6;
  color: white;
}

.submit-btn:hover:not(:disabled) { 
  background-color: #2563eb; 
  transform: translateY(-2px); 
}

.submit-btn:disabled {
  cursor: not-allowed;
  background-color: #4b5563;
  opacity: 0.7;
}

</style>