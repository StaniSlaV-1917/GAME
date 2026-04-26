<template>
  <div class="game-card" :style="{ '--delay': `calc(var(--i, 0) * 65ms)` }">
    <div
      class="game-card-inner"
      ref="cardInnerRef"
      @mousemove="handleMouseMove"
      @mouseleave="handleMouseLeave"
    >
      <!-- Декоративные бронзовые заклёпки в углах -->
      <span class="rivet rivet-tl" aria-hidden="true"></span>
      <span class="rivet rivet-tr" aria-hidden="true"></span>
      <span class="rivet rivet-bl" aria-hidden="true"></span>
      <span class="rivet rivet-br" aria-hidden="true"></span>

      <!-- Внутренняя рамка с forged clip-path -->
      <div class="card-frame">

        <!-- Image: кликабельная зона ведёт на страницу игры -->
        <RouterLink
          class="card-img-link"
          :to="{ name: 'game', params: { id: game.id } }"
          :aria-label="game.title"
        >
          <div class="card-img-wrap" :style="{ '--thumb': `url(${imageUrl})` }">
            <img :src="imageUrl" :alt="game.title" width="270" height="180" loading="lazy" class="card-img" />
            <div class="img-gradient"></div>
            <div class="img-vignette"></div>

            <!-- Badges — треугольные знамёна -->
            <div class="card-badges">
              <span v-if="game.is_featured" class="badge b-hot">
                <span class="badge-icon">⚔</span>
                <span>Хит</span>
              </span>
              <span v-if="game.is_new" class="badge b-new">
                <span class="badge-icon">✦</span>
                <span>Новое</span>
              </span>
              <span v-if="game.discount_percent" class="badge b-disc">
                <span>−{{ game.discount_percent }}%</span>
              </span>
            </div>

            <!-- External link — железный знак (превентивно гасим клик, чтобы не уходил в RouterLink) -->
            <a class="ext-link" :href="game.stopgame_url_code" target="_blank" rel="noopener" title="Обзор на StopGame" @click.stop>
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>

            <!-- Platform chip — кованая плашка -->
            <div class="platform-chip" v-if="game.platform">
              <span class="chip-dot"></span>
              <span>{{ game.platform }}</span>
            </div>
          </div>
        </RouterLink>

        <!-- Info -->
        <div class="card-info">
          <RouterLink :to="{ name: 'game', params: { id: game.id } }" class="card-title-link">
            <h2 class="card-title">{{ game.title }}</h2>
          </RouterLink>
          <p class="card-genre">{{ game.genre }}</p>

          <!-- Rating -->
          <div v-if="game.average_review_rating != null || game.rating != null" class="card-rating">
            <div class="stars-track">
              <span v-for="i in 5" :key="i" class="star" :class="{ filled: Number(game.average_review_rating ?? game.rating ?? 0) >= i - 0.25 }">★</span>
            </div>
            <span class="rating-val">{{ Number(game.average_review_rating ?? game.rating ?? 0).toFixed(1) }}</span>
          </div>

          <!-- Tribal divider -->
          <div class="card-divider" aria-hidden="true">
            <span></span><span class="card-divider-spike"></span><span></span>
          </div>

          <!-- Bottom row -->
          <div class="card-bottom">
            <div class="price-block">
              <span v-if="game.old_price" class="old-price">{{ Number(game.old_price).toFixed(0) }}₽</span>
              <span class="price">
                <span class="price-val">{{ Number(game.price).toFixed(0) }}</span>
                <span class="price-unit">₽</span>
              </span>
            </div>
            <div class="card-actions">
              <button
                class="buy-btn" type="button"
                @click.stop="handleAddToCart"
                :disabled="!authStore.isLoggedIn || isInCart"
                :class="{ 'in-cart': isInCart }"
                :title="!authStore.isLoggedIn ? 'Войдите для покупки' : isInCart ? 'Уже в добыче' : 'Забрать'"
              >
                <span v-if="isInCart">
                  <span class="buy-icon">✓</span>
                  В добыче
                </span>
                <span v-else>
                  <span class="buy-icon">⚔</span>
                  Забрать
                </span>
              </button>
              <RouterLink class="details-btn" :to="{ name: 'game', params: { id: game.id } }" title="Подробнее" @click.stop>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              </RouterLink>
            </div>
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
  const x = (e.clientX - r.left) / r.width  - 0.5;
  const y = (e.clientY - r.top)  / r.height - 0.5;
  el.style.setProperty('--rx', `${-y * 9}deg`);
  el.style.setProperty('--ry', `${x * 9}deg`);
  el.style.setProperty('--ty', '-6px');
  el.style.setProperty('--sc', '1.015');
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
/* ============================================================
   ASHENFORGE · GameCard — щит воина
   ============================================================ */

