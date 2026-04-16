<template>
  <div class="review-list">
    <h3 class="title is-4">Отзывы покупателей ({{ reviews.length }})</h3>
    <div v-if="reviews.length > 0" class="reviews-container">
      <div v-for="review in reviews" :key="review.id" class="review-card">
        <div class="review-header">
          <strong class="reviewer-name">{{ review.user?.name || 'Аноним' }}</strong>
          <div class="review-rating">
            <span v-for="star in 5" :key="star" class="star" :class="{ 'is-filled': star <= review.rating }">
              &#9733;
            </span>
          </div>
        </div>
        <p class="review-comment">{{ review.comment }}</p>
        <time class="review-date">{{ new Date(review.created_at).toLocaleDateString() }}</time>
      </div>
    </div>
    <div v-else>
      <p>Отзывов пока нет. Станьте первым!</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  reviews: {
    type: Array,
    default: () => []
  }
});
</script>

<style scoped>
.review-list {
    margin-top: 32px;
}
.reviews-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.review-card {
    background: rgba(31, 41, 55, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 12px;
}
.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.reviewer-name {
    font-weight: 600;
    color: #e5e7eb;
}
.review-rating .star {
    font-size: 1rem;
    color: #6b7280; /* grey */
}
.review-rating .star.is-filled {
    color: #f59e0b; /* amber-500 */
}
.review-comment {
    line-height: 1.7;
    color: #d1d5db;
    margin-bottom: 10px;
}
.review-date {
    font-size: 0.85rem;
    color: #9ca3af;
}
</style>
