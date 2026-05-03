<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useHead } from '@vueuse/head';

useHead({
  title: '404 — Руины форпоста | GameStore',
  meta: [
    { name: 'description', content: 'Этот форпост разрушен. Запрошенная страница не найдена. Вернитесь в оплот — на главную GameStore.' },
    { name: 'robots', content: 'noindex, follow' },
  ],
});

const router = useRouter();

// ── Mouse parallax ───────────────────────────────────────────
const mx = ref(0);
const my = ref(0);
function onMM(e) {
  mx.value = (e.clientX / window.innerWidth  - 0.5) * 2;
  my.value = (e.clientY / window.innerHeight - 0.5) * 2;
}
onMounted(()  => window.addEventListener('mousemove', onMM, { passive: true }));
onUnmounted(() => window.removeEventListener('mousemove', onMM));

const p1 = computed(() => ({ transform: `translate(${mx.value * -14}px, ${my.value * -9}px)` }));
const p2 = computed(() => ({ transform: `translate(${mx.value * -7}px,  ${my.value * -4}px)` }));
const p3 = computed(() => ({ transform: `translate(${mx.value *  9}px,  ${my.value *  5}px)` }));

// ── Particles ────────────────────────────────────────────────
const KINDS = ['ember', 'ember', 'cinder', 'ash'];
const particles = Array.from({ length: 28 }, (_, i) => ({
  id:       i,
  left:     `${3 + (i * 3.53) % 92}%`,
  delay:    `${(i * 0.41) % 8}s`,
  duration: `${5 + (i * 0.67) % 6}s`,
  size:     `${2 + (i * 0.53) % 5}px`,
  kind:     KINDS[i % 4],
  drift:    `${-25 + (i % 11) * 5}px`,
}));

// ── Drips ────────────────────────────────────────────────────
const drips = [
  { left: '12%', delay: '0s',   dur: '2.2s' },
  { left: '28%', delay: '0.6s', dur: '2.8s' },
  { left: '50%', delay: '1.1s', dur: '2.4s' },
  { left: '68%', delay: '0.3s', dur: '3.0s' },
  { left: '84%', delay: '0.9s', dur: '2.6s' },
];

// ── Nav ──────────────────────────────────────────────────────
const goHome    = () => router.push('/');
const goCatalog = () => router.push('/catalog');
const goNews    = () => router.push('/news');
const goFeed    = () => router.push('/feed');
</script>

