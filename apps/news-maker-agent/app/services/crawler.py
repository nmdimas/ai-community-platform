"""Crawler adapter: fetches source pages and extracts article candidates."""
import hashlib
import logging
import uuid
from datetime import datetime, timedelta, timezone

import requests
import trafilatura

from app.config import settings
from app.database import SessionLocal
from app.models.models import AgentSettings, NewsSource, RawNewsItem, SchedulerRun

logger = logging.getLogger(__name__)

CRAWL_TIMEOUT = 20
USER_AGENT = "Mozilla/5.0 (compatible; AICommunityBot/1.0)"


def _fetch_html(url: str, proxy_url: str | None = None) -> str | None:
    proxies = {"http": proxy_url, "https": proxy_url} if proxy_url else None
    try:
        resp = requests.get(
            url,
            timeout=CRAWL_TIMEOUT,
            headers={"User-Agent": USER_AGENT},
            proxies=proxies,
        )
        resp.raise_for_status()
        return resp.text
    except Exception as exc:
        logger.warning("Failed to fetch %s: %s", url, exc)
        return None


def _extract_article(html: str, url: str) -> dict | None:
    """Use trafilatura to extract article content from HTML."""
    result = trafilatura.extract(
        html,
        url=url,
        include_links=False,
        include_images=False,
        output_format="python",
        with_metadata=True,
    )
    if not result:
        return None

    text = result.get("text", "") or ""
    title = result.get("title", "") or ""
    if len(text) < 100:
        return None

    return {
        "title": title[:512],
        "raw_text": text,
        "excerpt": text[:512],
        "canonical_url": result.get("url") or url,
        "language": result.get("language"),
        "published_at_source": None,
    }


def _dedup_hash(url: str) -> str:
    return hashlib.sha256(url.encode()).hexdigest()


def run_crawl() -> None:
    """Main crawl job: fetch all enabled sources and store raw items."""
    db = SessionLocal()
    run = SchedulerRun(job_name="crawl")
    db.add(run)
    db.commit()

    try:
        settings_row = db.query(AgentSettings).first()
        proxy_url = settings_row.proxy_url if settings_row and settings_row.proxy_enabled else None
        ttl_hours = settings_row.raw_item_ttl_hours if settings_row else 72
        expires_at = datetime.now(timezone.utc) + timedelta(hours=ttl_hours)

        sources = db.query(NewsSource).filter(NewsSource.enabled == True).order_by(NewsSource.crawl_priority.desc()).all()  # noqa: E712

        items_seen = 0
        crawl_run_id = uuid.uuid4()

        for source in sources:
            html = _fetch_html(source.base_url, proxy_url)
            if not html:
                source.last_error_at = datetime.now(timezone.utc)
                db.commit()
                continue

            # Extract links from the source page
            links = _extract_links(html, source.base_url)

            for link in links[:20]:  # cap per source
                dedup = _dedup_hash(link)
                exists = db.query(RawNewsItem).filter(RawNewsItem.dedup_hash == dedup).first()
                if exists:
                    continue

                article_html = _fetch_html(link, proxy_url)
                if not article_html:
                    continue

                article = _extract_article(article_html, link)
                if not article:
                    continue

                item = RawNewsItem(
                    source_id=source.id,
                    source_url=link,
                    canonical_url=article["canonical_url"],
                    title=article["title"],
                    raw_text=article["raw_text"],
                    excerpt=article["excerpt"],
                    language=article["language"],
                    published_at_source=article["published_at_source"],
                    dedup_hash=dedup,
                    crawl_run_id=crawl_run_id,
                    expires_at=expires_at,
                    status="new",
                )
                db.add(item)
                items_seen += 1

            source.last_success_at = datetime.now(timezone.utc)
            db.commit()

        run.items_seen = items_seen
        run.status = "completed"
        run.finished_at = datetime.now(timezone.utc)
        db.commit()
        logger.info("Crawl run complete: %d items seen", items_seen)

    except Exception as exc:
        logger.exception("Crawl run failed")
        run.status = "failed"
        run.error_message = str(exc)
        run.finished_at = datetime.now(timezone.utc)
        db.commit()
    finally:
        db.close()


def _extract_links(html: str, base_url: str) -> list[str]:
    """Extract article links from a source page using trafilatura."""
    try:
        from trafilatura.spider import focused_crawler
        # Use trafilatura's focused_crawler for link extraction
        _, links = focused_crawler(base_url, max_seen_urls=30, max_known_urls=50)
        return list(links)[:20]
    except Exception:
        return []


def run_cleanup() -> None:
    """Remove expired raw items."""
    db = SessionLocal()
    run = SchedulerRun(job_name="cleanup")
    db.add(run)
    db.commit()

    try:
        now = datetime.now(timezone.utc)
        deleted = (
            db.query(RawNewsItem)
            .filter(RawNewsItem.expires_at < now, RawNewsItem.status.in_(["new", "scored", "discarded", "expired"]))
            .delete(synchronize_session=False)
        )
        db.commit()

        run.items_seen = deleted
        run.status = "completed"
        run.finished_at = datetime.now(timezone.utc)
        db.commit()
        logger.info("Cleanup run complete: %d items removed", deleted)

    except Exception as exc:
        logger.exception("Cleanup run failed")
        run.status = "failed"
        run.error_message = str(exc)
        run.finished_at = datetime.now(timezone.utc)
        db.commit()
    finally:
        db.close()
