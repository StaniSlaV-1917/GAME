/**
 * Centralized media URL resolver.
 *
 * ALL images are served from the backend (Laravel).
 * On deploy — change only VITE_MEDIA_BASE_URL in .env.
 *
 * Resolution rules:
 *  1. Null / empty → fallback SVG (in frontend public/)
 *  2. Already absolute URL (http/https) → use as-is  [News accessor returns full URL]
 *  3. Starts with "/" → prepend MEDIA_BASE           [/storage/gallery/... paths]
 *  4. Bare filename (no slashes) → MEDIA_BASE/img/<file>  [game covers in backend/public/img/]
 */

const MEDIA_BASE = (import.meta.env.VITE_MEDIA_BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');

export function resolveMediaUrl(path, fallback = '/img/noimage.svg') {
  if (!path) return fallback;
  if (/^https?:\/\//i.test(path)) return path;              // already absolute (News model)
  if (path.startsWith('/')) return `${MEDIA_BASE}${path}`;  // /storage/... paths
  return `${MEDIA_BASE}/img/${path}`;                        // bare filename → backend/public/img/
}