<template>
  <div class="r404">

    <!-- ══════════════ BACKGROUND -->
    <div class="bg" aria-hidden="true">

      <!-- Fog -->
      <div class="fog" :style="p1">
        <div class="fog-b fog-1"></div>
        <div class="fog-b fog-2"></div>
        <div class="fog-b fog-3"></div>
      </div>

      <!-- Ruins silhouette -->
      <div class="ruins-wrap" :style="p2">
        <svg class="ruins-svg" viewBox="0 0 1440 380" preserveAspectRatio="xMidYMax meet">
          <!-- Left tower -->
          <rect x="0"   y="110" width="55"  height="270" fill="#0d0b09"/>
          <polygon points="0,110 0,85 12,95 20,78 30,95 42,76 55,110" fill="#0d0b09"/>
          <rect x="14" y="158" width="14" height="22" rx="2" fill="#e24310" opacity="0.18"/>
          <!-- Left arch -->
          <path d="M80,380 L80,210 Q80,168 116,168 Q152,168 152,210 L152,380Z" fill="#0d0b09" opacity="0.9"/>
          <!-- Fallen blocks L -->
          <rect x="168" y="332" width="44" height="48" rx="2" fill="#0d0b09" opacity="0.7" transform="rotate(-11,190,356)"/>
          <rect x="222" y="348" width="30" height="32" rx="2" fill="#0d0b09" opacity="0.6" transform="rotate(7,237,364)"/>
          <rect x="262" y="358" width="20" height="22" rx="2" fill="#0d0b09" opacity="0.5"/>
          <!-- Wall L -->
          <rect x="300" y="272" width="175" height="9" fill="#0d0b09" opacity="0.55" transform="rotate(2,387,276)"/>
          <rect x="315" y="238" width="9" height="43" fill="#0d0b09" opacity="0.55"/>
          <rect x="360" y="230" width="9" height="51" fill="#0d0b09" opacity="0.55"/>
          <rect x="405" y="240" width="9" height="41" fill="#0d0b09" opacity="0.5" transform="rotate(5,409,260)"/>
          <!-- Gate arch center -->
          <path d="M560,380 L560,198 Q560,143 620,143 Q680,143 680,198 L680,380Z" fill="#0d0b09" opacity="0.95"/>
          <path d="M620,200 L615,232 L622,262 L617,295" stroke="#e24310" stroke-width="1.5" fill="none" opacity="0.14" stroke-dasharray="4,3"/>
          <!-- Wall R -->
          <rect x="700" y="252" width="158" height="9" fill="#0d0b09" opacity="0.5" transform="rotate(-3,779,256)"/>
          <rect x="716" y="220" width="9" height="41" fill="#0d0b09" opacity="0.5"/>
          <rect x="758" y="228" width="9" height="33" fill="#0d0b09" opacity="0.45"/>
          <rect x="800" y="232" width="9" height="28" fill="#0d0b09" opacity="0.4" transform="rotate(8,804,246)"/>
          <!-- Right arch -->
          <path d="M870,380 L870,192 Q870,152 910,152 Q950,152 950,192 L950,380Z" fill="#0d0b09" opacity="0.88"/>
          <!-- Fallen blocks R -->
          <rect x="966" y="342" width="38" height="38" rx="2" fill="#0d0b09" opacity="0.65" transform="rotate(9,985,361)"/>
          <rect x="1016" y="356" width="26" height="26" rx="2" fill="#0d0b09" opacity="0.55" transform="rotate(-5,1029,369)"/>
          <!-- Right tower (tall) -->
          <rect x="1080" y="88" width="76" height="292" fill="#0d0b09" opacity="0.95"/>
          <polygon points="1080,88 1092,58 1109,78 1122,43 1139,70 1153,50 1156,88" fill="#0d0b09" opacity="0.95"/>
          <rect x="1106" y="158" width="12" height="24" rx="2" fill="#c7841a" opacity="0.22"/>
          <rect x="1106" y="220" width="12" height="18" rx="2" fill="#c7841a" opacity="0.14"/>
          <!-- Far right -->
          <rect x="1265" y="182" width="48" height="198" fill="#0d0b09" opacity="0.7"/>
          <polygon points="1265,182 1277,155 1292,170 1313,148 1313,182" fill="#0d0b09" opacity="0.7"/>
          <!-- Ground -->
          <rect x="0" y="375" width="1440" height="5" fill="#0d0b09" opacity="0.6"/>
        </svg>
      </div>

      <!-- Grid texture -->
      <div class="bg-grid" :style="p3"></div>

      <!-- Glow orbs -->
      <div class="glow glow-1"></div>
      <div class="glow glow-2"></div>
      <div class="glow glow-3"></div>
      <div class="ground-glow"></div>

      <!-- Particles -->
      <div class="ptcls">
        <span
          v-for="p in particles" :key="p.id"
          class="ptcl"
          :class="`ptcl--${p.kind}`"
          :style="{
            left: p.left, width: p.size, height: p.size,
            '--drift': p.drift,
            animationDuration: p.duration,
            animationDelay: p.delay,
          }"
          aria-hidden="true"
        />
      </div>
    </div>

    <!-- ══════════════ CARD -->
    <div class="card">

      <!-- Rivets -->
      <span class="rivet rv-tl" aria-hidden="true"/>
      <span class="rivet rv-tr" aria-hidden="true"/>
      <span class="rivet rv-bl" aria-hidden="true"/>
      <span class="rivet rv-br" aria-hidden="true"/>

      <!-- Chains -->
      <div class="chain chain-l" aria-hidden="true">
        <span v-for="n in 5" :key="n" class="clink"/>
      </div>
      <div class="chain chain-r" aria-hidden="true">
        <span v-for="n in 5" :key="n" class="clink"/>
      </div>

      <!-- Spike row -->
      <div class="spikes" aria-hidden="true">
        <span v-for="n in 9" :key="n" class="sp" :class="{ tall: n === 5 }"/>
      </div>

      <!-- Top brand bar -->
      <div class="top-bar" aria-hidden="true"/>

      <div class="inner">

        <!-- Eyebrow -->
        <p class="eyebrow" aria-hidden="true">
          <svg class="zig" width="34" height="8" viewBox="0 0 34 8" fill="none">
            <path d="M0,4 L5,4 L8,1 L11,4 L14,1 L17,4 L20,1 L23,4 L26,1 L29,4 L34,4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>РУИНЫ · ФОРПОСТА · 404</span>
          <svg class="zig flip" width="34" height="8" viewBox="0 0 34 8" fill="none">
            <path d="M0,4 L5,4 L8,1 L11,4 L14,1 L17,4 L20,1 L23,4 L26,1 L29,4 L34,4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </p>

        <!-- 4 [rune] 4 -->
        <h1 class="hero" aria-label="404 — страница не найдена">
          <span class="hd hd-1" aria-hidden="true">4</span>

          <span class="rune-0" aria-hidden="true">
            <svg class="rune-svg" viewBox="-36 -36 72 72">
              <!-- Halo glow -->
              <circle r="33" class="rune-halo"/>
              <!-- Outer ring + teeth -->
              <circle r="29" class="rune-outer"/>
              <g class="rot-cw">
                <line v-for="t in 24" :key="t"
                      x1="0" y1="-30.5" x2="0" y2="-27"
                      :transform="`rotate(${(t-1)*15})`"
                      stroke="var(--brass)" stroke-width="2" stroke-linecap="square" opacity="0.65"/>
              </g>
              <!-- Decorative arcs -->
              <path d="M-14,-26 A28,28 0 0,1 14,-26" class="rune-arc" stroke="var(--ember-flame)" stroke-dasharray="3 3"/>
              <path d="M-14, 26 A28,28 0 0,0 14, 26" class="rune-arc" stroke="var(--ember-flame)" stroke-dasharray="3 3"/>
              <path d="M-26,-14 A28,28 0 0,0 -26,14" class="rune-arc" stroke="var(--bronze)" stroke-dasharray="3 4"/>
              <path d="M 26,-14 A28,28 0 0,1  26,14" class="rune-arc" stroke="var(--bronze)" stroke-dasharray="3 4"/>
              <!-- Inner ring + teeth -->
              <circle r="19" class="rune-inner"/>
              <g class="rot-ccw">
                <line v-for="t in 12" :key="t"
                      x1="0" y1="-20.5" x2="0" y2="-18"
                      :transform="`rotate(${(t-1)*30})`"
                      stroke="var(--ember-heart)" stroke-width="1.8" opacity="0.6"/>
              </g>
              <!-- Cracks -->
              <path pathLength="1" d="M1,-2 L-2,-8 L-5,-14 L-3,-20 L-5,-26" class="crack ck-1"/>
              <path pathLength="1" d="M-1,-3 L3,-9  L6,-16  L5,-22"          class="crack ck-2"/>
              <path pathLength="1" d="M2, 2 L7, 7  L5, 14  L8, 21"           class="crack ck-3"/>
              <path pathLength="1" d="M-2, 2 L-5, 8 L-4,16  L-7,22"          class="crack ck-4"/>
              <path pathLength="1" d="M3, 0 L8, 2  L14, 0  L20,-1"           class="crack ck-5"/>
              <path pathLength="1" d="M-3,1 L-8, 1 L-15, 3"                  class="crack ck-6"/>
              <!-- Ember sparks -->
              <circle cx="-5"  cy="-26" r="2.5" class="espark es-1"/>
              <circle cx=" 5"  cy="-22" r="2"   class="espark es-2"/>
              <circle cx=" 8"  cy=" 21" r="2.2" class="espark es-3"/>
              <circle cx="-7"  cy=" 22" r="1.8" class="espark es-4"/>
              <circle cx=" 20" cy=" -1" r="1.5" class="espark es-5"/>
              <circle cx="-15" cy="  3" r="1.5" class="espark es-6"/>
              <!-- Central void -->
              <circle r="8.5" class="void-ring"/>
              <circle r="5.5" class="void-fill"/>
              <circle r="2.5" class="void-core"/>
            </svg>
          </span>

          <span class="hd hd-2" aria-hidden="true">4</span>
        </h1>

        <!-- Caption -->
        <p class="caption">ТРОПА ОБРЫВАЕТСЯ · СТРАНИЦА НЕ НАЙДЕНА</p>

        <!-- Divider -->
        <div class="divider" aria-hidden="true">
          <span class="dline"/>
          <span class="dflame">
            <svg width="16" height="22" viewBox="0 0 16 22" fill="none">
              <path d="M8,0 C8,0 15,7 15,13 C15,17.5 11.9,21 8,21 C4.1,21 1,17.5 1,13 C1,7 8,0 8,0Z" class="fl-outer"/>
              <path d="M8,7 C8,7 12,11 12,15 C12,17.8 10.2,20 8,20 C5.8,20 4,17.8 4,15 C4,11 8,7 8,7Z" class="fl-inner"/>
              <circle cx="8" cy="18" r="2" class="fl-core"/>
            </svg>
          </span>
          <span class="dline"/>
        </div>

        <!-- Message -->
        <div class="msgs" role="status">
          <p class="msg ml-1">Заблудший воин — эту тропу поглотили руины.</p>
          <p class="msg ml-2">Здесь не осталось ни кузницы, ни ворот, лишь пепел и тишина.</p>
          <p class="msg ml-3 muted">Возможно, путь был перекован. Или его никогда не было.</p>
        </div>

        <!-- Quick nav -->
        <nav class="qnav" aria-label="Быстрые переходы">
          <button @click="goHome"    class="qlink"><span aria-hidden="true">🏰</span> Оплот</button>
          <span class="qdot" aria-hidden="true"/>
          <button @click="goCatalog" class="qlink"><span aria-hidden="true">⚔</span> Оружейная</button>
          <span class="qdot" aria-hidden="true"/>
          <button @click="goNews"    class="qlink"><span aria-hidden="true">📜</span> Хроники</button>
          <span class="qdot" aria-hidden="true"/>
          <button @click="goFeed"    class="qlink"><span aria-hidden="true">🔥</span> Лента</button>
        </nav>

        <!-- CTA -->
        <div class="cta-row">
          <button @click="goHome" class="cta">
            <span class="cta-shine" aria-hidden="true"/>
            <span class="cta-icon" aria-hidden="true">⚔</span>
            <span class="cta-text">Вернуться в оплот</span>
            <span class="cta-arr" aria-hidden="true">→</span>
          </button>
        </div>

      </div><!-- /inner -->

      <!-- Ember drips -->
      <div class="drip-row" aria-hidden="true">
        <span
          v-for="d in drips" :key="d.left"
          class="drip"
          :style="{ left: d.left, animationDelay: d.delay, animationDuration: d.dur }"
        />
      </div>

    </div><!-- /card -->

  </div>
