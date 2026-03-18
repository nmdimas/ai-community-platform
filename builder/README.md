# Builder Agent

Автономний мульти-агентний білдер для виконання задач через пайплайн.

## Як це працює

1. **Skill** створює файл задачі в `builder/tasks/todo/`
2. **Monitor** виявляє нову задачу та запускає worker
3. **Pipeline** (`builder/pipeline.sh`) виконує задачу через послідовність агентів:
   Architect → Coder → Validator → Tester → Summarizer
4. Результат — коміти на гілці `pipeline/<slug>`, summary в `builder/tasks/summary/`

## Встановлення (після git clone)

```bash
./builder/setup.sh
# або
make builder-setup
```

Скрипт створює `builder/tasks/` директорії (todo, in-progress, done, failed, summary, artifacts, archive).

Симлінки в `scripts/` для зворотної сумісності вже включені в репо.

## Структура

```
builder/
  README.md                     # цей файл
  setup.sh                      # створює gitignored директорії
  pipeline.sh                   # основний оркестратор агентів
  pipeline-batch.sh             # пакетний запуск задач
  pipeline-stats.sh             # статистика виконання
  skill/                        # Claude skill для створення задач
    SKILL.md
    references/example-task.md
  monitor/                      # TUI монітор
    pipeline-monitor.sh         # bash TUI (основний)
    pipeline-monitor-ink.sh     # Node.js wrapper
    ink/                        # React ink монітор
  tasks/                        # черга задач (gitignored, створюється setup.sh)
    todo/ in-progress/ done/ failed/ summary/ artifacts/ archive/
```

## Використання

### Створити задачу

Через Claude: скажіть "делегувати білдеру" або "на білдера".

Вручну: створіть `.md` файл в `builder/tasks/todo/`:

```markdown
<!-- priority: 1 -->
# Назва задачі

Опис що потрібно зробити.

## Validation

- PHPStan level 8 passes
- CS-Fixer passes
```

### Моніторинг

```bash
./builder/monitor/pipeline-monitor.sh
```

Клавіші: `[s]` старт, `[k]` зупинити, `[f]` повторити failed, `[+/-]` пріоритет, `[q]` вийти.

### Прямий запуск

```bash
# одна задача
./builder/pipeline.sh "Add feature X"
make pipeline TASK="Add feature X"

# пакетний запуск
./builder/pipeline-batch.sh builder/tasks/
make pipeline-batch FILE=builder/tasks/

# статистика
./builder/pipeline-stats.sh
```

## Конфігурація

| Змінна | За замовчуванням | Опис |
|--------|-----------------|------|
| `MONITOR_WORKERS` | `1` | Кількість паралельних workers |
| `MONITOR_AUTOSTART` | `true` | Автозапуск при появі задач |
| `MONITOR_LOG_RETENTION` | `7` | Днів зберігання логів |

## Зворотна сумісність

Симлінки в `scripts/` дозволяють старим посиланням працювати:

```
scripts/pipeline.sh           → builder/pipeline.sh
scripts/pipeline-batch.sh     → builder/pipeline-batch.sh
scripts/pipeline-stats.sh     → builder/pipeline-stats.sh
scripts/pipeline-monitor.sh   → builder/monitor/pipeline-monitor.sh
```
