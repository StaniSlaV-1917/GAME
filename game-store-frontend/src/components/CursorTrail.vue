<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

/**
 * CursorTrail — кованый шлейф искр за нативным курсором.
 *
 * Тип частиц:
 *   spark — самая яркая, маленькая, с blur 0
 *   ember — средняя, медленнее падает с гравитацией (имитация тлеющего угля)
 *   wisp  — тёмный «дымок» с большим blur, уплывает вверх
 *
 * При клике — бурст 6-8 искр радиально (как удар молота по наковальне).
 * Цвета через CSS-переменные → автоматически меняются с темой.
 *
 * Производительность:
 *   - спавн по дистанции (каждые 25px движения), не по времени — на медленном
 *     движении частицы редкие, на быстром густые, как настоящий шлейф искр
 *   - максимум 15 живых частиц одновременно (старые вытесняются)
 *   - анимация чисто CSS через transform/opacity (GPU)
 *   - на pointer:coarse (тач) — компонент не подключает листенеры
 *   - на prefers-reduced-motion — не подключает листенеры
 */

const particles = ref([]);
let nextId = 0;
let lastSpawnPos = null;

const MIN_SPAWN_DISTANCE = 25;   // px между спавнами на mousemove
const MAX_PARTICLES = 15;        // лимит одновременных
const PARTICLE_LIFETIME = 1500;  // ms, синхронизировано с CSS @keyframes

function spawnParticle(x, y, type, vx = 0, vy = 0) {
  if (particles.value.length >= MAX_PARTICLES) {
    particles.value.shift();
  }
  const id = ++nextId;
  // Лёгкий рандомный offset чтоб шлейф «дрожал» естественно
  const ox = (Math.random() - 0.5) * 8;
  const oy = (Math.random() - 0.5) * 8;
  particles.value.push({
    id,
    x: x + ox,
    y: y + oy,
    type,
    vx,
    vy,
  });
  setTimeout(() => {
    particles.value = particles.value.filter((p) => p.id !== id);
  }, PARTICLE_LIFETIME);
}

function pickType() {
  const r = Math.random();
  if (r < 0.45) return 'spark';
  if (r < 0.85) return 'ember';
  return 'wisp';
}

function onMouseMove(e) {
  if (!lastSpawnPos) {
    lastSpawnPos = { x: e.clientX, y: e.clientY };
    return;
  }
  const dx = e.clientX - lastSpawnPos.x;
  const dy = e.clientY - lastSpawnPos.y;
  const dist = Math.hypot(dx, dy);
  if (dist >= MIN_SPAWN_DISTANCE) {
    // Скорость движения курсора = направление слабого «следа» частицы
    const speed = Math.min(dist / 4, 12);
    const dirX = (dx / dist) * speed;
    const dirY = (dy / dist) * speed;
    spawnParticle(e.clientX, e.clientY, pickType(), -dirX, -dirY);
    lastSpawnPos = { x: e.clientX, y: e.clientY };
  }
}

function onMouseDown(e) {
  // Бурст: 6-8 искр радиально от точки клика
  const count = 6 + Math.floor(Math.random() * 3);
  for (let i = 0; i < count; i++) {
    const baseAngle = (i / count) * Math.PI * 2;
    const angle = baseAngle + (Math.random() - 0.5) * 0.4;
    const speed = 30 + Math.random() * 35;
    const vx = Math.cos(angle) * speed;
    const vy = Math.sin(angle) * speed;
    spawnParticle(e.clientX, e.clientY, 'spark', vx, vy);
  }
}

const enabled = ref(false);

onMounted(() => {
  const isTouch = window.matchMedia('(pointer: coarse)').matches;
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (isTouch || reducedMotion) {
    return;  // не подключаем листенеры — экономим батарею/процессор
  }

  enabled.value = true;
  document.addEventListener('mousemove', onMouseMove, { passive: true });
  document.addEventListener('mousedown', onMouseDown, { passive: true });
});

onUnmounted(() => {
  document.removeEventListener('mousemove', onMouseMove);
  document.removeEventListener('mousedown', onMouseDown);
});
</script>