</template>

<style scoped>
/* ══════════════════════════════════════════════════════════
   ROOT
   ══════════════════════════════════════════════════════════ */
.r404 {
  position: relative;
  min-height: calc(100vh - var(--header-h, 72px) - 60px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 20px 60px;
  overflow: hidden;
  text-align: center;
}

/* ══════════════════════════════════════════════════════════
   BACKGROUND
   ══════════════════════════════════════════════════════════ */
.bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}

/* Fog */
.fog {
  position: absolute;
  inset: -20px;
  transition: transform 0.12s ease-out;
}
.fog-b {
  position: absolute;
  left: -10%; right: -10%;
  border-radius: 50%;
  filter: blur(70px);
}
.fog-1 {
  bottom: -8%; height: 38%;
  background: radial-gradient(ellipse at 50% 100%,
    rgba(194,40,26,0.20) 0%, rgba(100,30,10,0.08) 55%, transparent 80%);
  animation: fogD 18s ease-in-out infinite;
}
.fog-2 {
  bottom: 0; height: 55%;
  background: radial-gradient(ellipse at 35% 100%,
    rgba(255,122,43,0.10) 0%, transparent 65%);
  animation: fogD 25s ease-in-out infinite reverse;
}
.fog-3 {
  top: -5%; height: 35%;
  background: radial-gradient(ellipse at 65% 0%,
    rgba(30,18,10,0.35) 0%, transparent 70%);
  animation: fogD 32s ease-in-out infinite 4s;
}
@keyframes fogD {
  0%,100% { transform: translateX(0); }
  33%     { transform: translateX(-28px); }
  66%     { transform: translateX(22px); }
}

