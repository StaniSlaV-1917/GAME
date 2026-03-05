<template>
  <div class="admin-dashboard">
    <h1>Панель администратора</h1>
    <p class="dashboard-subtitle">Выберите раздел для управления</p>

    <div class="dashboard-grid">
      <RouterLink to="/admin/games" class="dashboard-card">
        <div class="card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.15 19.38c.05-.28.12-.56.2-.84l1.35-4.5a1.39 1.39 0 0 1 1.3-1.04h4.5c.57 0 1.1.33 1.35 1.04l1.35 4.5c.08.28.15.56.2.84"/><path d="M12.56 19.38c.05-.28.12-.56.2-.84l1.35-4.5a1.39 1.39 0 0 1 1.3-1.04h4.5c.57 0 1.1.33 1.35 1.04l1.35 4.5c.08.28.15.56.2.84"/><path d="M8.25 13L10 6.5l1.75 6.5"/><path d="m18.25 13 1.75-6.5L21.75 13"/><path d="M4 22h16"/><path d="M2 13h20"/></svg>
        </div>
        <h2 class="card-title">Управление играми</h2>
        <p class="card-description">Добавляйте, редактируйте и удаляйте игры, управляйте ценами и жанрами.</p>
      </RouterLink>

      <RouterLink to="/admin/news" class="dashboard-card">
        <div class="card-icon">
           <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2V4a2 2 0 0 0-2-2"/><path d="M8 6h8"/><path d="M8 10h8"/><path d="M8 14h4"/></svg>
        </div>
        <h2 class="card-title">Управление новостями</h2>
        <p class="card-description">Создавайте, редактируйте и удаляйте новостные статьи.</p>
      </RouterLink>

      <RouterLink to="/admin/users" class="dashboard-card">
        <div class="card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <h2 class="card-title">Пользователи</h2>
        <p class="card-description">Просматривайте список пользователей, назначайте роли и управляйте их данными.</p>
      </RouterLink>

      <RouterLink to="/admin/orders" class="dashboard-card">
        <div class="card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <h2 class="card-title">Заказы</h2>
        <p class="card-description">Отслеживайте историю покупок, просматривайте детали заказов и статистику.</p>
      </RouterLink>

      <RouterLink to="/admin/reviews" class="dashboard-card">
        <div class="card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        </div>
        <h2 class="card-title">Отзывы</h2>
        <p class="card-description">Модерируйте отзывы пользователей, отвечайте на них или удаляйте.</p>
      </RouterLink>
      
      <RouterLink to="/admin/employees" class="dashboard-card">
        <div class="card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </div>
        <h2 class="card-title">Сотрудники</h2>
        <p class="card-description">Управляйте учетными записями сотрудников и их правами доступа.</p>
      </RouterLink>
    </div>
  </div>
</template>

<style scoped>
.admin-dashboard {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 0 1.5rem;
}

h1 {
  font-size: 2.5rem;
  font-weight: 800;
  color: #f9fafb;
  margin-bottom: 0.5rem;
}

.dashboard-subtitle {
  font-size: 1.1rem;
  color: #9ca3af;
  margin-bottom: 2.5rem;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.dashboard-card {
  display: block;
  text-decoration: none;
  background: #111827;
  border-radius: 12px;
  padding: 1.75rem;
  border: 1px solid #1f2937;
  transition: all 0.2s ease-in-out;
}

.dashboard-card:hover {
  transform: translateY(-5px);
  border-color: #3b82f6;
  box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.1);
}

.card-icon {
  margin-bottom: 1rem;
  color: #3b82f6;
  background-color: rgba(59, 130, 246, 0.1);
  width: 56px;
  height: 56px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #e5e7eb;
  margin-bottom: 0.5rem;
}

.card-description {
  font-size: 0.95rem;
  color: #9ca3af;
  line-height: 1.6;
}
</style>
