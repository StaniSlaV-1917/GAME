
<template>
  <div class="about-us-container">
    <div class="hero-section">
      <h1>О GameStore</h1>
      <p>Ваш надежный партнер в мире цифровых игр</p>
    </div>

    <div class="content-section">
      <div class="mission-statement">
        <h2>Наша миссия</h2>
        <p>Мы стремимся предоставить геймерам самый удобный, быстрый и безопасный способ покупки цифровых ключей. Мы верим, что доступ к любимым играм должен быть простым и мгновенным.</p>
      </div>

      <div class="values-grid">
        <div class="value-card">
          <i class="fa-solid fa-shield-halved"></i>
          <h3>Безопасность</h3>
          <p>Все транзакции защищены, а ключи поставляются только от проверенных дистрибьюторов.</p>
        </div>
        <div class="value-card">
          <i class="fa-solid fa-bolt-lightning"></i>
          <h3>Скорость</h3>
          <p>Получайте купленные ключи мгновенно после оплаты, без задержек и ожидания.</p>
        </div>
        <div class="value-card">
          <i class="fa-solid fa-life-ring"></i>
          <h3>Поддержка</h3>
          <p>Наша служба поддержки всегда готова помочь с любыми вопросами 24/7.</p>
        </div>
      </div>

      <div class="team-section">
        <h2>Наша команда</h2>
        <p>Мы — команда энтузиастов и геймеров, которые создали этот магазин для таких же, как мы. Мы знаем, что важно для игроков, и постоянно работаем над улучшением нашего сервиса.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.about-us-container {
  max-width: 1100px;
  margin: 32px auto;
  padding: 0 24px;
  color: #e5e7eb;
}

.hero-section {
  text-align: center;
  padding: 48px 24px;
  background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=2070') center/cover;
  border-radius: 16px;
  margin-bottom: 48px;
}

.hero-section h1 {
  font-size: 3rem;
  font-weight: 800;
  color: #fff;
  margin: 0;
}

.hero-section p {
  font-size: 1.25rem;
  color: #d1d5db;
  margin-top: 8px;
}

.content-section h2 {
  font-size: 2.25rem;
  font-weight: 700;
  color: #fff;
  margin-bottom: 24px;
  border-bottom: 2px solid #3b82f6;
  padding-bottom: 8px;
  display: inline-block;
}

.mission-statement, .team-section {
  background-color: #111827;
  padding: 32px;
  border-radius: 12px;
  margin-bottom: 48px;
  text-align: center;
}

.mission-statement p, .team-section p {
  font-size: 1.1rem;
  line-height: 1.8;
  max-width: 800px;
  margin: 0 auto;
}

.values-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 48px;
}

.value-card {
  background-color: #111827;
  padding: 32px;
  border-radius: 12px;
  text-align: center;
  border: 1px solid #1f2937;
  transition: transform 0.2s, box-shadow 0.2s;
}

.value-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.value-card i {
  font-size: 2.5rem;
  color: #3b82f6;
  margin-bottom: 16px;
}

.value-card h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 8px;
}

.value-card p {
  color: #9ca3af;
  line-height: 1.6;
}
</style>