/* Ruins */
.ruins-wrap {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  transition: transform 0.15s ease-out;
}
.ruins-svg { width: 100%; height: auto; display: block; }

/* Grid */
.bg-grid {
  position: absolute;
  inset: -20px;
  background-image:
    linear-gradient(rgba(122,93,72,0.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(122,93,72,0.05) 1px, transparent 1px);
  background-size: 56px 56px;
  mask-image: radial-gradient(ellipse at center, black 8%, transparent 72%);
  -webkit-mask-image: radial-gradient(ellipse at center, black 8%, transparent 72%);
  transition: transform 0.08s ease-out;
}

/* Glow orbs */
.glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  pointer-events: none;
}
.glow-1 {
  width: 700px; height: 700px;
  background: radial-gradient(circle, rgba(194,40,26,0.22) 0%, transparent 65%);
  bottom: -240px; left: 50%; transform: translateX(-50%);
  animation: glowP 12s ease-in-out infinite;
}
.glow-2 {
  width: 380px; height: 380px;
  background: radial-gradient(circle, rgba(255,122,43,0.14) 0%, transparent 65%);
  top: -70px; right: 4%;
  animation: glowPS 17s ease-in-out infinite 2s;
}
.glow-3 {
  width: 280px; height: 280px;
  background: radial-gradient(circle, rgba(199,154,94,0.11) 0%, transparent 65%);
  top: 12%; left: 3%;
  animation: glowPS 22s ease-in-out infinite 6s;
}
@keyframes glowP  {
  0%,100% { transform: translateX(-50%) scale(1);   opacity:1; }
  50%     { transform: translateX(-50%) scale(1.22); opacity:0.7; }
}
@keyframes glowPS {
  0%,100% { transform: scale(1);   opacity:1; }
  50%     { transform: scale(1.2); opacity:0.7; }
}

.ground-glow {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 130px;
  background: linear-gradient(to top,
    rgba(194,40,26,0.16) 0%,
    rgba(100,30,10,0.06) 60%,
    transparent 100%);
}

