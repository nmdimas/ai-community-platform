"""initial

Revision ID: 001
Revises:
Create Date: 2026-03-04

"""
import sqlalchemy as sa
from alembic import op
from sqlalchemy.dialects import postgresql

revision = "001"
down_revision = None
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.create_table(
        "news_sources",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("name", sa.String(128), nullable=False),
        sa.Column("base_url", sa.String(512), nullable=False),
        sa.Column("topic_scope", sa.String(64), nullable=False, server_default="ai"),
        sa.Column("enabled", sa.Boolean(), nullable=False, server_default=sa.text("true")),
        sa.Column("crawl_priority", sa.Integer(), nullable=False, server_default="5"),
        sa.Column("last_success_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("last_error_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("proxy_enabled_override", sa.Boolean(), nullable=True),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("name"),
    )

    op.create_table(
        "raw_news_items",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("source_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("source_url", sa.String(1024), nullable=False),
        sa.Column("canonical_url", sa.String(1024), nullable=True),
        sa.Column("title", sa.String(512), nullable=True),
        sa.Column("raw_text", sa.Text(), nullable=True),
        sa.Column("excerpt", sa.String(1024), nullable=True),
        sa.Column("published_at_source", sa.DateTime(timezone=True), nullable=True),
        sa.Column("language", sa.String(16), nullable=True),
        sa.Column("status", sa.String(32), nullable=False, server_default="new"),
        sa.Column("score", sa.Float(), nullable=True),
        sa.Column("dedup_hash", sa.String(64), nullable=False),
        sa.Column("crawl_run_id", postgresql.UUID(as_uuid=True), nullable=True),
        sa.Column("expires_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["source_id"], ["news_sources.id"]),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("dedup_hash"),
    )
    op.create_index("ix_raw_news_items_status", "raw_news_items", ["status"])
    op.create_index("ix_raw_news_items_dedup_hash", "raw_news_items", ["dedup_hash"])
    op.create_index("ix_raw_news_items_crawl_run_id", "raw_news_items", ["crawl_run_id"])
    op.create_index("ix_raw_news_items_expires_at", "raw_news_items", ["expires_at"])

    op.create_table(
        "curated_news_items",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("raw_news_item_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("title", sa.String(512), nullable=False),
        sa.Column("summary", sa.Text(), nullable=False),
        sa.Column("body", sa.Text(), nullable=False),
        sa.Column("language", sa.String(16), nullable=False, server_default="uk"),
        sa.Column("style_profile", sa.String(64), nullable=True),
        sa.Column("status", sa.String(32), nullable=False, server_default="draft"),
        sa.Column("reference_title", sa.String(512), nullable=True),
        sa.Column("reference_url", sa.String(1024), nullable=True),
        sa.Column("reference_domain", sa.String(128), nullable=True),
        sa.Column("published_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["raw_news_item_id"], ["raw_news_items.id"]),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("raw_news_item_id"),
    )
    op.create_index("ix_curated_news_items_status", "curated_news_items", ["status"])

    op.create_table(
        "agent_settings",
        sa.Column("id", sa.Integer(), autoincrement=True, nullable=False),
        sa.Column("ranker_prompt", sa.Text(), nullable=False),
        sa.Column("ranker_guardrail", sa.Text(), nullable=False),
        sa.Column("rewriter_prompt", sa.Text(), nullable=False),
        sa.Column("rewriter_guardrail", sa.Text(), nullable=False),
        sa.Column("crawl_cron", sa.String(64), nullable=False, server_default="0 */4 * * *"),
        sa.Column("cleanup_cron", sa.String(64), nullable=False, server_default="0 2 * * *"),
        sa.Column("raw_item_ttl_hours", sa.Integer(), nullable=False, server_default="72"),
        sa.Column("proxy_enabled", sa.Boolean(), nullable=False, server_default=sa.text("false")),
        sa.Column("proxy_url", sa.String(512), nullable=True),
        sa.Column("ranker_model", sa.String(128), nullable=False, server_default="gpt-4o-mini"),
        sa.Column("rewriter_model", sa.String(128), nullable=False, server_default="gpt-4o-mini"),
        sa.Column("updated_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.PrimaryKeyConstraint("id"),
    )

    op.create_table(
        "scheduler_runs",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("job_name", sa.String(64), nullable=False),
        sa.Column("started_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("finished_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("status", sa.String(32), nullable=False, server_default="running"),
        sa.Column("items_seen", sa.Integer(), nullable=False, server_default="0"),
        sa.Column("items_selected", sa.Integer(), nullable=False, server_default="0"),
        sa.Column("error_message", sa.Text(), nullable=True),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_index("ix_scheduler_runs_job_name", "scheduler_runs", ["job_name"])


def downgrade() -> None:
    op.drop_table("scheduler_runs")
    op.drop_table("agent_settings")
    op.drop_index("ix_curated_news_items_status")
    op.drop_table("curated_news_items")
    op.drop_index("ix_raw_news_items_expires_at")
    op.drop_index("ix_raw_news_items_crawl_run_id")
    op.drop_index("ix_raw_news_items_dedup_hash")
    op.drop_index("ix_raw_news_items_status")
    op.drop_table("raw_news_items")
    op.drop_table("news_sources")
