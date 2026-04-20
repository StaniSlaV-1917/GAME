<template>
  <div class="game-card" :style="{ '--delay': `calc(var(--i, 0) * 65ms)` }">
    <div
      class="game-card-inner"
      ref="cardInnerRef"
      @mousemove="handleMouseMove"
      @mouseleave="handleMouseLeave"
    >

      <RouterLink class="card-main-link" :to="{ name: 'game', params: { id: game.id } }" :aria-label="game.title"></RouterLink>

      <!-- Image: blurred bg of same image + contain so nothing is cropped -->
      <div class="card-img-wrap" :style="{ '--thumb': `url(${imageUrl})` }">
        <img :src="imageUrl" :alt="game.title" width="270" height="180" loading="lazy" class="card-img" />
        <div class="img-gradient"></div>

        <!-- Badges -->
        <div class="card-badges">
          <span v-if="game.is_featured" class="badge b-hot">Хит</span>
          <span v-if="game.is_new" class="badge b-new">✨ Новинка</span>
          <span v-if="game.discount_percent" class="badge b-disc">-{{ game.discount_percent }}%</span>
        </div>

        <!-- External link -->
        <a class="ext-link" :href="game.stopgame_url_code" target="_blank" rel="noopener" title="Обзор на StopGame" @click.stop>
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        </a>

        <!-- Platform chip -->
        <div class="platform-chip" v-if="game.platform">{{ game.platform }}</div>
      </div>

      <!-- Info -->
      <div class="card-info">
        <h2 class="card-title">{{ game.title }}</h2>
        <p class="card-genre">{{ game.genre }}</p>

        <!-- Rating -->
        <div v-if="game.average_review_rating != null || game.rating != null" class="card-rating">
          <span v-for="i in 5" :key="i" class="star" :class="{ filled: Number(game.average_review_rating ?? game.rating ?? 0) >= i - 0.25 }">★</span>
          <span class="rating-val">{{ Number(game.average_review_rating ?? game.rating ?? 0).toFixed(1) }}</span>
        </div>

        <!-- Bottom row -->
        <div class="card-bottom">
          <div class="price-block">
            <span v-if="game.old_price" class="old-price">{{ Number(game.old_price).toFixed(0) }} ₽</span>
            <span class="price">{{ Number(game.price).toFixed(0) }} ₽</span>
          </div>
          <div class="card-actions">
            <button
              class="buy-btn" type="button"
              @click.stop="handleAddToCart"
              :disabled="!authStore.isLoggedIn || isInCart"
              :class="{ 'in-cart': isInCart }"
              :title="!authStore.isLoggedIn ? 'Войдите для покупки' : isInCart ? 'Уже в корзине' : 'В корзину'"
            >
              <span v-if="isInCart">✓ В корзине</span>
              <span v-else>В корзину</span>
            </button>
            <RouterLink class="details-btn" :to="{ name: 'game', params: { id: game.id } }" title="Подробнее" @click.stop>
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </RouterLink>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { defineProps, computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useCartStore } from '../stores/cart';
import { useAuthStore } from '../stores/auth';
import { resolveMediaUrl } from '../utils/media';

const props = defineProps({ game: { type: Object, required: true } });

const cartStore = useCartStore();
const authStore = useAuthStore();
const isInCart = computed(() => cartStore.getItemById(props.game.id));

const handleAddToCart = () => {
  if (!authStore.isLoggedIn) return;
  cartStore.addItem({ id: props.game.id, title: props.game.title, price: props.game.price, image: props.game.image, platform: props.game.platform });
};

const imageUrl = computed(() => resolveMediaUrl(props.game.image));

// ── 3-D tilt ──
const cardInnerRef = ref(null);

const handleMouseMove = (e) => {
  const el = cardInnerRef.value;
  if (!el) return;
  const r = el.getBoundingClientRect();
  const x = (e.clientX - r.left) / r.width  - 0.5;   // -0.5 … 0.5
  const y = (e.clientY - r.top)  / r.height - 0.5;
  el.style.setProperty('--rx', `${-y * 11}deg`);
  el.style.setProperty('--ry', `${x * 11}deg`);
  el.style.setProperty('--ty', '-8px');
  el.style.setProperty('--sc', '1.02');
  el.style.setProperty('--tr', '0.08s linear');
};

const handleMouseLeave = () => {
  const el = cardInnerRef.value;
  if (!el) return;
  el.style.setProperty('--rx', '0deg');
  el.style.setProperty('--ry', '0deg');
  el.style.setProperty('--ty', '0px');
  el.style.setProperty('--sc', '1');
  el.style.setProperty('--tr', '0.55s cubic-bezier(.22,.68,0,1.2)');
};
</script>

<style scoped>
.game-card { height: 100%; }

