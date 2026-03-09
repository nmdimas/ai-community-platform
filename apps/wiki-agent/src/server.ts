import cookieParser from 'cookie-parser';
import express, { type Request, type Response, type NextFunction } from 'express';
import { Pool } from 'pg';
import { Client as OpenSearchClient } from '@opensearch-project/opensearch';
import { config } from './config.js';
import { createAdminSession, verifyAdminSession } from './adminAuth.js';
import { WikiRepository } from './repository.js';
import { WikiSearchService } from './search.js';
import { WikiChatService } from './chat.js';
import { renderAdminList, renderAdminLogin, renderPageEditor, renderWikiHome } from './render.js';

const pool = new Pool({ connectionString: config.databaseUrl });
const openSearch = new OpenSearchClient({ node: config.openSearchUrl });
const repository = new WikiRepository(pool, config.pgSchema);
const search = new WikiSearchService(openSearch, repository, config.openSearchIndex);
const chat = new WikiChatService(search, {
  litellmApiKey: config.litellmApiKey,
  litellmBaseUrl: config.litellmBaseUrl,
  llmModel: config.llmModel,
  publicBaseUrl: config.publicBaseUrl,
});

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(cookieParser());

function firstValue(input: unknown): string {
  return Array.isArray(input) ? String(input[0] ?? '') : String(input ?? '');
}

function requireAdmin(req: Request, res: Response, next: NextFunction): void {
  const valid = verifyAdminSession(req.cookies.wiki_admin_session as string | undefined, config.sessionSecret, config.adminUsername);
  if (!valid) {
    res.redirect('/wiki-admin/login');
    return;
  }

  next();
}

function manifest() {
  return {
    name: 'wiki-agent',
    version: '0.1.0',
    description: 'Standalone TypeScript wiki agent with grounded public chat and separate wiki-admin surface.',
    url: `${config.internalBaseUrl}/api/v1/a2a`,
    health_url: `${config.internalBaseUrl}/health`,
    admin_url: `${config.publicBaseUrl}/wiki-admin`,
    capabilities: {
      streaming: false,
      pushNotifications: false,
    },
    defaultInputModes: ['text'],
    defaultOutputModes: ['text'],
    skills: [
      {
        id: 'wiki.search',
        name: 'Wiki Search',
        description: 'Search published wiki pages.',
      },
      {
        id: 'wiki.answer',
        name: 'Wiki Answer',
        description: 'Answer a question using published wiki pages only.',
      },
    ],
    commands: ['/wiki'],
    storage: {
      postgres: {
        db_name: 'ai_community_platform',
        schema: config.pgSchema,
      },
      opensearch: {
        index: config.openSearchIndex,
      },
      rabbitmq: {
        exchange: process.env.RABBITMQ_EXCHANGE ?? 'wiki_agent.events',
        queue: process.env.RABBITMQ_QUEUE ?? 'wiki_agent.jobs',
      },
    },
  };
}

app.get('/health', (_req, res) => {
  res.json({ status: 'ok' });
});

app.get('/api/v1/manifest', (_req, res) => {
  res.json(manifest());
});

app.post('/api/v1/a2a', async (req, res) => {
  const tool = typeof req.body?.tool === 'string' ? req.body.tool : null;
  const input = typeof req.body?.input === 'object' && req.body.input !== null ? req.body.input as Record<string, unknown> : {};

  if (!tool) {
    res.status(422).json({ status: 'failed', output: null, error: 'Missing tool field.' });
    return;
  }

  try {
    if (tool === 'wiki.search') {
      const query = String(input.query ?? '').trim();
      const results = await search.search(query, 5);
      res.json({ status: 'completed', output: { results }, error: null });
      return;
    }

    if (tool === 'wiki.answer') {
      const question = String(input.question ?? '').trim();
      const currentSlug = typeof input.currentSlug === 'string' ? input.currentSlug : undefined;
      const answer = await chat.answer(question, currentSlug);
      res.json({ status: 'completed', output: answer, error: null });
      return;
    }

    res.json({ status: 'failed', output: null, error: `Unknown tool: ${tool}` });
  } catch (error) {
    res.json({
      status: 'failed',
      output: null,
      error: error instanceof Error ? error.message : 'Unexpected wiki-agent error.',
    });
  }
});

app.get('/api/v1/wiki/pages', async (req, res) => {
  const query = String(req.query.q ?? '').trim();
  if (query) {
    const results = await search.search(query, 10);
    res.json({ results });
    return;
  }

  const pages = await repository.listPages('published');
  res.json({ pages });
});

app.get('/api/v1/wiki/pages/:slug', async (req, res) => {
  const page = await repository.getPageBySlug(req.params.slug, true);
  if (!page) {
    res.status(404).json({ error: 'Wiki page not found.' });
    return;
  }

  res.json({ page });
});

