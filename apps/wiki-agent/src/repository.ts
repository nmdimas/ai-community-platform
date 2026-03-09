import { randomUUID } from 'node:crypto';
import { Pool, type QueryResultRow } from 'pg';
import { excerpt, parseTags, slugify, stripHtml } from './text.js';
import type { SearchResult, WikiPage, WikiPageStatus } from './types.js';

interface SavePageInput {
  id?: string;
  slug?: string;
  title: string;
  summary?: string;
  bodyHtml: string;
  tags?: string[] | string;
  status: WikiPageStatus;
}

export class WikiRepository {
  public constructor(
    private readonly pool: Pool,
    private readonly schema: string,
  ) {}

  public async ensureSchema(): Promise<void> {
    await this.pool.query(`CREATE SCHEMA IF NOT EXISTS ${this.schema}`);
    await this.pool.query(`
      CREATE TABLE IF NOT EXISTS ${this.schema}.wiki_pages (
        id TEXT PRIMARY KEY,
        slug TEXT NOT NULL UNIQUE,
        title TEXT NOT NULL,
        summary TEXT NOT NULL DEFAULT '',
        body_html TEXT NOT NULL,
        body_text TEXT NOT NULL,
        tags JSONB NOT NULL DEFAULT '[]'::jsonb,
        status TEXT NOT NULL CHECK (status IN ('draft', 'published')),
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
      )
    `);
    await this.pool.query(`
      CREATE INDEX IF NOT EXISTS wiki_pages_status_updated_idx
      ON ${this.schema}.wiki_pages (status, updated_at DESC)
    `);
  }

  public async listPages(status?: WikiPageStatus): Promise<WikiPage[]> {
    const params: unknown[] = [];
    let sql = `
      SELECT id, slug, title, summary, body_html, body_text, tags, status, created_at, updated_at
      FROM ${this.schema}.wiki_pages
    `;

    if (status) {
      params.push(status);
      sql += ` WHERE status = $${params.length}`;
    }

    sql += ' ORDER BY updated_at DESC, title ASC';

    const result = await this.pool.query(sql, params);
    return result.rows.map((row) => this.mapPage(row));
  }

  public async getPageById(id: string): Promise<WikiPage | null> {
    const result = await this.pool.query(
      `
        SELECT id, slug, title, summary, body_html, body_text, tags, status, created_at, updated_at
        FROM ${this.schema}.wiki_pages
        WHERE id = $1
      `,
      [id],
    );

    return result.rowCount ? this.mapPage(result.rows[0]) : null;
  }

  public async getPageBySlug(slug: string, publishedOnly = false): Promise<WikiPage | null> {
    const params: unknown[] = [slug];
    let sql = `
      SELECT id, slug, title, summary, body_html, body_text, tags, status, created_at, updated_at
      FROM ${this.schema}.wiki_pages
      WHERE slug = $1
    `;

    if (publishedOnly) {
      params.push('published');
      sql += ` AND status = $${params.length}`;
    }

    const result = await this.pool.query(sql, params);

    return result.rowCount ? this.mapPage(result.rows[0]) : null;
  }

  public async savePage(input: SavePageInput): Promise<WikiPage> {
    const id = input.id ?? randomUUID();
    const title = input.title.trim();
    const slug = slugify(input.slug?.trim() || title);
    const bodyHtml = input.bodyHtml.trim();
    const bodyText = stripHtml(bodyHtml);
    const summary = (input.summary?.trim() || excerpt(bodyHtml, 220)) || title;
    const tags = Array.isArray(input.tags) ? input.tags : parseTags(input.tags ?? '');

    await this.pool.query(
      `
        INSERT INTO ${this.schema}.wiki_pages (
          id, slug, title, summary, body_html, body_text, tags, status, created_at, updated_at
        ) VALUES ($1, $2, $3, $4, $5, $6, $7::jsonb, $8, NOW(), NOW())
        ON CONFLICT (id) DO UPDATE SET
          slug = EXCLUDED.slug,
          title = EXCLUDED.title,
          summary = EXCLUDED.summary,
          body_html = EXCLUDED.body_html,
          body_text = EXCLUDED.body_text,
          tags = EXCLUDED.tags,
          status = EXCLUDED.status,
          updated_at = NOW()
      `,
      [id, slug, title, summary, bodyHtml, bodyText, JSON.stringify(tags), input.status],
    );

    const page = await this.getPageById(id);
    if (!page) {
      throw new Error('Failed to persist wiki page');
    }

    return page;
  }

  public async deletePage(id: string): Promise<void> {
    await this.pool.query(`DELETE FROM ${this.schema}.wiki_pages WHERE id = $1`, [id]);
  }

  public async fallbackSearch(query: string, limit: number): Promise<SearchResult[]> {
    const searchTerm = `%${query.trim()}%`;
    const result = await this.pool.query(
      `
        SELECT id, slug, title, summary, body_text, tags
        FROM ${this.schema}.wiki_pages
        WHERE status = 'published'
          AND (
            title ILIKE $1
            OR summary ILIKE $1
            OR body_text ILIKE $1
            OR EXISTS (
              SELECT 1
              FROM jsonb_array_elements_text(tags) AS tag
              WHERE tag ILIKE $1
            )
          )
        ORDER BY updated_at DESC
        LIMIT $2
      `,
      [searchTerm, limit],
    );

    return result.rows.map((row, index) => ({
      id: String(row.id),
      slug: String(row.slug),
      title: String(row.title),
      summary: String(row.summary),
      excerpt: excerpt(String(row.body_text), 240),
      tags: this.parseRowTags(row.tags),
      score: Math.max(0.1, 1 - index * 0.1),
    }));
  }

  private mapPage(row: QueryResultRow): WikiPage {
    return {
      id: String(row.id),
      slug: String(row.slug),
      title: String(row.title),
      summary: String(row.summary),
      bodyHtml: String(row.body_html),
      bodyText: String(row.body_text),
      tags: this.parseRowTags(row.tags),
      status: row.status as WikiPageStatus,
      createdAt: new Date(row.created_at).toISOString(),
      updatedAt: new Date(row.updated_at).toISOString(),
    };
  }

  private parseRowTags(value: unknown): string[] {
    if (Array.isArray(value)) {
      return value.map(String);
    }

    if (typeof value === 'string') {
      try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed.map(String) : [];
      } catch {
        return [];
      }
    }

    return [];
  }
}