.game-card { height: 100%; }

/* Внешняя обёртка — держит 3D-tilt transform */
.game-card-inner {
  position: relative;
  display: flex;
  flex-direction: column;
  height: 100%;
  transform:
    perspective(900px)
    rotateX(var(--rx, 0deg))
    rotateY(var(--ry, 0deg))
    translateY(var(--ty, 0px))
    scale(var(--sc, 1));
  transition:
    transform var(--tr, var(--dur-med) var(--ease-forge)),
    filter var(--dur-med) var(--ease-smoke);
  will-change: transform;
  transform-style: preserve-3d;
}
.game-card-inner:hover { filter: brightness(1.06); }

/* ==========================================================
   RIVETS · бронзовые заклёпки в углах
   ========================================================== */
.rivet {
  position: absolute;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background:
    radial-gradient(circle at 30% 30%,
      var(--brass) 0%,
      var(--bronze) 45%,
      var(--iron-void) 100%);
  box-shadow:
    inset -1px -1px 2px rgba(0, 0, 0, 0.7),
    inset 1px 1px 1px rgba(255, 201, 121, 0.35),
    0 0 4px rgba(199, 154, 94, 0.45);
  z-index: 4;
  pointer-events: none;
  transition: box-shadow var(--dur-med) var(--ease-smoke);
}
.rivet-tl { top: 8px;    left: 8px; }
.rivet-tr { top: 8px;    right: 8px; }
.rivet-bl { bottom: 8px; left: 8px; }
.rivet-br { bottom: 8px; right: 8px; }

.game-card-inner:hover .rivet {
  box-shadow:
    inset -1px -1px 2px rgba(0, 0, 0, 0.7),
    inset 1px 1px 1px rgba(255, 201, 121, 0.5),
    0 0 8px rgba(255, 122, 43, 0.6);
}

/* ==========================================================
   CARD FRAME · основной контейнер с clip-path
   ========================================================== */
.card-frame {
  position: relative;
  display: flex;
  flex-direction: column;
  height: 100%;
  background:
    linear-gradient(180deg,
      var(--ash-ironrust) 0%,
      var(--ash-stone) 45%,
      var(--ash-coal) 100%);
  clip-path: var(--clip-forged-sm);
  overflow: hidden;
  /* Каменная рамка — две подкладки для имитации кованого края */
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    inset 0 1px 0 3px var(--iron-warm),
    var(--shadow-cast),
    var(--inset-forge);
  transition:
    box-shadow var(--dur-med) var(--ease-smoke);
}
.game-card-inner:hover .card-frame {
  box-shadow:
    inset 0 0 0 1px var(--bronze-dark),
    inset 0 0 0 3px var(--iron-void),
    inset 0 1px 0 3px var(--bronze),
    var(--shadow-lift),
    var(--inset-forge-hot),
    0 0 0 1px rgba(255, 122, 43, 0.1);
}

/* Каменная шероховатая текстура — едва заметная */
.card-frame::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    repeating-linear-gradient(127deg,
      transparent 0,
      transparent 2px,
      rgba(0, 0, 0, 0.06) 2px,
      rgba(0, 0, 0, 0.06) 3px);
  pointer-events: none;
  opacity: 0.5;
  z-index: 0;
}

