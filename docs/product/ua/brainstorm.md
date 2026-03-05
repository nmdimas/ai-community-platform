Як учасник багатьох ком’юніті, я бачу повторювані болі майже в кожному чаті (Telegram як типовий приклад): авто-ком’юніті, кодінг, хобі, локальні міські групи.

Що зазвичай “ламається” в чатах:

Організація й структуризація ключових об’єктів ком’юніті: місця, послуги, перевірені контакти, інструкції, “де купити/зробити”.

Новини та апдейти: важливе губиться в стрічці, дублюється, важко знайти “що змінилось за тиждень”.

Витяг цінних знань із переписки: корисні відповіді розчиняються в тисячах повідомлень замість того, щоб ставати “енциклопедією ком’юніті”.

Захист і довіра: фрод/скам, спроби “втюхати”, прохання передплати, підозрілі акаунти, маніпуляції.

Ідея продукту
Хочеться мати “платформу агентів для ком’юніті” — щось на кшталт OpenClaw, але під чати:
кожна потреба = окремий агент/модуль, який можна підключати або вимикати під конкретне ком’юніті. Тобто підхід “як WordPress + плагіни”, де функціональність нарощується без переписування всього бота.

Приклади модулів/агентів:

“Каталог місць/сервісів” (з тегами, рейтингом, підтвердженнями)

“Новинний дайджест” (щодня/щотижня + без дублів)

“Енциклопедія знань” (Q&A, FAQ, best practices, посилання на треди)

“Антифрод/модерація” (сигнали ризику, попередження, шаблони реакцій)

Результат для ком’юніті: менше хаосу в стрічці, більше корисної структури, вища довіра і масштабованість — кожне ком’юніті збирає собі набір модулів під свої правила й культуру.

Давай тепер своримо PRD для MVP

Кожен агент (модуль) повинен мати окремий PRD та develop план

Ок — нижче PRD для MVP платформи, а також шаблон PRD для кожного агента + шаблон develop plan. Це можна одразу класти в hackathon-doc.

PRD: Community Agents Platform — MVP

1. Контекст і проблема

Ком’юніті-чати (Telegram тощо) швидко перетворюються на “стрічку”, де:

корисні знання губляться

новини дублюються

немає структури об’єктів (місця/послуги/контакти)

з’являється фрод/скам

2. Ціль MVP

Запустити платформу модульних агентів (як “WordPress + плагіни”), яка:

працює в одному чаті/ком’юніті

має базову “шину” подій + модульність

дозволяє підключати/вимикати агентів

дає перші цінності через 2–3 базові агенти

3. MVP scope (що точно робимо)

Core Platform

Інтеграція з одним каналом (Telegram-чат)

Архітектура “Agent Registry”:

список агентів, статус enabled/disabled

конфіг агента (JSON / UI-команди)

Подієва модель:

нове повідомлення

редагування/видалення

команда (/command)

Спільні сервіси:

storage (Postgres)

пошук (мінімально: full-text; опц: embeddings)

permissions: admin/moderator/user

Набір базових команд:

/agents список

/agent enable <name>

/agent disable <name>

/help

MVP Agents (мінімум 2, краще 3)

Knowledge Extractor / Community Wiki (MVP)

News Digest (MVP)

Anti-fraud Signals (MVP-lite)

4. Out of scope (MVP)

мульти-чат/мульти-тенант

веб-панель керування (достатньо команд в чаті)

складні ролі/ACL

повний “маркетплейс” агентів

складні інтеграції (Jira/Confluence/Google Drive)

5. Персони

Admin/Owner: хоче порядок, контроль модулів, швидкий результат

Moderator: хоче інструменти для безпеки та курирування знань

Member: хоче швидко знайти відповідь/рекомендацію без спаму

6. Основні User Stories

Admin: “Увімкнути агента новин” → бот починає робити дайджест

Admin: “Вимкнути антифрод” → агент перестає реагувати

Member: “знайти відповідь” → команда /wiki <запит> повертає релевантні записи

Moderator: “позначити повідомлення як корисне” → потрапляє у wiki

7. UX / Команди (MVP)

/agents → список агентів зі статусом

/agent enable wiki / /agent disable wiki

/wiki add (reply на повідомлення) → зберегти як знання

/wiki <query> → пошук по знанням

/digest now → згенерувати дайджест вручну (адмін)

/fraud why (reply) → пояснення сигналу (без токсичності)

8. Дані та зберігання (мінімальна схема)

communities(id, name, channel_id, created_at)

agents(id, community_id, name, enabled, config_json, created_at)

messages(id, community_id, platform_msg_id, user_id, text, ts, meta_json)

knowledge(id, community_id, title, body, tags[], source_msg_id, created_by, created_at)

digests(id, community_id, period_start, period_end, body, created_at)

fraud_signals(id, community_id, msg_id, score, reasons_json, created_at)

9. Логіка модульності

Кожен агент:

має manifest (name, version, потрібні permissions, команди, події)

має handler-и: onMessage, onCommand, onSchedule (опційно)

має власний config schema

10. Нефункціональні вимоги

Безпечність: не банити автоматично; тільки сигнали/рекомендації (MVP)

Прозорість: “чому так” (reasons)

Мінімальні затримки: реакція в чаті < 2–3 сек для простих команд

Логи/метрики: хоча б structured logs + healthcheck

11. Метрики успіху MVP

% корисних збережених знань / тиждень

використань /wiki та CTR на відповіді
дайджестів + реакції (емодзі/відповіді)
фрод-сигналів, підтверджених модератором 12) Ризики

“шум” від агента → потрібні ліміти (rate-limit, quiet mode)

довіра до антифроду → тільки сигнали, не вироки

якість wiki → потрібні модераторські команди “approve/edit”

Далі: PRD для кожного агента (шаблон)
PRD Template: Agent <Name> (MVP)

Goal

Users & Jobs-to-be-done

Scope (MVP)

Out of scope

Inputs (події, команди, дані)

Outputs (повідомлення в чат, записи в БД)

UX / Commands

Data model (таблиці/поля, які використовує агент)

Rules/Heuristics/LLM prompts (мінімально описати)

Failure modes (що робимо при помилках)

Success metrics

Acceptance criteria (чек-лист)

Develop plan (шаблон) для кожного агента
Develop Plan Template: Agent <Name>

Phase 0 — Design

manifest, config schema

команда/події, permission модель

тестові кейси (10–20 прикладів)

Phase 1 — Implementation

handler-и (message/command/schedule)

storage layer (таблиці/репозиторій)

інтеграція з core platform

Phase 2 — Quality

unit tests (парсер команд, scoring, формати)

integration tests (chat simulator / fixtures)

rate limiting + retries

Phase 3 — Rollout

enable by default? (ні, тільки адмін)

“quiet mode”

мінімальний monitoring

Definition of Done

AC виконані

логування

документація команд

демо-сценарій в чаті

Щоб не гальмувати: 3 PRD на агенти для MVP (заготовки)

Якщо хочеш — я одразу накину окремі PRD + develop plan для:

Wiki Agent

Digest Agent

Anti-fraud Agent (lite)

Скажи тільки: в MVP точно робимо всі 3 чи лише 2? (я можу зробити всі 3 без уточнень, але пріоритезація допоможе).

Давай додамо ось що. Коли ми піднімаємо агента ( він повинен мати стандартні тули та типи взаємодій)
Наприклад агент місьц СТО (агент може встановлюватись декілька разів) наприкла Місця СТО, місця для покатушок. По факту це один агент і той самий агент але дві інсталяції з різними інструкціями.

Далі платформа повина мати ще Веб морду, а кожен плагін може до веб морди додавати свої веб додатки.
наприклад класична сторінка, буде описом та правилами комюніті та кнопками доєднатися.

Агент місця, буде мати можливість підключати карту, з обєктом там базовим функціоналом,
Додати обєкт, додати коментарь до обєкта, зручно шукати. та можливо фільтрувати по основним обєктам. Або додавати наприклад фото чи відео до цього обєекта з коментарями.

Також повина бути веб морда адмін панелі. Де перша сторінка це можливо базовий дашборд , сторінка самої платформи і тд
Далі кожен модуль повинен давати можливість розширювати цю адмінку, та додавати розділи управління собою. А саме управління агентом (промти, меморі і тд, кращі практики управління та покращувати агента, та елементи управління данними, наприклад якщо це місця то список, хто додав, коли , та можливість швидкого редагування та модерації)

