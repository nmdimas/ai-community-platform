import { excerpt } from './text.js';
import type { SearchResult, WikiPage } from './types.js';

function escapeHtml(input: string): string {
  return input
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function tagBadges(tags: string[]): string {
  if (tags.length === 0) {
    return '<span class="muted">Без тегів</span>';
  }

  return tags.map((tag) => `<span class="tag">${escapeHtml(tag)}</span>`).join('');
}

export function layout(title: string, body: string, options?: { admin?: boolean }): string {
  const nav = options?.admin
    ? `
      <nav class="topnav">
        <a href="/wiki">Public wiki</a>
        <a href="/wiki-admin">Wiki admin</a>
        <a href="/wiki-admin/pages/new">New page</a>
        <a href="/wiki-admin/logout">Logout</a>
      </nav>
    `
    : `
      <nav class="topnav">
        <a href="/wiki">Wiki</a>
        <a href="/wiki-admin">Wiki admin</a>
      </nav>
    `;

  return `<!doctype html>
  <html lang="uk">
    <head>
      <meta charset="utf-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>${escapeHtml(title)}</title>
      <style>
        :root {
          --bg: #081521;
          --bg-soft: #112538;
          --panel: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
          --ink: #e8f1fb;
          --accent: #26d0ce;
          --accent-2: #1a75ff;
          --accent-soft: rgba(38, 208, 206, 0.12);
          --line: rgba(255, 255, 255, 0.14);
          --danger: #ef4444;
          --muted: #9bb3c8;
          --strong: #cde0f3;
        }
        * { box-sizing: border-box; }
        body {
          margin: 0;
          font-family: "Manrope", "Segoe UI", sans-serif;
          color: var(--ink);
          background:
            radial-gradient(circle at 15% 20%, rgba(38, 208, 206, 0.22), transparent 40%),
            radial-gradient(circle at 85% 10%, rgba(26, 117, 255, 0.2), transparent 38%),
            linear-gradient(135deg, var(--bg) 0%, #0b1d2d 55%, #0d2233 100%);
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .topnav {
          display: flex;
          gap: 16px;
          padding: 16px 24px;
          background: rgba(6, 18, 31, 0.55);
          border-bottom: 1px solid var(--line);
          position: sticky;
          top: 0;
          backdrop-filter: blur(12px);
        }
        .shell {
          max-width: 1320px;
          margin: 0 auto;
          padding: 24px;
        }
        .grid {
          display: grid;
          grid-template-columns: 300px minmax(0, 1fr) 360px;
          gap: 20px;
          align-items: start;
        }
        .panel {
          background: var(--panel);
          border: 1px solid var(--line);
          border-radius: 18px;
          padding: 20px;
          box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
          backdrop-filter: blur(12px);
        }
        .panel h1, .panel h2, .panel h3 { margin-top: 0; color: var(--strong); }
        .list {
          display: grid;
          gap: 12px;
        }
        .card {
          display: block;
          padding: 14px;
          border: 1px solid var(--line);
          border-radius: 14px;
          background: linear-gradient(180deg, rgba(255,255,255,0.035), rgba(255,255,255,0.015));
        }
        .tag {
          display: inline-flex;
          margin-right: 8px;
          margin-top: 8px;
          padding: 3px 8px;
          border-radius: 999px;
          font-size: 12px;
          background: var(--accent-soft);
          color: var(--accent);
          border: 1px solid var(--line);
        }
        .muted { color: var(--muted); }
        .searchbar, input[type=text], input[type=password], textarea, select {
          width: 100%;
          border: 1px solid var(--line);
          border-radius: 12px;
          padding: 12px 14px;
          font: inherit;
          background: rgba(255, 255, 255, 0.03);
          color: var(--ink);
        }
        textarea {
          min-height: 160px;
          resize: vertical;
        }
        button {
          border: none;
          border-radius: 12px;
          padding: 10px 14px;
          font: inherit;
          background: linear-gradient(90deg, var(--accent), #66e6e4);
          color: #04101b;
          cursor: pointer;
          font-weight: 700;
        }
        button.secondary {
          background: rgba(255,255,255,0.02);
          color: var(--ink);
          border: 1px solid var(--line);
        }
        button.danger { background: var(--danger); }
        .toolbar {
          display: flex;
          flex-wrap: wrap;
          gap: 8px;
          margin-bottom: 8px;
        }
        .editor {
          min-height: 260px;
          padding: 16px;
          border: 1px solid var(--line);
          border-radius: 12px;
          background: rgba(255, 255, 255, 0.03);
        }
        .meta { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .chat-log {
          display: grid;
          gap: 12px;
          margin-top: 16px;
        }
        .chat-bubble {
          padding: 12px 14px;
          border-radius: 14px;
          background: linear-gradient(180deg, rgba(255,255,255,0.035), rgba(255,255,255,0.015));
          border: 1px solid var(--line);
          white-space: pre-wrap;
        }
        .chat-bubble.agent {
          background: linear-gradient(180deg, rgba(38, 208, 206, 0.12), rgba(26, 117, 255, 0.12));
        }
        .row {
          display: flex;
          gap: 10px;
          flex-wrap: wrap;
          align-items: center;
        }
        .table {
          width: 100%;
          border-collapse: collapse;
        }
        .table th, .table td {
          border-bottom: 1px solid var(--line);
          padding: 12px 10px;
          text-align: left;
        }
        .table th { color: var(--muted); }
        .actions {
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
        }
        @media (max-width: 1080px) {
          .grid { grid-template-columns: 1fr; }
        }
      </style>
    </head>
    <body>
      ${nav}
      <main class="shell">
        ${body}
      </main>
    </body>
  </html>`;
}

function searchCards(results: SearchResult[]): string {
  if (results.length === 0) {
    return '<p class="muted">Нічого не знайдено.</p>';
  }

  return `
    <div class="list">
      ${results
        .map(
          (result) => `
            <a class="card" href="/wiki/page/${escapeHtml(result.slug)}">
              <strong>${escapeHtml(result.title)}</strong>
              <p>${escapeHtml(result.excerpt || result.summary)}</p>
              <div>${tagBadges(result.tags)}</div>
            </a>
          `,
        )
        .join('')}
    </div>
  `;
}

export function renderWikiHome(args: {
  pages: WikiPage[];
  selectedPage: WikiPage | null;
  query: string;
  results: SearchResult[];
}): string {
  const sideList = args.pages
    .map(
      (page) => `
        <a class="card" href="/wiki/page/${escapeHtml(page.slug)}">
          <strong>${escapeHtml(page.title)}</strong>
          <p class="muted">${escapeHtml(excerpt(page.summary || page.bodyText, 90))}</p>
        </a>
      `,
    )
    .join('');

  const content = args.selectedPage
    ? `
      <article class="panel">
        <div class="meta">
          <span class="muted">Опубліковано в wiki</span>
          ${tagBadges(args.selectedPage.tags)}
        </div>
        <h1>${escapeHtml(args.selectedPage.title)}</h1>
        <p class="muted">${escapeHtml(args.selectedPage.summary)}</p>
        <div>${args.selectedPage.bodyHtml}</div>
      </article>
    `
    : `
      <article class="panel">
        <h1>Wiki для комʼюніті</h1>
        <p>Це публічна wiki-поверхня з вбудованим агентом. Контент іде з окремого <code>wiki-agent</code>, а не з core чи knowledge-agent.</p>
        ${args.query ? `<h2>Результати пошуку</h2>${searchCards(args.results)}` : '<p>Відкрийте сторінку зліва або скористайтесь пошуком.</p>'}
      </article>
    `;

  return layout(
    args.selectedPage?.title ?? 'Wiki',
    `
      <div class="grid">
        <aside class="panel">
          <h2>Сторінки</h2>
          <form method="GET" action="/wiki">
            <input class="searchbar" type="text" name="q" value="${escapeHtml(args.query)}" placeholder="Пошук по wiki" />
          </form>
          <div class="list" style="margin-top: 16px;">${sideList || '<p class="muted">Поки немає опублікованих сторінок.</p>'}</div>
        </aside>
        ${content}
        <section class="panel">
          <h2>Wiki Agent</h2>
          <p class="muted">Агент відповідає лише на основі опублікованих сторінок wiki.</p>
          <form id="chat-form">
            <textarea id="chat-question" name="question" placeholder="Поставте запитання по wiki"></textarea>
            <input type="hidden" id="chat-page-slug" value="${escapeHtml(args.selectedPage?.slug ?? '')}" />
            <div class="row" style="margin-top: 12px;">
              <button type="submit">Запитати</button>
            </div>
          </form>
          <div id="chat-log" class="chat-log"></div>
          <script>
            const form = document.getElementById('chat-form');
            const log = document.getElementById('chat-log');
            form?.addEventListener('submit', async (event) => {
              event.preventDefault();
              const question = document.getElementById('chat-question').value.trim();
              const currentSlug = document.getElementById('chat-page-slug').value || undefined;
              if (!question) return;

              log.innerHTML = '<div class="chat-bubble">Шукаю по wiki…</div>';

              const response = await fetch('/api/v1/wiki/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question, currentSlug }),
              });
              const payload = await response.json();
              const answerText = payload.answer || payload.error || 'Немає відповіді';
              const citations = (payload.citations || [])
                .map((item) => '<a href="' + item.url + '">' + item.title + '</a>')
                .join('<br />');

              log.innerHTML = ''
                + '<div class="chat-bubble">' + question + '</div>'
                + '<div class="chat-bubble agent">' + answerText + '</div>'
                + (citations ? '<div class="chat-bubble"><strong>Джерела</strong><br />' + citations + '</div>' : '');
            });
          </script>
        </section>
      </div>
    `,
  );
}

export function renderAdminLogin(errorMessage?: string): string {
  return layout(
    'Wiki admin login',
    `
      <div class="panel" style="max-width: 460px; margin: 80px auto;">
        <h1>Wiki admin</h1>
        <p class="muted">Окремий вхід для нового <code>wiki-agent</code>.</p>
        ${errorMessage ? `<p style="color: #b13b2e;">${escapeHtml(errorMessage)}</p>` : ''}
        <form method="POST" action="/wiki-admin/login">
          <div style="display:grid; gap:12px;">
            <input type="text" name="username" placeholder="Username" />
            <input type="password" name="password" placeholder="Password" />
            <button type="submit">Login</button>
          </div>
        </form>
      </div>
    `,
  );
}

export function renderAdminList(pages: WikiPage[]): string {
  return layout(
    'Wiki admin',
    `
      <div class="panel">
        <div class="row" style="justify-content: space-between;">
          <div>
            <h1>Wiki admin</h1>
            <p class="muted">Керуйте сторінками нового <code>wiki-agent</code> без зміни існуючого knowledge-agent.</p>
          </div>
          <a href="/wiki-admin/pages/new"><button>Нова сторінка</button></a>
        </div>
        <table class="table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Slug</th>
              <th>Status</th>
              <th>Updated</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            ${pages
              .map(
                (page) => `
                  <tr>
                    <td>${escapeHtml(page.title)}</td>
                    <td><code>${escapeHtml(page.slug)}</code></td>
                    <td>${escapeHtml(page.status)}</td>
                    <td>${new Date(page.updatedAt).toLocaleString('uk-UA')}</td>
                    <td>
                      <div class="actions">
                        <a href="/wiki/page/${escapeHtml(page.slug)}">View</a>
                        <a href="/wiki-admin/pages/${escapeHtml(page.id)}/edit">Edit</a>
                        <form method="POST" action="/wiki-admin/pages/${escapeHtml(page.id)}/delete" onsubmit="return confirm('Видалити сторінку?');">
                          <button class="danger" type="submit">Delete</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                `,
              )
              .join('')}
          </tbody>
        </table>
      </div>
    `,
    { admin: true },
  );
}

export function renderPageEditor(page?: WikiPage, errorMessage?: string): string {
  const title = page ? `Редагувати: ${page.title}` : 'Нова сторінка wiki';

  return layout(
    title,
    `
      <div class="panel">
        <h1>${escapeHtml(title)}</h1>
        ${errorMessage ? `<p style="color: #b13b2e;">${escapeHtml(errorMessage)}</p>` : ''}
        <form method="POST" action="${page ? `/wiki-admin/pages/${escapeHtml(page.id)}` : '/wiki-admin/pages'}" id="editor-form">
          <div style="display:grid; gap:16px;">
            <input type="text" name="title" placeholder="Title" value="${escapeHtml(page?.title ?? '')}" required />
            <input type="text" name="slug" placeholder="Slug" value="${escapeHtml(page?.slug ?? '')}" />
            <textarea name="summary" placeholder="Summary">${escapeHtml(page?.summary ?? '')}</textarea>
            <input type="text" name="tags" placeholder="ai, wiki, platform" value="${escapeHtml(page?.tags.join(', ') ?? '')}" />
            <select name="status">
              <option value="draft" ${page?.status === 'draft' ? 'selected' : ''}>draft</option>
              <option value="published" ${page?.status === 'published' ? 'selected' : ''}>published</option>
            </select>
            <div>
              <div class="toolbar">
                <button class="secondary" type="button" data-cmd="bold">Bold</button>
                <button class="secondary" type="button" data-cmd="italic">Italic</button>
                <button class="secondary" type="button" data-cmd="insertUnorderedList">Bullet list</button>
                <button class="secondary" type="button" data-cmd="formatBlock" data-value="h2">H2</button>
                <button class="secondary" type="button" data-cmd="formatBlock" data-value="p">Paragraph</button>
              </div>
              <div id="editor" class="editor" contenteditable="true">${page?.bodyHtml ?? '<p>Почніть редагувати контент сторінки…</p>'}</div>
              <textarea name="bodyHtml" id="bodyHtml" style="display:none;"></textarea>
            </div>
            <div class="actions">
              <button type="submit">Save page</button>
              <a href="/wiki-admin"><button class="secondary" type="button">Back</button></a>
            </div>
          </div>
        </form>
        <script>
          const editor = document.getElementById('editor');
          const bodyField = document.getElementById('bodyHtml');
          const form = document.getElementById('editor-form');

          document.querySelectorAll('[data-cmd]').forEach((button) => {
            button.addEventListener('click', () => {
              document.execCommand(button.dataset.cmd, false, button.dataset.value || null);
            });
          });

          form?.addEventListener('submit', () => {
            bodyField.value = editor.innerHTML;
          });
        </script>
      </div>
    `,
    { admin: true },
  );
}
