import uuid
from datetime import datetime

from pydantic import BaseModel


class NewsSourceRead(BaseModel):
    id: uuid.UUID
    name: str
    base_url: str
    topic_scope: str
    enabled: bool
    crawl_priority: int
    last_success_at: datetime | None
    last_error_at: datetime | None
    created_at: datetime

    model_config = {"from_attributes": True}


class NewsSourceCreate(BaseModel):
    name: str
    base_url: str
    topic_scope: str = "ai"
    enabled: bool = True
    crawl_priority: int = 5


class CuratedNewsItemRead(BaseModel):
    id: uuid.UUID
    title: str
    summary: str
    body: str
    language: str
    status: str
    reference_title: str | None
    reference_url: str | None
    reference_domain: str | None
    published_at: datetime | None
    created_at: datetime

    model_config = {"from_attributes": True}


class AgentSettingsRead(BaseModel):
    id: int
    ranker_prompt: str
    ranker_guardrail: str
    rewriter_prompt: str
    rewriter_guardrail: str
    crawl_cron: str
    cleanup_cron: str
    raw_item_ttl_hours: int
    proxy_enabled: bool
    proxy_url: str | None
    ranker_model: str
    rewriter_model: str
    updated_at: datetime

    model_config = {"from_attributes": True}


class SchedulerRunRead(BaseModel):
    id: uuid.UUID
    job_name: str
    started_at: datetime
    finished_at: datetime | None
    status: str
    items_seen: int
    items_selected: int
    error_message: str | None

    model_config = {"from_attributes": True}