Кожен агент пропонує повинен бути в контейнері. (можливо навіть docker-compose) щоб можна було підключати БД які потрібні та допоміжні інструменти.

В нас виходить вже є три інтерфейса які повині бути жорсткими, а що за ними нам все одно (БД, мова програумування і тд)

1. інтерфейс як агент головний (платформи буде спілкуватися з профільним агентом)
2. як розширювати клієнтський веб
3. як розширювати адмін сайд

Давай по брейнштормим, можливо знайдемо кращі рішення, по критикуємо мою пропозицію , а потім сформуємо опис вже для PRD опрацювання

Круто, що ти сформулював “жорсткі інтерфейси” — це реально робить систему масштабованою. Але в твоїй пропозиції є кілька місць, де можна дуже легко перемудрити MVP і вбити хакатон. Давай пройдемося: (1) що добре, (2) де ризики, (3) як спростити, але зберегти майбутнє, (4) які саме 3 інтерфейси можна зафіксувати для PRD.

1. Що в твоїй пропозиції дуже сильне
   Multi-install агента (один код — багато інсталяцій)

Це must-have. “Places” як агент, який ставиться як:

“СТО”

“Місця для покатушок”

“Зарядки/EV”
…це ідеально.

Правильна модель: AgentDefinition (код) + AgentInstance (налаштований екземпляр).

Плагіни розширюють Web (public) і Admin

Так робить більшість успішних платформ (WordPress, Strapi plugins, Grafana plugins, Backstage plugins).

Контейнеризація агента

Тут теж логічно: агент може мати свої залежності, кеш, навіть свою БД. Але…

2. Критика і головні ризики
   Ризик №1: “Кожен агент в своєму контейнері + docker-compose”

Це тягне за собою:

оркестрацію, мережі, секрети, апдейти, логування

дебаг стає важчим

на MVP це може з’їсти весь час

Компроміс для MVP:

агенти як процеси/пакети в одному рантаймі (монорепа)

контейнеризацію залишити як deployment option (пізніше)

Або якщо прям хочеш контейнер: зроби один “agents-runner” контейнер, який підтягує плагіни як пакети.

Ризик №2: “Плагін додає свої веб-додатки (UI)”

Це супер, але якщо дозволити “будь-який фронт”, ти отримаєш:

хаос стилів/авторизації/роутінгу

безпеку (XSS, доступи)

складність інтеграції

Компроміс: плагін не “вбудовує свій Next.js”, а реєструє сторінки через контракт:

“ось React component/route”

або “ось iframe micro-frontend” (але це вже важче)

Для MVP найпростіше:

платформа має єдину веб-апку

плагін додає routes + sidebar items + API endpoints через manifest

Ризик №3: 3 інтерфейси одразу (Agent API + Client Web + Admin Ext)

Це правильно стратегічно, але для MVP можна зробити “тонку версію” кожного:

агент інтерфейс — повний (бо без нього нічого)

client web — мінімум (landing + “join” + 1 сторінка places)

admin ext — мінімум (ввімкнути/вимкнути + конфіг інстансу)

3. Пропозиція кращої архітектури (простішої, але “на виріст”)
   Ключові сутності

Plugin (Definition): назва, версія, capabilities

Plugin Instance: community_id, instance_key, config, enabled

Resources / Objects: дані, якими керує плагін (places, wiki entries, digests)

Події та “toolbox” для агента

Ти казав “стандартні тули та типи взаємодій” — це треба формалізувати:

Стандартні Tools (SDK)

storage: KV + relational (через платформу)

search: full-text + optional embeddings

scheduler: cron / periodic tasks

messaging: sendMessage, replyTo, reactions, moderation actions

permissions: can(user, action)

secrets/config: read-only

media: upload/link (фото/відео) через платформу

Так агентам не треба знати, що під капотом (Postgres чи що завгодно).

4. Твої “3 жорсткі інтерфейси” — як їх краще зафіксувати
   (1) Interface: Platform ↔ Agent (runtime contract)

Варіанти:

In-process plugin (найпростіше)

агент = модуль, який імпортується і реєструється

швидко, MVP-friendly

gRPC/HTTP між контейнерами (потужніше)

більше складності

потрібно discovery, auth, retries

Я б для MVP зробив #1, але спроєктував API так, ніби завтра це буде #2.

Контракт (MVP-версія):

manifest: name, version, commands, webRoutes, adminRoutes, permissionsNeeded

handlers:

onMessage(ctx, msg)

onCommand(ctx, cmd)

onSchedule(ctx, job)

ctx.tools: storage/search/messaging/scheduler/media

(2) Interface: Client Web extension

Мета: плагін додає сторінки для користувачів (public/community portal).

MVP контракт:

webRoutes[]: path + title + componentId

apiRoutes[]: endpoint + permission (public/auth)

плагін повертає “schema” UI, або реєструє React-компонент

Супер-спрощення для MVP:
плагін не приносить UI-код, а приносить:

page_type: "map" | "list" | "details"

JSON schema (fields, filters)
і платформа рендерить сторінку сама.
Це дуже “no-code-ish”, але геніально для швидкого запуску.

(3) Interface: Admin extension

Мета: плагін додає розділ керування собою: конфіг, промпти, дані, модерація.

MVP контракт:

adminSections[]: slug + title + permissions

adminActions[]: (approve/edit/delete/export)

configSchema: поля конфігу інстансу (назва, опис, правила, модератори, промпти)

Тут теж можна schema-first:
плагін каже “ось таблиця керування даними” (columns, filters), платформа рендерить CRUD.

5. Як “Places agent” виглядає в цій моделі

Один Plugin Definition: places
Дві інсталяції:

places#sto (інструкції: “додавай СТО, шиномонтаж, електриків”)

places#rides (інструкції: “додавай маршрути, точки старту, офроуд локації”)

ConfigSchema приклад:

display_name (“СТО” / “Покатушки”)

categories (array)

required_fields (name, location, description)

moderation_mode (open / approve_required)

map_provider (OSM/Google) — поки OSM

Data model (generic):

object(id, instance_id, type=place, title, geo, tags, created_by, created_at, status)

object_media(object_id, url, type)

object_comments(object_id, text, created_by)

6. “Краще рішення” для контейнерів (щоб і ти був щасливий, і MVP не помер)

Пропозиція: 2 рівні

MVP: один runtime, плагіни як модулі (швидко)

Next: “remote agents” через HTTP/gRPC + optional docker-compose templates

І важливо: навіть в MVP зроби manifest так, щоб він мав:

deployment: inprocess | remote

remote.endpoint (якщо треба)

7. Як це перетворити в PRD-опис (підготовка)

Ось як я би додав у PRD “Platform MVP” новий розділ:

Plugin System Requirements (MVP)

Support multiple instances of same plugin with different configs/prompts

Provide stable SDK tools (storage/search/messaging/scheduler/media/permissions)

Provide Web extension contract (schema-first або routes-first)

Provide Admin extension contract (config + CRUD moderation views)

Provide manifest-based registration and capability declaration

8. Що я би “побрізав” для хакатону (рекомендація)

Якщо це AI-хакатон і треба демо:

✅ Multi-install places

✅ Admin enable/disable + config UI

✅ Public web: landing + places map/list

⛔ Не робити контейнер per plugin (тільки один compose для всього)

⛔ Не робити “довільні фронтенди” від плагінів (тільки schema-first)

Як наступний крок я можу:

Сформувати оновлений PRD платформи MVP з цими 3 інтерфейсами як “hard contracts”

Зробити PRD + develop plan для Places plugin (multi-instance) як еталон

Потім по цьому ж шаблону — для Wiki/Digest/Anti-fraud

Сделаю це одразу в наступному повідомленні, якщо ок.

Чому я хочу контейнери, в нас команда різних людей. Хтось на пайтон пише, а хтось пише на PHP а хтось на TS
Хочеться щоб ми меньше синхронізувались і в кожно була свобода рішень.
Якщо буде адмінка або веб клієнт різним стилем і різний стек, не бачу проблем.

Давай подумаємо над тим як наприклад ми можемо додавати url як частина одного сервісу. наприклад
rivian-club.pp.ua (головна сторінка) rivian-club.pp.ua/map модуль карт. І там може бути свій стек, бо функціонально це складна частина.

