<template>
  <div class="about-page">
    <div class="hero is-primary">
      <div class="hero-body">
        <div class="container">
          <h1 class="title is-1">О GameStore</h1>
          <p class="subtitle">Ваш надежный поставщик цифровых ключей</p>
        </div>
      </div>
    </div>
    <section class="section">
      <div class="container">
        <div class="columns is-multiline">
          <div class="column is-one-third">
            <div class="box has-text-centered">
              <p class="title is-1 has-text-primary">10,000+</p>
              <p class="subtitle is-5">Проданных ключей</p>
            </div>
          </div>
          <div class="column is-one-third">
            <div class="box has-text-centered">
              <p class="title is-1 has-text-primary">100%</p>
              <p class="subtitle is-5">Гарантия активации</p>
            </div>
          </div>
          <div class="column is-one-third">
            <div class="box has-text-centered">
              <p class="title is-1 has-text-primary">5+</p>
              <p class="subtitle is-5">Лет на рынке</p>
            </div>
          </div>
        </div>
        <div class="content">
          <h2 class="title is-2">Наша история</h2>
          <p>GameStore был основан в 2019 году группой энтузиастов, которые хотели сделать покупку игр проще, быстрее и доступнее для всех. Мы начали с небольшой коллекции инди-игр и выросли в одного из ведущих дистрибьюторов цифровых ключей в регионе.</p>

          <h2 class="title is-2">Наша миссия</h2>
          <p>Мы стремимся предоставить геймерам самый широкий выбор игр по лучшим ценам с мгновенной доставкой и безупречной поддержкой. Наша цель — чтобы каждая покупка в GameStore приносила вам только положительные эмоции.</p>

          <h2 class="title is-2">Наша команда</h2>
          <p>За GameStore стоит команда увлеченных геймеров, разработчиков и специалистов по поддержке клиентов. Мы знаем, что важно для вас, потому что мы сами — часть этого сообщества. Мы работаем каждый день, чтобы сделать ваш игровой опыт лучше.</p>
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
    }
  ]
});
</script>

<style scoped>
.about-page .box {
  height: 100%;
}
</style>
