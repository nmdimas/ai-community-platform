"""APScheduler setup for crawl and cleanup jobs."""
import logging

from apscheduler.schedulers.background import BackgroundScheduler
from apscheduler.triggers.cron import CronTrigger

from app.database import SessionLocal
from app.models.models import AgentSettings

logger = logging.getLogger(__name__)

_scheduler: BackgroundScheduler | None = None


def _run_crawl_pipeline() -> None:
    from app.services.crawler import run_crawl
    from app.services.ranker import run_ranking
    from app.services.rewriter import run_rewriting

    logger.info("Starting crawl pipeline")
    crawled_items = run_crawl()
    ranked_items = run_ranking()
    rewritten_items = run_rewriting()
    logger.info(
        "Crawl pipeline finished: crawled=%d ranked_selected=%d rewritten_ready=%d",
        crawled_items,
        ranked_items,
        rewritten_items,
    )


def _run_cleanup() -> None:
    from app.services.crawler import run_cleanup
    logger.info("Starting cleanup job")
    run_cleanup()


def _get_settings() -> AgentSettings | None:
    db = SessionLocal()
    try:
        return db.query(AgentSettings).first()
    finally:
        db.close()


def start_scheduler() -> None:
    global _scheduler

    agent_settings = _get_settings()
    crawl_cron = agent_settings.crawl_cron if agent_settings else "0 */4 * * *"
    cleanup_cron = agent_settings.cleanup_cron if agent_settings else "0 2 * * *"

    _scheduler = BackgroundScheduler()
    _scheduler.add_job(
        _run_crawl_pipeline,
        CronTrigger.from_crontab(crawl_cron),
        id="crawl_pipeline",
        replace_existing=True,
    )
    _scheduler.add_job(
        _run_cleanup,
        CronTrigger.from_crontab(cleanup_cron),
        id="cleanup",
        replace_existing=True,
    )
    _scheduler.start()
    logger.info("Scheduler started (crawl: %s, cleanup: %s)", crawl_cron, cleanup_cron)


def stop_scheduler() -> None:
    global _scheduler
    if _scheduler and _scheduler.running:
        _scheduler.shutdown(wait=False)
        logger.info("Scheduler stopped")


def trigger_crawl_now() -> None:
    """Manually trigger crawl pipeline immediately."""
    import threading
    threading.Thread(target=_run_crawl_pipeline, daemon=True, name="news-crawl-manual").start()
    logger.info("Manual crawl trigger accepted")


def trigger_cleanup_now() -> None:
    """Manually trigger cleanup immediately."""
    import threading
    threading.Thread(target=_run_cleanup, daemon=True, name="news-cleanup-manual").start()
    logger.info("Manual cleanup trigger accepted")
