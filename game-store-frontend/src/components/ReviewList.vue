<template>
  <div class="review-list-wrapper">
    <div v-if="reviews.length > 0" class="reviews-container">
      <div v-for="review in reviews" :key="review.id" class="review-card">
        <span class="rc-rivet rc-rivet--tl" aria-hidden="true"></span>
        <span class="rc-rivet rc-rivet--tr" aria-hidden="true"></span>

        <div class="review-header">
          <div class="author-info">
            <div class="author-avatar">
              <img v-if="review.user?.avatar" :src="`/avatars/${encodeURIComponent(review.user.avatar)}`" :alt="review.user.fullname" class="avatar-img" />
              <span v-else>{{ review.user?.fullname?.charAt(0) || '?' }}</span>
            </div>
            <span class="author-name">{{ review.user?.fullname || 'Безымянный воин' }}</span>
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
      <span class="tribal-eyebrow">
        <span class="eb-spike"></span>
        Тишина
        <span class="eb-spike"></span>
      </span>
      <p>Ни одного суда ещё не оставлено.</p>
      <span class="np-sub">Станьте первым — поделитесь мнением о клинке.</span>
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
.review-list-wrapper { margin-top: 24px; }

.reviews-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ── review card ── */
.review-card {
  position: relative;
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  padding: 24px 26px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  transition: border-color 0.2s var(--ease-smoke), box-shadow 0.2s var(--ease-smoke);
}
.review-card:hover {
  border-color: var(--bronze-dark);
  box-shadow: var(--inset-iron-top), var(--shadow-cast), 0 0 18px rgba(255, 201, 121, 0.22);
}

.rc-rivet {
  position: absolute;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 50%,
    var(--iron-void) 100%);
  z-index: 2;
  box-shadow: inset -1px -1px 1px rgba(0, 0, 0, 0.7);
}
.rc-rivet--tl { top: 10px; left: 10px; }
.rc-rivet--tr { top: 10px; right: 10px; }

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.author-info {
  display: flex;
  align-items: center;
  gap: 14px;
}

/* ── гекс-аватар ── */
.author-avatar {
  width: 44px;
  height: 44px;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  background: linear-gradient(180deg,
    var(--ash-forge) 0%,
    var(--ash-bloodrock) 100%);
  border: 1px solid var(--bronze-dark);
  color: var(--text-bright);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: 1.15rem;
  overflow: hidden;
  flex-shrink: 0;
  box-shadow: var(--inset-iron-top), 0 0 8px rgba(199, 154, 94, 0.3);
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}
.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
}

.author-name {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.04rem;
  color: var(--text-bright);
  letter-spacing: 0.3px;
}

.rating-display .star {
  font-size: 1.2rem;
  color: var(--iron-warm);
}
.rating-display .star.filled {
  color: var(--ember-gold);
  text-shadow: 0 0 6px rgba(255, 201, 121, 0.5);
}

.review-content {
  border-top: 1px dashed var(--iron-dark);
  border-bottom: 1px dashed var(--iron-dark);
  padding: 16px 0;
}

.review-title {
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-bright);
  margin: 0 0 10px;
  letter-spacing: 0.3px;
}

.review-body {
  font-family: var(--font-body);
  font-size: 1rem;
  line-height: 1.75;
  color: var(--text-bone);
  white-space: pre-wrap;
  margin: 0;
}

.review-footer { text-align: right; }
.review-date {
  font-family: var(--font-ui);
  font-size: 0.8rem;
  color: var(--text-ash);
  letter-spacing: 0.5px;
  font-style: italic;
}

/* ── placeholder ── */
.no-reviews-placeholder {
  background: linear-gradient(180deg,
    var(--ash-stone) 0%,
    var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  clip-path: var(--clip-forged-sm);
  padding: 48px 40px;
  text-align: center;
  margin-top: 24px;
  box-shadow: var(--inset-iron-top), var(--shadow-cast);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}
.no-reviews-placeholder .tribal-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-ui);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 2.8px;
  text-transform: uppercase;
  color: var(--bronze);
  margin-bottom: 4px;
}
.eb-spike {
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 6px solid var(--bronze);
  filter: drop-shadow(0 0 3px rgba(199, 154, 94, 0.5));
}
.no-reviews-placeholder p {
  font-family: var(--font-display);
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--text-bright);
  margin: 0;
  letter-spacing: 0.3px;
}
.no-reviews-placeholder .np-sub {
  font-family: var(--font-body);
  color: var(--text-parchment);
  font-size: 0.95rem;
}
</style>