/* RouterLink-обёртка вокруг картинки — не должна сама ничего стилизовать,
   просто оборачивает .card-img-wrap чтобы клик по картинке шёл на страницу. */
.card-img-link {
  display: block;
  position: relative;
  z-index: 1;
  text-decoration: none;
  color: inherit;
}

/* ==========================================================
   IMAGE
   ========================================================== */
.card-img-wrap {
  position: relative;
  height: 210px;
  overflow: hidden;
  background: var(--ash-void);
  border-bottom: 1px solid var(--iron-dark);
}
/* Blurred duplicate как фон — чтобы letterbox не был пустым */
.card-img-wrap::before {
  content: '';
  position: absolute;
  inset: -10px;
  background-image: var(--thumb);
  background-size: cover;
  background-position: center;
  filter: blur(16px) brightness(0.35) saturate(0.9) contrast(1.1);
  transform: scale(1.08);
  z-index: 0;
}
.card-img {
  position: relative;
  z-index: 1;
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: center;
  transition: transform var(--dur-slow) var(--ease-smoke);
  display: block;
}
.game-card-inner:hover .card-img { transform: scale(1.06); }

/* Градиент снизу — читаемость platform chip */
.img-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top,
    rgba(8, 6, 10, 0.9) 0%,
    rgba(8, 6, 10, 0.3) 35%,
    transparent 70%);
  pointer-events: none;
  z-index: 2;
}
/* Виньетка — тёмные углы */
.img-vignette {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center,
    transparent 55%,
    rgba(0, 0, 0, 0.45) 100%);
  pointer-events: none;
  z-index: 2;
}

/* ==========================================================
   BADGES — знамёна
   ========================================================== */
.card-badges {
  position: absolute;
  left: 14px;
  top: 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  z-index: 3;
}
.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 10px;
  font-family: var(--font-display);
  font-size: 0.68rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  color: var(--text-bright);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.75);
  clip-path: polygon(0 0, 100% 0, 95% 50%, 100% 100%, 0 100%, 5% 50%);
  border: 1px solid transparent;
  line-height: 1;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.2),
    inset 0 -1px 0 rgba(0, 0, 0, 0.4);
}
.badge-icon { font-size: 0.78rem; }

.b-hot {
  background: var(--grad-ember-hot);
  border-color: var(--ember-flame);
}
.b-new {
  background: linear-gradient(135deg, var(--orc-green) 0%, var(--orc-moss) 100%);
  border-color: var(--orc-emerald);
}
.b-disc {
  background: linear-gradient(135deg, var(--ember-gold) 0%, var(--warn-ember) 100%);
  border-color: var(--gold-faint);
  color: var(--ember-abyss);
  text-shadow: none;
  font-weight: var(--fw-black);
}

/* ==========================================================
   EXTERNAL LINK
   ========================================================== */
.ext-link {
  position: absolute;
  right: 14px;
  top: 14px;
  width: 30px; height: 30px;
  border-radius: var(--r-xs);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  border: 1px solid var(--iron-mid);
  color: var(--bronze);
  display: grid;
  place-items: center;
  cursor: pointer;
  z-index: 4;
  opacity: 0;
  transform: translateY(-6px) scale(0.85);
  transition: all var(--dur-med) var(--ease-forge);
  box-shadow: var(--inset-iron-top);
}
.game-card-inner:hover .ext-link {
  opacity: 1;
  transform: translateY(0) scale(1);
}
.ext-link:hover {
  color: var(--ember-spark);
  border-color: var(--ember-heart);
  background: linear-gradient(180deg, var(--ash-bloodrock) 0%, var(--ash-stone) 100%);
  box-shadow: var(--glow-ember-soft);
}

/* ==========================================================
   PLATFORM CHIP
   ========================================================== */
