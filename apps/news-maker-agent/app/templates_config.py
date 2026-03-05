from pathlib import Path

from fastapi.templating import Jinja2Templates

# Templates are at <project_root>/templates/, this file is at <project_root>/app/
_TEMPLATES_DIR = Path(__file__).resolve().parent.parent / "templates"
templates = Jinja2Templates(directory=str(_TEMPLATES_DIR))
