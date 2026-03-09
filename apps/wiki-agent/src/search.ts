import { Client } from '@opensearch-project/opensearch';
import { excerpt } from './text.js';
import type { SearchResult, WikiPage } from './types.js';
import { WikiRepository } from './repository.js';

export class WikiSearchService {
  public constructor(
    private readonly client: Client,
    private readonly repository: WikiRepository,
    private readonly indexName: string,
  ) {}

  public async ensureIndex(): Promise<void> {
    try {
      const exists = await this.client.indices.exists({ index: this.indexName });
      if (exists.body === true) {
        return;
      }

      await this.client.indices.create({
        index: this.indexName,
        body: {
          mappings: {
            properties: {
              id: { type: 'keyword' },
              slug: { type: 'keyword' },
              title: { type: 'text' },
              summary: { type: 'text' },
              body_text: { type: 'text' },
              tags: { type: 'keyword' },
              status: { type: 'keyword' },
              updated_at: { type: 'date' },
            },
          },
        },
      });
    } catch (error) {
      console.warn('[wiki-agent] OpenSearch index bootstrap failed, continuing with Postgres fallback.', error);
    }
  }

  public async reindexPublishedPages(): Promise<void> {
    const pages = await this.repository.listPages('published');
    await Promise.all(pages.map((page) => this.upsertPage(page)));
  }

  public async upsertPage(page: WikiPage): Promise<void> {
    if (page.status !== 'published') {
      await this.removePage(page.id);
      return;
    }

    try {
      await this.client.index({
        index: this.indexName,
        id: page.id,
        refresh: true,
        body: {
          id: page.id,
          slug: page.slug,
          title: page.title,
          summary: page.summary,
          body_text: page.bodyText,
          tags: page.tags,
          status: page.status,
          updated_at: page.updatedAt,
        },
      });
    } catch (error) {
      console.warn('[wiki-agent] OpenSearch index update failed.', error);
    }
  }

  public async removePage(id: string): Promise<void> {
    try {
      await this.client.delete({
        index: this.indexName,
        id,
        refresh: true,
      });
    } catch {
      // Ignore delete failures when the document is absent or OpenSearch is not available.
    }
  }

  public async search(query: string, limit = 8, currentSlug?: string): Promise<SearchResult[]> {
    const trimmed = query.trim();
    if (!trimmed) {
      return [];
    }

    try {
      const response = await this.client.search({
        index: this.indexName,
        size: limit,
        body: {
          query: {
            bool: {
              filter: [{ term: { status: 'published' } }],
              should: [
                {
                  multi_match: {
                    query: trimmed,
                    fields: ['title^3', 'summary^2', 'body_text', 'tags^2'],
                    type: 'best_fields',
                  },
                },
                ...(currentSlug
                  ? [{ term: { slug: { value: currentSlug, boost: 2 } } }]
                  : []),
              ],
              minimum_should_match: 1,
            },
          },
        },
      });

      const hits = (response.body.hits?.hits ?? []) as Array<{ _score?: number; _source?: Record<string, unknown> }>;
      if (hits.length > 0) {
        return hits.map((hit, index) => {
          const source = hit._source ?? {};
          return {
            id: String(source.id ?? ''),
            slug: String(source.slug ?? ''),
            title: String(source.title ?? ''),
            summary: String(source.summary ?? ''),
            excerpt: excerpt(String(source.body_text ?? source.summary ?? ''), 240),
            tags: Array.isArray(source.tags) ? source.tags.map(String) : [],
            score: hit._score ?? Math.max(0.1, 1 - index * 0.1),
          };
        });
      }
    } catch (error) {
      console.warn('[wiki-agent] OpenSearch search failed, using Postgres fallback.', error);
    }

    return this.repository.fallbackSearch(trimmed, limit);
  }
}