.platform-chip {
  position: absolute;
  bottom: 12px;
  right: 12px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 9px;
  background: linear-gradient(180deg, rgba(58, 42, 34, 0.92) 0%, rgba(18, 16, 13, 0.92) 100%);
  backdrop-filter: blur(4px);
  border: 1px solid var(--iron-mid);
  color: var(--text-parchment);
  font-family: var(--font-display);
  font-size: 0.66rem;
  font-weight: var(--fw-semibold);
  letter-spacing: var(--ls-wider);
  text-transform: uppercase;
  border-radius: var(--r-xs);
  z-index: 3;
  box-shadow: var(--shadow-subtle);
}
.chip-dot {
  display: inline-block;
  width: 5px; height: 5px;
  border-radius: 50%;
  background: var(--ember-glow);
  box-shadow: 0 0 6px rgba(255, 122, 43, 0.8);
  animation: emberPulse 2s ease-in-out infinite;
}

/* ==========================================================
   CARD INFO
   ========================================================== */
.card-info {
  padding: 16px 18px 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-grow: 1;
  position: relative;
  z-index: 1;
}

.card-title-link {
  text-decoration: none;
  color: inherit;
  display: block;
  transition: color var(--dur-fast) var(--ease-smoke);
}
.card-title-link:hover .card-title {
  color: var(--ember-spark);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6), 0 0 12px rgba(255, 167, 88, 0.45);
}

.card-title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.05rem;
  font-weight: var(--fw-semibold);
  color: var(--text-bright);
  letter-spacing: var(--ls-wide);
  line-height: 1.25;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
  transition: color var(--dur-fast) var(--ease-smoke), text-shadow var(--dur-fast) var(--ease-smoke);
}
.card-genre {
  margin: 0;
  font-family: var(--font-body);
  font-style: italic;
  font-size: 0.82rem;
  color: var(--text-ash);
}

/* Rating */
.card-rating {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 2px;
}
.stars-track { display: flex; gap: 1px; }
.star {
  color: var(--iron-dark);
  font-size: 0.92rem;
  line-height: 1;
  transition: color var(--dur-fast);
}
.star.filled {
  color: var(--ember-gold);
  text-shadow: 0 0 6px rgba(255, 201, 121, 0.55);
}
.rating-val {
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: var(--fw-bold);
  color: var(--ember-gold);
  letter-spacing: var(--ls-wide);
}

/* Tribal divider */
.card-divider {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 8px 0 4px;
  height: 8px;
}
.card-divider > span:first-child,
.card-divider > span:last-child {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--iron-mid) 50%, transparent);
}
.card-divider-spike {
  width: 0; height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 6px solid var(--ember-deep);
  filter: drop-shadow(0 0 3px rgba(194, 40, 26, 0.5));
  flex-shrink: 0;
}

/* ==========================================================
   BOTTOM (price + actions)
   ========================================================== */
.card-bottom {
  margin-top: auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding-top: 4px;
}

.price-block {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0;
  line-height: 1;
}
.old-price {
  font-family: var(--font-body);
  font-size: 0.78rem;
  color: var(--text-smoke);
  text-decoration: line-through;
  text-decoration-color: var(--ember-deep);
  text-decoration-thickness: 1.5px;
}
.price {
  display: inline-flex;
  align-items: baseline;
  gap: 2px;
  font-family: var(--font-display);
  font-weight: var(--fw-black);
  color: var(--ember-gold);
  text-shadow: 0 0 10px rgba(255, 201, 121, 0.35);
}
.price-val { font-size: 1.25rem; letter-spacing: var(--ls-tight); }
.price-unit { font-size: 0.9rem; color: var(--brass); margin-left: 1px; }

.card-actions {
  display: flex;
  align-items: center;
  gap: 6px;
  position: relative;
  z-index: 3;
}

/* ==========================================================
   BUY BUTTON · кованый
   ========================================================== */
