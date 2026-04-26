
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../api/axios';
import { useCartStore } from './cart';

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

      // Загружаем корзину с сервера
      try {
        const cartStore = useCartStore();
        await cartStore.loadFromServer();
      } catch (cartError) {
        console.error('Failed to load cart:', cartError);
      }
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

      // Загружаем корзину с сервера после входа
      try {
        const cartStore = useCartStore();
        await cartStore.loadFromServer();
      } catch (cartError) {
        console.error('Failed to load cart:', cartError);
      }

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

        // Загружаем корзину с сервера после входа
        try {
          const cartStore = useCartStore();
          await cartStore.loadFromServer();
        } catch (cartError) {
          console.error('Failed to load cart:', cartError);
        }

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

      // Загружаем корзину с сервера после регистрации
      try {
        const cartStore = useCartStore();
        await cartStore.loadFromServer();
      } catch (cartError) {
        console.error('Failed to load cart:', cartError);
      }

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

  async function sendPasswordResetCode(email) {
    try {
      await api.post('/auth/forgot-password', { email });
    } catch (error) {
      throw new Error(extractErrorMessage(error, 'Не удалось отправить код. Проверьте email и попробуйте снова.'));
    }
  }

  async function resetPassword(payload) {
    try {
      await api.post('/auth/reset-password', payload);
    } catch (error) {
      throw new Error(extractErrorMessage(error, 'Не удалось сбросить пароль. Проверьте код и попробуйте снова.'));
    }
  }

  async function requestEmailChange(newEmail) {
    try {
      await api.post('/auth/email-change/request', { email: newEmail });
    } catch (error) {
      throw new Error(extractErrorMessage(error, 'Не удалось отправить код. Попробуйте снова.'));
    }
  }

  async function confirmEmailChange(code) {
    try {
      const { data } = await api.post('/auth/email-change/confirm', { code });
      if (data.user) setUser(data.user);
      return data;
    } catch (error) {
      throw new Error(extractErrorMessage(error, 'Неверный код. Попробуйте снова.'));
    }
  }

  // Первичная настройка при загрузке
  if (token.value) {
    setToken(token.value);
  }

  return { user, token, isLoggedIn, login, register, logout, fetchUser, setToken, setUser, sendLoginCode, loginWithCode, sendPasswordResetCode, resetPassword, requestEmailChange, confirmEmailChange };
});
