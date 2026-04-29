/**
 * Markdown → safe HTML.
 *
 * Phase 2 / Batch C. Используется:
 *   • в редакторе /post/new для preview-вкладки
 *   • в Post-view (Batch D) для отрисовки тела поста
 *
 * Pipeline:
 *   1. marked.js парсит markdown в HTML
 *   2. DOMPurify санитизирует HTML, убирая потенциально вредные теги
 *      и атрибуты (onclick, javascript: links, <script>, <iframe> etc.)
 *
 * Конфигурация marked:
 *   • gfm: true — GitHub Flavored Markdown (таблицы, autolink, и т.п.)
 *   • breaks: true — \n становится <br> (юзеры не привыкли к двойным
 *     переносам markdown'а)
 */
import { marked } from 'marked';
import DOMPurify from 'dompurify';

// Настраиваем marked один раз на загрузку модуля
marked.setOptions({
  gfm: true,
  breaks: true,
});

// DOMPurify allowlist — что РАЗРЕШЕНО:
//   • Текстовые: p, br, strong, em, h1-h6, blockquote
//   • Списки: ul, ol, li
//   • Код: code, pre
//   • Ссылки: a (с rel/target enforced)
//   • Картинки: img (с loading=lazy enforced)
//   • Цитаты, hr, таблицы (gfm)
const PURIFY_CONFIG = {
  ALLOWED_TAGS: [
    'p', 'br', 'strong', 'em', 'u', 's', 'del',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'blockquote', 'hr',
    'ul', 'ol', 'li',
    'code', 'pre',
    'a', 'img',
    'table', 'thead', 'tbody', 'tr', 'th', 'td',
    'span',
  ],
  ALLOWED_ATTR: ['href', 'src', 'alt', 'title', 'class', 'loading', 'rel', 'target'],
  ALLOW_DATA_ATTR: false,
  // Запрещаем javascript: data: vbscript: и подобные опасные схемы
  ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto|tel):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i,
};

// Хук: enforce rel/target на всех ссылках (защита от tab-nabbing)
DOMPurify.addHook('afterSanitizeAttributes', (node) => {
  if (node.tagName === 'A') {
    node.setAttribute('rel', 'noopener noreferrer nofollow');
    node.setAttribute('target', '_blank');
  }
  if (node.tagName === 'IMG') {
    node.setAttribute('loading', 'lazy');
    // Картинки в постах не должны быть огромными
    node.setAttribute('style', 'max-width:100%;height:auto');
  }
});

/**
 * Конвертирует markdown в безопасный HTML.
 * @param {string} md  Markdown source
 * @returns {string}   Sanitized HTML
 */
export function renderMarkdown(md) {
  if (!md) return '';
  const html = marked.parse(String(md));
  return DOMPurify.sanitize(html, PURIFY_CONFIG);
}