подібно і адмінка. Але в нас головне щоб безпека не розсипалась. Думаю треба ввести JWT токен, і платформа його вміє генерувати, оновлювати, та валідувати. А всі інші модулі будуть робити запити на головний модуль всередені docker network на валідацію, якщо ні то вести на сторінку логіну admin.rivian-club.pp.ua/login

Я знаю що можна логінитись телеграмом. А чи можна логінитись вайбером чи вотцапом?

Обробка тривала 15s

Твої аргументи за контейнери — дуже валідні: різні мови/стеки, мінімум синхронізації, “власність” модулів командою. Це якраз той випадок, де microservices + plugin контракт має сенс.

Нижче — як це зробити так, щоб:

модулі могли бути будь-яким стеком (PHP/TS/Python),

працювали під одним доменом і роутами (/map),

безпека не “розсипалась”,

і це лягло в PRD як чіткі інтерфейси.

1. Роутінг під одним доменом з різними стеками

Те, що ти описав:

rivian-club.pp.ua/ — головний сайт (core web)

rivian-club.pp.ua/map — модуль карт (окремий стек/контейнер)

admin.rivian-club.pp.ua/ — адмінка (core admin)

admin.rivian-club.pp.ua/places — адмін-розділ places (може бути інший контейнер)

Це робиться стандартно через reverse proxy (Nginx / Traefik / Caddy) з path-based routing:

/map → контейнер places-web (або map-service)

/api/places/\* → контейнер places-api

решта → core web / core api

Це супер підходить для “складної” частини (карта), яка потребує свого фронту/беку.

2. Безпека: як не робити “валідацію токена через головний сервіс на кожен реквест”

Твоя ідея “модулі питають у core validate(jwt)” працює, але є мінуси:

latency + залежність: core впав → вся система 401/500

важко масштабується

DoS по validate endpoint

Краще (і стандартніше) зробити так:

Рекомендована схема (OIDC-lite)

Core Auth видає JWT (access token) і має endpoint з публічними ключами JWKS.

Кожен модуль валідує JWT локально, використовуючи public key з JWKS (кешує).

Для “живих” сесій і відкликання:

короткий access token (5–15 хв)

refresh token (довше) або сесійна cookie

Це типова практика для мікросервісів; OIDC/JWKS саме для цього і придумано.

Де валідувати JWT

Варіанти:

на gateway (Nginx/Traefik): центрально відсікає неавторизовані запити до приватних сервісів

в кожному сервісі: або разом із gateway (defense in depth)

3. Як зробити логін і SSO для admin.\* та модулів

Ти написав: “якщо не валідно — вести на /login”. Це норм, але треба визначитись хто редіректить:

Найчистіше для веба:

admin.\* — cookie-based session (HttpOnly, SameSite=Lax/Strict)

gateway пропускає до адмін-роутів тільки якщо є валідна session cookie

інакше редірект на admin.../login

JWT лишається для API/m2m або SPA-частин.

Комбо, яке працює майже завжди:

для браузера: session cookie

для API: JWT Bearer

4. Контракт “плагін додає URL” (те що ти просиш)

Це треба зафіксувати як частину PRD.

Plugin Manifest (ідея)

Плагін реєструє:

web_routes:

/map → places-web (service + port)

/places → places-public-web (опційно)

admin_routes:

/places → places-admin-web

api_routes:

/api/places/\* → places-api

permissions: що треба (admin/moderator/user)

Core платформа:

читає manifest (наприклад, з каталогу або через endpoint),

конфігурує gateway (на MVP — статичний конфіг; далі — динаміка),

надає Auth/JWKS.

5. “Жорсткі 3 інтерфейси” у твоїй контейнерній моделі

Ти сам правильно сформулював. Я б записав так:

1. Agent Runtime Interface (platform ↔ agent)

події/команди (webhook або message bus)

SDK tools (storage/search/scheduler/messaging)

healthcheck + metrics

instance config (один агент — багато інстансів)

2. Web Extension Interface (public)

manifest оголошує web_routes

правила авторизації (public / logged-in)

contract щодо assets (CSP, CORS, headers)

3. Admin Extension Interface (admin)

manifest оголошує admin_routes

вимога auth (admin/moderator)

CRUD/модерація через API або власний UI

6. Про “логін через Telegram / Viber / WhatsApp”
   Telegram

Так, для вебсайтів є офіційний Telegram Login (widget/flow). Є і класичний widget, і новіший flow, описані в їх документації/блозі.

WhatsApp

Тут важливий нюанс: “Login with WhatsApp” як стандартний OAuth-провайдер для сайтів (як Google/GitHub) — не виглядає як офіційна універсальна фіча для кінцевих користувачів. Натомість у WhatsApp/Meta є підходи типу:

підтвердження користувача через OTP в WhatsApp (як “канал доставки коду”) — це офіційно обговорюється в контексті auth templates/best practices.

OAuth у WhatsApp часто стосується підключення WhatsApp Business акаунтів/інтеграцій, а не “SSO для юзера”.

Тобто: “увійти як юзер сайту своїм WhatsApp” — скоріше робиться як phone login (OTP), де WhatsApp лише канал доставки (або взагалі SMS).

Viber

У Viber є API/боти, але це не означає, що є універсальний “Login with Viber” для сторонніх сайтів як SSO. Документація про “authentication token” стосується авторизації бота до API, а не входу юзера на сайт.

Практичний висновок для PRD MVP:

✅ Login via Telegram (найпростіше і “нативно” для ком’юніті)

✅ Альтернатива: phone OTP (SMS/WhatsApp як канал — опційно пізніше)

⛔ Не закладати Viber/WhatsApp як “SSO provider” у MVP, якщо немає чіткого офіційного user-login flow

7. Пропозиція “як краще” (з критикою твоєї схеми) — коротко

Твоя схема правильна по духу, але я б змінив 1 річ:

Не робити validate endpoint як must на кожен реквест.
Замість цього:

core auth видає JWT

модулі валідять по JWKS локально

gateway може робити pre-check для адмін-роутів

Це дасть і безпеку, і незалежність команд.

Як наступний крок, я можу:

дописати в PRD платформи розділ “Containerized plugin architecture” (manifest, routing, auth/JWKS, multi-instance)

і зробити окремий PRD для Places Plugin (public map + admin CRUD + media) вже в цій архітектурі.

OIDC/JWKS - супер
Nginx/Traefik - виглядає що траефік топ, і виходить що авторизація і секюріті мінімальні в самому модулі?
погоджуюсь з усіма коментарями який ти щойно видав.

Давай тоді опишемо протокол взаємодії з агентами.

Нижче — протокол взаємодії Platform ⇄ Agent у твоїй контейнерній моделі (Traefik + OIDC/JWKS). Я зробив його так, щоб:

агент можна було писати на будь-якій мові

агент міг мати багато інстансів (СТО/покатушки)

платформа залишалась центром безпеки (OIDC, JWKS, RBAC)

модулі мали мінімум “сек’юріті коду”, але не були totally blind (defense-in-depth)

0. Компоненти в системі

Core Platform

Auth service (OIDC-lite): видає JWT, JWKS endpoint

Platform API: керування ком’юніті, агентами, інстансами, подіями

Event Dispatcher: доставляє події агентам (webhook або queue)

Shared services: storage/search/media/scheduler (як API)

Gateway (Traefik)

routing /map, /api/_, admin._

auth middleware (перевірка JWT/сесії) + RBAC на рівні роутів

forward headers: user/roles/community/instance

Agent (Plugin Service)

один контейнер або набір контейнерів на плагін

має manifest + handlers

(опційно) власна БД/кеш

1. Основна ідея протоколу

Платформа спілкується з агентом через 3 канали:

Discovery/Registration (manifest) — хто ти і що вмієш

Event Delivery (webhooks) — повідомлення/команди/cron/модерація

Tool Access (platform APIs) — агент читає/пише дані, шле відповіді, працює з медіа, search, etc.

Для MVP рекомендую webhooks (простий HTTP). Queue (NATS/Kafka/Rabbit) — можна додати пізніше без ламання контракту.

2. Discovery: Plugin Manifest Protocol
   2.1 Endpoint

Агент обов’язково піднімає:

GET /.well-known/plugin.json

