<script setup>
import { computed } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useHead } from '@vueuse/head';
import { useModeStore } from '../stores/mode';

const route = useRoute();
const modeStore = useModeStore();

/**
 * Один компонент для всех заглушек форумных разделов: /feed, /posts, /mods, /community.
 * Текст и иконка выбираются по `route.meta.stub`.
 */
const stubInfo = computed(() => {
  const meta = route.meta?.stub || {};
  return {
    title:    meta.title    || 'Раздел в разработке',
    eyebrow:  meta.eyebrow  || 'Скоро',
    icon:     meta.icon     || '⚙',
  };
});

useHead(() => ({
  title: `${stubInfo.value.title} | GameStore`,
  meta: [{ name: 'robots', content: 'noindex, follow' }],
}));
</script>

<template>
  <main class="stub-page">
    <div class="stub-bg" aria-hidden="true">
      <div class="stub-glow stub-glow-1"></div>
      <div class="stub-glow stub-glow-2"></div>
    </div>

    <div class="stub-slab">
      <span class="rivet rivet-tl" aria-hidden="true"></span>
      <span class="rivet rivet-tr" aria-hidden="true"></span>
      <span class="rivet rivet-bl" aria-hidden="true"></span>
      <span class="rivet rivet-br" aria-hidden="true"></span>

      <span class="stub-eyebrow">
        <span class="eb-spike"></span>
        {{ stubInfo.eyebrow }}
        <span class="eb-spike"></span>
      </span>

      <div class="stub-icon-wrap" aria-hidden="true">
        <span class="stub-icon">{{ stubInfo.icon }}</span>
        <span class="stub-icon-halo"></span>
      </div>

      <h1 class="stub-title">{{ stubInfo.title }}</h1>

      <div class="stub-divider" aria-hidden="true">
        <span></span>
        <span class="stub-divider-spike"></span>
        <span></span>
      </div>

      <p class="stub-message">
        Этот раздел ещё в кузнице — мастера куют его прямо сейчас.<br>
        Загляни позже, или вернись в магазин, где всё уже готово.
      </p>

      <div class="stub-actions">
        <RouterLink to="/" class="forge-btn primary" @click="modeStore.setMode('shop')">
          <span class="fb-icon" aria-hidden="true">⚔</span>
          <span class="fb-label">В магазин</span>
        </RouterLink>
      </div>
    </div>
  </main>
</template>

<style scoped>
.stub-page {
  position: relative;
  min-height: calc(100vh - 200px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 24px;
  overflow: hidden;
}

.stub-bg { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.stub-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  opacity: 0.28;
}
.stub-glow-1 {
  width: 540px; height: 540px;
  background: radial-gradient(circle, var(--ember-glow) 0%, transparent 70%);
  bottom: -160px; left: 25%;
  animation: stubFloat 12s ease-in-out infinite;
}
.stub-glow-2 {
  width: 420px; height: 420px;
  background: radial-gradient(circle, var(--ember-heart) 0%, transparent 70%);
  top: -100px; right: 12%;
  animation: stubFloat 16s ease-in-out infinite reverse;
}
@keyframes stubFloat {
  0%, 100% { transform: translate(0, 0); }
  50%      { transform: translate(20px, -24px); }
}

.stub-slab {
  position: relative;
  z-index: 2;
  max-width: 580px;
  width: 100%;
  padding: 56px 44px 44px;
  text-align: center;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone) 45%,
    var(--ash-coal) 100%);
  border: 1px solid var(--bronze-dark);
  clip-path: var(--clip-forged-md);
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    inset 0 1px 0 3px var(--iron-warm),
    var(--shadow-deep),
    var(--inset-forge);
  animation: stubFade 0.7s var(--ease-smoke) both;
}
@keyframes stubFade {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: none; }
}

.rivet {
  position: absolute;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    var(--brass) 0%,
    var(--bronze) 45%,
    var(--iron-void) 100%);
  box-shadow:
    inset -1px -1px 2px rgba(0, 0, 0, 0.7),
    inset 1px 1px 1px rgba(255, 201, 121, 0.4),
    0 0 6px rgba(199, 154, 94, 0.5);
  z-index: 4;
}
.rivet-tl { top: 14px;    left: 14px; }
.rivet-tr { top: 14px;    right: 14px; }
.rivet-bl { bottom: 14px; left: 14px; }
.rivet-br { bottom: 14px; right: 14px; }

.stub-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--bronze);
  margin-bottom: 22px;
}
.eb-spike {
  width: 0; height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 7px solid var(--bronze);
  filter: drop-shadow(0 0 4px rgba(199, 154, 94, 0.55));
}

.stub-icon-wrap {
  position: relative;
  width: 96px;
  height: 96px;
  margin: 0 auto 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.stub-icon {
  position: relative;
  z-index: 2;
  font-size: 3.5rem;
  filter: drop-shadow(0 0 10px rgba(255, 201, 121, 0.55));
  animation: stubIconSpin 18s linear infinite;
}
.stub-icon-halo {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(226, 67, 16, 0.3) 0%, transparent 70%);
  filter: blur(8px);
  animation: stubHaloPulse 3s ease-in-out infinite;
}
@keyframes stubIconSpin {
  to { transform: rotate(360deg); }
}
@keyframes stubHaloPulse {
  0%, 100% { transform: scale(1);    opacity: 0.7; }
  50%      { transform: scale(1.15); opacity: 1;   }
}

.stub-title {
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(1.6rem, 4vw, 2.2rem);
  color: var(--text-bright);
  margin: 0 0 4px;
  letter-spacing: 0.5px;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.55);
}

.stub-divider {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 16px auto 18px;
  max-width: 280px;
}
.stub-divider > span:first-child,
.stub-divider > span:last-child {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--bronze-dark) 50%, transparent);
}
.stub-divider-spike {
  width: 0; height: 0;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-top: 8px solid var(--ember-deep);
  filter: drop-shadow(0 0 4px rgba(194, 40, 26, 0.6));
}

.stub-message {
  font-family: var(--font-body);
  font-size: 0.96rem;
  color: var(--text-parchment);
  line-height: 1.75;
  margin: 0 0 28px;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.45);
}

.stub-actions {
  display: flex;
  justify-content: center;
  gap: 14px;
  flex-wrap: wrap;
}
.forge-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 14px 32px;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 0.96rem;
  letter-spacing: 1.4px;
  text-transform: uppercase;
  text-decoration: none;
  cursor: pointer;
  overflow: hidden;
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    var(--glow-ember);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.65);
  transition: transform 0.18s var(--ease-forge), box-shadow 0.2s var(--ease-smoke);
  clip-path: var(--clip-forged-sm);
}
.forge-btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg,
    transparent 0%, rgba(255, 201, 121, 0.4) 50%, transparent 100%);
  transform: translateX(-120%);
  transition: transform 0.6s var(--ease-smoke);
  pointer-events: none;
}
.forge-btn:hover {
  transform: translateY(-2px);
  box-shadow:
    var(--inset-iron-top),
    inset 0 -2px 3px rgba(0, 0, 0, 0.35),
    var(--glow-ember-strong);
}
.forge-btn:hover::after { transform: translateX(120%); }
.fb-icon, .fb-label { position: relative; z-index: 1; }
.fb-icon { font-size: 1rem; filter: drop-shadow(0 0 4px rgba(255, 201, 121, 0.5)); }

@media (max-width: 480px) {
  .stub-slab { padding: 40px 22px 30px; }
  .stub-title { font-size: 1.4rem; }
  .stub-icon { font-size: 2.8rem; }
  .stub-icon-wrap { width: 80px; height: 80px; }
}
</style>
