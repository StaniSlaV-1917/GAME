
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../api/axios';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('user')));
  const token = ref(localStorage.getItem('token'));

  const isLoggedIn = computed(() => !!token.value);

  function setToken(newToken) {
    token.value = newToken;
    localStorage.setItem('token', newToken);
    if (newToken) {
      api.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
    } else {
      delete api.defaults.headers.common['Authorization'];
    }
  }

  function setUser(newUser) {
    user.value = newUser;
    localStorage.setItem('user', JSON.stringify(newUser));
  }

  async function fetchUser() {
    if (!token.value) return;
    try {
      const { data } = await api.get('/auth/me');
      setUser(data);
    } catch (error) {
      console.error('Failed to fetch user:', error);
      if (error.response && error.response.status === 401) {
        logout();
      }
    }
  }

  async function login(credentials) {
    try {
      const { data } = await api.post('/auth/login', credentials);
      setToken(data.token);
      setUser(data.user);
      return true; // Успех
    } catch (error) {
      // Пробрасываем ошибку дальше, чтобы компонент мог ее обработать
      throw error.response.data.message || 'Login failed';
    }
  }
  
  async function sendLoginCode(email) {
    try {
        await api.post('/auth/passwordless', { email });
    } catch (error) {
        throw error.response.data.message || 'Failed to send code';
    }
  }

  async function loginWithCode(credentials) {
    try {
        const { data } = await api.post('/auth/passwordless/login', credentials);
        setToken(data.token);
        setUser(data.user);
        return true;
    } catch (error) {
        throw error.response.data.message || 'Login with code failed';
    }
  }

  async function register(credentials) {
    try {
      const { data } = await api.post('/auth/register', credentials);
      setToken(data.token);
      setUser(data.user);
      return true; // Успех
    } catch (error) {
      // Пробрасываем ошибку дальше для обработки в компоненте
      throw error.response.data.message || 'Registration failed';
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout');
    } catch(e) {
      console.error("Server logout failed, continuing with client-side cleanup", e)
    }
    
    setUser(null);
    setToken(null);
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  }

  // Первичная настройка при загрузке
  if (token.value) {
    setToken(token.value);
  }

  return { user, token, isLoggedIn, login, register, logout, fetchUser, setToken, setUser, sendLoginCode, loginWithCode };
});