/* Particles */
.ptcls { position: absolute; inset: 0; overflow: hidden; }
.ptcl {
  position: absolute;
  top: -10px;
  border-radius: 50%;
  animation: ptclF linear infinite;
}
.ptcl--ember {
  background: radial-gradient(circle, #ffc979 0%, #ff7a2b 50%, #e24310 80%, transparent 100%);
  box-shadow: 0 0 6px 2px rgba(255,122,43,0.55), 0 0 12px 3px rgba(226,67,16,0.28);
}
.ptcl--cinder {
  background: radial-gradient(circle, #e24310 0%, #8a1f18 60%, transparent 100%);
  box-shadow: 0 0 4px 1px rgba(226,67,16,0.38);
}
.ptcl--ash {
  background: radial-gradient(circle, #5a4535 0%, #2a1f18 70%, transparent 100%);
  opacity: 0.35 !important;
  filter: blur(0.5px);
}
@keyframes ptclF {
  0%   { transform: translateY(0) translateX(0) rotate(0); opacity: 0; }
  8%   { opacity: 1; }
  85%  { opacity: 0.8; }
  100% { transform: translateY(112vh) translateX(var(--drift,0px)) rotate(130deg); opacity: 0; }
}

/* ══════════════════════════════════════════════════════════
   CARD
   ══════════════════════════════════════════════════════════ */
.card {
  position: relative;
  z-index: 2;
  max-width: 820px;
  width: 100%;
  padding: 68px 56px 56px;
  background: linear-gradient(180deg,
    var(--ash-ironrust) 0%,
    var(--ash-stone)    38%,
    var(--ash-coal)     100%);
  border: 1px solid var(--bronze-dark);
  clip-path: var(--clip-forged-md);
  box-shadow:
    inset 0 0 0 1px var(--iron-mid),
    inset 0 0 0 3px var(--iron-void),
    inset 0 2px 0 rgba(255,201,121,0.07),
    0 32px 80px rgba(0,0,0,0.72),
    0 0 60px rgba(194,40,26,0.12);
  animation: cardRise 0.8s cubic-bezier(0.22,1.2,0.36,1) 0.1s both;
}
/* Subtle scanline texture */
.card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    0deg, transparent, transparent 2px,
    rgba(0,0,0,0.025) 2px, rgba(0,0,0,0.025) 4px
  );
  pointer-events: none;
  z-index: 0;
  border-radius: inherit;
}
@keyframes cardRise {
  from { opacity:0; transform: translateY(40px) scale(0.97); }
  to   { opacity:1; transform: none; }
}

/* Top brand bar */
.top-bar {
  position: absolute;
  top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg,
    transparent 0%, var(--bronze-dark) 15%,
    var(--ember-gold) 50%,
    var(--bronze-dark) 85%, transparent 100%);
  box-shadow: 0 0 12px rgba(199,154,94,0.45);
}

/* Rivets */
.rivet {
  position: absolute; z-index: 5;
  width: 13px; height: 13px;
  border-radius: 50%;
  background: radial-gradient(circle at 32% 32%,
    var(--brass) 0%, var(--bronze) 45%, var(--iron-void) 100%);
  box-shadow:
    inset -1px -1px 2px rgba(0,0,0,0.75),
    inset 1px 1px 1px rgba(255,201,121,0.4),
    0 0 8px rgba(199,154,94,0.45);
}
.rv-tl { top:16px;    left:16px; }
.rv-tr { top:16px;    right:16px; }
.rv-bl { bottom:16px; left:16px; }
.rv-br { bottom:16px; right:16px; }

/* Chains */
.chain {
  position: absolute;
  top: -46px;
  display: flex; flex-direction: column;
  align-items: center; gap: 2px;
  animation: chainS 5s ease-in-out infinite;
  transform-origin: top center;
}
.chain-l { left: 54px; }
.chain-r { right: 54px; animation-delay: -2.5s; }
@keyframes chainS {
  0%,100% { transform: rotate(-3deg); }
  50%     { transform: rotate(3deg); }
}
.clink {
  display: block;
  width: 14px; height: 9px;
  border: 1.8px solid var(--bronze-dark);
  border-radius: 4px;
  background: linear-gradient(135deg, var(--ash-stone), var(--ash-coal));
  box-shadow: inset -1px -1px 2px rgba(0,0,0,0.6), 0 0 2px rgba(199,154,94,0.14);
}
.clink:nth-child(2n) { width: 9px; height: 14px; }

/* Spikes */
.spikes {
  position: absolute;
  top: -13px; left: 50%;
  transform: translateX(-50%);
  display: flex; gap: 14px; align-items: flex-end; z-index: 3;
}
.sp {
  width: 0; height: 0;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-bottom: 11px solid var(--iron-warm);
  filter: drop-shadow(0 -2px 3px rgba(199,154,94,0.28));
}
.sp.tall {
  border-left-width: 7px; border-right-width: 7px;
  border-bottom-width: 20px;
  border-bottom-color: var(--bronze);
  filter: drop-shadow(0 -3px 6px rgba(199,154,94,0.65));
}