app.post('/api/v1/wiki/chat', async (req, res) => {
  const question = String(req.body?.question ?? '').trim();
  if (!question) {
    res.status(422).json({ error: 'Question is required.' });
    return;
  }

  try {
    const currentSlug = typeof req.body?.currentSlug === 'string' ? req.body.currentSlug : undefined;
    const answer = await chat.answer(question, currentSlug);
    res.json(answer);
  } catch (error) {
    res.status(502).json({
      error: error instanceof Error ? error.message : 'Wiki chat failed.',
    });
  }
});

app.get('/wiki', async (req, res) => {
  const query = String(req.query.q ?? '').trim();
  const pages = await repository.listPages('published');
  const results = query ? await search.search(query, 10) : [];

  res.type('html').send(renderWikiHome({
    pages,
    query,
    results,
    selectedPage: null,
  }));
});

app.get('/wiki/page/:slug', async (req, res) => {
  const page = await repository.getPageBySlug(req.params.slug, true);
  if (!page) {
    res.status(404).type('html').send(renderWikiHome({
      pages: await repository.listPages('published'),
      query: '',
      results: [],
      selectedPage: null,
    }));
    return;
  }

  res.type('html').send(renderWikiHome({
    pages: await repository.listPages('published'),
    query: '',
    results: [],
    selectedPage: page,
  }));
});

app.get('/wiki-admin/login', (_req, res) => {
  res.type('html').send(renderAdminLogin());
});

app.post('/wiki-admin/login', (req, res) => {
  const username = String(req.body?.username ?? '');
  const password = String(req.body?.password ?? '');

  if (username !== config.adminUsername || password !== config.adminPassword) {
    res.status(401).type('html').send(renderAdminLogin('Invalid credentials.'));
    return;
  }

  res.cookie('wiki_admin_session', createAdminSession(username, config.sessionSecret), {
    httpOnly: true,
    sameSite: 'lax',
  });
  res.redirect('/wiki-admin');
});

app.get('/wiki-admin/logout', (_req, res) => {
  res.clearCookie('wiki_admin_session');
  res.redirect('/wiki-admin/login');
});

app.get('/wiki-admin', requireAdmin, async (_req, res) => {
  res.type('html').send(renderAdminList(await repository.listPages()));
});

app.get('/wiki-admin/pages/new', requireAdmin, (_req, res) => {
  res.type('html').send(renderPageEditor());
});

app.get('/wiki-admin/pages/:id/edit', requireAdmin, async (req, res) => {
  const pageId = firstValue(req.params.id);
  const page = await repository.getPageById(pageId);
  if (!page) {
    res.status(404).type('html').send(renderAdminList(await repository.listPages()));
    return;
  }

  res.type('html').send(renderPageEditor(page));
});

app.post('/wiki-admin/pages', requireAdmin, async (req, res) => {
  try {
    const page = await repository.savePage({
      title: String(req.body?.title ?? ''),
      slug: String(req.body?.slug ?? ''),
      summary: String(req.body?.summary ?? ''),
      bodyHtml: String(req.body?.bodyHtml ?? ''),
      tags: String(req.body?.tags ?? ''),
      status: req.body?.status === 'published' ? 'published' : 'draft',
    });
    await search.upsertPage(page);
    res.redirect('/wiki-admin');
  } catch (error) {
    res.status(422).type('html').send(renderPageEditor(undefined, error instanceof Error ? error.message : 'Failed to save page.'));
  }
});

app.post('/wiki-admin/pages/:id', requireAdmin, async (req, res) => {
  try {
    const pageId = firstValue(req.params.id);
    const page = await repository.savePage({
      id: pageId,
      title: String(req.body?.title ?? ''),
      slug: String(req.body?.slug ?? ''),
      summary: String(req.body?.summary ?? ''),
      bodyHtml: String(req.body?.bodyHtml ?? ''),
      tags: String(req.body?.tags ?? ''),
      status: req.body?.status === 'published' ? 'published' : 'draft',
    });
    await search.upsertPage(page);
    res.redirect('/wiki-admin');
  } catch (error) {
    const page = await repository.getPageById(firstValue(req.params.id));
    res.status(422).type('html').send(renderPageEditor(page ?? undefined, error instanceof Error ? error.message : 'Failed to update page.'));
  }
});

app.post('/wiki-admin/pages/:id/delete', requireAdmin, async (req, res) => {
  const pageId = firstValue(req.params.id);
  const page = await repository.getPageById(pageId);
  await repository.deletePage(pageId);
  if (page) {
    await search.removePage(page.id);
  }
  res.redirect('/wiki-admin');
});

async function bootstrap(): Promise<void> {
  await repository.ensureSchema();
  await search.ensureIndex();
  await search.reindexPublishedPages();
}

bootstrap()
  .then(() => {
    app.listen(config.port, () => {
      console.log(`[wiki-agent] listening on :${config.port}`);
    });
  })
  .catch((error) => {
    console.error('[wiki-agent] startup failed', error);
    process.exitCode = 1;
  });
