/**
 * Post-build prerender script.
 *
 * Запускается автоматически после `vite build`.
 * Для каждой статической публичной страницы создаёт dist/<route>/index.html
 * с уже вшитыми meta-тегами (title, description, og:*, canonical).
 *
 * Firebase Hosting сначала ищет статический файл по пути, и только если
 * не находит — применяет SPA rewrite на /index.html. Это значит что
 * /catalog → dist/catalog/index.html отдаётся краулеру мгновенно,
 * без ожидания JavaScript.
 */

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = fileURLToPath(new URL('.', import.meta.url));
const distDir = join(__dirname, 'dist');
const SITE_URL = 'https://game-45428688-fe94e.web.app';

const routes = [
  {
    path: '/',
    title: 'GameStore — Купить ключи Steam, Epic Games, GOG дёшево',
    description: 'Магазин лицензионных ключей для игр. Steam, Epic Games, GOG и другие платформы по выгодным ценам. Мгновенная доставка на e-mail.',
  },
  {
    path: '/catalog',
    title: 'Все игры — купить ключи дёшево | GameStore',
    description: 'Каталог лицензионных ключей Steam, Epic Games, GOG по выгодным ценам. Огромный выбор, мгновенная доставка на e-mail.',
  },
  {
    path: '/news',
    title: 'Новости игр — анонсы, обзоры, события | GameStore',
    description: 'Свежие новости из мира игр: анонсы релизов, обзоры и события. Следи за хрониками GameStore.',
  },
  {
    path: '/about',
    title: 'О магазине GameStore — лицензионные ключи для игр',
    description: 'GameStore — магазин лицензионных игровых ключей. Мгновенная доставка, гарантия качества, поддержка 24/7.',
  },
  {
    path: '/feed',
    title: 'Лента сообщества — посты и обсуждения | GameStore',
    description: 'Свежие посты и обсуждения сообщества GameStore. Игровые новости, советы и живое общение.',
  },
];

const baseHtml = readFileSync(join(distDir, 'index.html'), 'utf-8');

function inject(html, route) {
  const url = `${SITE_URL}${route.path}`;
  const { title, description } = route;

  return html
    .replace(/<title>[^<]*<\/title>/, `<title>${title}</title>`)
    .replace(
      /(<meta name="description" content=")[^"]*(")/,
      `$1${description}$2`
    )
    .replace(
      /(<meta property="og:title" content=")[^"]*(")/,
      `$1${title}$2`
    )
    .replace(
      /(<meta property="og:description" content=")[^"]*(")/,
      `$1${description}$2`
    )
    .replace(
      /(<meta property="og:url" content=")[^"]*(")/,
      `$1${url}$2`
    )
    .replace(
      /(<link rel="canonical" href=")[^"]*(")/,
      `$1${url}$2`
    );
}

let count = 0;

for (const route of routes) {
  const html = inject(baseHtml, route);

  if (route.path === '/') {
    // Перезаписываем корневой index.html
    writeFileSync(join(distDir, 'index.html'), html, 'utf-8');
    console.log(`✓  /  →  dist/index.html`);
  } else {
    // Создаём dist/<route>/index.html
    const routeDir = join(distDir, route.path.slice(1));
    if (!existsSync(routeDir)) mkdirSync(routeDir, { recursive: true });
    writeFileSync(join(routeDir, 'index.html'), html, 'utf-8');
    console.log(`✓  ${route.path}  →  dist${route.path}/index.html`);
  }
  count++;
}

console.log(`\n🔥 Prerender complete: ${count} pages`);