2.2 Manifest schema (v1)
{
"schema_version": "1.0",
"plugin": {
"id": "places",
"name": "Places",
"version": "0.1.0",
"description": "Catalog of places with map/list/detail pages"
},
"capabilities": {
"events": ["message.created", "command.invoked", "schedule.tick"],
"web_routes": [
{ "path": "/map", "visibility": "public" }
],
"admin_routes": [
{ "path": "/admin/places", "role": "moderator" }
],
"api_routes": [
{ "path_prefix": "/api/places", "auth": "jwt", "role": "member" }
],
"commands": [
{ "name": "place.add", "description": "Add new place via chat" },
{ "name": "place.search", "description": "Search places" }
]
},
"instance_model": {
"supports_multiple_instances": true,
"config_schema": {
"type": "object",
"properties": {
"display_name": { "type": "string" },
"categories": { "type": "array", "items": { "type": "string" } },
"moderation_mode": { "type": "string", "enum": ["open", "approve_required"] }
},
"required": ["display_name"]
}
},
"health": {
"healthcheck_path": "/healthz",
"readycheck_path": "/readyz"
}
}
2.3 Як платформа це використовує

читає manifest

реєструє плагін (definition)

дозволяє створювати instances

конфігурує Traefik routes (статично в MVP або через provider API далі)

3. Auth між Platform і Agent
   3.1 Service-to-service auth

Потрібно 2 види токенів:

User JWT (OIDC) — коли користувач заходить на /map чи викликає /api/places

Platform-to-Agent token — коли платформа пушить event’и (webhook)

Варіант для MVP (простий і надійний)

Platform підписує webhook HMAC (shared secret per plugin)

додає X-Platform-Timestamp і X-Platform-Signature

агент перевіряє підпис, без OIDC у себе

(Це часто швидше і простіше за mTLS на хакатоні.)

Варіант “дорослий”

mTLS між сервісами або OAuth2 client credentials

але це можна додати пізніше

4. Event Delivery Protocol (Webhooks)
   4.1 Endpoint агента

POST /events

4.2 Загальний формат event envelope
{
"event_id": "evt_01HXYZ...",
"event_type": "message.created",
"occurred_at": "2026-03-04T10:31:05Z",
"community": {
"id": "com_123",
"platform": "telegram",
"chat_id": "-1001234567890"
},
"actor": {
"user_id": "tg_9988",
"display_name": "Dmytro",
"roles": ["member"]
},
"agent_instance": {
"plugin_id": "places",
"instance_id": "inst_sto",
"config_version": 12
},
"payload": { }
}
4.3 Події (MVP мінімум)

message.created

"payload": {
"message": {
"id": "tg_msg_123",
"text": "Порадьте СТО по Rivian",
"reply_to": null,
"attachments": []
}
}

command.invoked

"payload": {
"command": {
"name": "place.add",
"args": { "name": "СТО X", "lat": 50.45, "lng": 30.52 },
"source_message_id": "tg_msg_124"
}
}

schedule.tick

"payload": {
"job": { "name": "daily_digest", "scheduled_for": "2026-03-04T08:00:00Z" }
}

(опційно) moderation.action

"payload": {
"action": "approve",
"target": { "type": "place", "id": "place_77" }
}
4.4 Відповідь агента на подію

Два підходи:

A) Async only (рекомендовано)

Agent відповідає 200 OK швидко, а далі викликає Tool APIs (sendMessage, storeKnowledge, etc).

B) Hybrid (можна для MVP)

Agent може повернути “instructions”:

{
"actions": [
{
"type": "chat.sendMessage",
"to": "reply",
"text": "Знайшов 3 СТО. Хочеш на мапі чи списком?"
}
]
}

Платформа виконає ці actions сама.

Я б заклав обидва, але в MVP реалізував A, а B — як optional.

5. Tool API Protocol (агент → платформа)

Платформа дає агентам єдиний набір API, щоб не залежати від БД/інфраструктури.

5.1 Мінімальний набір tool endpoints

POST /tool/chat/sendMessage

POST /tool/chat/addReaction

POST /tool/storage/kv/get / set

POST /tool/storage/sql/query (або конкретні CRUD endpoints)

POST /tool/search/query

POST /tool/media/upload (presigned URL)

POST /tool/audit/log

Auth для tool API:

service token (plugin-level) або mTLS

optional scoping: plugin_id, instance_id

5.2 Приклад sendMessage
{
"community_id": "com_123",
"reply_to_message_id": "tg_msg_124",
"text": "Додав СТО ✅. Хочеш додати фото/коментар?"
} 6) Multi-instance: як платформа “адресує” правильний інстанс

У кожній події платформа передає:

agent_instance.instance_id

agent_instance.config_version

Агент має:

або зберігати config локально і синхронізувати по version

або тягнути актуальний config з platform tool API:

GET /tool/instances/{instance_id}/config

Як визначається інстанс для chat-команди

У чаті це може бути:

окремі команди /sto add ... і /rides add ...

або контекст (чат “СТО” vs чат “покатушки”)

або “активний інстанс” у цьому чаті

Для MVP найпростіше: instance прив’язаний до community/chat.

7. Надійність: retries, idempotency, rate limit
   7.1 Idempotency

event_id унікальний

агент має зберігати event_id processed (хоча б KV)

повторна доставка не має дублювати дії

7.2 Retries

платформа: 3–5 retries з backoff

якщо 5xx/timeout — ретраїмо

якщо 4xx — не ретраїмо (помилка контракту/валідації)

7.3 Dead-letter

події, що не доставились, падають у DLQ (таблиця failed_events)

в адмінці можна “replay”

8. Health/Readiness протокол

Агент має:

GET /healthz → 200 якщо процес живий

GET /readyz → 200 якщо готовий обробляти (прогріті ключі/JWKS, міграції і тд)

9. Мінімальні вимоги безпеки для модулів (щоб “не розсипалось”)

Навіть якщо Traefik робить основне:

агент має перевіряти X-Platform-Signature на /events

агент має не довіряти ролям з хедерів без JWT (для web/api)

CSP/headers на web routes (через gateway політики)

10. Пакет “Protocol v1” для PRD

Як назва: CAP (Community Agent Protocol) v1

Manifest: /.well-known/plugin.json

Events: POST /events + envelope + types

Tools: /tool/\* API

Health: /healthz, /readyz

Security: HMAC webhook signing + OIDC/JWKS for user traffic

Якщо ок, наступним повідомленням я:

Зроблю коротку специфікацію CAP v1 (як “RFC документ” — чітко, без води)

Дам приклад для Places plugin: manifest + що він слухає/які тулзи викликає

Додам розділ “Traefik routing rules” (path mapping) під цей протокол

а на скільки це дійсно визначений протокол?
наприклад a2a MCP виглядають як протоколи які визначені

Те, що я щойно описав (manifest + webhooks + tool API + health + HMAC/JWKS) — не “визнаний стандарт” на рівні MCP чи A2A. Це радше добре відпрацьований патерн (plugin contract / event-driven webhooks / OIDC для auth), який ти можеш формалізувати як власний протокол (умовно “CAP v1”) і зробити його “визначеним” для твоєї платформи.

А от OIDC/JWKS — це якраз стандартизована частина (OAuth2/OIDC, JWKS). А решта (manifest/events/tools) — стандартом не є, але дуже нормально лягає в “RFC-стиль” документа і стає протоколом у вашому проєкті.

Чому MCP/A2A “виглядають визначеними”

Бо вони:

мають публічну специфікацію

чіткі схеми повідомлень

зрозумілі семантики “tools/resources/transport”

інколи референс-імплементації

Що робити, якщо ти хочеш “справжній протокол”, а не “наш API”

Є 3 хороші варіанти.

Варіант A — “Наш протокол, але формально”

Те, що ми описали, оформлюємо як CAP v1 Spec:

версії

JSON Schema для manifest і events

правила сумісності

idempotency, retries

security профіль (HMAC + OIDC)

conformance tests (набір запитів/відповідей)

Після цього це реально стає визначеним протоколом у вашому світі.

Варіант B — Використати існуючий transport/shape: CloudEvents + OpenAPI

Щоб не вигадувати велосипед:

Events оформлюємо як CloudEvents (це стандарт для event envelope)

Tool API описуємо OpenAPI

Manifest лишається вашим, але теж по JSON Schema

Тоді у вас частина протоколу “визнана” одразу.

Варіант C — Підтримати MCP/A2A як “першокласний” інтерфейс агентів

Це цікаво, але треба тверезо:

MCP добре описує “tool calling / resources” у зв’язці з LLM-клієнтом.
У вас же платформа — це host, а агенти — плагіни, і вам потрібні ще:

multi-instance

web/admin route extensions

чат-евенти/модерація/шедулінг
Це виходить за межі MCP.

A2A (залежно від конкретної реалізації, бо їх кілька) зазвичай про agent-to-agent взаємодію.
Вам потрібен platform-to-agent plugin contract + UI extensions.

Реалістичний підхід: не “замість”, а “паралельно”:

CAP v1 як platform plugin protocol

MCP endpoint optional всередині агента для “LLM-tooling”, якщо комусь зручно

Моя рекомендація для хакатону і майбутнього

CAP v1 як ваш основний протокол (бо вам потрібні web/admin extensions і multi-instance)

Взяти CloudEvents для envelope, щоб було “standard-looking”

OIDC/JWKS — вже стандарт, лишаємо

MCP — як опційний internal-інтерфейс агента (не обов’язковий)

Як відповісти на твоє питання прямо

Наскільки це визначений протокол?
На даний момент — це дизайн контракту, який стане протоколом, якщо ви його:

зафіксуєте у spec (CAP v1)

додасте schema + tests + версіонування

Чи рівень MCP/A2A?
Як “публічний індустріальний стандарт” — ні.
Як “визначений протокол вашої платформи” — так, і це нормальна практика.

Якщо хочеш, я наступним повідомленням напишу CAP v1 Spec у стилі “короткий RFC”:

терміни

manifest schema

event schema (CloudEvents-совісний)

tool API мінімум

security профіль

conformance checklist (що має пройти агент, щоб вважатись сумісним)

Давай ще раз проговоримо, мені здається що протокл для Admin, протокол для WEB та API це один протокол (умовно скажмо CAP v1)
а ось агенти між собою спілкуються бо стандартним протоколам.
Коли наприклад іде щось на ручну модерацію, то такий функціонал може мігрувати наприкад в API.

А ось наприклад коли хтось спитає де знаходиться запобіжник в rivian, то головний (core) агент може спитати в агента енцоклопедія комюніті, а той через RAG знайде інформацію і видасть відповідь Головному агенту (core) а той в свою чергу вже відповість через telegram bot одразу в чат.

Так, твоя рамка виглядає дуже правильною і “чистою”:

CAP v1 = єдиний протокол плагінів платформи (і для Web/Admin/UI розширень, і для API, і для подій/інстансів).

Agent↔Agent = окремий шар, бажано на стандартних протоколах (A2A / MCP / HTTP), де core-agent оркеструє інших.

Функції можуть “мігрувати” між шарами: якщо щось стає платформеним (наприклад модерація), воно переїжджає в CAP API, а плагіни лише підключаються.

Нижче я “проговорю” це як архітектуру і дам конкретний протокол для core-agent → encyclopedia-agent кейсу.

1. Два шари протоколів
   Шар A: CAP v1 (Platform Plugin Protocol)

Це контракт “як плагін підключається до платформи”.

В CAP v1 входить все, що ти назвав:

Web extensions (routes під основним доменом)

Admin extensions (sections + керування даними/конфігом)

API routes (публічні/приватні endpoints)

Events (message.created / command.invoked / schedule.tick)

Multi-instance (один плагін — багато інстансів)

Security (OIDC/JWKS + service auth + RBAC)

(Опційно) модерація як platform capability

Це дійсно один протокол — просто різні “capabilities” всередині.

Шар B: Agent-to-Agent Protocols (Standard)

Це коли “розумні агенти” (LLM/RAG/аналіз) між собою спілкуються незалежно від платформи.

Тут можна використовувати:

A2A (якщо ви його обираєте як стандарт для агентів)

MCP (для tool/resource доступу в рамках агента/оркестратора)

або просто HTTP/gRPC з чіткими JSON схемами (в MVP)

Важливо: ці агенти можуть взагалі не мати Web/Admin routes. Вони “обчислювальні”. Платформа їх може не бачити напряму — тільки core-agent.

2. Роль core-agent (оркестратор)

Core-agent = “мозок платформи в чаті”. Він:

приймає подію з платформи по CAP (наприклад message.created)

вирішує, що робити: відповісти сам, або викликати інші агенти

звертається до інших агентів по A2A/MCP/HTTP

збирає відповідь і відправляє у чат через platform tool API

Тобто core-agent — це bridge між CAP світом (плагіни/інстанси/UI) і агентним світом (A2A/MCP).

3. Приклад: “де запобіжник у Rivian?” → RAG у Encyclopedia Agent
   Потік

Telegram повідомлення → Platform → CAP event → Core-agent

Core-agent робить “intent routing”: це питання про знання → викликає Encyclopedia-agent

Encyclopedia-agent робить RAG (vector search, docs, snippets), формує відповідь

Core-agent:

перевіряє формат/тон

додає попередження/уточнення (“залежить від року/моделі”)

відповідає у чат

Чому core-agent відповідає, а не encyclopedia-agent напряму?

Бо:

є єдиний голос/стиль ком’юніті

core-agent може звести відповіді з кількох агентів

централізований rate-limit, антиспам, модерація

простіше керувати політиками (що можна відповідати/як)

4. Як виглядає протокол Agent↔Agent для цього кейсу (мінімальний стандарт)

Навіть якщо ви потім перейдете на A2A, в PRD можна зафіксувати логічний контракт:

Request (Core → Encyclopedia)

POST /a2a/query (умовна назва; transport не важливий)

{
"request_id": "req_123",
"community_id": "com_123",
"user_context": {
"language": "uk",
"roles": ["member"]
},
"query": {
"text": "де знаходиться запобіжник в rivian?",
"entities": { "vehicle": "Rivian", "model": "R1S", "year": null }
},
"constraints": {
"max_tokens": 400,
"citations_required": true,
"answer_style": "chat_short"
}
}
Response (Encyclopedia → Core)
{
"request_id": "req_123",
"answer": {
"text": "У Rivian R1S блоки запобіжників зазвичай розташовані ...",
"confidence": 0.72,
"followups": [
"Який рік випуску і R1S чи R1T?",
"Потрібен салонний чи підкапотний блок?"
]
},
"evidence": [
{
"title": "Community Wiki: Electrical / Fuses",
"source": "community_knowledge",
"snippet": "…",
"link": "/wiki/electrical/fuses",
"score": 0.81
}
],
"moderation": {
"needs_review": false,
"reasons": []
}
}

Core-agent вже вирішує: чи ставити follow-up питання, чи відповідати одразу.

5. Де тут “ручна модерація” і як вона може мігрувати в API

Ти правий: ручна модерація — це platform concern (CAP), а не agent concern.

Тобто:

Encyclopedia-agent може виставити needs_review=true

Але черга модерації, UI, approve/reject — це CAP Admin capability:

/admin/moderation/queue

POST /api/moderation/approve

логування, ролі, аудит

Агенти лише додають “signals/metadata”, а платформа реалізує workflow.

6. Як це формально описати в PRD (одним абзацом)

CAP v1 є єдиним протоколом інтеграції модулів із платформою, включно з web/admin/API розширеннями, подіями, multi-instance та security. Інтелектуальні агенти взаємодіють між собою через стандартні agent-to-agent протоколи (A2A/MCP/HTTP), при цьому core-agent виконує роль оркестратора та єдиної точки відповіді в чаті. Модераційні workflow реалізуються платформою через CAP API та admin UI, а агенти постачають лише сигнали/чернетки/обґрунтування.

Якщо ок — наступним кроком я запропоную “CAP v1 capability matrix” (що входить в протокол як модулі: Events, WebRoutes, AdminRoutes, ApiRoutes, Moderation, Tools) і окремо “A2A layer contract” (мінімальний набір endpoint-ів для agent↔agent), щоб це можна було прямо вставити в PRD.

Давай визначимо тоді один стандартний протокол, щоб для MVP не розсіюватись з різними підходами, давай виберемо такий які легше та гнучкий, в нашому кейсі як для довгих відповідей так і для швидких, так і в кейсах коли можливо з уточненнями

Обробка тривала 19s

Так — для MVP краще вибрати один “agent↔agent” стандарт і не міксувати підходи. У вашому кейсі (короткі відповіді, довгі, уточнення, інколи стрімінг) найкраще лягає:

Обираємо: A2A (Agent2Agent) over HTTP як єдиний протокол agent↔agent для MVP

Чому саме він:

