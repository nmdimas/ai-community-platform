function required(name: string, fallback?: string): string {
  const value = process.env[name] ?? fallback;
  if (!value) {
    throw new Error(`Missing required environment variable: ${name}`);
  }

  return value;
}

export const config = {
  port: Number.parseInt(process.env.PORT ?? '3000', 10),
  databaseUrl: required('DATABASE_URL'),
  pgSchema: process.env.PG_SCHEMA ?? 'wiki_agent',
  openSearchUrl: required('OPENSEARCH_URL', 'http://opensearch:9200'),
  openSearchIndex: process.env.OPENSEARCH_INDEX ?? 'wiki_agent_pages',
  litellmBaseUrl: required('LITELLM_BASE_URL', 'http://litellm:4000'),
  litellmApiKey: required('LITELLM_API_KEY', 'dev-key'),
  llmModel: process.env.LLM_MODEL ?? 'minimax/minimax-m2.5',
  sessionSecret: required('SESSION_SECRET', 'wiki-agent-dev-session-secret'),
  adminUsername: process.env.ADMIN_USERNAME ?? 'admin',
  adminPassword: process.env.ADMIN_PASSWORD ?? 'test-password',
  publicBaseUrl: process.env.PUBLIC_BASE_URL ?? 'http://localhost:8090',
  internalBaseUrl: process.env.INTERNAL_BASE_URL ?? 'http://wiki-agent:3000',
};
