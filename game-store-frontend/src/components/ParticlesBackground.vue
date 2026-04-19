<template>
  <canvas ref="canvasRef" class="particles-canvas"></canvas>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const canvasRef = ref(null);
let animId = null;
let particles = [];
const COUNT = 55;

onMounted(() => {
  const canvas = canvasRef.value;
  const ctx = canvas.getContext('2d');

  const resize = () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  };
  resize();
  window.addEventListener('resize', resize, { passive: true });

  // Gaming-themed colours: blue, indigo, violet
  const palette = ['59,130,246', '99,102,241', '139,92,246', '59,130,246'];

  particles = Array.from({ length: COUNT }, () => ({
    x: Math.random() * window.innerWidth,
    y: Math.random() * window.innerHeight,
    vx: (Math.random() - 0.5) * 0.35,
    vy: (Math.random() - 0.5) * 0.35,
    r: Math.random() * 1.6 + 0.4,
    opacity: Math.random() * 0.45 + 0.08,
    color: palette[Math.floor(Math.random() * palette.length)],
    pulse: Math.random() * Math.PI * 2,
    pulseSpeed: 0.012 + Math.random() * 0.012,
  }));

  const draw = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    particles.forEach(p => {
      p.x += p.vx;
      p.y += p.vy;
      p.pulse += p.pulseSpeed;
      const alpha = p.opacity * (0.7 + 0.3 * Math.sin(p.pulse));

      // Wrap edges
      if (p.x < -5) p.x = canvas.width + 5;
      if (p.x > canvas.width + 5) p.x = -5;
      if (p.y < -5) p.y = canvas.height + 5;
      if (p.y > canvas.height + 5) p.y = -5;

      // Dot with soft glow
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${p.color}, ${alpha})`;
      ctx.fill();
    });

    // Connections
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const p1 = particles[i], p2 = particles[j];
        const dist = Math.hypot(p1.x - p2.x, p1.y - p2.y);
        if (dist < 130) {
          ctx.beginPath();
          ctx.moveTo(p1.x, p1.y);
          ctx.lineTo(p2.x, p2.y);
          ctx.strokeStyle = `rgba(99,102,241,${(1 - dist / 130) * 0.12})`;
          ctx.lineWidth = 0.6;
          ctx.stroke();
        }
      }
    }

    animId = requestAnimationFrame(draw);
  };

  draw();

  onUnmounted(() => {
    cancelAnimationFrame(animId);
    window.removeEventListener('resize', resize);
  });
});
</script>

<style scoped>
.particles-canvas {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  pointer-events: none;
  z-index: 0;
  opacity: 0.75;
}
</style>
