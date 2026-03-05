# Agent State Model And UI Hints

This document defines how agent states are represented in the admin UI and how operators should interpret each state.

## 1. Runtime Status (`enabled` / `disabled`)

Runtime status controls routing behavior in core.

| Status | Meaning | Routing behavior | Operator action |
|---|---|---|---|
| `enabled` | Agent is allowed to process platform traffic | Event Bus + Command Router may call the agent | Monitor health and violations |
| `disabled` | Agent is intentionally turned off | Event Bus + Command Router skip the agent | Enable only after config/provisioning checks |

### UI hint text

- `enabled`: "Агент увімкнений: події та команди будуть оброблятися."
- `disabled`: "Агент вимкнений: події та команди не маршрутизуються."

## 2. Health/Convention State (`health_status`)

Health state reflects discovery, convention checks, and polling outcomes.

| State | Meaning | Typical cause | Operator action |
|---|---|---|---|
| `healthy` | Agent is reachable and passes baseline conventions | Valid manifest + healthy endpoint | No action required |
| `degraded` | Agent is reachable but has convention violations | Missing optional contract fields, schema mismatch, partial compliance | Open violation details and fix contract issues |
| `unavailable` | Agent cannot be reached consistently | Network issue, container down, failing health endpoint | Check container/network and rerun discovery |
| `error` | Critical discovery/manifest error | Invalid manifest JSON, required field missing, parsing/registration failure | Fix manifest/endpoint contract immediately |
| `unknown` | State is not established yet | New/just-registered service, no successful discovery cycle yet | Run discovery or wait for next poll cycle |

### UI hint text

- `healthy`: "Агент доступний, конвенції пройдено, помилок не виявлено."
- `degraded`: "Виявлено порушення конвенцій. Натисніть badge для деталей."
- `unavailable`: "Агент тимчасово недоступний по мережі або не відповідає на health-check."
- `error`: "Критична помилка в маніфесті або discovery. Перевірте деталі."
- `unknown`: "Стан ще не визначений. Запустіть discovery або зачекайте health-check."

## 3. Admin UI Display Rules

- Admin MUST show both runtime status and health/convention state independently.
- Each state badge MUST include a short tooltip/hint.
- Degraded/error/unavailable badges SHOULD link to violation details when available.
- Admin MUST include a "state legend" block explaining all available states.

## 4. Test Expectations

When updating state rendering:

- Keep stable CSS selectors for badges:
  - runtime: `.badge-enabled`, `.badge-disabled`
  - health: `.badge-healthy`, `.badge-degraded`, `.badge-unavailable`, `.badge-error`, `.badge-unknown`
- Keep violation modal behavior for clickable degraded/error states.
- Keep `/admin/agents` column headers stable (`Статус`, `Здоров'я`) to avoid contract drift in test suites.