<template>
  <Teleport to="body">
    <div v-if="enabled" class="cursor-trail" aria-hidden="true">
      <span
        v-for="p in particles"
        :key="p.id"
        :class="['ct-particle', `ct-${p.type}`]"
        :style="{
          left: p.x + 'px',
          top: p.y + 'px',
          '--vx': p.vx + 'px',
          '--vy': p.vy + 'px',
        }"
      ></span>
    </div>
  </Teleport>
</template>

<style scoped>
.cursor-trail {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 99999;
}

.ct-particle {
  position: fixed;
  pointer-events: none;
  will-change: transform, opacity;
  /* Theme-aware колоркод: dark — оранжево-красный огонь,
     light — золотисто-бронзовый, legacy — магически пурпурный.
     Переопределяется ниже через [data-theme="..."] :deep(). */
  --c-core: var(--ember-gold);
  --c-mid:  var(--ember-flame);
  --c-edge: var(--ember-deep);
}

/* ── SPARK: яркая колкая искра, без блюра ── */
.ct-spark {
  width: 4px; height: 4px;
  margin: -2px 0 0 -2px;
  border-radius: 50%;
  background: radial-gradient(circle, var(--c-core), var(--c-mid));
  box-shadow:
    0 0 4px var(--c-mid),
    0 0 10px var(--c-edge);
  animation: ct-spark-anim 1.5s linear forwards;
}
@keyframes ct-spark-anim {
  0%   { opacity: 0; transform: translate3d(0, 0, 0) scale(0.4); }
  8%   { opacity: 1; transform: translate3d(calc(var(--vx) * 0.15), calc(var(--vy) * 0.15), 0) scale(1); }
  60%  { opacity: 0.9; }
  100% { opacity: 0; transform: translate3d(var(--vx), calc(var(--vy) + 70px), 0) scale(0.25); }
}

/* ── EMBER: средний тлеющий уголёк, лёгкий блюр, падает с «гравитацией» ── */
.ct-ember {
  width: 6px; height: 6px;
  margin: -3px 0 0 -3px;
  border-radius: 50%;
  background: radial-gradient(circle, var(--c-core), var(--c-mid) 60%, transparent);
  box-shadow: 0 0 8px var(--c-mid);
  filter: blur(0.6px);
  animation: ct-ember-anim 1.6s ease-out forwards;
}
@keyframes ct-ember-anim {
  0%   { opacity: 0; transform: translate3d(0, 0, 0) scale(0.5); }
  15%  { opacity: 1; transform: translate3d(calc(var(--vx) * 0.25), calc(var(--vy) * 0.25), 0) scale(1.1); }
  100% { opacity: 0; transform: translate3d(var(--vx), calc(var(--vy) + 90px), 0) scale(0.4); }
}

/* ── WISP: дымок, большой блюр, поднимается вверх и тает ── */
.ct-wisp {
  width: 10px; height: 10px;
  margin: -5px 0 0 -5px;
  border-radius: 50%;
  background: radial-gradient(circle, var(--c-edge), transparent 70%);
  filter: blur(2.5px);
  opacity: 0;
  animation: ct-wisp-anim 1.8s ease-out forwards;
}
@keyframes ct-wisp-anim {
  0%   { opacity: 0; transform: translate3d(0, 0, 0) scale(0.5); }
  30%  { opacity: 0.55; transform: translate3d(calc(var(--vx) * 0.4), calc(var(--vy) * 0.4 - 14px), 0) scale(1.6); }
  100% { opacity: 0; transform: translate3d(var(--vx), calc(var(--vy) - 60px), 0) scale(2.2); }
}

/* ── Theme adaptation ──
   В scoped-стиле для перекрытия per-theme нужен :global() или работа через
   data-theme на html. Делаем :global селекторы чтоб поймать атрибут на html. */
:global([data-theme="light"]) .ct-particle {
  --c-core: var(--ember-gold);
  --c-mid:  var(--ember-glow);
  --c-edge: var(--bronze);
}

:global([data-theme="legacy"]) .ct-particle {
  /* В legacy палитре ember-* = магические пурпур+лазурь */
  --c-core: var(--ember-gold);     /* #d4c8ff */
  --c-mid:  var(--ember-spark);    /* #a89af5 */
  --c-edge: var(--ember-flame);    /* #5e4cc8 */
}
</style>
