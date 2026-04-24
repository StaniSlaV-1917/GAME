<script setup>
import { useRouter } from 'vue-router';
import { useHead } from '@vueuse/head';

useHead({
  title: '404 — Тропа теряется | GameStore',
  meta: [
    { name: 'description', content: 'Запрошенная страница не найдена. Вернитесь в оплот — на главную страницу GameStore.' },
    { name: 'robots', content: 'noindex, follow' },
  ],
});

const router = useRouter();
const goHome = () => router.push('/');
</script>

<template>
  <div class="not-found">
    <div class="nf-bg" aria-hidden="true">
      <div class="nf-glow nf-glow-1"></div>
      <div class="nf-glow nf-glow-2"></div>
      <div class="nf-grid"></div>
    </div>

    <div class="nf-inner">
      <span class="tribal-eyebrow">
        <span class="eb-spike"></span>
        Тропа потеряна
        <span class="eb-spike"></span>
      </span>

      <h1 class="nf-big" aria-label="404">
        <span class="nf-digit">4</span>
        <span class="nf-sigil" aria-hidden="true">
          <svg viewBox="-32 -32 64 64" width="120" height="120" class="nf-sigil-svg">
            <circle r="24" class="nf-ring" />
            <g class="nf-ring-teeth">
              <line v-for="i in 12" :key="i"
                    x1="0" y1="-26" x2="0" y2="-22"
                    :transform="`rotate(${(i - 1) * 30})`" />
            </g>
            <polygon class="nf-gear"
              points="0,-13 4,-6.5 11.3,-6.5 7,0 11.3,6.5 4,6.5 0,13 -4,6.5 -11.3,6.5 -7,0 -11.3,-6.5 -4,-6.5" />
            <circle r="4" class="nf-core" />
          </svg>
        </span>
        <span class="nf-digit">4</span>
      </h1>

      <p class="nf-message">
        Здесь нет ни кузницы, ни оружейной — только пепел и ветер.<br>
        Возможно, эта тропа была перекована, а может — её никогда и не было.
      </p>

      <div class="nf-actions">
        <button @click="goHome" class="forge-btn">
          <span class="fb-label">Вернуться в оплот</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.not-found {
  position: relative;
  min-height: calc(100vh - 180px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  overflow: hidden;
  text-align: center;
}

/* ── фон ── */
.nf-bg { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.nf-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
}
.nf-glow-1 {
  width: 620px; height: 620px;
  background: radial-gradient(circle, var(--ember-glow) 0%, transparent 70%);
  bottom: -200px; left: 30%;
  opacity: 0.3;
  animation: nfGlowFloat 10s ease-in-out infinite;
}
.nf-glow-2 {
  width: 440px; height: 440px;
  background: radial-gradient(circle, var(--ember-heart) 0%, transparent 70%);
  top: -120px; right: 10%;
  opacity: 0.22;
  animation: nfGlowFloat 14s ease-in-out infinite reverse;
}
.nf-grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(122, 93, 72, 0.06) 1px, transparent 1px),
    linear-gradient(90deg, rgba(122, 93, 72, 0.06) 1px, transparent 1px);
  background-size: 52px 52px;
  mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
  -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
@keyframes nfGlowFloat {
  0%, 100% { transform: translate(0, 0); }
  50%      { transform: translate(20px, -24px); }
}

/* ── содержимое ── */
.nf-inner {
  position: relative;
  z-index: 2;
  max-width: 680px;
  animation: nfFade 0.8s var(--ease-smoke) both;
}
@keyframes nfFade {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: none; }
}

.tribal-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  font-family: var(--font-ui);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 3.2px;
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

/* ── "404" с кузнечной сигилой вместо нуля ── */
.nf-big {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-family: var(--font-display);
  font-weight: var(--fw-black, 900);
  font-size: clamp(7rem, 18vw, 12rem);
  line-height: 1;
  color: var(--text-bright);
  margin: 0 0 20px;
  letter-spacing: -0.02em;
}
.nf-digit {
  text-shadow:
    0 0 12px rgba(255, 201, 121, 0.2),
    0 0 32px rgba(226, 67, 16, 0.25),
    0 4px 0 rgba(0, 0, 0, 0.5);
  background: linear-gradient(180deg,
    var(--text-bright) 0%,
    var(--text-bone) 40%,
    var(--bronze) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: nfFloat 5s ease-in-out infinite;
}
.nf-digit:last-child { animation-delay: 0.6s; }

@keyframes nfFloat {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-10px); }
}

/* ── SVG сигила-шестерня ── */
.nf-sigil {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: clamp(100px, 14vw, 160px);
  height: clamp(100px, 14vw, 160px);
  animation: nfFloat 6s ease-in-out 0.3s infinite;
}
.nf-sigil-svg { overflow: visible; }
.nf-sigil-svg g {
  transform-origin: 0 0;
  transform-box: view-box;
}
.nf-ring {
  fill: none;
  stroke: var(--bronze);
  stroke-width: 2;
  opacity: 0.7;
  filter: drop-shadow(0 0 4px rgba(199, 154, 94, 0.55));
}
.nf-ring-teeth {
  animation: nfSpin 20s linear infinite;
}
.nf-ring-teeth line {
  stroke: var(--brass);
  stroke-width: 2.5;
  stroke-linecap: square;
  opacity: 0.8;
}
.nf-gear {
  fill: none;
  stroke: var(--ember-flame);
  stroke-width: 2;
  animation: nfSpinRev 12s linear infinite;
  transform-origin: 0 0;
  transform-box: view-box;
  filter: drop-shadow(0 0 6px rgba(255, 122, 43, 0.65));
}
.nf-core {
  fill: var(--ember-gold);
  filter: drop-shadow(0 0 10px rgba(255, 201, 121, 0.8));
  animation: nfPulse 1.8s ease-in-out infinite;
}
@keyframes nfSpin    { to { transform: rotate(360deg); } }
@keyframes nfSpinRev { to { transform: rotate(-360deg); } }
@keyframes nfPulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50%      { transform: scale(1.3); opacity: 0.85; }
}

/* ── сообщение ── */
.nf-message {
  font-family: var(--font-body);
  font-size: clamp(1rem, 1.5vw, 1.15rem);
  color: var(--text-parchment);
  line-height: 1.75;
  margin: 0 0 36px;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.55);
}

/* ── кнопка ── */
.nf-actions {
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
  padding: 16px 36px;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.05rem;
  letter-spacing: 1.5px;
  text-transform: uppercase;
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
.forge-btn:active { transform: translateY(0); }
.fb-label { position: relative; z-index: 1; }

@media (max-width: 480px) {
  .nf-big { gap: 2px; }
  .nf-message { font-size: 0.95rem; }
}
</style>
