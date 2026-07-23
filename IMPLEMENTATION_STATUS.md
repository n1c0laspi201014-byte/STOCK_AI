# PaperTrade AI implementation review

Source contract: `papertrade_ai_mvp_codex_spec_v2.md` supplied by the project owner.

Main database: `stockdata` (intentional user-requested override of the specification's example database name).

Status meanings: `[x]` implemented and checked automatically; `[ ]` pending; `BLOCKED_BY_SETUP` requires a local service, account, key, or GUI action.

## Phase 1 — Foundation

- [x] Exact project folders created.
- [x] Environment loader and `stockdata` configuration created.
- [x] PDO database connection created.
- [x] Front controller, request/response helpers, router, and Apache rewrite configuration created.
- [x] Shared layout, four-link navbar, login, logout, CSRF, authentication, and guest middleware created.
- [x] Login route renders the development helper, Admin and Trader accounts, and CSRF form without an authenticated navbar.
- [ ] Authenticated runtime login checkpoint — `BLOCKED_BY_SETUP`: WAMP MySQL service is installed but stopped; apply schema/seed first.

## Phase 2 — Database and paper portfolio

- [x] Schema, foreign keys, indexes, InnoDB, utf8mb4, DECIMAL money/quantity fields.
- [x] Idempotent Admin/Trader seed, per-user portfolios/settings, transactions, holdings, non-owned watchlists, snapshots, and alert rules.
- [x] Ownership-scoped repositories with prepared SQL.
- [x] Transactional weighted-average buy/sell, cash, realized P/L, oversell/fractional/stale-price checks.
- [x] Portfolio, holdings, watchlist, transaction-history, and explicit password-confirmed reset APIs.
- [ ] Database integration checkpoint — BLOCKED_BY_SETUP until MySQL is running.

## Phase 3 — Market integration

- [x] Provider interface, Finnhub default, Twelve Data fallback.
- [x] Search, quote, profile, history, news, and market status normalization.
- [x] File cache, database snapshot fallback, freshness labels, and normalized rate-limit/auth/network errors.
- [x] Search-to-watchlist action works without buying and returns duplicate-safe success.
- [x] Real-provider test — Finnhub search, quote, profile, history, and browser-facing provider source verified with the deployed key.
- [x] Screenshot review correction: exact-ticker searches now suppress foreign suffix variants when an exact listing exists, enrich exchange/country from the provider profile, and label closed-session quotes as `Last close` instead of `Stale`.
- [x] US exchange names are normalized for Finnhub market status, with a documented weekday/session fallback when the optional provider endpoint is unavailable.

## Phase 4 — Four pages

- [x] Dashboard with eight KPI states, two configurable graphs, transaction, performers, important stocks, and recent alerts.
- [x] Stocks with Owned, Watchlist, Search, buy/sell confirmations, and in-page profile/chart/news details dialog.
- [x] Predictions with My stocks, Watchlist, limited-universe Opportunities, History, factors, outcomes, and actions.
- [x] Setup sections for paper account, dashboard, AI, Telegram, alerts, reports, quiet hours, and automation.
- [x] Responsive dark financial UI, keyboard/focus labels, mobile tables/navigation, Chart.js, toasts, and empty/error states.

## Phase 5 — Prediction engine

- [x] Technical momentum/SMA/RSI/MACD/volatility indicators and deterministic weighted score with missing-weight redistribution.
- [x] OpenRouter strict structured-output client, bounds/safety validation, one repair retry, and technical-only partial fallback.
- [x] Prediction storage, details, limited discovery, history, and due-outcome evaluation.
- [x] Probability/timeframe and confidence separation verified by unit/contract checks.
- [x] Live AI test — OpenRouter authentication/model/JSON/schema/bounds/safe-language checks PASS; an authenticated browser request saved a complete `fresh` AAPL prediction.

## Phase 6 — Telegram and n8n

- [x] Internal API-key middleware and eight automation endpoints.
- [x] Alert thresholds, interval/cooldown/quiet hours/daily maximum/market-hours rules, report generation, and Telegram result logging.
- [x] The initial three active-purpose workflow exports and persistent Docker Compose were created; those exports are now backed up and superseded by the single Hub.
- [x] Server-side Telegram client, connection verification, rule test action, and educational message templates.
- [x] Direct Telegram delivery test — bot token plus enabled/verified Admin connection confirmed; last test status is `success`.
- [x] Docker Desktop installed on Windows.
- [x] WSL 2 and Ubuntu installed successfully; local Unix user initialized.
- [x] Docker Engine 29.6.2 running after Windows restart.
- [x] Private 64-character internal n8n API key generated; missing/wrong keys rejected and the correct key accepted.
- [x] n8n 2.31.5 container running on `http://localhost:5678` with persistent `papertrade_n8n_data` volume and HTTP 200.
- [x] Local n8n owner account created.
- [x] Alert Monitor, Morning Report, and Market Close Report imported into the owner project with stable IDs and no duplicates.
- [x] Encrypted `PaperTrade Telegram` credential imported from the server-side token and assigned to all three Telegram nodes.
- [x] n8n 2.x environment-expression compatibility enabled so workflow nodes can read the internal base URL and private API key from Docker environment variables.
- [x] Alert Monitor IF-node upgraded to the n8n 2.x boolean-condition format so non-triggered rules do not reach Telegram.
- [x] Alert Monitor, Morning Report, and Market Close Report tests PASS; the shared n8n Telegram credential sent a real labeled test message successfully.
- [x] The initial three-workflow deployment was published and verified before its safe consolidation into the single Hub documented in Phase 14.

## Phase 7 — Security, tooling, and handoff

- [x] Ownership/role checks, login throttling, CSRF, secret masking, safe logs, and server-only credentials.
- [x] Four Windows helpers plus PHP requirement/database/market/OpenRouter/internal/demo/application utilities.
- [x] Manual setup, provider, OpenRouter, Telegram, n8n, troubleshooting, API, handoff, team status, demo, and acceptance docs.
- [x] PHP syntax checks: all PHP files PASS on WAMP PHP 8.4.15.
- [x] JavaScript syntax and all JSON workflow/config validation checks PASS.
- [x] Contract, router, CSRF, technical-indicator, scoring, probability, and login-render smoke tests PASS.
- [x] Final automatic review completed on 2026-07-22.
- [x] Project copied to `C:\wamp64\www\papertrade-ai` on 2026-07-22 (148 files, zero copy failures); the Codex workspace was retained because it is currently open.
- [x] WAMP deployment initialized: fixed MySQL 8.4 reserved-word and native-PDO placeholder compatibility issues, applied the schema, and seeded `stockdata`.
- [x] Deployed health check returns HTTP 200; the retained Admin and Trader accounts each authenticate and redirect successfully to the dashboard.
- [x] Fixed Apache PHP TLS trust with the official CA bundle; live browser search now returns `source: finnhub` instead of the local-catalog fallback.

## Phase 8 — Bootstrap responsive redesign

- [x] Official Bootstrap 5.3.8 CSS and bundled JavaScript stored locally under `public/assets/vendor/bootstrap`; no Bootstrap CDN is required at runtime.
- [x] Shared authenticated and login layouts load Bootstrap plus a dedicated PaperTrade responsive theme.
- [x] Login page rebuilt with a controlled desktop split layout and clean tablet/mobile stacking; oversized typography and clipping removed.
- [x] Main navigation rebuilt as a Bootstrap responsive collapse with accessible mobile toggle, active-page state, market status, and account menu preserved.
- [x] Dashboard cards/charts, stock holdings/search/results, prediction tabs/cards, setup forms, tables, alerts, and dialogs adapted for desktop, tablet, and phone widths.
- [x] Responsive browser validation PASS for Login plus Dashboard, Stocks, Predictions, and Setup at 1440 px, 768 px, and 390 px; zero page-level horizontal overflow.
- [x] Mobile navigation open/close behavior, horizontally scrollable data tables/tabs, PHP view lint, JavaScript syntax, and local Bootstrap asset delivery all PASS.
- [x] Bootstrap redesign deployed to `C:\wamp64\www\papertrade-ai` and verified from the live WAMP site.

## Phase 9 — Telegram automation simplification

- [x] Confirmed one shared `PaperTrade Telegram` bot credential was assigned across the original automations and remains the only credential used by the consolidated Hub.
- [x] Alert Monitor remains limited to enabled stock rules selected by each user in Setup and respects each rule's chosen check interval, threshold, direction, cooldown, quiet hours, daily limit, and market-hours option.
- [x] A fresh prediction is generated only after a configured price threshold is crossed; the same Telegram alert includes signal, probability and horizon, confidence, risk, and explanation.
- [x] Prediction generation falls back to the most recent saved estimate only when a temporary external-provider failure prevents a fresh result, so the price alert is not silently lost.
- [x] Morning Report now checks due users every minute for per-user timezone support, sends at or immediately after the selected local time, and uses a local-date execution key to enforce one report per user per day.
- [x] Market Close Report now has a direct weekday cron schedule at 4:00 PM in `America/New_York` and uses a New York-date execution key to enforce one report per user per market day.
- [x] The standalone Prediction Refresh export and internal endpoint were removed. Its live n8n copy is renamed `RETIRED - Prediction Refresh (disabled)` and is unpublished/inactive.
- [x] Setup copy now explains one bot, stock-specific alert selection, fresh prediction delivery, exact daily morning behavior, and weekday market-close behavior; the obsolete refresh-frequency control is hidden.
- [x] Source and WAMP PHP lint, unit checks, contract checks, original workflow validation, authenticated Setup render, live n8n state, and a non-delivery fresh-prediction alert test all passed before consolidation.

## Phase 10 — Paper-buy persistence confirmation

- [x] Confirmed the existing Buy action is a simulated trade only and never calls a broker or places a real market order.
- [x] An isolated live-WAMP purchase of one AAPL share created a `buy` transaction, reduced virtual cash, and created an Owned holding in `stockdata`.
- [x] The temporary verification account and its cascading portfolio, holding, and transaction records were removed after the test; no user portfolio was changed.
- [x] No implementation change was required because the requested paper-buy flow was already present and working.

## Phase 11 — Actual graph market data

- [x] Stock-detail history now uses real provider OHLCV data: Finnhub remains the live quote/profile source and Yahoo Finance supplies historical candles when the configured Finnhub plan rejects candle history.
- [x] AAPL `1M` live-WAMP verification returned 22 actual daily observations from 2026-06-22 through 2026-07-22 with source `Yahoo Finance historical market data`; 1D, 7D, 3M, and 1Y ranges are mapped to provider intervals as well.
- [x] Dashboard portfolio-value history is reconstructed from `stockdata` paper transactions plus recorded provider prices instead of a made-up series or cash-only line.
- [x] Dashboard holdings refresh their current provider quotes before charting; portfolio allocation and profit/loss graphs never substitute purchase cost for a missing market price.
- [x] Repeated identical provider snapshots are de-duplicated, while honest local-snapshot fallback remains available when every historical provider is unavailable.
- [x] Graph captions display the actual data source and observation count, and asset URLs now include file-modification cache versions so updated graph code loads immediately.
- [x] Live reconciliation PASS: the final 30-day portfolio graph point exactly matched the calculated portfolio value (`USD 101,317.73`) across 55 recorded events.
- [x] Browser rendering PASS on the responsive WAMP site: portfolio-value, allocation, and AAPL historical graphs rendered with actual data and no console errors.

## Phase 12 — Accounts, estimate coverage, and Telegram questions

- [x] Added a real self-service `/register` page linked from Sign in, protected by CSRF, guest-only middleware, unique email validation, and strong password validation.
- [x] Registration transactionally creates the trader user, personalized user settings, virtual-cash portfolio, and dashboard preferences, then signs the user in and opens Setup.
- [x] Registration personalization includes display name, portfolio name, timezone, USD/EUR paper currency, starting virtual cash, fractional-share choice, estimate horizon, and Telegram report preferences.
- [x] Added a Profile section in Setup for editing display name and timezone; the signed-in session name updates immediately after saving.
- [x] Isolated live registration test PASS with EUR 75,000, personalized portfolio, Brussels timezone, 30-day horizon, 08:15 morning report, and report toggles; the temporary account and all cascading data were removed.
- [x] Predictions now automatically create missing owned/watchlist estimates from real quote and historical data. Automatic first estimates use the fast deterministic technical model; per-stock Refresh still adds OpenRouter/news context.
- [x] Live estimate-coverage test PASS: the Trader account moved from five missing estimates to zero; after the performance correction, the remaining three were generated in 6.7 seconds and the page rendered normally.
- [x] Added a Telegram stock-question service supporting `/stock AAPL`, `stock MSFT`, `$NVDA`, and symbol-only questions, returning current provider quote, change, timestamp, prediction, confidence, risk, reason, and disclaimer.
- [x] Live stock-answer content test PASS for AAPL using the verified Admin Telegram connection; the generated response contained a current price, estimate, confidence, and disclaimer.
- [x] Added and imported `PaperTrade AI - Telegram Stock Questions` into local n8n with five nodes and the shared `PaperTrade Telegram` credential.
- [x] Incoming stock questions activated through local Telegram polling after the Hub passed its complete pre-cutover test; the former n8n Cloud webhook was then removed with zero pending updates.
- [x] Real random-stock Telegram alert test PASS: AAPL was randomly selected with an increase scenario, the evaluated movement was +2.00%, the message included a prediction, Telegram accepted message ID 58, and the temporary rule/event were removed.
- [x] Responsive browser verification PASS for Register, Predictions, and Setup; expected account/estimate/workflow content rendered with zero console errors.

## Phase 13 — Two-role authorization model

- [x] Reduced the database role enum to exactly `admin` and `trader`; the obsolete `analyst` role is no longer valid in fresh schemas.
- [x] Combined research and paper-trading access under Trader. There were no Analyst-only permission gates to preserve because all non-Admin application features were already shared.
- [x] Self-service registration remains hard-coded to create only `trader`; the public form has no role field and cannot create an Admin.
- [x] Removed Analyst Demo from seed data, the login helper, documentation, demo script, handoff, and acceptance checks.
- [x] Added an existing-database migration that deletes the retired Analyst Demo, converts any other legacy Analyst users to Trader, and narrows the role enum safely.
- [x] The confirmed demo-reset path also reapplies the two-role enum before reseeding, so Analyst cannot return after a reset.
- [x] Live `stockdata` migration PASS: Analyst Demo and its cascading portfolio/watchlist/alert data were removed, and the role column is now exactly `enum('admin','trader')`.
- [x] Live authorization test PASS: Admin and Trader reached Dashboard, the retired Analyst credentials were rejected, and the login helper displayed exactly two accounts.
- [x] Registration enforcement test PASS: a deliberately submitted `role=admin` value was ignored and the temporary account was stored as `trader`; the test account and cascading records were removed.
- [x] Source/WAMP deployment, PHP lint, router/contract/unit checks, and application-database checks PASS.

## Phase 14 — Consolidated Telegram Hub

- [x] Exported all five pre-consolidation workflow drafts and all three published versions before any workflow or webhook state changed; credentials were not exported or decrypted.
- [x] Built and imported the inactive 25-node `PaperTrade AI – Telegram Hub` with four schedule branches and one shared `PaperTrade Telegram` credential reference.
- [x] Alert branch validation PASS: only configured due rules are selected; a non-triggering rule created no prediction, while a crossed threshold created a fresh prediction and alert message.
- [x] Morning branch validation PASS at a fixed user-local time, including once-per-local-date execution-key protection.
- [x] Market-close branch validation PASS at 4:00 PM New York on a weekday, including once-per-market-date protection and weekend suppression.
- [x] Stock-question validation PASS for `/stock AAPL`: real quote, provider, provider timestamp, prediction, confidence, risk, reason, and disclaimer were produced.
- [x] Real pre-cutover Telegram stock-answer delivery PASS as message 60 while the old workflows and cloud webhook were still untouched.
- [x] n8n-to-WAMP internal authentication PASS for alert, morning, close, and question endpoints without exposing the private API key.
- [x] After all pre-cutover tests passed, removed the former `sanofeki.app.n8n.cloud` webhook with zero pending updates and initialized local `getUpdates` polling successfully.
- [x] Published only `PaperTrade AI – Telegram Hub`, unpublished Alert Monitor, Morning Report, Market Close Report, Telegram Stock Questions, and retired Prediction Refresh, then restarted n8n.
- [x] Live Hub question execution PASS: `/stock AAPL` was answered and logged with execution key `telegram-question-770588006`; a replacement verification answer was also delivered as message 63.
- [x] Live scheduler-driven random alert PASS: META increase was randomly selected, +2.00% crossed the rule, fresh prediction 29 was attached, and Telegram status became `sent` at 10:15:43.
- [x] Temporary random-alert rule, event, and prediction were removed after evidence was recorded.
- [x] Published Hub export verification PASS: 25 nodes, four Telegram nodes, exactly one unique credential named `PaperTrade Telegram`, and no decrypted secret material.
- [x] Source now contains one current workflow export; the replaced definitions remain recoverable from the dated backup and as unpublished local n8n records.
- [x] Final live verification PASS: one active Hub, five unpublished legacy records, no Telegram webhook, zero pending updates, and zero recent n8n workflow error lines.
- [x] Final WAMP verification PASS: Setup renders the one-Hub/local-polling status with HTTP 200; PHP lint, unit/router/contract/application checks, secret scan, and source/WAMP hashes all pass.

## Phase 15 — Telegram company-name questions

- [x] Telegram questions now accept company names as well as ticker symbols, including `/stock NVIDIA`, `Apple`, `stock Microsoft`, `what about Tesla?`, `$META`, and multi-word names.
- [x] Added ranked provider search that favors exact company/ticker matches and primary US listings, while returning up to five ticker choices instead of guessing when a name is ambiguous.
- [x] Added safe common-name aliases for familiar names and rebrands such as Google/Alphabet, Facebook/Meta, Coca-Cola, NVIDIA, Berkshire Hathaway, Disney, and the main demo-universe companies.
- [x] Help and Setup copy now explain that users can ask naturally without knowing the ticker.
- [x] Automated resolution checks PASS: NVIDIA→NVDA, Apple→AAPL, Tesla→TSLA, Microsoft→MSFT, Berkshire Hathaway→BRK.A, and `$META`→META.
- [x] Live provider resolution PASS for Adobe→ADBE; the generic `United` query returned several ticker choices rather than selecting an unsafe match.
- [x] Live `/stock NVIDIA` answer PASS with matched ticker, real quote, provider timestamp, prediction, and disclaimer.
- [x] Real company-name Telegram delivery PASS as message 67.

## Phase 16 — Conversational Telegram AI Agent

- [x] Backed up the published pre-AI `PaperTrade AI – Telegram Hub` before changing workflow state; the backup contains no decrypted credentials.
- [x] Kept Telegram `getUpdates` polling because WAMP is local, but replaced the old deterministic question-to-Telegram path with an n8n `AI Agent`.
- [x] Extended the WAMP polling payload with the original user question, a separate authoritative factual answer, and a stock-context flag while retaining the legacy message field during the safe cutover.
- [x] Added an encrypted `PaperTrade OpenRouter` n8n credential from the already configured WAMP key without printing the key or saving it in project files.
- [x] Added `OpenRouter Chat Model`, `AI Stock Chatbot`, and `Telegram Chat Memory` nodes; memory is isolated by Telegram chat ID with an eight-message context window.
- [x] Grounding rules require the chatbot to use WAMP as the only source for current quote, change, provider timestamp, symbol, prediction, probability, confidence, risk, and reason; unavailable facts must remain unavailable.
- [x] Chatbot rules prohibit invented prices and guaranteed returns, explain terms in plain language, keep replies phone-friendly, and retain the simulated-education/not-financial-advice boundary.
- [x] Imported the 28-node AI Hub as an inactive candidate while the original 25-node Hub remained active.
- [x] Isolated n8n/OpenRouter grounding smoke PASS: the Agent preserved the exact NVDA symbol, `123.45` price, `-1.25%` change, WATCH label, and safety language; the memory node loaded and saved context.
- [x] Published the tested 28-node AI Hub, unpublished the old deterministic Hub, and restarted n8n.
- [x] Live end-to-end n8n AI delivery PASS: real NVIDIA quote/prediction context flowed through OpenRouter, the AI Agent, chat memory, and the shared Telegram credential; Telegram accepted message 68.
- [x] Removed both temporary inactive AI test workflows after validation.
- [x] Final n8n state verification PASS: `PaperTrade AI – Telegram Hub` is the only active workflow, the old Hub remains recoverable but unpublished, and no key or bot token appears in the workflow export.
- [x] Final regression checks PASS: PHP lint, AI workflow contract, Telegram company resolution, router, scoring/CSRF units, application contract, JSON validation, and secret-pattern scan.

## Automatic test results

| Test | Result | Evidence / remaining action |
|---|---|---|
| PHP 8/extensions/files/storage requirements | PASS | `scripts/php/check_requirements.php` on WAMP PHP 8.4.15 |
| PHP syntax | PASS | All project PHP files linted |
| JavaScript syntax | PASS | All eight browser scripts checked with Node |
| JSON validity | PASS | Current Hub export and all ten dated backup exports parsed; n8n secret scan passed |
| Contract/security/static checks | PASS | 29 required files, 15 required tables, four navbar links, CSRF/internal middleware, one current workflow, no browser secret names |
| Router/login/CSRF/scoring unit checks | PASS | `tests/php/router_smoke.php`, `unit_smoke.php`, `contract_smoke.php` |
| Temporary HTTP login render | PASS | PHP server returned HTTP 200 with the Admin/Trader helper and CSRF form |
| `stockdata` schema/seed/application integration | PASS | WAMP database connection, all schema objects, Admin and Trader users, portfolios, and demo data verified |
| Live market quotes and actual history | PASS | Finnhub supplies current AAPL/MSFT quotes; the authenticated AAPL 1M API returns 22 real Yahoo Finance OHLCV observations when the Finnhub plan blocks candles |
| OpenRouter live structured response | PASS | Authentication, free-model routing, structured JSON, schema/bounds/safe language, browser generation, and database persistence verified |
| Internal n8n auth | PASS | Generated private key; missing/wrong keys rejected and correct key accepted against the live WAMP API |
| Direct Telegram delivery | PASS | Admin bot connection is enabled, verified, and records a successful real test message |
| Scheduled n8n delivery | PASS | Exactly one workflow active: the 28-node Telegram Hub handles 5-minute configured alerts, minute-precision daily morning reports, 4:00 PM New York weekday close reports, and local question polling through an AI Agent |
| Alert prediction delivery contract | PASS | Non-delivery integration test crossed a temporary rule, saved a fresh prediction, and verified real signal/probability/confidence/risk values in the prepared Telegram message; test records removed |
| Paper-buy persistence | PASS | Isolated one-share AAPL paper purchase saved a buy transaction and Owned holding while decreasing virtual cash; no broker order; temporary account removed |
| Actual graph data | PASS | Stock history, portfolio value, allocation, source labels, cache-busting, live API response, rendered canvases, and zero browser console errors verified |
| Self-service registration | PASS | HTTP registration, automatic sign-in, personalized settings/portfolio persistence, responsive page, and cascading test-account cleanup verified |
| Two-role authorization model | PASS | Database and UI expose only Admin/Trader; both logins pass, retired Analyst is rejected, and registration cannot request Admin |
| Automatic estimate coverage | PASS | Trader owned/watchlist missing count reduced from five to zero; fast technical first estimates and optional full AI/news refresh verified |
| Telegram stock-question answer | PASS | AAPL question builder returned live quote plus prediction/confidence/risk/disclaimer; local n8n workflow imported with the shared credential |
| Telegram question activation | PASS | Cloud webhook remains removed; local polling feeds verified WAMP facts and the original question to the n8n AI Agent |
| Telegram company-name resolution | PASS | Common aliases, ranked provider search, ambiguity choices, and real NVIDIA message 67 verified |
| Conversational Telegram AI | PASS | OpenRouter grounding smoke and real NVIDIA Telegram message 68 verified AI Agent output, exact factual values, safety language, and chat-memory load/save |
| Consolidated Hub credentials | PASS | Published Hub has four Telegram nodes using one unique `PaperTrade Telegram` credential plus one encrypted `PaperTrade OpenRouter` model credential |
| Random movement alert delivery | PASS | Original AAPL message-58 test passed; final active-Hub test randomly selected META increase, delivered +2.00% with fresh prediction, and removed temporary records |
| Bootstrap assets and responsive layout | PASS | Local Bootstrap 5.3.8 assets return HTTP 200; Login and all four authenticated routes have zero horizontal overflow at 1440 px, 768 px, and 390 px |
| Responsive browser console | PASS | No browser warnings or errors recorded during desktop/tablet/mobile navigation and page checks |

## Human-only completion gates

- [ ] Person 1: visually review the dashboard as Admin and Trader (WAMP/database setup and automated login checks are complete).
- [x] Person 2: Finnhub and OpenRouter keys/models configured; live provider and structured-output integration tests complete.
- [x] Person 3: local n8n owner created; the single Telegram Hub is tested, published, uses one credential, and is the only active workflow after restart.
- [ ] All team: execute `tests/manual/acceptance-checklist.md`.
