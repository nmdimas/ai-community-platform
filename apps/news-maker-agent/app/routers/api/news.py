import uuid
from datetime import datetime, timezone
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database import get_db
from app.models.models import CuratedNewsItem, RawNewsItem, SchedulerRun
from app.schemas import CuratedNewsItemRead

router = APIRouter(prefix="/api/v1/news", tags=["news-api"])


@router.get("/ready", response_model=list[CuratedNewsItemRead])
def list_ready(db: Annotated[Session, Depends(get_db)]):
    return (
        db.query(CuratedNewsItem)
        .filter(CuratedNewsItem.status == "ready")
        .order_by(CuratedNewsItem.created_at.desc())
        .limit(50)
        .all()
    )


@router.get("/published", response_model=list[CuratedNewsItemRead])
def list_published(db: Annotated[Session, Depends(get_db)]):
    return (
        db.query(CuratedNewsItem)
        .filter(CuratedNewsItem.status == "published")
        .order_by(CuratedNewsItem.published_at.desc())
        .limit(50)
        .all()
    )


@router.get("/stats")
def get_stats(db: Annotated[Session, Depends(get_db)]):
    total_ready = db.query(CuratedNewsItem).filter(CuratedNewsItem.status == "ready").count()
    total_published = db.query(CuratedNewsItem).filter(CuratedNewsItem.status == "published").count()
    total_raw_pending = db.query(RawNewsItem).filter(RawNewsItem.status == "new").count()
    last_run = db.query(SchedulerRun).order_by(SchedulerRun.started_at.desc()).first()
    return {
        "ready": total_ready,
        "published": total_published,
        "raw_pending": total_raw_pending,
        "last_run": last_run.started_at.isoformat() if last_run else None,
    }


@router.post("/{item_id}/publish", response_model=CuratedNewsItemRead)
def publish_item(item_id: uuid.UUID, db: Annotated[Session, Depends(get_db)]):
    item = db.query(CuratedNewsItem).filter(CuratedNewsItem.id == item_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Item not found")
    if item.status not in ("ready", "draft"):
        raise HTTPException(status_code=422, detail=f"Cannot publish item with status '{item.status}'")
    item.status = "published"
    item.published_at = datetime.now(timezone.utc)
    db.commit()
    db.refresh(item)
    return item


@router.post("/{item_id}/reject", response_model=CuratedNewsItemRead)
def reject_item(item_id: uuid.UUID, db: Annotated[Session, Depends(get_db)]):
    item = db.query(CuratedNewsItem).filter(CuratedNewsItem.id == item_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Item not found")
    item.status = "rejected"
    db.commit()
    db.refresh(item)
    return item