.buy-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-family: var(--font-display);
  font-size: 0.78rem;
  font-weight: var(--fw-bold);
  text-transform: uppercase;
  letter-spacing: var(--ls-wide);
  padding: 9px 14px;
  border-radius: var(--r-xs);
  border: 1px solid var(--ember-heart);
  cursor: pointer;
  background: var(--grad-ember);
  color: var(--text-bright);
  white-space: nowrap;
  transition: all var(--dur-fast) var(--ease-smoke);
  overflow: hidden;
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    var(--glow-ember-soft);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
}
.buy-icon {
  display: inline-flex;
  font-size: 0.85rem;
  filter: drop-shadow(0 0 4px rgba(255, 201, 121, 0.5));
}
.buy-btn::after {
  content: '';
  position: absolute;
  top: 0; left: -120%;
  width: 60%; height: 100%;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(255, 201, 121, 0.4) 50%,
    transparent 100%);
  transform: skewX(-20deg);
  transition: left 0.6s var(--ease-smoke);
}
.buy-btn:hover:not(:disabled) {
  filter: brightness(1.15) saturate(1.1);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    var(--glow-ember-strong);
  transform: translateY(-1px);
}
.buy-btn:hover:not(:disabled)::after { left: 120%; }
.buy-btn:active:not(:disabled) {
  transform: translateY(0);
  animation: forgeClang var(--dur-med) var(--ease-forge);
}
.buy-btn:disabled {
  background: linear-gradient(180deg, var(--iron-dark) 0%, var(--iron-void) 100%);
  border-color: var(--iron-mid);
  color: var(--text-smoke);
  cursor: not-allowed;
  filter: none;
  transform: none;
  box-shadow: inset 0 -2px 3px rgba(0, 0, 0, 0.35);
  text-shadow: none;
}
.buy-btn.in-cart {
  background: linear-gradient(180deg, var(--orc-green) 0%, var(--orc-moss) 100%);
  border-color: var(--orc-emerald);
  color: var(--text-bright);
  cursor: default;
  opacity: 0.95;
}
.buy-btn.in-cart:hover { filter: none; transform: none; box-shadow: var(--inset-iron-top), inset 0 -2px 3px rgba(0, 0, 0, 0.35); }

/* ==========================================================
   DETAILS BUTTON
   ========================================================== */
.details-btn {
  display: grid;
  place-items: center;
  width: 34px; height: 34px;
  border-radius: var(--r-xs);
  border: 1px solid var(--iron-mid);
  background: linear-gradient(180deg, var(--ash-stone) 0%, var(--ash-coal) 100%);
  color: var(--text-ash);
  transition: all var(--dur-fast) var(--ease-smoke);
  text-decoration: none;
}
.details-btn:hover {
  color: var(--ember-spark);
  border-color: var(--ember-deep);
  background: linear-gradient(180deg, var(--ash-bloodrock) 0%, var(--ash-stone) 100%);
  box-shadow: var(--glow-ember-soft);
}

/* ==========================================================
   RESPONSIVE
   ========================================================== */
@media (max-width: 768px) {
  .card-img-wrap { height: 190px; }
  .card-info { padding: 14px 16px 13px; gap: 5px; }
  .card-title { font-size: 1rem; }
}
@media (max-width: 540px) {
  .card-img-wrap { height: 170px; }
  .card-info { padding: 12px 14px 12px; }
  .card-title { font-size: 0.96rem; }
  .card-genre { font-size: 0.78rem; }
  .price-val { font-size: 1.1rem; }
  .buy-btn { padding: 8px 11px; font-size: 0.72rem; gap: 4px; }
  .details-btn { width: 32px; height: 32px; }
  .badge { font-size: 0.62rem; padding: 4px 8px; }
  .platform-chip { font-size: 0.6rem; padding: 3px 7px; }
}
@media (max-width: 380px) {
  .card-img-wrap { height: 150px; }
  /* Кнопки рядом не помещаются — складываем bottom-row в столбец */
  .card-bottom { flex-direction: column; align-items: stretch; gap: 8px; }
  .price-block { align-items: center; }
  .card-actions { justify-content: center; }
  .buy-btn { flex: 1; justify-content: center; }
}
/* Touch-устройства: tilt-эффект убираем, чтобы избежать ломаных трансформов */
@media (hover: none) {
  .game-card-inner { transform: none !important; }
  .game-card-inner:hover { filter: none; }
  .ext-link { opacity: 1; transform: none; }
}
</style>
