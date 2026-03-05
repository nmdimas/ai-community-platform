-- Create dedicated database for the news-maker-agent
SELECT 'CREATE DATABASE ai_news_maker'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'ai_news_maker')\gexec

GRANT ALL PRIVILEGES ON DATABASE ai_news_maker TO app;
