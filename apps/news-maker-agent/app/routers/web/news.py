from typing import Annotated

from fastapi import APIRouter, Depends, Request
from fastapi.responses import HTMLResponse
from sqlalchemy.orm import Session

from app.database import get_db
from app.models.models import CuratedNewsItem
from app.templates_config import templates

router = APIRouter(tags=["web"])


@router.get("/", response_class=HTMLResponse)
def news_listing(request: Request, db: Annotated[Session, Depends(get_db)]):
    items = (
        db.query(CuratedNewsItem)
        .filter(CuratedNewsItem.status == "published")
        .order_by(CuratedNewsItem.published_at.desc())
        .limit(30)
        .all()
    )
    return templates.TemplateResponse(request, "web/news.html", {"items": items})
