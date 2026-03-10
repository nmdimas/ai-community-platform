# Центральний планувальник задач

## Огляд

Платформа включає централізований планувальник задач, що дозволяє будь-якому агенту реєструвати
періодичні або одноразові задачі. Планувальник працює як довготривалий Symfony Command
(`scheduler:run`) у Docker-сервісі `core-scheduler` та викликає скіли агентів через A2A протокол.

### Архітектура

- **Процес планувальника**: `php bin/console scheduler:run` — опитує базу даних кожні 10 секунд
- **Персистентність**: стан задач зберігається в таблиці `scheduled_jobs` PostgreSQL — не втрачається при перезапуску
- **Виклик**: задачі виконуються через `A2AClient::invoke($skillId, $payload, ...)` — той самий шлях, що й звичайні A2A виклики
- **Конкурентність**: `SELECT ... FOR UPDATE SKIP LOCKED` запобігає дублюванню виконання при кількох екземплярах планувальника
- **Graceful shutdown**: обробляє `SIGTERM`/`SIGINT` через `SignalableCommandInterface`

## Швидкий старт

Щоб агент реєстрував задачі в планувальнику, достатньо додати секцію `scheduled_jobs` до `manifest.json`:

```json
{
  "name": "my-agent",
  "version": "1.0.0",
  "scheduled_jobs": [
    {
      "job_name": "crawl_pipeline",
      "skill_id": "my_agent.trigger_crawl",
      "cron": "0 */4 * * *",
      "payload": {},
      "max_retries": 3,
      "retry_delay_seconds": 120,
      "timezone": "UTC"
    }
  ]
}
```

Після встановлення агента (`install`) задачі автоматично реєструються в планувальнику.

## Конфігурація manifest.json

### Поля `scheduled_jobs`

| Поле | Тип | Обов'язкове | За замовчуванням | Опис |
|------|-----|-------------|------------------|------|
| `job_name` | string | ✓ | — | Унікальне ім'я задачі в межах агента (макс. 128 символів) |
| `skill_id` | string | ✓ | — | A2A skill ID для виклику |
| `cron` | string | — | null | Crontab вираз (null = одноразова задача) |
| `payload` | object | — | `{}` | Аргументи, що передаються скілу |
| `max_retries` | integer | — | 3 | Максимальна кількість спроб перед dead-letter |
| `retry_delay_seconds` | integer | — | 60 | Секунди між спробами повтору |
| `timezone` | string | — | `"UTC"` | Часовий пояс для обчислення cron виразу |

### Формат cron виразу

Стандартний 5-польний формат crontab: `хвилина година день-місяця місяць день-тижня`

Приклади:
- `* * * * *` — щохвилини
- `0 * * * *` — щогодини
- `0 */4 * * *` — кожні 4 години
- `0 12 * * 1-5` — у будні дні опівдні
- `@daily` — раз на день (псевдонім)
- `@hourly` — раз на годину (псевдонім)

Використовує бібліотеку [`dragonmantank/cron-expression`](https://github.com/dragonmantank/cron-expression).

## Інтеграція з lifecycle агента

Задачі автоматично керуються як частина lifecycle агента:

| Подія | Дія |
|-------|-----|
| `install` | Зареєструвати всі `scheduled_jobs` з manifest (upsert) |
| `uninstall` | Видалити всі задачі агента |
| `enable` | Увімкнути всі задачі агента |
| `disable` | Вимкнути всі задачі агента |

## Політика повторів та dead-letter

1. При помилці: `retry_count` збільшується, `next_run_at` встановлюється на `now() + retry_delay_seconds`
2. Коли `retry_count >= max_retries`: задача вимикається (`enabled = false`) і логується як dead-lettered
3. Dead-lettered задачі можна повторно увімкнути вручну через Admin UI

## Catch-up політика

Якщо планувальник був зупинений і `next_run_at` в минулому:
- Задача виконується **один раз** негайно
- `next_run_at` обчислюється від поточного часу (без відтворення пропущених запусків)

## Адмін-панель

Перейдіть на `/admin/scheduler` для перегляду всіх запланованих задач.

| Колонка | Опис |
|---------|------|
| Agent | Агент, що зареєстрував задачу |
| Job | Ім'я задачі |
| Skill | A2A skill ID |
| Cron | Cron вираз (або `—` для одноразових) |
| Next Run | Запланований час наступного виконання |
| Last Run | Час останнього виконання |
| Status | `completed` / `failed` / `—` |
| Retry | Поточна/максимальна кількість спроб |
| Enabled | Кнопка перемикання |
| Actions | Кнопка "Run Now" для ручного тригера |

### Ручний тригер

Натисніть **▶ Зараз** щоб встановити `next_run_at = now()`. Планувальник підхопить задачу протягом 10 секунд.

### Увімкнення/вимкнення

Натисніть кнопку перемикання (✓/✗) щоб увімкнути або вимкнути конкретну задачу без впливу на інші задачі того ж агента.

## Docker сервіс

Планувальник працює як окремий сервіс у `compose.core.yaml`:

```yaml
core-scheduler:
  command: ["php", "bin/console", "scheduler:run"]
  restart: unless-stopped
  depends_on:
    - core
```

Політика перезапуску `unless-stopped` забезпечує автоматичне відновлення після збоїв.

## API ендпоінти

| Метод | Шлях | Опис |
|-------|------|------|
| `GET` | `/admin/scheduler` | Сторінка адмін UI |
| `POST` | `/api/v1/internal/scheduler/{id}/run` | Тригер задачі негайно |
| `POST` | `/api/v1/internal/scheduler/{id}/toggle` | Увімкнути/вимкнути задачу |

Обидва API ендпоінти вимагають автентифікацію `ROLE_ADMIN`.

## Схема бази даних

```sql
CREATE TABLE scheduled_jobs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    agent_name VARCHAR(64) NOT NULL,
    job_name VARCHAR(128) NOT NULL,
    skill_id VARCHAR(128) NOT NULL,
    payload JSONB NOT NULL DEFAULT '{}',
    cron_expression VARCHAR(64) DEFAULT NULL,
    next_run_at TIMESTAMPTZ NOT NULL,
    last_run_at TIMESTAMPTZ DEFAULT NULL,
    last_status VARCHAR(32) DEFAULT NULL,
    retry_count INTEGER NOT NULL DEFAULT 0,
    max_retries INTEGER NOT NULL DEFAULT 3,
    retry_delay_seconds INTEGER NOT NULL DEFAULT 60,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_scheduled_jobs_agent_job UNIQUE (agent_name, job_name)
);

CREATE INDEX idx_scheduled_jobs_enabled_next_run ON scheduled_jobs (enabled, next_run_at);
```
