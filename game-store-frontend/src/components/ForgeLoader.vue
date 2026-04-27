<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

/**
 * ForgeLoader — лоадер «раздуваем горн».
 * Показывается когда первичный API-запрос затягивается (cold-start Fly).
 * Появляется через `delay` мс — если запрос успел — лоадер не моргнул.
 *
 * Использование:
 *   <ForgeLoader v-if="loading" :delay="600" message="Раздуваем горн…" />
 */
const props = defineProps({
  delay:   { type: Number, default: 600 },           // мс до появления (anti-flash)
  message: { type: String, default: 'Раздуваем горн…' },
  hint:    { type: String, default: 'Сервер просыпается, это займёт несколько секунд' },
});

const visible = ref(false);
let showTimer = null;

onMounted(() => {
  showTimer = setTimeout(() => { visible.value = true; }, props.delay);
});
onUnmounted(() => {
  clearTimeout(showTimer);
});
</script>

<template>
  <Transition name="forge-fade">
    <div v-if="visible" class="forge-loader" role="status" aria-live="polite">
      <div class="forge-loader-inner">
        <!-- Анвиль с углями -->
        <div class="anvil-wrap" aria-hidden="true">
          <!-- Сам анвиль (силуэт) -->
          <svg class="anvil" viewBox="0 0 80 60" width="80" height="60">
            <path d="M 8 12 L 72 12 L 68 24 L 52 24 L 52 36 L 60 36 L 60 44 L 20 44 L 20 36 L 28 36 L 28 24 L 12 24 Z"
                  class="anvil-body" />
          </svg>

          <!-- Угли в горне под анвилем (3 пульсирующих) -->
          <div class="forge-bed">
            <span class="coal coal-1"></span>
            <span class="coal coal-2"></span>
            <span class="coal coal-3"></span>
          </div>

          <!-- Языки пламени над углями -->
          <div class="flames" aria-hidden="true">
            <span class="flame flame-1"></span>
            <span class="flame flame-2"></span>
            <span class="flame flame-3"></span>
          </div>

          <!-- Искры по сторонам -->
          <span v-for="i in 6" :key="i" class="spark" :style="{ '--i': i }"></span>
        </div>

        <p class="forge-message">{{ message }}</p>
        <p v-if="hint" class="forge-hint">{{ hint }}</p>

        <!-- Прогресс-полоса в виде раскалённого металла -->
        <div class="forge-bar">
          <div class="forge-bar-fill"></div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.forge-loader {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 24px;
  min-height: 420px;
}

.forge-loader-inner {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  text-align: center;
}

/* ── Анвиль + горн ── */
.anvil-wrap {
  position: relative;
  width: 120px;
  height: 100px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
}
.anvil {
  position: relative;
  z-index: 3;
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.7));
}
.anvil-body {
  fill: var(--iron-mid);
  stroke: var(--iron-void);
  stroke-width: 1.5;
  stroke-linejoin: miter;
}

.forge-bed {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 70px;
  height: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  z-index: 1;
}
.coal {
  width: 10px; height: 10px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--ember-gold) 0%,
    var(--ember-flame) 45%,
    var(--ember-deep) 100%);
  box-shadow:
    0 0 14px rgba(255, 122, 43, 0.85),
    0 0 28px rgba(226, 67, 16, 0.5);
  animation: coalPulse 1.6s ease-in-out infinite;
}
.coal-1 { animation-delay: 0s; }
.coal-2 { animation-delay: 0.4s; }
.coal-3 { animation-delay: 0.8s; }

/* ── Языки пламени ── */
.flames {
  position: absolute;
  bottom: 8px;
  left: 50%;
  transform: translateX(-50%);
  width: 70px;
  height: 38px;
  display: flex;
  justify-content: center;
  gap: 6px;
  z-index: 2;
  pointer-events: none;
}
.flame {
  width: 14px;
  height: 100%;
  background: radial-gradient(ellipse 60% 80% at 50% 100%,
    var(--ember-spark) 0%,
    var(--ember-glow) 30%,
    var(--ember-flame) 60%,
    transparent 100%);
  border-radius: 50% 50% 30% 30% / 70% 70% 30% 30%;
  filter: blur(0.5px);
  opacity: 0.9;
  mix-blend-mode: screen;
  transform-origin: 50% 100%;
}
.flame-1 { animation: flameFlick 1.2s ease-in-out infinite;        }
.flame-2 { animation: flameFlick 1.4s ease-in-out 0.2s infinite;   }
.flame-3 { animation: flameFlick 1s   ease-in-out 0.4s infinite;   }

/* ── Искры ── */
.spark {
  position: absolute;
  bottom: 8px;
  left: 50%;
  width: 2px; height: 2px;
  border-radius: 50%;
  background: var(--ember-gold);
  box-shadow: 0 0 4px rgba(255, 201, 121, 0.9);
  opacity: 0;
  animation: sparkFly 2.4s ease-out infinite;
  animation-delay: calc(var(--i) * -0.4s);
  --angle: calc((var(--i) - 3) * 35deg);
}

/* ── Текст ── */
.forge-message {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: var(--fw-bold);
  letter-spacing: 0.04em;
  color: var(--text-bright);
  text-shadow: 0 0 10px rgba(255, 122, 43, 0.4);
}
.forge-hint {
  margin: 0;
  font-family: var(--font-body);
  font-size: 0.86rem;
  color: var(--text-ash);
  font-style: italic;
  max-width: 320px;
  line-height: 1.5;
}

/* ── Раскалённая полоса прогресса ── */
.forge-bar {
  position: relative;
  width: 220px;
  height: 4px;
  background: var(--iron-void);
  border: 1px solid var(--iron-dark);
  overflow: hidden;
  box-shadow: var(--inset-iron-top);
  margin-top: 6px;
}
.forge-bar-fill {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%,
    var(--ember-deep) 25%,
    var(--ember-flame) 50%,
    var(--ember-glow) 75%,
    transparent 100%);
  background-size: 200% 100%;
  animation: barShimmer 1.6s linear infinite;
}

/* ── Анимации ── */
@keyframes coalPulse {
  0%, 100% { transform: scale(1);    opacity: 0.85; }
  50%      { transform: scale(1.25); opacity: 1;    }
}
@keyframes flameFlick {
  0%, 100% { transform: scaleY(1)    scaleX(1);    opacity: 0.85; }
  35%      { transform: scaleY(1.25) scaleX(0.85); opacity: 1;    }
  70%      { transform: scaleY(0.85) scaleX(1.1);  opacity: 0.7;  }
}
@keyframes sparkFly {
  0%   { opacity: 0; transform: rotate(var(--angle)) translateY(0) scale(0.5); }
  15%  { opacity: 1; transform: rotate(var(--angle)) translateY(-12px) scale(1); }
  100% { opacity: 0; transform: rotate(var(--angle)) translateY(-80px) scale(0.2); }
}
@keyframes barShimmer {
  0%   { background-position: -100% 0; }
  100% { background-position:  200% 0; }
}

/* ── Появление ── */
.forge-fade-enter-active { transition: opacity 0.3s var(--ease-smoke); }
.forge-fade-leave-active { transition: opacity 0.2s var(--ease-smoke); }
.forge-fade-enter-from,
.forge-fade-leave-to    { opacity: 0; }

/* ── Reduce-motion ── */
@media (prefers-reduced-motion: reduce) {
  .coal, .flame, .spark, .forge-bar-fill { animation: none; }
  .coal { opacity: 0.85; }
  .forge-bar-fill { background: var(--ember-flame); opacity: 0.6; }
}
</style>
