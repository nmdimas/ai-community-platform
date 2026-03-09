export type WikiPageStatus = 'draft' | 'published';

export interface WikiPage {
  id: string;
  slug: string;
  title: string;
  summary: string;
  bodyHtml: string;
  bodyText: string;
  tags: string[];
  status: WikiPageStatus;
  createdAt: string;
  updatedAt: string;
}

export interface SearchResult {
  id: string;
  slug: string;
  title: string;
  summary: string;
  excerpt: string;
  tags: string[];
  score: number;
}