/* ══════════════════════════════════════════════════════════
   CARD INNER
   ══════════════════════════════════════════════════════════ */
.inner { position: relative; z-index: 2; }

/* Eyebrow */
.eyebrow {
  display: inline-flex; align-items: center; gap: 12px;
  font-family: var(--font-ui);
  font-size: 0.7rem; font-weight: 700;
  letter-spacing: 0.24em; text-transform: uppercase;
  color: var(--bronze);
  margin-bottom: 28px;
  animation: revUp 0.5s 0.55s both;
}
.zig { color: var(--bronze); opacity: 0.65; }
.zig.flip { transform: scaleX(-1); }

/* Hero 404 */
.hero {
  display: flex; align-items: center; justify-content: center;
  gap: 8px;
  font-family: var(--font-display); font-weight: 900;
  font-size: clamp(6rem, 16vw, 11rem);
  line-height: 1; margin: 0 0 16px;
  letter-spacing: -0.03em;
}
.hd {
  background: linear-gradient(180deg,
    var(--text-bright) 0%,
    var(--text-bone)   35%,
    var(--bronze)      80%,
    rgba(100,60,20,0.8) 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 0 10px rgba(255,201,121,0.22)) drop-shadow(0 6px 0 rgba(0,0,0,0.55));
}
.hd-1 { animation: digitD 0.7s cubic-bezier(0.3,1.5,0.4,1) 0.75s both, digitF 6s ease-in-out 1.6s infinite; }
.hd-2 { animation: digitD 0.7s cubic-bezier(0.3,1.5,0.4,1) 1.05s both, digitF 6s ease-in-out 2.1s infinite; }
@keyframes digitD {
  from { opacity:0; transform: translateY(-28px) scale(0.85); }
  to   { opacity:1; transform: none; }
}
@keyframes digitF {
  0%,100% { transform: translateY(0); }
  50%     { transform: translateY(-10px); }
}

/* Rune "0" */
.rune-0 {
  display: inline-flex; align-items: center; justify-content: center;
  width: clamp(88px, 13vw, 150px); height: clamp(88px, 13vw, 150px);
  flex-shrink: 0;
  animation: runeA 0.9s cubic-bezier(0.2,1.3,0.4,1) 0.9s both, runeF 7s ease-in-out 1.9s infinite;
}
@keyframes runeA {
  from { opacity:0; transform: scale(0.2) rotate(-120deg); }
  70%  { transform: scale(1.08) rotate(8deg); }
  to   { opacity:1; transform: scale(1) rotate(0); }
}
@keyframes runeF {
  0%,100% { transform: translateY(0) rotate(0); }
  50%     { transform: translateY(-9px) rotate(1.5deg); }
}
.rune-svg { overflow: visible; }

/* Halo */
.rune-halo {
  fill: none; stroke: rgba(226,67,16,0.12); stroke-width: 8;
  filter: blur(5px);
  animation: haloP 3s ease-in-out infinite;
}
@keyframes haloP {
  0%,100% { stroke-width:8;  opacity:0.3; }
  50%     { stroke-width:16; opacity:0.7; }
}
/* Outer ring */
.rune-outer {
  fill: none; stroke: var(--bronze); stroke-width: 1.5; opacity: 0.72;
  filter: drop-shadow(0 0 3px rgba(199,154,94,0.5));
}
.rot-cw  { animation: rotCW  36s linear infinite; transform-box: view-box; transform-origin: 0 0; }
.rot-ccw { animation: rotCCW 22s linear infinite; transform-box: view-box; transform-origin: 0 0; }
@keyframes rotCW  { to { transform: rotate( 360deg); } }
@keyframes rotCCW { to { transform: rotate(-360deg); } }
/* Arcs */
.rune-arc {
  fill: none; stroke-width: 1; opacity: 0.42;
}
/* Inner ring */
.rune-inner {
  fill: none; stroke: var(--ember-deep); stroke-width: 1.5; opacity: 0.58;
  filter: drop-shadow(0 0 4px rgba(226,67,16,0.4));
}
/* Cracks */
.crack {
  fill: none;
  stroke: var(--ember-gold); stroke-width: 1.2;
  stroke-linecap: round; stroke-linejoin: round;
  stroke-dasharray: 1; stroke-dashoffset: 1;
  filter: drop-shadow(0 0 3px rgba(255,201,121,0.7));
  opacity: 0;
  animation: crackD 0.35s ease-out forwards;
}
.ck-1 { animation-delay: 1.0s; }
.ck-2 { animation-delay: 1.15s; }
.ck-3 { animation-delay: 1.3s; }
.ck-4 { animation-delay: 1.45s; }
.ck-5 { animation-delay: 1.6s; }
.ck-6 { animation-delay: 1.75s; }
@keyframes crackD {
  from { opacity:1; stroke-dashoffset:1; }
  to   { opacity:1; stroke-dashoffset:0; }
}
/* Ember sparks */
.espark {
  fill: var(--ember-gold);
  filter: drop-shadow(0 0 5px rgba(255,201,121,0.9));
  opacity: 0;
  animation: sparkP 1.8s ease-in-out infinite;
}
.es-1 { animation-delay:1.1s; } .es-2 { animation-delay:1.3s; }
.es-3 { animation-delay:1.5s; } .es-4 { animation-delay:1.7s; }
.es-5 { animation-delay:1.9s; } .es-6 { animation-delay:2.1s; }
@keyframes sparkP {
  0%  { opacity:0; transform:scale(0); }
  20% { opacity:1; transform:scale(1.2); }
  60% { opacity:0.8; transform:scale(1); }
  100%{ opacity:0.7; transform:scale(1.1); }
}
/* Void */
.void-ring { fill: none; stroke: var(--iron-dark); stroke-width: 2; }
.void-fill { fill: var(--ash-coal); filter: drop-shadow(0 0 8px rgba(0,0,0,0.85)); }
.void-core {
  fill: var(--ember-heart);
  filter: drop-shadow(0 0 8px rgba(194,40,26,0.9));
  animation: coreP 2s ease-in-out infinite;
}
@keyframes coreP {
  0%,100% { transform:scale(1);   opacity:0.9; }
  50%     { transform:scale(1.5); opacity:1;
            filter: drop-shadow(0 0 14px rgba(226,67,16,1)); }
}

