<template>
  <div class="game-card">
    <div class="game-card-inner">

      <RouterLink class="card-main-link" :to="{ name: 'game', params: { id: game.id } }" :aria-label="game.title"></RouterLink>

      <div class="game-card-top">
        <img 
          :src="imageUrl" 
          :alt="game.title" 
          width="270" 
          height="180" 
          loading="lazy"
        />
        <div class="game-badges">
          <span v-if="game.is_featured" class="badge badge-featured">Хит</span>
          <span v-if="game.is_new" class="badge badge-new">Новинка</span>
          <span v-if="game.discount_percent" class="badge badge-discount">-{{ game.discount_percent }}%</span>
        </div>
        <a class="external-link-icon" :href="game.stopgame_url_code" target="_blank" rel="noopener" title="Обзор на StopGame" @click.stop>
           <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
        </a>
      </div>
      <div class="game-info">
        <h2>{{ game.title }}</h2>
        <p class="game-meta">{{ game.genre }} · {{ game.platform }}</p>
        <div class="game-rating" v-if="game.average_review_rating != null || game.rating != null">
          <div class="stars">
            <span v-for="i in 5" :key="i" class="star" :class="{'star-filled': Number(game.average_review_rating ?? game.rating ?? 0) >= i - 0.25}">★</span>
          </div>
          <span class="rating-value">{{ Number(game.average_review_rating ?? game.rating ?? 0).toFixed(1) }}</span>
        </div>
        <div class="game-bottom">
          <div class="price-block">
            <span v-if="game.old_price" class="game-old-price">{{ Number(game.old_price).toFixed(0) }} ₽</span>
            <span class="game-price">{{ Number(game.price).toFixed(0) }} ₽</span>
          </div>
          <div class="game-actions">
            <button 
              class="game-buy-btn" 
              type="button" 
              @click.stop="handleAddToCart"
              :disabled="!authStore.isLoggedIn || isInCart"
              :title="!authStore.isLoggedIn ? 'Войдите, чтобы добавить в корзину' : (isInCart ? 'Игра уже в корзине' : 'Добавить в корзину')"
              :class="{ 'in-cart': isInCart }"
            >
              {{ isInCart ? 'В корзине' : 'В корзину' }}
            </button>
            <RouterLink class="details-btn" :to="{ name: 'game', params: { id: game.id } }" title="Подробнее" @click.stop>
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.4 2.2a2.3 2.3 0 013.2 0l7.3 8.3c.6.6.7 1.5.3 2.2l-5 8.3c-.6.9-1.8 1-2.7.3l-7.3-8.3c-.6-.6-.7-1.5-.3-2.2l5-8.3z"></path><path d="M9.2 12.3a3 3 0 11-2.8 4.3"></path></svg>
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue';
import { RouterLink } from 'vue-router';
import { useCartStore } from '../stores/cart';
import { useAuthStore } from '../stores/auth';

const props = defineProps({
  game: {
    type: Object,
    required: true
  }
});

const cartStore = useCartStore();
const authStore = useAuthStore();

const isInCart = computed(() => cartStore.getItemById(props.game.id));

const getGameDataForCart = (gameData) => ({
    id: gameData.id,
    title: gameData.title,
    price: gameData.price,
    image: gameData.image,
    platform: gameData.platform,
});

const handleAddToCart = () => {
  if (!authStore.isLoggedIn) return;
  cartStore.addItem(getGameDataForCart(props.game));
};

const resolveImageUrl = (imagePath) => {
    if (!imagePath) return '/img/noimage.png';
    if (imagePath.includes('/')) {
        return `http://localhost:8000${imagePath}`;
    }
    return `/img/${imagePath}`;
};

const imageUrl = computed(() => resolveImageUrl(props.game.image));

</script>

<style scoped>
.game-card { height: 100%; }
.game-card-inner { 
  position: relative;
  display: flex; flex-direction: column; height: 100%; border-radius: 12px; overflow: hidden; background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); transition: all 0.25s ease-in-out; 
}
.game-card-inner:hover { transform: translateY(-6px); border-color: #3b82f6; box-shadow: 0 18px 40px rgba(0,0,0,0.6), 0 0 25px rgba(59, 130, 246, 0.4); }

.card-main-link {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1; 
  text-indent: -9999px; 
}

.game-actions, .external-link-icon, .details-btn {
  position: relative; 
  z-index: 2;
}

.game-card-top { position: relative; height: 180px; background: #000; overflow: hidden; }
.game-card-top img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease-in-out; }
.game-card-inner:hover .game-card-top img { transform: scale(1.07); }
.game-badges { position: absolute; left: 8px; top: 8px; display: flex; flex-direction: column; gap: 4px; z-index: 2; }
.badge { display: inline-flex; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; color: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
.badge-featured { background: rgba(249, 115, 22, 0.8); }
.badge-new { background: rgba(52, 211, 153, 0.8); }
.badge-discount { background: rgba(239, 68, 68, 0.8); }
.external-link-icon { position: absolute; right: 10px; top: 10px; width: 32px; height: 32px; border-radius: 50%; background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(4px); color: #9ca3af; display: grid; place-items: center; cursor: pointer; z-index: 3; opacity: 0; transform: scale(0.8); transition: all 0.2s ease; }
.game-card-inner:hover .external-link-icon { opacity: 1; transform: scale(1); }
.external-link-icon:hover { background: #3b82f6; color: #fff; }
.game-info { padding: 12px; display: flex; flex-direction: column; gap: 8px; flex-grow: 1;}
.game-info h2 { margin: 0; font-size: 1.05rem; font-weight: 600; color: #f9fafb; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.game-meta { margin: 0; font-size: 0.85rem; color: #9ca3af; }
.game-rating { display: flex; align-items: center; gap: 6px; }
.stars { display: inline-flex; color: #4b5563; }
.star-filled { color: #facc15; text-shadow: 0 0 8px rgba(250,204,21,0.5); }
.rating-value { font-weight: 600; color: #fbbf24; }
.game-bottom { margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 8px;}
.price-block { display: flex; align-items: baseline; gap: 6px; }
.game-old-price { font-size: 0.85rem; color: #6b7280; text-decoration: line-through; }
.game-price { font-weight: 700; font-size: 1.1rem; color: #4ade80; }
.game-actions { display: flex; align-items: center; gap: 6px; }
.game-buy-btn {
  font-size: 0.9rem; padding: 8px 14px; border-radius: 8px; border: none; cursor: pointer; 
  background: linear-gradient(90deg, #3b82f6, #6366f1); 
  color: #fff; font-weight: 600; transition: all 0.2s ease; 
}
.game-buy-btn:hover:not(:disabled) { filter: brightness(1.15); transform: scale(1.05); }
.game-buy-btn:disabled {
  background: #4b5563;
  cursor: not-allowed;
  filter: none;
  transform: none;
}
.game-buy-btn.in-cart {
  background: #22c55e;
}
.details-btn { width: 36px; height: 36px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.15); background: rgba(255, 255, 255, 0.05); display: grid; place-items: center; color: #9ca3af; transition: all 0.2s ease; }
.details-btn:hover { border-color: #60a5fa; color: #fff; background: rgba(255, 255, 255, 0.1); }
</style>