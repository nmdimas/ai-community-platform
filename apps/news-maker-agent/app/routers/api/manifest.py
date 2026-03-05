from fastapi import APIRouter

router = APIRouter()


@router.get("/api/v1/manifest")
def get_manifest() -> dict:
    return {
        "name": "news-maker-agent",
        "version": "0.1.0",
        "description": "AI-powered news curation and publishing",
        "capabilities": ["news.publish", "news.curate"],
        "a2a_endpoint": "http://news-maker-agent:8000/api/v1/a2a",
        "health_url": "http://news-maker-agent:8000/health",
    }