/* Caption */
.caption {
  font-family: var(--font-ui);
  font-size: 0.67rem; letter-spacing: 0.22em;
  text-transform: uppercase; color: var(--text-muted);
  margin: 0 0 24px;
  animation: revUp 0.4s 1.15s both;
}

/* Divider */
.divider {
  display: flex; align-items: center; gap: 14px;
  margin: 0 auto 28px; max-width: 340px;
  animation: revUp 0.4s 1.25s both;
}
.dline {
  flex: 1; height: 1px;
  background: linear-gradient(90deg,
    transparent, var(--bronze-dark) 40%, var(--bronze-dark) 60%, transparent);
}
.dflame { display: flex; animation: flameD 2.4s ease-in-out infinite; }
@keyframes flameD {
  0%,100% { transform: scaleX(1)   scaleY(1);   }
  25%     { transform: scaleX(0.9) scaleY(1.1); }
  75%     { transform: scaleX(1.05) scaleY(0.95); }
}
.fl-outer { fill: var(--ember-deep); filter: drop-shadow(0 0 6px rgba(226,67,16,0.6)); }
.fl-inner { fill: var(--ember-flame); filter: drop-shadow(0 0 4px rgba(255,122,43,0.7)); }
.fl-core  { fill: var(--ember-gold); filter: drop-shadow(0 0 6px rgba(255,201,121,0.9)); }

/* Messages */
.msgs { margin-bottom: 28px; }
.msg {
  font-family: var(--font-body);
  font-size: clamp(0.95rem, 1.4vw, 1.08rem);
  color: var(--text-parchment);
  line-height: 1.82; margin: 0 0 5px;
}
.muted {
  color: var(--text-muted);
  font-size: 0.9rem; font-style: italic;
}
.ml-1 { animation: revUp 0.5s 1.35s both; }
.ml-2 { animation: revUp 0.5s 1.50s both; }
.ml-3 { animation: revUp 0.5s 1.65s both; }

/* Quick nav */
.qnav {
  display: flex; align-items: center; justify-content: center;
  gap: 8px; flex-wrap: wrap;
  margin-bottom: 32px;
  animation: revUp 0.5s 1.8s both;
}
.qlink {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 14px;
  background: none;
  border: 1px solid var(--iron-dark);
  border-radius: var(--r-xs);
  color: var(--text-ash);
  font-family: var(--font-ui); font-size: 0.8rem; font-weight: 600;
  letter-spacing: 0.06em; text-transform: uppercase;
  cursor: pointer;
  transition: all 0.2s var(--ease-smoke);
}
.qlink:hover {
  color: var(--text-bright);
  border-color: var(--bronze-dark);
  background: rgba(199,154,94,0.08);
  box-shadow: 0 0 14px rgba(199,154,94,0.14), inset 0 0 8px rgba(199,154,94,0.05);
  transform: translateY(-2px);
}
.qdot { display: block; width: 3px; height: 3px; border-radius: 50%; background: var(--bronze-dark); opacity: 0.45; }