Саме для agent↔agent взаємодії (на відміну від MCP, який більше про “host↔tools/resources” у JSON-RPC).

Нейтральний до мови/стека: HTTP+JSON легко робити на PHP/TS/Python.

Добре підходить для вашого патерну “core-agent оркеструє спеціалізовані агенти”.

MCP при цьому можна лишити “внутрішньо” для агента (опційно) — але не брати як agent↔agent протокол, щоб не розмазуватися. MCP дійсно формалізований як JSON-RPC 2.0 і має стандартні транспорти (stdio / streamable HTTP), але його роль інша.

A2A MVP профіль: мінімальна, але гнучка специфікація

Нижче — “A2A-MVP profile” (те, що ви фіксуєте як один стандарт на хакатон).

1. Discovery (щоб core-agent знав, як викликати агента)

GET /.well-known/a2a.json

{
"agent_id": "community-encyclopedia",
"version": "0.1",
"capabilities": {
"modes": ["qa", "rag"],
"supports_streaming": true,
"max_context_chars": 20000
}
} 2) Єдиний endpoint для запитів

POST /a2a/v1/request

Request envelope
{
"request_id": "req_123",
"trace_id": "tr_abc",
"conversation_id": "conv_777",
"community_id": "com_123",
"caller": { "agent_id": "core-agent" },

"intent": "qa.rag",
"input": {
"text": "де знаходиться запобіжник в rivian?",
"lang": "uk",
"entities": { "brand": "Rivian", "model": "R1S", "year": null }
},

"response_mode": {
"type": "sync",
"max_tokens": 500
},
"constraints": {
"need_citations": true,
"allow_followups": true
}
} 3) Відповідь має 3 стани (цього достатньо для MVP)

A) completed — агент дав відповідь
B) needs_clarification — треба уточнення (ваш кейс!)
C) queued / in_progress — довга задача (опційно)

A) completed
{
"request_id": "req_123",
"status": "completed",
"answer": {
"text": "…відповідь…",
"confidence": 0.74
},
"evidence": [
{ "title": "Community Wiki / Electrical", "snippet": "…", "url": "/wiki/electrical/fuses" }
]
}
B) needs_clarification (ключове для ваших “уточнень”)
{
"request_id": "req_123",
"status": "needs_clarification",
"questions": [
{ "id": "q1", "text": "Яка модель: R1S чи R1T?" },
{ "id": "q2", "text": "Який рік випуску?" }
],
"partial_answer": {
"text": "Зазвичай є кілька блоків запобіжників… (деталі залежать від моделі/року)."
}
}
C) queued/in_progress (для дуже довгих відповідей)
{
"request_id": "req_123",
"status": "queued",
"job_id": "job_999",
"eta_sec": 10
} 4) Стрімінг (опційно, але класно для довгих відповідей)

Якщо supports_streaming=true, робимо:

SSE: GET /a2a/v1/stream?job_id=...

події: token, progress, final

Це дає “довгі відповіді” без таймаутів.

Як це лягає на ваш core-agent сценарій

Platform (CAP) прислала core-agent’у message.created

Core-agent робить POST /a2a/v1/request до encyclopedia-agent

Якщо needs_clarification — core-agent задає уточнення в чат (через CAP tool chat.sendMessage)

Після відповіді юзера core-agent робить ще один A2A request з answers_to_questions і отримує completed

Core-agent постить фінальну відповідь в чат

Фіксуємо в PRD одним реченням

CAP v1 — протокол інтеграції модулів з платформою (web/admin/api/events/security).

A2A MVP profile — єдиний стандарт agent↔agent взаємодії всередині системи.

Якщо ок — наступним повідомленням я оформлю це як короткий “A2A MVP Profile Spec” (поля обов’язкові/опційні, коди помилок, idempotency, timeouts, retries) і відразу дам приклад для вашого Encyclopedia Agent + Core Agent router.

фіксумємо
Далі давай тепер проговоримо що в нас core-agent. Чи може він бути наприклад openclaw ? Чи доцільно зробити базові речі в core-platform ?

Мені дуже імпонує що він вже вміє працювати з чатами, вбудовані багато модулей які маст хев, та розвивається

Обробка тривала 4 с

Може. І це навіть дуже логічно — якщо правильно розділити ролі між:

core-platform (ваш продукт: CAP v1, web/admin, дані, auth, роутінг)

core-agent (оркестратор: розумна маршрутизація питань/завдань, виклик інших агентів по A2A)

А OpenClaw тоді може бути або:

готовим core-agent’ом / runtime для core-agent, або

окремим “chat gateway” (але тут вже конфлікт із вашою платформою, бо ви теж робите gateway + CAP).

Найкраще рішення для MVP — OpenClaw як core-agent runtime, а не як core-platform.

Чому OpenClaw як “core-platform” — ризиково

OpenClaw у своїй природі — це personal assistant gateway/control plane під “одного оператора” (security posture саме так описують) .
Ваш кейс — ком’юніті платформа з:

multi-tenant (хоча б multi-community),

ролями модераторів,

публічним web + admin,

плагінами з routing під одним доменом.

Це інша модель продукту.

Чому OpenClaw як core-agent — дуже доречно

Бо він уже “вирішує болюче”:

підключення до чатів (Telegram точно є в документації)

routing/ізоляція агентів, екосистема skills/модулів

активний розвиток (плюс великий “time-to-demo” буст)

Як я б зафіксував ролі (щоб не “змішати”)
Core-platform (ваше)

Обов’язково в core-platform:

CAP v1: plugin manifest, instances, events, tool API

OIDC/JWKS (auth для web/admin/api)

Traefik routing під одним доменом (і субдомен admin)

Data plane: зберігання об’єктів ком’юніті (places/wiki/digests), аудит, модерація

Admin UI framework (мінімум) + можливість розширення

Це “скелет продукту”, його не хочеться віддавати OpenClaw, бо тоді ви стаєте “плагіном до OpenClaw”, а не навпаки.

Core-agent (оркестратор)

Core-agent робить:

intent routing: “це питання → encyclopedia agent”

multi-step: уточнення → фінальна відповідь

виклик агентів по A2A MVP profile

формування відповіді під стиль ком’юніті

відправка назад у чат через CAP tool chat.sendMessage

І тут OpenClaw може бути:

“движком” (runtime) який тримає розмови, пам’ять, інструменти, канали

або просто окремим агентом, якщо вам зручно, щоб він ще й спілкувався з Telegram

Але важливий нюанс: “хто саме підключений до Telegram?”

Є два варіанти:

Варіант A (рекомендую): Telegram інтеграція у core-platform

Telegram бот → ваш Platform Gateway → CAP events → core-agent (по внутрішньому виклику)

OpenClaw тут НЕ потрібен для каналів

плюс: єдина security/roles модель, простіше “platform product”

OpenClaw використовується тільки як runtime core-agent’а (без його каналів).

Варіант B: Telegram через OpenClaw, а ви як “backend platform”

Telegram → OpenClaw → core-agent → ваш CAP tool API / platform API → web/admin

мінус: ви стаєте залежними від OpenClaw routing/session model

плюс: найшвидше демо “в чаті”

Для хакатону B може бути швидше, але для продукту A чистіше.

Чи доцільно “базові речі” робити в core-platform?

Так. Бо це ваш moat.

У core-platform точно повинні бути:

OIDC/JWKS, RBAC, audit log

CAP v1 registry + instances

moderation queue + approval workflow

data ownership (places/wiki)

web/admin hosting + route mounting через Traefik

Core-agent (OpenClaw чи ні) — це “мозок”, але дані і правила мають жити в платформі.

Ще один важливий момент про OpenClaw (без паніки, просто факт)

Навколо OpenClaw зараз активно обговорюють security-ризики, особливо через “skills/extensions” і модель довіри. Це не означає “не можна”, але означає:

не тягнути сторонні skills з маркетплейсу без рев’ю

запускати в sandbox/ізоляції

мінімізувати права агента до потрібних tool’ів

(Це часто підсвічують медіа/дослідники останніми тижнями.)

Пропозиція фіксації для MVP

Я б зафіксував так:

Core-platform: CAP v1 + OIDC/JWKS + Traefik + мінімальний web/admin + storage/moderation

Core-agent: OpenClaw runtime (або ваш мінімальний агент)

Agent↔Agent: A2A MVP profile (єдиний стандарт)

Чати:

