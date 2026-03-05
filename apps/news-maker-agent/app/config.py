from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    database_url: str = "postgresql://app:app@postgres:5432/ai_news_maker"
    litellm_base_url: str = "http://litellm:4000"
    litellm_api_key: str = "dev-key"
    ranker_model: str = "gpt-4o-mini"
    rewriter_model: str = "gpt-4o-mini"
    platform_core_url: str = "http://core"
    app_internal_token: str = "dev-internal-token"

    model_config = {"env_file": ".env"}


settings = Settings()