/* CTA */
.cta-row {
  display: flex; justify-content: center;
  animation: revUp 0.5s 1.95s both;
}
.cta {
  position: relative;
  display: inline-flex; align-items: center; gap: 10px;
  padding: 18px 46px;
  border: 1px solid var(--ember-heart);
  background: var(--grad-ember);
  color: var(--text-bright);
  font-family: var(--font-display); font-weight: 700;
  font-size: 1.05rem; letter-spacing: 0.1em;
  text-transform: uppercase;
  cursor: pointer; overflow: hidden;
  clip-path: var(--clip-forged-sm);
  box-shadow:
    inset 0 1px 0 rgba(255,201,121,0.18),
    inset 0 -1px 0 rgba(0,0,0,0.4),
    0 8px 24px rgba(194,40,26,0.38),
    0 0 22px rgba(226,67,16,0.2);
  transition: transform 0.2s var(--ease-forge), box-shadow 0.22s var(--ease-smoke);
  animation: ctaGlow 3s ease-in-out 2.5s infinite;
}
@keyframes ctaGlow {
  0%,100% { box-shadow: inset 0 1px 0 rgba(255,201,121,0.18), inset 0 -1px 0 rgba(0,0,0,0.4), 0 8px 24px rgba(194,40,26,0.38), 0 0 22px rgba(226,67,16,0.2); }
  50%     { box-shadow: inset 0 1px 0 rgba(255,201,121,0.18), inset 0 -1px 0 rgba(0,0,0,0.4), 0 10px 36px rgba(194,40,26,0.6), 0 0 45px rgba(226,67,16,0.45); }
}
.cta-shine {
  position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255,201,121,0.35), transparent);
  transform: translateX(-120%);
  transition: transform 0.7s ease;
  pointer-events: none;
}
.cta:hover .cta-shine { transform: translateX(120%); }
.cta:hover {
  transform: translateY(-3px);
  box-shadow:
    inset 0 1px 0 rgba(255,201,121,0.28),
    inset 0 -1px 0 rgba(0,0,0,0.4),
    0 14px 40px rgba(194,40,26,0.65),
    0 0 55px rgba(226,67,16,0.5);
}
.cta:active { transform: translateY(-1px); }
.cta-icon { font-size: 1.1rem; filter: drop-shadow(0 0 4px rgba(255,201,121,0.5)); position: relative; z-index: 1; }
.cta-text { position: relative; z-index: 1; }
.cta-arr  { font-size: 1.1rem; transition: transform 0.25s ease; position: relative; z-index: 1; }
.cta:hover .cta-arr { transform: translateX(5px); }

/* Ember drips */
.drip-row { position: absolute; bottom: 0; left: 0; right: 0; height: 0; overflow: visible; }
.drip {
  position: absolute; bottom: 0;
  width: 4px;
  background: linear-gradient(to bottom, var(--ember-heart), var(--ember-deep), transparent);
  border-radius: 0 0 50% 50%;
  filter: drop-shadow(0 2px 4px rgba(226,67,16,0.55));
  animation: dripD ease-in infinite;
  transform-origin: top center;
}
@keyframes dripD {
  0%   { height:0;  opacity:0; }
  20%  { height:12px; opacity:1; }
  70%  { height:22px; opacity:0.8; }
  100% { height:32px; transform: translateY(20px); opacity:0; }
}

/* Shared reveal */
@keyframes revUp {
  from { opacity:0; transform: translateY(16px); }
  to   { opacity:1; transform: none; }
}

/* ══════════════════════════════════════════════════════════
   RESPONSIVE
   ══════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
  .card { padding: 52px 32px 44px; }
  .chain { display: none; }
}
@media (max-width: 480px) {
  .card { padding: 44px 20px 36px; }
  .qnav { gap: 6px; }
  .qlink { padding: 5px 10px; font-size: 0.74rem; }
  .cta { padding: 14px 26px; font-size: 0.95rem; }
  .msg { font-size: 0.9rem; }
}
@media (max-width: 380px) {
  .card { padding: 36px 14px 28px; }
  .qnav { display: none; }
}

/* ══════════════════════════════════════════════════════════
   REDUCED MOTION
   ══════════════════════════════════════════════════════════ */
@media (prefers-reduced-motion: reduce) {
  .card, .eyebrow, .hd-1, .hd-2, .rune-0,
  .caption, .divider, .ml-1, .ml-2, .ml-3,
  .qnav, .cta-row { animation: none; opacity: 1; transform: none; }
  .ptcl  { display: none; }
  .rot-cw, .rot-ccw { animation: none; }
  .crack { opacity: 1; stroke-dashoffset: 0; }
  .espark { opacity: 0.8; }
  .void-core { animation: none; }
  .fog-b, .glow, .cta, .dflame, .chain { animation: none; }
  .drip { display: none; }
}
</style>