MVP швидкий: через OpenClaw або напряму платформою (обираєте)

цільова архітектура: чати через platform gateway

Якщо кажеш “фіксуємо OpenClaw як core-agent runtime”, то наступний крок:
описати межу інтеграції: які саме tool API core-platform дає core-agent’у (sendMessage, getCommunityConfig, storeKnowledge, createModerationItem, etc.) і як core-agent викликає спеціалізовані агенти по A2A.

Давай тепер PRD по кожному вузлу сформуємо
також давай Develop Plan по кожному вузлу

Нижче — набір PRD + Develop Plan по кожному “вузлу” MVP, виходячи з того, що ми зафіксували:

CAP v1 = один протокол для plugin/web/admin/api/events/security

A2A MVP profile = один стандарт agent↔agent

Traefik як gateway (path/subdomain routing + auth middleware)

OIDC/JWKS як auth стандарт

OpenClaw як runtime для core-agent (оркестратор), а не як core-platform

0. Карта вузлів MVP

Gateway (Traefik)

Auth Service (OIDC/JWKS)

Core Platform API (CAP v1 Registry + Tool API + Events Dispatcher)

Web App (Public)

Admin App

Core-Agent (OpenClaw runtime + Orchestrator logic)

Encyclopedia Agent (RAG/Q&A)

Places Plugin (CAP plugin: web/admin/api + multi-instance)

1. Gateway (Traefik)
   PRD

Goal: Єдиний вхід в систему: роутінг доменів/шляхів + централізована auth політика.

Scope (MVP):

Routing:

rivian-club.pp.ua/\* → public web (за замовчуванням)

rivian-club.pp.ua/map\* → places web

rivian-club.pp.ua/api/\* → platform api та plugin api routes

admin.rivian-club.pp.ua/\* → admin web

Auth middleware:

Public: без auth

Admin: тільки authenticated + role (admin/moderator)

API: Bearer JWT required (для приватних), public endpoints optional

Security headers (мінімум): HSTS (якщо HTTPS), basic CSP шаблон, X-Content-Type-Options.

Out of scope (MVP):

Dynamic route provisioning з manifest “на льоту” (можна статично в compose)

mTLS між сервісами

Acceptance criteria:

/map відкриває UI мапи

admin.\* без логіну редіректить на /login

admin.\* з роллю member — 403

API приватні без JWT — 401

Develop Plan

Phase 0: Схема доменів/route table, middleware chain
Phase 1: Traefik docker-compose + labels для сервісів
Phase 2: JWT middleware (JWKS) + RBAC rules
Phase 3: Hardening headers + basic rate-limit (optional)
DoD: роутінг працює, auth правила пройдені, конфіг задокументований

2. Auth Service (OIDC/JWKS)
   PRD

Goal: Єдиний Identity Provider: логін, токени, JWKS для валідації на gateway і сервісах.

Scope (MVP):

OIDC endpoints мінімум:

/authorize, /token, /userinfo, /.well-known/openid-configuration, /jwks.json

JWT access tokens:

claims: sub, community_id, roles, exp, iss, aud

Login провайдер:

Telegram Login як primary

Session для web/admin:

cookie (HttpOnly), або short-lived access + refresh (будь-який 1)

Out of scope:

WhatsApp/Viber SSO (можна потім)

складні політики MFA

Acceptance criteria:

gateway може валідувати токен через JWKS

ролі читаються і працює RBAC

admin login працює через Telegram

Develop Plan

Phase 0: Вибір реалізації (готовий OIDC провайдер чи мінімальний custom) + схема ролей
Phase 1: Реалізація OIDC + JWKS, видача JWT
Phase 2: Telegram login інтеграція, мапінг user→roles
Phase 3: Refresh/session, logout
DoD: OIDC discovery + JWKS доступні, токен валідний у Traefik, login працює

3. Core Platform API (CAP v1)
   PRD

Goal: “Скелет продукту”: CAP registry + instances + events + Tool API + moderation/data ownership.

Scope (MVP):

CAP Registry:

plugin definitions (manifest з /.well-known/plugin.json)

plugin instances (multi-instance per plugin)

Events dispatcher:

прийом подій від chat gateway (або з OpenClaw, якщо тимчасово)

доставка POST /events у plugin services (HMAC signature)

Tool API (мінімум):

chat.sendMessage

storage.kv get/set

instances.getConfig

audit.log

Moderation (мінімум як platform capability):

moderation.queue (таблиця + API)

approve/reject

Data ownership:

community, users, roles базово

CAP v1 spec docs (JSON schema) як артефакт.

Out of scope:

Marketplace плагінів

Multi-tenant billing

Складний перформанс/кешування

Acceptance criteria:

можна зареєструвати plugin + створити 2 instances з різним config

events доставляються в plugin і проходять HMAC verification

core-agent може викликати tool chat.sendMessage і отримати delivery в Telegram

Develop Plan

Phase 0: CAP v1 schema + таблиці БД + HMAC security профіль
Phase 1: CRUD registry/instances + manifest fetch
Phase 2: Events dispatcher + retries + idempotency (event_id)
Phase 3: Tool API endpoints (мінімум)
Phase 4: Moderation queue + API
DoD: CAP registry працює, events доставляються, tool API використовується core-agent’ом

4. Public Web App
   PRD

Goal: Публічний портал ком’юніті: landing + правила + entry points до модулів.

Scope (MVP):

/ landing: опис, правила, кнопка “Join”

link “Open map” → /map

(опційно) /wiki read-only якщо буде

SEO мінімум, мобільний responsive

Out of scope:

повний профіль юзера

складна кастомізація тем

Acceptance criteria:

сторінка відкривається під основним доменом

є кнопки-навігація, базова інфо ком’юніті

Develop Plan

Phase 0: IA/structure, дизайн мінімалістичний
Phase 1: React/Next/будь-що, static pages
Phase 2: інтеграція з auth (опційно)
DoD: landing працює, лінки ведуть у модулі

5. Admin App
   PRD

Goal: Адмін панель платформи + точка, яку розширюють плагіни.

Scope (MVP):

/login (через Auth service)

/dashboard (health, counts: plugins, instances, events failures)

/plugins (list, enable/disable, create instance, edit config)

/moderation (queue, approve/reject)

Extension points:

sidebar items від plugin

route mapping (через Traefik) до plugin admin UI

Out of scope:

granular ACL editor

аудит по всіх діях детально (мінімум)

Acceptance criteria:

адмін може створити 2 інстанси places (sto/rides)

адмін бачить DLQ/failed events і може replay (хоча б кнопка)

Develop Plan

Phase 0: навігація + auth guard
Phase 1: dashboard + plugins/instances CRUD
Phase 2: moderation queue UI
Phase 3: plugin extension nav + links
DoD: основні екрани працюють, адмінський workflow проходить end-to-end

6. Core-Agent (OpenClaw runtime + Orchestrator)
   PRD

Goal: Єдиний “голос” ком’юніті в чаті. Оркеструє агентів по A2A, відповідає в Telegram через Tool API.

Scope (MVP):

Receive message events (через CAP або через OpenClaw channels — як тимчасовий шлях)

Router:

“knowledge question” → Encyclopedia agent (A2A)

“places” → Places plugin commands / або places agent (якщо він окремий)

Clarification loop:

якщо A2A відповів needs_clarification, core-agent ставить уточнююче питання, чекає відповідь, робить follow-up A2A request

Response composer:

коротко, по суті, з посиланням на /map чи /wiki

Safety:

rate limit, anti-spam

не робити автоматичних банів

Out of scope:

довга “пам’ять” по всіх чатах

складні multi-agent планувальники

Acceptance criteria:

питання “де запобіжник…” → core-agent → encyclopedia → відповідь у чат

кейс з уточненням працює (2-step)

Develop Plan

Phase 0: A2A MVP profile client + intent taxonomy
Phase 1: інтеграція з Tool API (sendMessage)
Phase 2: Router + clarification state machine
Phase 3: logging/trace_id end-to-end
DoD: 3 демо сценарії: коротка відповідь, довга відповідь, уточнення

7. Encyclopedia Agent (RAG)
   PRD

Goal: Давати точні відповіді з “джерелами” з бази знань ком’юніті.

Scope (MVP):

A2A endpoint:

GET /.well-known/a2a.json

POST /a2a/v1/request

RAG pipeline:

retrieval: vector/top-k + фільтр по community_id

