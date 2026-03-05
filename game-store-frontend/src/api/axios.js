import axios from 'axios';

const api = axios.create({
  // Устанавливаем базовый URL, который использовался в рабочей версии
  baseURL: 'http://localhost:3000',
});

export default api;
