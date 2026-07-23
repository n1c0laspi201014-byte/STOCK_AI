# Mandatory three-person setup handoff

Work in parallel after Codex generation. Never place secrets in Git, chat, screenshots, or `TEAM_STATUS.md`.

## Person 1 — WAMP64, PHP, MySQL, application, and login

Mission: make Apache/PHP/MySQL work, create `stockdata`, seed the Admin and Trader accounts, and prove routing/login/data isolation.

- [ ] Start WAMP64; tray icon green; `http://localhost/` opens.
- [ ] Copy project to `C:\wamp64\www\papertrade-ai`.
- [ ] Run `scripts\windows\01-check-requirements.bat`; enable any missing WAMP PHP extensions and restart Apache.
- [ ] Run `scripts\windows\02-create-local-env.bat`.
- [ ] In `.env`, confirm `APP_URL=http://localhost/papertrade-ai/public`, WAMP's DB host/port/user/password, and `DB_DATABASE=stockdata`. Leave market/OpenRouter/Telegram keys blank for their owners.
- [ ] Run `php scripts\php\setup_database.php`. Expected: database connection, `stockdata`, schema, Admin and Trader users, portfolios, and demo data all PASS.
- [ ] If creation is denied, create `stockdata` at `http://localhost/phpmyadmin/` with `utf8mb4_unicode_ci`, then rerun.
- [ ] Open `http://localhost/papertrade-ai/public/health.php`; expect PHP/extensions/storage/database healthy.
- [ ] Open `http://localhost/papertrade-ai/public/login`. If 404 while health works, enable `mod_rewrite` and `AllowOverride All`, restart Apache.
- [ ] Log in/out with Admin (`admin@papertrade.local` / `Admin123!`) and Trader (`trader@papertrade.local` / `Trader123!`).
- [ ] Confirm the navbar has only Dashboard, Stocks, Predictions, Setup; invalid password fails; sessions/data do not mix.
- [ ] Run `php scripts\php\create_demo_data.php` and `scripts\windows\03-test-application.bat`.
- [ ] After Person 2 reports quote PASS: as Trader search AAPL, watchlist it, buy a small quantity, verify cash decreases/holding appears, sell part, verify cash increases, and confirm overselling is rejected.
- [ ] Write Person 1 PASS/FAIL/BLOCKED status and exact error in `TEAM_STATUS.md`.

First command:

```bat
scripts\windows\01-check-requirements.bat
```

## Person 2 — Real market data, OpenRouter, predictions, and charts

Mission: privately configure external market/AI credentials and prove search, quote, chart/fallback, and estimates.

- [ ] Create a Finnhub account/key; do not share the key.
- [ ] Set `MARKET_DATA_PROVIDER=finnhub`, `MARKET_DATA_API_KEY=...`, cache `60`, stale limit `900` in `.env`; restart Apache if needed.
- [ ] Run `php scripts\php\test_market_api.php AAPL`.
- [ ] Expect authentication, search, quote, profile, timestamp/freshness PASS. If history is plan-blocked, record PARTIAL and verify local `price_snapshots`/honest no-chart fallback.
- [ ] For 401/403 recopy the key/check plan; for 429 wait and use cache.
- [ ] In Stocks > Search test AAPL, Microsoft, NVDA. Verify price timestamp/freshness and Buy/Add to watchlist/details/prediction actions. Add a non-owned stock twice; no duplicate row.
- [ ] Open stock details and test a chart range or the truthful plan-limitation fallback.
- [ ] Create an OpenRouter account/key, select an exact available structured-output model, and ensure credits/availability.
- [ ] Set `OPENROUTER_API_KEY`, exact `OPENROUTER_MODEL`, site URL/name in `.env`; restart Apache if needed.
- [ ] Run `php scripts\php\test_openrouter.php AAPL 7d`; expect authentication/model/JSON/schema/bounds/safety PASS.
- [ ] If invalid model, copy exact identifier; if insufficient credit, select an available model/add credit; if invalid JSON, verify repair then deterministic partial fallback.
- [ ] In Stocks generate a short estimate. Verify signal, estimated probability, confidence, risk, and horizon appear separately.
- [ ] In Predictions verify My stocks, Watchlist, Opportunities, History; each opportunity has Add to watchlist and Simulated buy.
- [ ] Add one opportunity to watchlist and confirm it appears in Stocks.
- [ ] Record delay/freshness label and Person 2 PASS/FAIL/PARTIAL status in `TEAM_STATUS.md` without secrets.

First command after adding the key:

```bat
php scripts\php\test_market_api.php AAPL
```

## Person 3 — Docker, n8n, Telegram, alerts, and reports

Mission: run n8n, connect Telegram and WAMP, import workflows, and deliver a test alert/report.

- [ ] Install/start Docker Desktop; run `docker version` and `docker compose version`.
- [ ] Replace `.env` `INTERNAL_N8N_API_KEY` placeholder with a long private random value; ensure Compose receives the same value.
- [ ] Run `docker compose -f docker-compose.n8n.yml up -d`, then `docker compose -f docker-compose.n8n.yml ps`.
- [ ] Open `http://localhost:5678`; create the private n8n owner account.
- [ ] From n8n HTTP Request, test `http://host.docker.internal/papertrade-ai/public/health.php`; never use container `localhost` for WAMP. Add Apache port if not 80.
- [ ] Run `php scripts\php\test_internal_api.php`; missing/wrong keys rejected, correct key accepted.
- [ ] In Telegram BotFather run `/newbot`, create a username ending in `bot`, privately copy token, open the bot, and press Start.
- [ ] Create n8n Telegram credentials with that token.
- [ ] Obtain chat ID using a temporary Telegram Trigger/getUpdates flow. Save it in Setup and click Send test message. Fix `chat not found` by starting the bot/rechecking bot+ID.
- [ ] Import `alert-monitor.json`, `morning-report.json`, and `market-close-report.json` from `n8n/workflows/`.
- [ ] Assign Telegram credentials; verify WAMP base URL and internal key on HTTP nodes; save; run manually before activation.
- [ ] In Setup create AAPL Percentage alert value `0.2`, direction Both; UI must show `0.2%`. Click Test so no real move is required.
- [ ] Verify Telegram message, `alert_events` delivery status, timestamp, probability+horizon, confidence, and disclaimer.
- [ ] Enable morning report; manually run/send test report. Verify portfolio value, cash, important predictions, and timestamp.
- [ ] Test the alert, morning, market-close, and stock-question branches, then activate only `PaperTrade AI – Telegram Hub`. Use the same Telegram credential on all four Telegram nodes.
- [ ] Record Person 3 PASS/FAIL status, active workflow names, and exact error in `TEAM_STATUS.md` without secrets.

First command:

```bat
docker compose -f docker-compose.n8n.yml up -d
```

## All-team checkpoint

At the joint checkpoint, require Person 1 login/database PASS, Person 2 real AAPL quote PASS, and Person 3 n8n UI + Telegram bot created. Then execute `tests/manual/acceptance-checklist.md` in order.