.game-card-inner {
  position: relative; display: flex; flex-direction: column; height: 100%;
  border-radius: 14px; overflow: hidden;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 8px 28px rgba(0,0,0,0.45);
  /* 3-D tilt via CSS vars (set by JS on mousemove/mouseleave) */
  transform: perspective(900px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)) translateY(var(--ty, 0px)) scale(var(--sc, 1));
  transition: transform var(--tr, 0.28s cubic-bezier(.22,.68,0,1.2)), border-color 0.28s, box-shadow 0.28s;
  will-change: transform;
  transform-style: preserve-3d;
}
.game-card-inner:hover {
  border-color: rgba(59,130,246,0.55);
  box-shadow: 0 20px 48px rgba(0,0,0,0.6), 0 0 32px rgba(59,130,246,0.25);
}

.card-main-link { position: absolute; inset: 0; z-index: 1; text-indent: -9999px; }
.card-actions, .ext-link, .details-btn { position: relative; z-index: 2; }

/* ─── Image ─── */
.card-img-wrap {
  position: relative; height: 200px; overflow: hidden; background: #0a0f1e;
  /* Blurred duplicate of cover as background — fills letterbox gaps */
}
.card-img-wrap::before {
  content: '';
  position: absolute; inset: -10px;
  background-image: var(--thumb); background-size: cover; background-position: center;
  filter: blur(14px) brightness(0.45) saturate(1.2);
  transform: scale(1.05);
  z-index: 0;
}
/* The actual cover: contain so full art is shown, no cropping */
.card-img {
  position: relative; z-index: 1;
  width: 100%; height: 100%;
  object-fit: contain; object-position: center;
  transition: transform 0.35s ease; display: block;
}
.game-card-inner:hover .card-img { transform: scale(1.05); }
.img-gradient {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(15,23,42,0.85) 0%, rgba(15,23,42,0.1) 60%);
  pointer-events: none;
}

/* Badges */
.card-badges { position: absolute; left: 10px; top: 10px; display: flex; flex-direction: column; gap: 5px; z-index: 2; }
.badge { display: inline-flex; padding: 4px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; color: #fff; backdrop-filter: blur(6px); }
.b-hot { background: rgba(249,115,22,0.85); }
.b-new { background: rgba(16,185,129,0.85); }
.b-disc { background: rgba(239,68,68,0.85); }

/* External link */
.ext-link {
  position: absolute; right: 10px; top: 10px;
  width: 32px; height: 32px; border-radius: 8px;
  background: rgba(17,24,39,0.75); backdrop-filter: blur(6px);
  color: #9ca3af; display: grid; place-items: center; cursor: pointer; z-index: 3;
  opacity: 0; transform: scale(0.85) translateY(-4px);
  transition: all 0.22s ease;
}
.game-card-inner:hover .ext-link { opacity: 1; transform: scale(1) translateY(0); }
.ext-link:hover { background: #3b82f6; color: #fff; }

/* Platform chip */
.platform-chip {
  position: absolute; bottom: 10px; right: 10px;
  background: rgba(0,0,0,0.65); backdrop-filter: blur(6px);
  color: #cbd5e1; font-size: 0.68rem; font-weight: 600; letter-spacing: 0.06em;
  padding: 3px 9px; border-radius: 6px; text-transform: uppercase; z-index: 2;
}

/* ─── Info ─── */
.card-info { padding: 14px; display: flex; flex-direction: column; gap: 7px; flex-grow: 1; }

.card-title { margin: 0; font-size: 1.02rem; font-weight: 700; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-genre { margin: 0; font-size: 0.8rem; color: #6b7280; }

/* Rating */
.card-rating { display: flex; align-items: center; gap: 5px; }
.star { color: #374151; font-size: 0.9rem; transition: color 0.1s; }
.star.filled { color: #fbbf24; text-shadow: 0 0 6px rgba(251,191,36,0.5); }
.rating-val { font-size: 0.82rem; font-weight: 700; color: #fbbf24; }

/* Bottom */
.card-bottom { margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06); }

.price-block { display: flex; align-items: baseline; gap: 7px; }
.old-price { font-size: 0.82rem; color: #6b7280; text-decoration: line-through; }
.price { font-weight: 800; font-size: 1.12rem; color: #4ade80; }

.card-actions { display: flex; align-items: center; gap: 7px; }

.buy-btn {
  font-size: 0.85rem; padding: 8px 16px; border-radius: 9px; border: none; cursor: pointer;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: #fff; font-weight: 600; transition: all 0.22s ease; white-space: nowrap;
}
.buy-btn:hover:not(:disabled) { filter: brightness(1.15); transform: scale(1.06); box-shadow: 0 4px 16px rgba(99,102,241,0.4); }
.buy-btn:disabled { background: #374151; cursor: not-allowed; filter: none; transform: none; }
.buy-btn.in-cart { background: linear-gradient(135deg, #16a34a, #22c55e); }

.details-btn {
  width: 36px; height: 36px; border-radius: 9px;
  border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04);
  display: grid; place-items: center; color: #6b7280;
  transition: all 0.2s ease;
}
.details-btn:hover { border-color: #60a5fa; color: #93c5fd; background: rgba(59,130,246,0.1); }
</style>
