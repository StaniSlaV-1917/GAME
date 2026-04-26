<template>
  <div class="review-list-wrapper">
    <div v-if="reviews.length > 0" class="reviews-container">
      <div v-for="review in reviews" :key="review.id" class="review-card">
        <div class="review-header">
          <div class="author-info">
            <div class="author-avatar">
              <img v-if="review.user?.avatar" :src="`/avatars/${encodeURIComponent(review.user.avatar)}`" :alt="review.user.fullname" class="avatar-img" />
              <span v-else>{{ review.user?.fullname?.charAt(0) || '?' }}</span>
            </div>
            <span class="author-name">{{ review.user?.fullname || 'Анонимный пользователь' }}</span>
          </div>
          <div class="rating-display">
            <span v-for="star in 5" :key="star" class="star" :class="{ 'filled': star <= review.rating }">&#9733;</span>
          </div>
        </div>
        <div class="review-content">
          <h4 v-if="review.title" class="review-title">{{ review.title }}</h4>
          <p class="review-body">{{ review.body }}</p>
        </div>
        <div class="review-footer">
          <time :datetime="review.created_at" class="review-date">
            {{ new Date(review.created_at).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }) }}
          </time>
        </div>
      </div>
    </div>
    <div v-else class="no-reviews-placeholder">
      <p>Для этой игры еще нет отзывов.</p>
      <span>Станьте первым, кто поделится своим мнением!</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  reviews: {
    type: Array,
    default: () => []
  }
});
</script>

<style scoped>
.review-list-wrapper {
  margin-top: 24px;
}

.reviews-container {
  display: flex;
  flex-direction: column;
  gap: 24px; /* Increased gap */
}

.review-card {
  /* Using the same glassmorphism effect as the parent page */
  background: rgba(31, 41, 55, 0.7); /* Slightly darker than form for contrast */
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 24px;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap; /* Allow wrapping on small screens */
  gap: 12px;
}

.author-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.author-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: #3b82f6;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
  overflow: hidden;
  flex-shrink: 0;
}
.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
  display: block;
}

.author-name {
  font-weight: 600;
  font-size: 1.1rem;
  color: #e5e7eb;
}

.rating-display .star {
  font-size: 1.25rem; /* Larger stars */
  color: #6b7280; /* Gray for empty */
}

.rating-display .star.filled {
  color: #f59e0b; /* Yellow for filled */
}

.review-content {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding: 16px 0;
}

.review-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 8px;
}

.review-body {
  font-size: 1rem;
  line-height: 1.7;
  color: #d1d5db;
  white-space: pre-wrap; /* Preserve line breaks from textarea */
  margin: 0;
}

.review-footer {
  text-align: right;
}

.review-date {
  font-size: 0.8rem;
  color: #9ca3af;
  font-style: italic;
}

.no-reviews-placeholder {
  background: rgba(31, 41, 55, 0.7);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 40px;
  border-radius: 12px;
  text-align: center;
  margin-top: 24px;
}

.no-reviews-placeholder p {
  font-size: 1.2rem;
  color: #e5e7eb;
  margin: 0 0 8px;
}

.no-reviews-placeholder span {
  color: #9ca3af;
}
</style>
