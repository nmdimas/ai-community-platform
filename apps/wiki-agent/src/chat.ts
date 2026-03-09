import type { SearchResult } from './types.js';
import { WikiSearchService } from './search.js';

interface ChatConfig {
  litellmBaseUrl: string;
  litellmApiKey: string;
  llmModel: string;
  publicBaseUrl: string;
}

interface ChatAnswer {
  answer: string;
  citations: Array<{ title: string; slug: string; url: string }>;
}

export class WikiChatService {
  private static readonly ENABLE_SUMMARY_FALLBACK = false;

  public constructor(
    private readonly search: WikiSearchService,
    private readonly config: ChatConfig,
  ) {}

  public async answer(question: string, currentSlug?: string): Promise<ChatAnswer> {
    const trimmed = question.trim();
    if (!trimmed) {
      return {
        answer: 'Поставте запитання про матеріали wiki.',
        citations: [],
      };
    }

    const sources = await this.search.search(trimmed, 4, currentSlug);
    if (sources.length === 0) {
      return {
        answer: 'Я не знайшов релевантної інформації у wiki. Спробуйте уточнити запит або відкрити іншу сторінку.',
        citations: [],
      };
    }

    let answer: string;
    try {
      answer = await this.completeWithSources(trimmed, sources);
    } catch (error) {
      if (WikiChatService.ENABLE_SUMMARY_FALLBACK) {
        answer = this.fallbackAnswer(sources);
      } else {
        throw error;
      }
    }

    return {
      answer,
      citations: sources.map((source) => ({
        title: source.title,
        slug: source.slug,
        url: `${this.config.publicBaseUrl}/wiki/page/${source.slug}`,
      })),
    };
  }

  private async completeWithSources(question: string, sources: SearchResult[]): Promise<string> {
    const sourceBlocks = sources
      .map(
        (source, index) => [
          `Source ${index + 1}`,
          `Title: ${source.title}`,
          `Slug: ${source.slug}`,
          `Summary: ${source.summary}`,
          `Excerpt: ${source.excerpt}`,
        ].join('\n'),
      )
      .join('\n\n');

    const response = await fetch(`${this.config.litellmBaseUrl}/chat/completions`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${this.config.litellmApiKey}`,
      },
      body: JSON.stringify({
        model: this.config.llmModel,
        temperature: 0.2,
        messages: [
          {
            role: 'system',
            content: [
              'You are the public wiki agent for AI Community Platform.',
              'Answer only from the supplied sources.',
              'If the sources are insufficient, explicitly say that the wiki does not contain enough information.',
              'Keep the answer concise and factual.',
            ].join(' '),
          },
          {
            role: 'user',
            content: `Question:\n${question}\n\nSources:\n${sourceBlocks}`,
          },
        ],
      }),
    });

    if (!response.ok) {
      throw new Error(`LiteLLM request failed with status ${response.status}`);
    }

    const payload = (await response.json()) as {
      choices?: Array<{ message?: { content?: string } }>;
    };

    return payload.choices?.[0]?.message?.content?.trim() || 'Не вдалося сформувати відповідь із наявних матеріалів wiki.';
  }

  private fallbackAnswer(sources: SearchResult[]): string {
    const [primary, ...rest] = sources;
    const additional = rest
      .slice(0, 2)
      .map((source) => source.title)
      .join(', ');

    if (!primary) {
      return 'Я не знайшов релевантної інформації у wiki.';
    }

    const extraSentence = additional
      ? ` Додатково перегляньте: ${additional}.`
      : '';

    return `${primary.summary || primary.excerpt}${extraSentence}`;
  }
}
