<template>
  <div class="about-page">
    <!-- Hero Section -->
    <section class="hero is-medium has-background">
      <div class="hero-body has-text-centered">
        <h1 class="title is-1 has-text-white">О GameStore</h1>
        <p class="subtitle is-4 has-text-white">Ваш надежный поставщик цифровых ключей</p>
      </div>
    </section>

    <!-- Our Story Section -->
    <section class="section">
      <div class="container">
        <div class="columns is-vcentered">
          <div class="column is-6">
            <h2 class="title is-2">Наша история</h2>
            <div class="content is-medium">
              <p>GameStore был основан в 2019 году группой энтузиастов, которые хотели сделать покупку игр проще, быстрее и доступнее. Мы начали с небольшой коллекции инди-игр и выросли в одного из ведущих дистрибьюторов цифровых ключей в регионе.</p>
              <p>Сегодня мы гордимся тем, что предлагаем тысячи игр от ведущих издателей и независимых разработчиков.</p>
            </div>
          </div>
          <div class="column is-6 has-text-centered">
            <span class="icon is-large has-text-primary">
              <i class="fas fa-gamepad fa-5x"></i>
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- Achievements Section -->
    <section class="section has-background-light">
        <div class="container">
            <h2 class="title is-2 has-text-centered">Наши достижения</h2>
            <div class="columns is-multiline is-centered mt-5">
                <div class="column is-one-third">
                    <div class="box has-text-centered achievement-box">
                        <span class="icon is-large has-text-primary"><i class="fas fa-key fa-3x"></i></span>
                        <p class="title is-3 mt-4">10,000+</p>
                        <p class="subtitle is-5">Проданных ключей</p>
                    </div>
                </div>
                <div class="column is-one-third">
                    <div class="box has-text-centered achievement-box">
                        <span class="icon is-large has-text-success"><i class="fas fa-check-circle fa-3x"></i></span>
                        <p class="title is-3 mt-4">100%</p>
                        <p class="subtitle is-5">Гарантия активации</p>
                    </div>
                </div>
                <div class="column is-one-third">
                    <div class="box has-text-centered achievement-box">
                        <span class="icon is-large has-text-info"><i class="fas fa-calendar-alt fa-3x"></i></span>
                        <p class="title is-3 mt-4">5+</p>
                        <p class="subtitle is-5">Лет на рынке</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Mission and Team Section -->
    <section class="section">
        <div class="container">
            <div class="columns">
                <div class="column">
                    <div class="card mission-card">
                        <div class="card-content">
                            <h2 class="title is-3 has-text-centered">Наша миссия</h2>
                            <div class="content">
                                Мы стремимся предоставить геймерам самый широкий выбор игр по лучшим ценам с мгновенной доставкой и безупречной поддержкой. Наша цель — чтобы каждая покупка в GameStore приносила вам только положительные эмоции.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="column">
                    <div class="card team-card">
                        <div class="card-content">
                             <h2 class="title is-3 has-text-centered">Наша команда</h2>
                            <div class="content">
                                За GameStore стоит команда увлеченных геймеров, разработчиков и специалистов по поддержке клиентов. Мы знаем, что важно для вас, потому что мы сами — часть этого сообщества.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="section has-background-light">
        <div class="container">
            <h2 class="title is-2 has-text-centered">Почему выбирают нас?</h2>
            <div class="columns is-centered mt-5">
                <div class="column is-one-quarter">
                    <div class="box has-text-centered feature-box">
                         <span class="icon is-large has-text-danger"><i class="fas fa-shipping-fast fa-3x"></i></span>
                        <h3 class="title is-4 mt-4">Мгновенная доставка</h3>
                        <p>Получайте ключи сразу после оплаты на вашу электронную почту.</p>
                    </div>
                </div>
                <div class="column is-one-quarter">
                    <div class="box has-text-centered feature-box">
                        <span class="icon is-large has-text-warning"><i class="fas fa-shield-alt fa-3x"></i></span>
                        <h3 class="title is-4 mt-4">Безопасные платежи</h3>
                        <p>Все транзакции защищены современными протоколами шифрования.</p>
                    </div>
                </div>
                <div class="column is-one-quarter">
                    <div class="box has-text-centered feature-box">
                         <span class="icon is-large has-text-info"><i class="fas fa-headset fa-3x"></i></span>
                        <h3 class="title is-4 mt-4">Поддержка 24/7</h3>
                        <p>Наша команда поддержки всегда готова помочь вам с любым вопросом.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
  </div>
</template>

<script setup>
import { useHead } from '@vueuse/head';

useHead({
  title: 'О магазине GameStore - Наша история и миссия',
  meta: [
    {
      name: 'description',
      content: 'Узнайте больше о GameStore: нашей истории, миссии и команде. Мы - ваш надежный партнер в мире цифровых игровых ключей с гарантией качества и поддержкой 24/7.'
    },
    // Add Font Awesome for icons
    {
      rel: 'stylesheet',
      href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'
    }
  ]
});
</script>

<style scoped>
/* Hero Section Styles */
.hero.has-background {
  background: #1c1c1c url('https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=2070&auto=format&fit=crop') no-repeat center center;
  background-size: cover;
  position: relative;
}

.hero.has-background::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
}

.hero-body {
    position: relative;
    z-index: 1;
}

.title, .subtitle {
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
}

/* General Styles */
.section {
    padding-top: 4rem;
    padding-bottom: 4rem;
}

/* Achievement & Feature Box Styles */
.achievement-box, .feature-box, .card {
  height: 100%;
  border-radius: 8px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.achievement-box:hover, .feature-box:hover, .card:hover {
  transform: translateY(-10px);
  box-shadow: 0 12px 25px rgba(0,0,0,0.1);
}

.box .icon {
    transition: transform 0.3s ease;
}

.box:hover .icon {
    transform: scale(1.1);
}

.content h2 {
    margin-bottom: 1.5rem;
}

.card {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.mission-card {
    border-left: 5px solid #ff3860; /* Bulma's primary color */
}

.team-card {
    border-left: 5px solid #48c78e; /* Bulma's success color */
}

</style>