generate: short answer + evidence

Output modes:

completed з evidence

needs_clarification якщо мало контексту

Admin: дані/індексація управляється через platform (не обов’язково своя адмінка)

Out of scope:

auto-ingestion з Reddit/YouTube (пізніше)

повна багатомовна нормалізація

Acceptance criteria:

дає відповідь з 1–3 evidence items

вміє просити уточнення (model/year)

Develop Plan

Phase 0: формат knowledge docs + retrieval strategy
Phase 1: A2A server + базовий retrieval (можна навіть full-text спочатку)
Phase 2: embeddings + vector index (опційно)
Phase 3: clarification rules + confidence scoring
DoD: стабільні відповіді на 20 тестових питань

8. Places Plugin (CAP plugin: map/list/detail + admin CRUD)
   PRD

Goal: Каталог “місць” з мапою, пошуком, медіа, коментарями + multi-instance.

Scope (MVP):

CAP manifest:

web route: /map

admin route: /admin/places

api routes: /api/places/\*

supports multiple instances

Public:

map view (OSM)

list/search, filters by category

place details: опис + фото/відео + коментарі

Admin:

list places, edit швидко, approve/reject якщо moderation_mode=approve_required

Chat hooks (optional MVP):

/place add через core-agent → places api

Out of scope:

рейтинги/відгуки 5 зірок з антинакруткою

складні гео-пошуки по радіусу (можна простіше)

Acceptance criteria:

2 інстанси: “СТО” і “Покатушки” з різними категоріями

place можна додати, знайти, відкрити, прикріпити фото, коментувати

модерація працює якщо включена

Develop Plan

Phase 0: data schema + instance config schema
Phase 1: API CRUD + storage
Phase 2: Public UI map/list/details
Phase 3: Admin UI + moderation actions
Phase 4: інтеграція з core-platform registry + routing
DoD: end-to-end демо: додали місце → видно на мапі → модерація → publish

Як це “зшити” в один MVP демо (рекомендований сценарій)

Адмін логіниться в admin.\*

Вмикає Places plugin і створює 2 instances (СТО/Покатушки)

На rivian-club.../map додає місце, прикріплює фото

В чаті питають “де запобіжник…” → core-agent → encyclopedia → відповідь + лінк

(опційно) core-agent пропонує “додати це в wiki/knowledge” → moderation

Якщо хочеш — я перетворю це в один структурований документ PRD (Platform MVP) + окремі PRD файли на вузли (в однаковому шаблоні), щоб можна було одразу кидати команді як “таск пакети”.

давай, також давай сформуємо план які документи \*.md потрібно сформувати щоб агент краще розумів.

Ок — нижче даю:

Пакет PRD-документів (один “master” + окремі по вузлах)

Пакет технічних спеців/контрактів (CAP v1 + A2A MVP profile + security/routing)

\*План .md, які варто створити, щоб будь-який агент/учасник команди швидко “зрозумів систему” і працював узгоджено (особливо з різними мовами/стеками)

1. Структура репозиторію документів

Рекомендую /docs:

docs/
00_overview.md
01_architecture.md
02_mvp_scope.md
03_prd_platform.md
prd/
gateway_traefik.md
auth_oidc.md
platform_cap.md
web_public.md
web_admin.md
core_agent_openclaw.md
agent_encyclopedia_rag.md
plugin_places.md
specs/
cap_v1.md
a2a_mvp_profile.md
routing_and_domains.md
security_model.md
data_model.md
eventing_and_reliability.md
runbooks/
local_dev.md
deployment_docker_compose.md
troubleshooting.md
decisions/
adr_0001_protocols.md
adr_0002_openclaw_role.md
adr_0003_auth_oidc_jwks.md 2) Які саме PRD \*.md сформувати
A) Master PRD (обов’язково)

docs/03_prd_platform.md
Містить:

Problem / Goal / Personas

MVP scope / out of scope

Success metrics

Node map (вузли)

Ключові флоу (E2E сценарії)

Нефункціональні вимоги (security, reliability, observability)

Definition of Done для MVP

B) PRD по вузлах (окремо, щоб делегувати в команду)

Усе те, що я накидав раніше, розкладаємо в:

docs/prd/gateway_traefik.md

docs/prd/auth_oidc.md

docs/prd/platform_cap.md

docs/prd/web_public.md

docs/prd/web_admin.md

docs/prd/core_agent_openclaw.md

docs/prd/agent_encyclopedia_rag.md

docs/prd/plugin_places.md

Кожен PRD вузла — однаковий шаблон:

Goal

Scope (MVP)

Out of scope

Interfaces (що читає/що віддає)

Data ownership

Acceptance criteria

Risks

3. Специфікації, які “агент має знати” (найважливіше)

Це саме ті файли, які знімають хаос між командами/стеками.

1. CAP v1 Spec

docs/specs/cap_v1.md
Має бути максимально конкретний:

Plugin manifest: /.well-known/plugin.json (schema + приклад)

Instance model: definition vs instance, config schema/versioning

Events → plugin: POST /events (envelope + types + idempotency)

Tools API: мінімальний список endpoint-ів + auth

HMAC signing профіль для webhooks

Health endpoints: /healthz, /readyz

Versioning rules: backward compatibility, feature flags

2. A2A MVP Profile Spec (єдиний agent↔agent стандарт)

docs/specs/a2a_mvp_profile.md

Discovery: /.well-known/a2a.json

Request: POST /a2a/v1/request

Response statuses: completed, needs_clarification, queued (опц)

Clarification loop: як передавати answers

Evidence format (для RAG)

Timeouts/retries + idempotency (request_id)

(опційно) streaming (SSE) — можна “MVP+”

3. Security Model

docs/specs/security_model.md

OIDC/JWKS: issuer, audience, token claims, role mapping

Gateway enforcement rules (admin/public/api)

Service-to-service: HMAC webhooks + service tokens для tool API

Cookies vs JWT у вебі

Basic hardening: CSP, CORS, rate limiting policy

Audit log requirements

4. Routing & Domains

docs/specs/routing_and_domains.md

Домени: rivian-club.pp.ua, admin.rivian-club.pp.ua

Path routing: /map, /api/_, /admin/_

Як плагін додає URL (через CAP manifest capabilities)

Traefik labels approach (MVP static) + future dynamic

5. Data Model (спільна мова для всіх)

docs/specs/data_model.md

таблиці ядра: communities, users, roles, plugins, instances

events + failed_events (DLQ)

moderation queue

shared object model (якщо буде): objects/media/comments

conventions: IDs, timestamps, soft delete, created_by

6. Eventing & Reliability

docs/specs/eventing_and_reliability.md

retry policy, backoff

idempotency keys

DLQ + replay

observability: trace_id, request_id, correlation

4. Runbooks (щоб команда могла запускати без питань)

docs/runbooks/local_dev.md
як підняти все локально, які env vars, тестові токени, seed дані

docs/runbooks/deployment_docker_compose.md
“одна кнопка” deploy через docker-compose

docs/runbooks/troubleshooting.md
типові проблеми: JWKS кеш, 401/403, webhook signature mismatch, routing loops

5. ADR (коротко: чому ми так вирішили)

Це дуже допомагає агентам не “переобирати” архітектуру щоразу.

docs/decisions/adr_0001_protocols.md
CAP v1 + A2A MVP profile (чому так, що відкинули)

docs/decisions/adr_0002_openclaw_role.md
OpenClaw як runtime core-agent, не як platform

docs/decisions/adr_0003_auth_oidc_jwks.md
чому OIDC/JWKS + Traefik enforcement

6. Мінімальний набір документів для MVP (якщо стисло)

Якщо хакатон і часу мало — робимо тільки ці 8:

docs/00_overview.md

docs/03_prd_platform.md

docs/specs/cap_v1.md

docs/specs/a2a_mvp_profile.md

docs/specs/security_model.md

docs/specs/routing_and_domains.md

docs/specs/data_model.md

docs/runbooks/local_dev.md

7. Що я можу зробити прямо зараз у цьому чаті

Я можу одразу “згенерувати” вміст цих документів у вигляді готових секцій (markdown), починаючи з:

00_overview.md

03_prd_platform.md

specs/cap_v1.md

specs/a2a_mvp_profile.md

Скажи лише: ви хочете, щоб документи були українською повністю, чи EN (або UA+EN коротко)? Я можу зробити UA за замовчуванням.
