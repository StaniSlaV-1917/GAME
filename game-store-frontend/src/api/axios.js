// src/views/api/axios.js (или где он у тебя лежит)
import axios from 'axios';

const api = axios.create({
  // ЗАМЕНИТЬ: Поменяй этот URL на публичный адрес твоего Laravel API
  baseURL: 'http://127.0.0.1:8000/api',
});

// перехватчик для всех запросов
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token'); // или откуда ты его берёшь после логина
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
}, (error) => Promise.reject(error));

export default api;
