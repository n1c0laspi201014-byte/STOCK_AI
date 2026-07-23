# PaperTrade AI — Codex-Ready MVP Specification

> **Codex implementation review:** Completed automatic work and verification results are checked in [`IMPLEMENTATION_STATUS.md`](IMPLEMENTATION_STATUS.md). Human-only WAMP, credentials, Docker, Telegram, and final browser-delivery checks remain unchecked in [`SETUP_HANDOFF.md`](SETUP_HANDOFF.md) and [`tests/manual/acceptance-checklist.md`](tests/manual/acceptance-checklist.md). The configured main database is `stockdata`, per the project owner's explicit override.

> **Purpose:** Build a school-project paper-trading website that uses real market data but never places real trades or connects to a brokerage account.
>
> **Primary stack:** HTML, CSS, JavaScript, PHP, MySQL/phpMyAdmin, WAMP64, n8n, OpenRouter, Telegram Bot API, Chart.js, and a stock-market data API.
>
> **Main application pages:** Dashboard, Stocks, Predictions, and Setup.
>
> **Authentication:** A separate login screen with three seeded demo accounts. The login screen is not counted as one of the four navbar pages.

---

## 0. Master instructions for Codex

Codex must treat this document as the implementation contract.

## 0.1 Urgent 10-hour execution mode

This project is being prepared by a team of three people with approximately **10 total working hours before the deadline**. Codex must optimize for a working end-to-end demonstration rather than a slow tutorial workflow.

### Automatic-generation-first rule

Codex must first create **everything it can create without human accounts, secret keys, GUI clicks, or administrator permission**.

Codex must not stop after creating one phase and wait for the user to ask for the next phase. It must generate the complete project foundation and all implementation files in one continuous task, while keeping the code separated into logical commits or clearly labelled phases.

Codex must automatically create, where technically possible:

- The complete folder structure
- PHP front controller and router
- MVC controllers, models, repositories, services, views, and middleware
- Shared four-page navbar
- Login and logout
- Three-account seed script
- SQL schema
- Database reset and seed utilities
- Portfolio, holdings, transactions, and watchlist logic
- Stock search, quote, profile, and history integrations
- Market-provider interface and default provider implementation
- Dashboard, Stocks, Predictions, and Setup pages
- Stock search with Buy and Add to watchlist actions
- Simulated buy and sell flows
- Chart.js integration
- Prediction scoring and OpenRouter client
- Structured AI-response validation and fallback behavior
- Internal n8n API endpoints
- Importable n8n workflow JSON files
- Docker Compose configuration for n8n
- Telegram test endpoint and message templates
- `.env.example`
- `.gitignore`
- Apache `.htaccess`
- Health checks and integration test scripts
- Setup scripts for Windows where practical
- Troubleshooting documentation
- Three-person setup handoff document
- End-to-end test checklist
- Demo data and a demo reset command

Codex must not tell a human teammate to manually type a file that Codex can generate.

### Required helper files

Codex must create these additional files:

```text
docker-compose.n8n.yml
SETUP_HANDOFF.md
TEAM_STATUS.md
DEMO_SCRIPT.md
scripts/windows/01-check-requirements.bat
scripts/windows/02-create-local-env.bat
scripts/windows/03-test-application.bat
scripts/windows/04-test-integrations.bat
scripts/php/check_requirements.php
scripts/php/setup_database.php
scripts/php/test_market_api.php
scripts/php/test_openrouter.php
scripts/php/test_internal_api.php
scripts/php/create_demo_data.php
public/health.php
```

The scripts must be safe to run repeatedly where possible. They must print:

- PASS
- FAIL
- What was tested
- The exact likely fix
- The file or setting that must be changed

Never print secret values.

### Human-only task rule

After automatic generation is complete, Codex must identify only tasks that truly require a person, such as:

- Installing or starting WAMP64
- Installing or starting Docker Desktop
- Creating external accounts
- Generating API keys
- Creating a Telegram bot
- Copying secret values into `.env` or n8n credentials
- Importing workflows through the n8n interface when automatic import is unavailable
- Allowing Apache through Windows Firewall
- Clicking an activation or test button

These tasks must be divided among exactly three people as defined later in this specification.

### No unnecessary questions

Codex should inspect the environment and make reasonable defaults. It must not pause to ask questions about minor preferences such as colors, model names, sample stocks, or chart ranges. Use the defaults in this specification and leave them configurable.

Ask a question only when Codex cannot safely continue without a machine-specific value. When possible, generate a placeholder, continue building, and list the value in the relevant person’s setup checklist.

### Must-have priority order

When time is limited, protect features in this exact order:

1. Application starts
2. Three accounts can log in
3. Four-page navbar works
4. Real stock search and quote work
5. Search results can be added to the watchlist
6. Simulated buy and sell work
7. Portfolio data and charts work
8. Basic prediction works
9. Detailed Predictions page works
10. Telegram test message works
11. Percentage-change alert works
12. Morning report works
13. Secondary features and visual polish

If a lower-priority feature blocks a higher-priority feature, disable or postpone the lower-priority feature and document it clearly.


### Required working style

1. Build the project in small, testable phases.
2. Do not replace PHP with Node.js, Python, React, Laravel, or another framework unless the user explicitly requests it.
3. Use a clear custom PHP MVC architecture with:
   - Controllers
   - Models
   - Repositories
   - Services
   - Views
   - API endpoints
4. Keep all API keys and credentials server-side.
5. Never place a real trade and never integrate a real brokerage account.
6. Use real market data where the configured provider and plan allow it.
7. Clearly display whether prices are real-time, delayed, cached, stale, or unavailable.
8. Use prepared SQL statements and secure password hashing.
9. Do not silently invent data when an external API fails.
10. Use seeded mock data only where this document explicitly requests demo data.
11. When a manual action is required, guide the user through it instead of only saying “configure this.”
12. For every manual action, explain:
    - What is required
    - Where to obtain or configure it
    - What value must be copied
    - Which local file or field receives the value
    - How to test it
    - Common errors and how to fix them
13. Keep the code readable for a student. Use comments for architecture decisions, not for obvious syntax.
14. Create a `README.md`, `.env.example`, SQL migration/schema file, seed script, and troubleshooting guide.
15. Do not create extra top-level navbar pages. Detailed stock information must appear inside the Stocks page using a modal, drawer, expandable panel, or an internal page state.
16. Do not claim that AI can predict stock prices with certainty.
17. Label all prediction percentages as **estimated probabilities**, not guaranteed outcomes.
18. Before declaring the project complete, run the acceptance checklist in this document.

### Guidance checkpoints

At the end of each build phase, Codex must show:

- Files created or changed
- What the user must do manually
- Exact URL or command to test
- Expected result
- The most likely failure and its fix

---

# 1. Product definition

## 1.1 Product name

Working name:

**PaperTrade AI**

The name may be changed later from a single configuration value.

## 1.2 Main goal

Build a responsive web application that lets users:

- Log in to a simulated trading account
- Use real market prices and real company information
- Search for stocks
- Simulate buying and selling stocks
- Add stocks to a watchlist without buying them
- View portfolio and stock charts
- Receive short AI-assisted predictions for each stock
- Open a detailed Predictions page for deeper analysis
- Discover potentially interesting stocks that are not currently owned
- Configure Telegram price alerts
- Configure morning and market-close reports
- Receive AI-assisted explanations with alerts
- Track prediction history and outcomes

## 1.3 Explicit non-goals

The MVP must not:

- Use real money
- Connect to a broker
- Submit real market orders
- Promise profits
- Present predictions as professional financial advice
- Train a machine-learning model from scratch
- Scan every listed stock in the world
- Require microservices
- Require a frontend framework

## 1.4 Required disclaimer

Display this disclaimer in the footer, Predictions page, stock prediction panels, and Telegram prediction messages:

> This application is a paper-trading educational project. Market predictions are uncertain estimates generated from available data and are not financial advice. No real trades are placed.

---

# 2. Required technology stack

## 2.1 Frontend

- HTML5
- CSS3
- Vanilla JavaScript
- Chart.js for charts
- Fetch API for asynchronous requests
- Optional Font Awesome for icons
- Responsive shared navbar

Do not use React, Vue, Angular, or another frontend framework for the MVP.

## 2.2 Backend

- PHP 8.x supported by the installed WAMP64 version
- Custom MVC architecture
- PDO for MySQL access
- PHP cURL for external API calls
- PHP sessions for authentication
- Composer autoloading if Composer is installed
- A simple fallback autoloader if Composer is not installed

Prefer PDO over MySQLi because repository classes and transactions are easier to manage consistently with PDO.

## 2.3 Database

- MySQL through WAMP64
- phpMyAdmin for manual database inspection
- UTF-8 MB4 encoding
- InnoDB tables
- Foreign keys
- DECIMAL values for money and quantities

## 2.4 Automation

- n8n running separately from WAMP64
- Recommended local installation: Docker Desktop
- Alternative: n8n through npm
- n8n workflows call authenticated PHP API endpoints
- n8n must not directly modify database tables unless a later version explicitly requires it

## 2.5 AI

- OpenRouter API
- Model configured through environment variables and the Setup page
- Structured JSON output
- AI used for:
  - News sentiment
  - Explanation
  - Risk summary
  - Suggested BUY, HOLD, SELL, or WATCH label
  - Discovery summaries
- AI must not be the only source of the displayed probability

## 2.6 Market data

Create a provider abstraction.

Default implementation:

- `FinnhubMarketDataProvider`

Optional fallback implementation:

- `TwelveDataMarketDataProvider`

Required provider abilities:

- Search stock symbols
- Fetch latest quote
- Fetch company profile
- Fetch historical price series
- Fetch company news where supported
- Fetch or determine market status where supported

The code must not assume that every endpoint is available on every free plan. If historical candles are unavailable:

1. Try the configured fallback provider.
2. If no fallback is configured, use saved `price_snapshots` for charts.
3. Clearly mark the chart as locally accumulated history.
4. Never fabricate missing candles.

## 2.7 Local server

WAMP64 provides:

- Apache
- PHP
- MySQL
- phpMyAdmin

n8n is a separate process and must not be assumed to be included in WAMP64.

---

# 3. High-level architecture

Use:

**MVC + Service Layer + Repository Layer + JSON API + n8n workflows**

```text
Browser
  |
  v
Apache / public/index.php
  |
  v
Router
  |
  +--> Auth Middleware
  |
  v
Controller
  |
  v
Service
  |
  +--> Repository --> MySQL
  |
  +--> Market Data Provider
  |
  +--> OpenRouter Client
  |
  +--> Prediction Engine
  |
  +--> Telegram/N8N integration
  |
  v
View or JSON response
```

Automation flow:

```text
n8n Schedule Trigger
  |
  v
Authenticated internal PHP endpoint
  |
  v
Active rules / report settings
  |
  v
Market data refresh
  |
  v
Threshold or report calculation
  |
  +--> Optional prediction refresh
  |
  v
Telegram node
  |
  v
Callback PHP endpoint to save result/log
```

## 3.1 Why this architecture

- MVC keeps HTTP handling and views organized.
- Services contain business logic.
- Repositories isolate SQL.
- Provider interfaces allow changing market APIs.
- n8n handles schedules and messaging.
- MySQL remains the single source of truth.
- The project stays understandable for a student and for Codex.

---

# 4. Exact project folder structure

```text
papertrade-ai/
|
|-- app/
|   |-- Config/
|   |   |-- AppConfig.php
|   |   |-- Database.php
|   |   `-- Env.php
|   |
|   |-- Controllers/
|   |   |-- AuthController.php
|   |   |-- DashboardController.php
|   |   |-- StocksController.php
|   |   |-- PredictionsController.php
|   |   |-- SetupController.php
|   |   `-- Api/
|   |       |-- MarketApiController.php
|   |       |-- PortfolioApiController.php
|   |       |-- WatchlistApiController.php
|   |       |-- PredictionApiController.php
|   |       |-- SettingsApiController.php
|   |       `-- AutomationApiController.php
|   |
|   |-- Middleware/
|   |   |-- AuthMiddleware.php
|   |   |-- GuestMiddleware.php
|   |   |-- CsrfMiddleware.php
|   |   `-- InternalApiMiddleware.php
|   |
|   |-- Models/
|   |   |-- User.php
|   |   |-- Stock.php
|   |   |-- Portfolio.php
|   |   |-- Holding.php
|   |   |-- Transaction.php
|   |   |-- WatchlistItem.php
|   |   |-- Prediction.php
|   |   |-- AlertRule.php
|   |   `-- UserSetting.php
|   |
|   |-- Repositories/
|   |   |-- UserRepository.php
|   |   |-- StockRepository.php
|   |   |-- PortfolioRepository.php
|   |   |-- TransactionRepository.php
|   |   |-- WatchlistRepository.php
|   |   |-- PriceSnapshotRepository.php
|   |   |-- PredictionRepository.php
|   |   |-- AlertRuleRepository.php
|   |   |-- AlertEventRepository.php
|   |   |-- SettingsRepository.php
|   |   `-- AutomationLogRepository.php
|   |
|   |-- Services/
|   |   |-- AuthService.php
|   |   |-- MarketDataService.php
|   |   |-- PortfolioService.php
|   |   |-- WatchlistService.php
|   |   |-- PredictionService.php
|   |   |-- PredictionScoreService.php
|   |   |-- TechnicalIndicatorService.php
|   |   |-- OpenRouterService.php
|   |   |-- DashboardService.php
|   |   |-- AlertService.php
|   |   |-- ReportService.php
|   |   `-- AutomationService.php
|   |
|   |-- Integrations/
|   |   |-- MarketData/
|   |   |   |-- MarketDataProviderInterface.php
|   |   |   |-- FinnhubMarketDataProvider.php
|   |   |   `-- TwelveDataMarketDataProvider.php
|   |   |
|   |   |-- OpenRouter/
|   |   |   `-- OpenRouterClient.php
|   |   |
|   |   `-- Telegram/
|   |       `-- TelegramTestClient.php
|   |
|   |-- Support/
|   |   |-- Router.php
|   |   |-- Request.php
|   |   |-- Response.php
|   |   |-- View.php
|   |   |-- Validator.php
|   |   |-- Csrf.php
|   |   |-- Logger.php
|   |   `-- Helpers.php
|   |
|   `-- Views/
|       |-- layouts/
|       |   |-- app.php
|       |   `-- auth.php
|       |
|       |-- partials/
|       |   |-- navbar.php
|       |   |-- flash.php
|       |   |-- stock-card.php
|       |   |-- prediction-badge.php
|       |   |-- chart-shell.php
|       |   `-- footer.php
|       |
|       |-- auth/
|       |   `-- login.php
|       |
|       |-- dashboard/
|       |   `-- index.php
|       |
|       |-- stocks/
|       |   `-- index.php
|       |
|       |-- predictions/
|       |   `-- index.php
|       |
|       |-- setup/
|       |   `-- index.php
|       |
|       `-- errors/
|           |-- 404.php
|           `-- 500.php
|
|-- bootstrap/
|   `-- app.php
|
|-- config/
|   |-- app.php
|   |-- market.php
|   |-- prediction.php
|   `-- routes.php
|
|-- database/
|   |-- schema.sql
|   |-- seed.php
|   `-- reset.php
|
|-- docs/
|   |-- MANUAL_SETUP.md
|   |-- N8N_SETUP.md
|   |-- TELEGRAM_SETUP.md
|   |-- MARKET_DATA_SETUP.md
|   |-- OPENROUTER_SETUP.md
|   |-- TROUBLESHOOTING.md
|   `-- API.md
|
|-- n8n/
|   `-- workflows/
|       |-- alert-monitor.json
|       |-- morning-report.json
|       |-- market-close-report.json
|       `-- prediction-refresh.json
|
|-- public/
|   |-- index.php
|   |-- .htaccess
|   |
|   `-- assets/
|       |-- css/
|       |   |-- reset.css
|       |   |-- variables.css
|       |   |-- app.css
|       |   |-- navbar.css
|       |   |-- dashboard.css
|       |   |-- stocks.css
|       |   |-- predictions.css
|       |   |-- setup.css
|       |   `-- responsive.css
|       |
|       `-- js/
|           |-- app.js
|           |-- api.js
|           |-- charts.js
|           |-- dashboard.js
|           |-- stocks.js
|           |-- predictions.js
|           |-- setup.js
|           `-- notifications.js
|
|-- storage/
|   |-- cache/
|   |-- logs/
|   `-- exports/
|
|-- tests/
|   |-- manual/
|   |   `-- acceptance-checklist.md
|   `-- php/
|
|-- .env
|-- .env.example
|-- .gitignore
|-- composer.json
`-- README.md
```

---

# 5. Navigation and page rules

## 5.1 Shared navbar

The authenticated navbar contains exactly:

```text
PaperTrade AI | Dashboard | Stocks | Predictions | Setup | Market status | Refresh | User menu
```

Required behavior:

- Shared across all four pages
- Current page visibly highlighted
- Responsive hamburger menu on small screens
- Market status badge:
  - Open
  - Closed
  - Delayed
  - Unknown
- Last successful market-data refresh time
- Manual refresh button
- User menu:
  - Account name
  - Role
  - Logout
- No separate Watchlist navbar page
- No separate Portfolio navbar page
- No separate stock-details navbar page

## 5.2 Login screen

The login screen is outside the authenticated navbar.

Required fields:

- Email
- Password
- Remember-me checkbox optional
- Login button
- Demo-account helper panel in development mode only

After login:

- Redirect to Dashboard
- Regenerate the PHP session ID
- Store only user ID, role, and minimal session metadata
- Do not store plain passwords

---

# 6. Three required demo accounts

Create exactly three seeded accounts:

| Display name | Email | Development password | Role | Intended use |
|---|---|---|---|---|
| Admin Demo | `admin@papertrade.local` | `Admin123!` | `admin` | Full setup and account controls |
| Analyst Demo | `analyst@papertrade.local` | `Analyst123!` | `analyst` | Predictions, watchlist, alerts |
| Trader Demo | `trader@papertrade.local` | `Trader123!` | `trader` | Normal paper trading |

## 6.1 Password rules

- Generate hashes through PHP `password_hash($password, PASSWORD_DEFAULT)`.
- Do not store plain passwords in SQL.
- The seed script may contain the development passwords only to generate hashes.
- Display these credentials only when `APP_ENV=development`.
- Add a warning that all passwords must be changed before any public deployment.

## 6.2 Role permissions

### Admin

Can:

- Use all four pages
- Change integration settings
- Test Telegram
- View automation logs
- Pause all automations
- Reset demo portfolios
- Change prediction configuration

### Analyst

Can:

- View all pages
- Search stocks
- Add/remove watchlist stocks
- View predictions
- Configure personal Telegram alerts and reports
- Simulate buy/sell
- Cannot reset other users or edit global integration credentials

### Trader

Can:

- View all pages
- Search stocks
- Buy/sell simulated stocks
- Add/remove watchlist stocks
- View predictions
- Configure personal alerts
- Cannot change global AI/provider settings

For this MVP, every user owns a separate paper portfolio, watchlist, settings, alerts, and prediction history.

---

# 7. Database design

Create `database/schema.sql` using InnoDB and `utf8mb4`.

## 7.1 Required tables

### `users`

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'analyst', 'trader') NOT NULL DEFAULT 'trader',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `user_settings`

```sql
CREATE TABLE user_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    base_currency CHAR(3) NOT NULL DEFAULT 'USD',
    timezone VARCHAR(64) NOT NULL DEFAULT 'Europe/Prague',
    starting_cash DECIMAL(18,2) NOT NULL DEFAULT 100000.00,
    current_cash DECIMAL(18,2) NOT NULL DEFAULT 100000.00,
    allow_fractional_shares TINYINT(1) NOT NULL DEFAULT 1,
    default_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    morning_report_enabled TINYINT(1) NOT NULL DEFAULT 0,
    morning_report_time TIME NOT NULL DEFAULT '07:30:00',
    market_close_report_enabled TINYINT(1) NOT NULL DEFAULT 0,
    quiet_hours_enabled TINYINT(1) NOT NULL DEFAULT 0,
    quiet_hours_start TIME NULL,
    quiet_hours_end TIME NULL,
    max_alerts_per_day INT UNSIGNED NOT NULL DEFAULT 20,
    ai_model VARCHAR(190) NOT NULL DEFAULT 'openrouter/free',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_settings_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `stocks`

```sql
CREATE TABLE stocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(32) NOT NULL,
    exchange_code VARCHAR(32) NOT NULL DEFAULT '',
    company_name VARCHAR(190) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    country VARCHAR(80) NULL,
    industry VARCHAR(120) NULL,
    logo_url VARCHAR(500) NULL,
    provider_symbol VARCHAR(80) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_stock_symbol_exchange (symbol, exchange_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `portfolios`

```sql
CREATE TABLE portfolios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL DEFAULT 'Main Paper Portfolio',
    base_currency CHAR(3) NOT NULL DEFAULT 'USD',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_portfolio_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `transactions`

All buys and sells must be transactions. Never directly edit a holding quantity as the primary operation.

```sql
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id BIGINT UNSIGNED NOT NULL,
    stock_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM('buy', 'sell') NOT NULL,
    quantity DECIMAL(20,8) NOT NULL,
    execution_price DECIMAL(20,8) NOT NULL,
    fee DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    gross_amount DECIMAL(18,2) NOT NULL,
    net_cash_effect DECIMAL(18,2) NOT NULL,
    executed_at DATETIME NOT NULL,
    note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transactions_portfolio_date (portfolio_id, executed_at),
    INDEX idx_transactions_stock_date (stock_id, executed_at),
    CONSTRAINT fk_transaction_portfolio
        FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    CONSTRAINT fk_transaction_stock
        FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `holdings`

This is a calculated cache for fast page loading. It must be updated inside the same database transaction as every simulated buy/sell.

```sql
CREATE TABLE holdings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id BIGINT UNSIGNED NOT NULL,
    stock_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(20,8) NOT NULL DEFAULT 0,
    average_cost DECIMAL(20,8) NOT NULL DEFAULT 0,
    total_cost DECIMAL(18,2) NOT NULL DEFAULT 0,
    realized_profit_loss DECIMAL(18,2) NOT NULL DEFAULT 0,
    first_bought_at DATETIME NULL,
    last_transaction_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_holding_portfolio_stock (portfolio_id, stock_id),
    CONSTRAINT fk_holding_portfolio
        FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    CONSTRAINT fk_holding_stock
        FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `watchlist_items`

The watchlist must accept stocks that the user does not own.

```sql
CREATE TABLE watchlist_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    stock_id BIGINT UNSIGNED NOT NULL,
    note VARCHAR(500) NULL,
    target_buy_price DECIMAL(20,8) NULL,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_watchlist_user_stock (user_id, stock_id),
    CONSTRAINT fk_watchlist_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_watchlist_stock
        FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `price_snapshots`

```sql
CREATE TABLE price_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_id BIGINT UNSIGNED NOT NULL,
    price DECIMAL(20,8) NOT NULL,
    open_price DECIMAL(20,8) NULL,
    high_price DECIMAL(20,8) NULL,
    low_price DECIMAL(20,8) NULL,
    previous_close DECIMAL(20,8) NULL,
    volume DECIMAL(24,4) NULL,
    provider VARCHAR(50) NOT NULL,
    provider_timestamp DATETIME NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_delayed TINYINT(1) NOT NULL DEFAULT 0,
    delay_minutes INT UNSIGNED NULL,
    INDEX idx_price_stock_received (stock_id, received_at),
    CONSTRAINT fk_price_stock
        FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `predictions`

```sql
CREATE TABLE predictions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    stock_id BIGINT UNSIGNED NOT NULL,
    horizon ENUM('1d', '7d', '30d') NOT NULL DEFAULT '7d',
    signal ENUM('buy', 'hold', 'sell', 'watch') NOT NULL,
    estimated_probability_up DECIMAL(5,2) NOT NULL,
    estimated_probability_down DECIMAL(5,2) NOT NULL,
    confidence_score DECIMAL(5,2) NOT NULL,
    risk_level ENUM('low', 'medium', 'high') NOT NULL,
    technical_score DECIMAL(5,2) NOT NULL,
    news_score DECIMAL(5,2) NULL,
    market_score DECIMAL(5,2) NULL,
    summary TEXT NOT NULL,
    positive_factors JSON NULL,
    negative_factors JSON NULL,
    invalidation_conditions JSON NULL,
    source_data_timestamp DATETIME NULL,
    model_name VARCHAR(190) NOT NULL,
    status ENUM('fresh', 'stale', 'partial', 'failed') NOT NULL DEFAULT 'fresh',
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    INDEX idx_prediction_user_stock_date (user_id, stock_id, generated_at),
    CONSTRAINT fk_prediction_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_prediction_stock
        FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `prediction_outcomes`

```sql
CREATE TABLE prediction_outcomes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prediction_id BIGINT UNSIGNED NOT NULL UNIQUE,
    start_price DECIMAL(20,8) NOT NULL,
    end_price DECIMAL(20,8) NULL,
    actual_change_percent DECIMAL(10,4) NULL,
    outcome ENUM('correct', 'incorrect', 'neutral', 'pending') NOT NULL DEFAULT 'pending',
    evaluated_at DATETIME NULL,
    CONSTRAINT fk_outcome_prediction
        FOREIGN KEY (prediction_id) REFERENCES predictions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `alert_rules`

```sql
CREATE TABLE alert_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    stock_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    threshold_type ENUM('percent', 'absolute_price', 'target_price') NOT NULL DEFAULT 'percent',
    threshold_value DECIMAL(20,8) NOT NULL,
    direction ENUM('increase', 'decrease', 'both') NOT NULL DEFAULT 'both',
    reference_type ENUM('previous_close', 'last_alert_price', 'average_cost', 'fixed_price') NOT NULL DEFAULT 'last_alert_price',
    reference_price DECIMAL(20,8) NULL,
    check_interval_minutes INT UNSIGNED NOT NULL DEFAULT 5,
    cooldown_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    market_hours_only TINYINT(1) NOT NULL DEFAULT 1,
    ai_commentary_enabled TINYINT(1) NOT NULL DEFAULT 1,
    minimum_confidence DECIMAL(5,2) NOT NULL DEFAULT 0,
    last_checked_at DATETIME NULL,
    last_alert_at DATETIME NULL,
    last_alert_price DECIMAL(20,8) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_alert_enabled_interval (is_enabled, check_interval_minutes),
    CONSTRAINT fk_alert_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_stock
        FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `alert_events`

```sql
CREATE TABLE alert_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alert_rule_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    stock_id BIGINT UNSIGNED NOT NULL,
    reference_price DECIMAL(20,8) NOT NULL,
    current_price DECIMAL(20,8) NOT NULL,
    change_amount DECIMAL(20,8) NOT NULL,
    change_percent DECIMAL(10,4) NOT NULL,
    prediction_id BIGINT UNSIGNED NULL,
    urgency ENUM('safe', 'watch', 'urgent') NOT NULL DEFAULT 'watch',
    message TEXT NOT NULL,
    telegram_status ENUM('pending', 'sent', 'failed', 'skipped') NOT NULL DEFAULT 'pending',
    telegram_error VARCHAR(500) NULL,
    triggered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    CONSTRAINT fk_alert_event_rule
        FOREIGN KEY (alert_rule_id) REFERENCES alert_rules(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_event_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_event_stock
        FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_event_prediction
        FOREIGN KEY (prediction_id) REFERENCES predictions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `telegram_connections`

```sql
CREATE TABLE telegram_connections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    chat_id VARCHAR(100) NOT NULL,
    telegram_username VARCHAR(100) NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    verified_at DATETIME NULL,
    last_test_at DATETIME NULL,
    last_test_status ENUM('success', 'failed') NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_telegram_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `dashboard_preferences`

```sql
CREATE TABLE dashboard_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    primary_chart_type ENUM('portfolio_value', 'stock_price', 'profit_loss') NOT NULL DEFAULT 'portfolio_value',
    primary_chart_stock_id BIGINT UNSIGNED NULL,
    secondary_chart_type ENUM('portfolio_allocation', 'stock_price', 'daily_performance') NOT NULL DEFAULT 'portfolio_allocation',
    secondary_chart_stock_id BIGINT UNSIGNED NULL,
    important_stock_ids JSON NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dashboard_pref_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_dashboard_primary_stock
        FOREIGN KEY (primary_chart_stock_id) REFERENCES stocks(id) ON DELETE SET NULL,
    CONSTRAINT fk_dashboard_secondary_stock
        FOREIGN KEY (secondary_chart_stock_id) REFERENCES stocks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `automation_logs`

```sql
CREATE TABLE automation_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_name VARCHAR(120) NOT NULL,
    execution_key VARCHAR(190) NULL,
    user_id BIGINT UNSIGNED NULL,
    status ENUM('started', 'success', 'partial', 'failed', 'skipped') NOT NULL,
    message VARCHAR(1000) NULL,
    context JSON NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL,
    INDEX idx_automation_workflow_date (workflow_name, started_at),
    CONSTRAINT fk_automation_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 7.2 Seed data

The seed script must:

1. Create the three users.
2. Create a portfolio and user settings for each user.
3. Add sample stock metadata for a small liquid demo universe, for example:
   - AAPL
   - MSFT
   - NVDA
   - AMZN
   - GOOGL
   - META
   - TSLA
   - AMD
4. Add a few simulated transactions for each account.
5. Add at least three watchlist stocks that are not owned.
6. Add at least two alert rules.
7. Generate passwords with `password_hash`.
8. Never overwrite existing production data unless the user explicitly runs `database/reset.php`.

---

# 8. Application routes

Use a front controller and route definitions.

## 8.1 Web routes

```text
GET  /login
POST /login
POST /logout

GET  /dashboard
GET  /stocks
GET  /predictions
GET  /setup
```

Only those four authenticated GET routes represent main pages.

## 8.2 JSON API routes

### Market

```text
GET /api/market/search?q={query}
GET /api/market/quote?symbol={symbol}&exchange={exchange}
GET /api/market/history?symbol={symbol}&range=1d|7d|1m|3m|1y
GET /api/market/profile?symbol={symbol}
GET /api/market/news?symbol={symbol}
GET /api/market/status?exchange={exchange}
POST /api/market/refresh
```

### Portfolio

```text
GET  /api/portfolio
GET  /api/portfolio/transactions
POST /api/portfolio/buy
POST /api/portfolio/sell
POST /api/portfolio/reset
```

### Watchlist

```text
GET    /api/watchlist
POST   /api/watchlist
DELETE /api/watchlist/{stockId}
PATCH  /api/watchlist/{stockId}
```

### Predictions

```text
GET  /api/predictions/owned
GET  /api/predictions/watchlist
GET  /api/predictions/discovery
GET  /api/predictions/history?stock_id={id}
POST /api/predictions/generate
POST /api/predictions/discover
```

### Setup

```text
GET  /api/settings
PATCH /api/settings/profile
PATCH /api/settings/dashboard
PATCH /api/settings/paper-account
PATCH /api/settings/ai
POST /api/settings/telegram/test
POST /api/settings/telegram/verify
GET  /api/settings/alerts
POST /api/settings/alerts
PATCH /api/settings/alerts/{id}
DELETE /api/settings/alerts/{id}
GET  /api/settings/automation-logs
POST /api/settings/automations/pause
POST /api/settings/automations/resume
```

### Internal n8n routes

Protect with `X-Internal-Api-Key`.

```text
GET  /api/internal/automation/due-alert-rules
POST /api/internal/automation/evaluate-alert
GET  /api/internal/automation/due-morning-reports
GET  /api/internal/automation/due-close-reports
POST /api/internal/automation/report-data
POST /api/internal/automation/log
POST /api/internal/automation/telegram-result
POST /api/internal/automation/refresh-predictions
```

Never expose the internal API key to browser JavaScript.

---

# 9. Page 1 — Dashboard

## 9.1 Purpose

Give a fast summary of the user’s paper portfolio, newest transactions, important stocks, predictions, and alerts.

## 9.2 KPI cards

Display:

- Current portfolio market value
- Available virtual cash
- Total invested cost
- Unrealized profit/loss
- Realized profit/loss
- Today’s change
- Number of owned stocks
- Number of watchlist stocks

Each value must show:

- Currency
- Positive/negative styling
- Data timestamp
- Loading state
- Error state

## 9.3 Recent transaction card

Show the most recent simulated buy or sell:

- Symbol and company
- Buy or sell
- Quantity
- Execution price
- Total
- Date and time
- Current value
- Profit/loss since transaction where meaningful

## 9.4 Two configurable graphs

Graph 1 and Graph 2 are controlled from Setup.

Allowed graph choices:

- Portfolio value over time
- Profit/loss over time
- Portfolio allocation
- Selected stock price
- Daily performance by holding

If the user selects a stock graph, Setup must also require a stock selection.

Use Chart.js.

## 9.5 Important-stock section

Show user-selected important stocks:

- Price
- Daily change
- Holding quantity, if owned
- Watchlist state
- Small prediction signal
- Estimated probability
- Confidence
- Prediction horizon
- Data freshness

## 9.6 Best and worst performers

Show:

- Best performing owned stock
- Worst performing owned stock
- Percentage and money change
- Link/button that opens the stock details panel inside the Stocks page

## 9.7 Recent alerts

Show the latest five alert events:

- Stock
- Change
- Urgency
- AI label
- Telegram delivery status
- Time

## 9.8 Dashboard acceptance criteria

- Each account sees only its own data.
- Both graphs change after Setup preferences are saved.
- No chart displays fabricated data.
- Stale data is labeled.
- Dashboard works with an empty portfolio.
- Buttons navigate to Stocks or Predictions without creating extra navbar pages.

---

# 10. Page 2 — Stocks

## 10.1 Purpose

This page combines:

- Owned stocks
- Watchlist
- Stock search
- Simulated buying
- Simulated selling
- Stock details
- Small predictions

Use internal tabs or segmented controls:

```text
Owned | Watchlist | Search
```

These are not navbar pages.

## 10.2 Owned tab

Each holding card or table row displays:

- Symbol
- Company name
- Exchange
- Quantity
- Average cost
- Current price
- Market value
- Total cost
- Today’s change
- Unrealized profit/loss
- Realized profit/loss
- Portfolio allocation
- Last price update
- Data delay status
- Small prediction:
  - Signal
  - Estimated probability of predicted direction
  - Confidence
  - Horizon
  - Risk
  - One-sentence explanation

Actions:

- View chart/details
- Buy more
- Sell
- Add alert
- Generate/refresh prediction
- Open detailed Predictions page

## 10.3 Watchlist tab

Every watchlist item displays:

- Symbol
- Company
- Current price
- Daily change
- Target buy price, optional
- Date added
- Short prediction
- Owned/not owned indicator
- Price freshness

Actions:

- Simulate buy
- Remove from watchlist
- Edit note/target
- Add alert
- View details
- Open detailed prediction

A stock may be both owned and on the watchlist. Do not remove it automatically after a simulated buy unless the user selects that option.

## 10.4 Search tab — critical requirement

The user must be able to search while deciding whether to buy.

Search by:

- Symbol
- Company name

For each result, show:

- Symbol
- Company
- Exchange
- Country
- Current price, when available
- Daily change
- Market status
- Already owned badge
- Already on watchlist badge

Every search result must provide these actions:

1. **Buy**
2. **Add to watchlist**
3. **View details**
4. **Detailed prediction**

The **Add to watchlist** action must work even when:

- The user owns zero shares
- No prediction exists yet
- The market is closed

If already on the watchlist:

- Replace the button with “In watchlist”
- Offer “Remove” through a secondary action
- Prevent duplicate database rows

When the user presses Buy:

1. Save/update stock metadata locally.
2. Open a simulated-buy modal.
3. Show current quote and timestamp.
4. Ask for quantity.
5. Show estimated total and fee.
6. Verify virtual cash.
7. Require CSRF-protected confirmation.
8. Create a transaction.
9. Update holding and cash in one SQL transaction.
10. Optionally ask whether to keep/add the stock in the watchlist.

## 10.5 Simulated sell behavior

The sell modal must:

- Display available quantity
- Prevent selling more than owned
- Support fractional quantity if enabled
- Show estimated proceeds and fee
- Require confirmation
- Create a sell transaction
- Update average-cost/realized-P&L logic correctly
- Remove the holding cache row or set quantity to zero when fully sold
- Keep transaction history
- Keep watchlist status unchanged

Use weighted-average cost for the MVP.

## 10.6 Stock details without a fifth page

Open details in a large modal, side drawer, or expandable panel within `/stocks`.

Display:

- Symbol and profile
- Current quote
- Price timestamp and delay
- Time-range selector:
  - 1D
  - 7D
  - 1M
  - 3M
  - 1Y
- Price chart
- Volume if available
- Open/high/low/previous close
- Owned quantity and average cost
- Watchlist status
- Recent transactions
- Recent news
- Short prediction
- Prediction history
- Buy, sell, watchlist, and alert actions

## 10.7 Empty and failure states

Required messages:

- “You do not own any stocks yet.”
- “Your watchlist is empty.”
- “No matching stocks found.”
- “Current quote unavailable.”
- “Historical data is unavailable on the configured provider plan.”
- “Showing the most recent cached price from {timestamp}.”

---

# 11. Page 3 — Predictions

## 11.1 Purpose

Provide detailed analysis for:

- Owned stocks
- Watchlist stocks
- Discovery candidates not currently owned

Internal sections:

```text
My stocks | Watchlist | Opportunities | History
```

These are not navbar pages.

## 11.2 Prediction timeframe

Every prediction must include one horizon:

- Next trading day (`1d`)
- Next 7 calendar days (`7d`)
- Next 30 calendar days (`30d`)

Default: `7d`.

The percentage must never be displayed without its horizon.

Correct example:

```text
Estimated chance of increasing: 67%
Horizon: Next 7 days
Confidence: 61%
Risk: Medium
Signal: WATCH
```

## 11.3 Probability versus confidence

Keep them separate.

### Estimated probability

The application’s estimated chance that the price moves in the predicted direction during the stated horizon.

### Confidence

How reliable the system considers the estimate based on:

- Data completeness
- Data freshness
- Agreement between technical, news, and market signals
- Availability of historical points
- News coverage
- API failures

## 11.4 Explainable MVP scoring method

Do not ask the LLM to invent an unsupported percentage.

Calculate a heuristic score in PHP.

Suggested components:

### Technical score: 50%

Use available price history to calculate:

- Short momentum
- SMA20 versus SMA50
- RSI
- MACD direction where enough data exists
- Volume trend where volume exists
- Distance from recent high/low
- Volatility penalty

### News score: 30%

OpenRouter analyses recent news supplied by the backend and returns a bounded sentiment score from 0 to 100 plus reasons.

### Market score: 20%

Use:

- Relevant market index trend
- Sector trend where available
- Market open/closed context
- Broad volatility context where available

Formula:

```text
raw_probability_up =
    technical_score * 0.50
  + news_score * 0.30
  + market_score * 0.20
```

Fallback weights:

- If news is unavailable, redistribute its weight across technical and market.
- If market context is unavailable, redistribute across available components.
- Record which components were missing.
- Clamp displayed probabilities to 5%–95%.
- Set `estimated_probability_down = 100 - estimated_probability_up`.
- Mark prediction `partial` if any important component is missing.

This is a transparent heuristic for a school MVP, not a statistically calibrated guarantee.

## 11.5 Signal mapping

Suggested default mapping:

```text
BUY:
  probability_up >= 70
  confidence >= 60
  risk is not high

WATCH:
  probability_up >= 58
  or confidence is below BUY threshold

HOLD:
  probability_up between 45 and 57
  for an owned stock

SELL:
  probability_down >= 70
  confidence >= 60
```

Allow configuration later, but keep defaults in `config/prediction.php`.

## 11.6 Detailed prediction card

Show:

- Stock
- Owned/watchlist/discovery status
- Current price
- Prediction horizon
- Estimated probability up
- Estimated probability down
- Confidence
- BUY/HOLD/SELL/WATCH
- Risk
- Technical score
- News score
- Market score
- Main explanation
- Positive factors
- Negative factors
- Conditions that would invalidate the analysis
- Data sources and timestamps
- Prediction generation time
- Expiration time
- Buttons:
  - Add to watchlist
  - Buy simulated
  - Add alert
  - Refresh prediction
  - View history

## 11.7 Opportunities discovery

This section finds potentially interesting stocks that the user does not currently own.

Do not scan the entire market.

Use a configurable candidate universe:

- Seed list of liquid stocks
- User-selected exchanges
- User-selected sectors
- Optional price range
- Optional risk level
- Optional minimum probability
- Optional minimum confidence
- Maximum candidates per run

Default maximum: 20 candidates analysed, 5 shown.

Exclude or label:

- Already owned stocks
- Already watchlisted stocks

Each opportunity must have:

- Add to watchlist button
- Simulated buy button
- Detailed prediction
- Reason it was selected
- Probability, confidence, risk, and horizon
- Freshness timestamp

The **Add to watchlist** button is required for every discovery result.

## 11.8 Prediction history and evaluation

Save every generated prediction.

When its horizon ends:

- Fetch end price
- Calculate actual percentage change
- Mark:
  - Correct
  - Incorrect
  - Neutral
  - Pending
- Display previous result accuracy carefully
- Do not call the system “accurate” from a tiny sample

History table:

- Generated date
- Horizon
- Signal
- Probability
- Confidence
- Start price
- End price
- Actual change
- Outcome

---

# 12. Page 4 — Setup

## 12.1 Purpose

Configure:

- Paper account
- Dashboard
- Important stocks
- Market data display
- AI behavior
- Telegram
- Alert rules
- Morning reports
- Market-close reports
- Automation status

Use sections or accordions on one `/setup` page.

## 12.2 Paper account settings

- Base currency
- Starting virtual balance
- Current virtual cash display
- Fractional shares enabled
- Default simulated fee
- Reset portfolio button

Reset requires:

- Admin or owner confirmation
- Password re-entry
- Clear warning
- Separate server action
- Recreate starting cash
- Delete simulated transactions, holdings, predictions tied to positions, and alerts only according to explicit choices
- Never run automatically

## 12.3 Dashboard settings

- Select Graph 1 type
- Select Graph 1 stock when needed
- Select Graph 2 type
- Select Graph 2 stock when needed
- Select important stocks
- Choose maximum important-stock cards

Stock selectors must search both:

- Owned stocks
- Watchlist stocks

## 12.4 AI settings

- OpenRouter model name
- Default horizon
- Auto-refresh frequency
- Minimum confidence to label urgent
- Enable/disable news analysis
- Maximum news items per request
- Show token/cost usage when available
- Test AI connection button

Only Admin may edit global API credentials. Other users may choose from allowed model options.

## 12.5 Telegram connection

Fields:

- Chat ID
- Telegram username, optional
- Enable Telegram
- Test message button
- Verification status
- Last test time and result

Never expose the bot token in the browser. Store it in `.env` or n8n credentials.

## 12.6 Alert-rule builder

Required fields:

- Stock search/select
- Alert name
- Enabled
- Threshold type:
  - Percentage change
  - Absolute price movement
  - Target price
- Threshold value
- Direction:
  - Increase
  - Decrease
  - Both
- Reference:
  - Previous close
  - Last alert price
  - Average purchase price
  - Fixed reference price
- Check interval:
  - 5 minutes
  - 15 minutes
  - 30 minutes
  - 60 minutes
- Cooldown
- Market-hours only
- Include AI commentary
- Minimum confidence
- Test alert

Default interpretation of `0.2`:

- If threshold type is percentage, it means `0.2%`.
- Always show the unit beside the input.
- Never guess between percent and currency.

## 12.7 Duplicate alert prevention

An alert triggers only when:

1. The threshold is crossed.
2. The rule is enabled.
3. The cooldown has expired.
4. Quiet hours permit delivery.
5. The daily maximum has not been reached.
6. Market-hours-only rules are inside valid exchange hours.
7. A quote newer than the allowed stale limit exists.

After a sent alert:

- Save the event
- Save current price as `last_alert_price`
- Save `last_alert_at`

## 12.8 Telegram alert format

```text
⚠️ PRICE ALERT — {SYMBOL}

Current price: {PRICE} {CURRENCY}
Movement: {CHANGE_PERCENT}%
Reference: {REFERENCE_PRICE}
Threshold: {THRESHOLD}
Market status: {MARKET_STATUS}
Price updated: {PRICE_TIMESTAMP}

AI signal: {SIGNAL}
Urgency: {SAFE|WATCH|URGENT}
Estimated probability: {PROBABILITY}% over {HORIZON}
Confidence: {CONFIDENCE}%
Risk: {RISK}

Reason:
{SHORT_EXPLANATION}

Paper-trading educational analysis only.
```

“Safe,” “Watch,” and “Urgent” are interface urgency labels, not commands to execute a trade.

## 12.9 Morning report

User settings:

- Enabled
- Time
- Timezone
- Weekdays or every day
- Important stocks
- Include watchlist
- Maximum stocks
- Include news
- Include predictions

Default timezone:

`Europe/Prague`

Report contains:

- Previous market-session summary
- Portfolio value and change
- Virtual cash
- Best and worst owned stocks
- Important-stock moves
- Watchlist opportunities
- Overnight/recent news summary
- Alerts close to thresholds
- Prediction changes
- Data timestamps

## 12.10 Market-close report

Settings:

- Enabled
- Exchanges to follow
- Important stocks
- Include watchlist
- Include prediction update

Do not hardcode one universal close time.

Use exchange status or an exchange calendar strategy. If reliable exchange-calendar data is unavailable:

- Use configured exchange timezone and schedule
- Display that the schedule is approximate
- Do not send multiple close reports for the same exchange session

## 12.11 Quiet hours

- Enabled
- Start
- End
- Timezone
- Urgent-alert override optional

Morning reports are allowed at their scheduled time even if it overlaps quiet hours, unless explicitly disabled.

## 12.12 Automation status

Display:

- n8n configured/not configured
- Last successful alert workflow
- Last morning report
- Last market-close report
- Last prediction refresh
- Last error
- Pause all
- Resume all
- View recent logs

---

# 13. Authentication and security

## 13.1 Login

- PHP sessions
- `password_verify`
- Session regeneration after login
- Generic invalid-credentials message
- Rate-limit repeated attempts
- Inactive users cannot log in
- Logout must destroy the session
- POST logout with CSRF token

## 13.2 Authorization

Every controller and API endpoint must verify:

- Authenticated user
- Resource ownership
- Role permission where required

Never trust `user_id` from the browser. Use the authenticated session user.

## 13.3 CSRF

Require CSRF protection for:

- Login
- Logout
- Buy
- Sell
- Watchlist mutation
- Settings changes
- Alert changes
- Portfolio reset

## 13.4 SQL

- PDO prepared statements
- Transactions for buy/sell/reset
- No SQL string concatenation with user values
- Validate DECIMAL inputs
- Enforce positive quantities

## 13.5 External API security

Never place these in frontend code:

```text
MARKET_DATA_API_KEY
MARKET_DATA_FALLBACK_API_KEY
OPENROUTER_API_KEY
TELEGRAM_BOT_TOKEN
INTERNAL_N8N_API_KEY
DB_PASSWORD
```

## 13.6 Logging

Do not log:

- Passwords
- API keys
- Full authorization headers
- Telegram bot token
- Session IDs

Mask secrets in errors.

---

# 14. Environment configuration

Create `.env.example`:

```dotenv
APP_NAME="PaperTrade AI"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/papertrade-ai/public
APP_TIMEZONE=Europe/Prague
SESSION_NAME=papertrade_session

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=papertrade_ai
DB_USERNAME=root
DB_PASSWORD=

MARKET_DATA_PROVIDER=finnhub
MARKET_DATA_API_KEY=
MARKET_DATA_FALLBACK_PROVIDER=twelvedata
MARKET_DATA_FALLBACK_API_KEY=
MARKET_DATA_CACHE_SECONDS=60
MARKET_DATA_STALE_AFTER_SECONDS=900

OPENROUTER_API_KEY=
OPENROUTER_MODEL=openrouter/free
OPENROUTER_SITE_URL=http://localhost/papertrade-ai/public
OPENROUTER_SITE_NAME="PaperTrade AI"

TELEGRAM_BOT_TOKEN=

INTERNAL_N8N_API_KEY=replace-with-a-long-random-value
N8N_BASE_URL=http://localhost:5678
AUTOMATIONS_ENABLED=true

DEFAULT_BASE_CURRENCY=USD
DEFAULT_STARTING_CASH=100000.00
```

Add `.env` to `.gitignore`.

---

# 15. Market-data behavior

## 15.1 Normalized quote object

Every provider must return the same internal structure:

```json
{
  "symbol": "AAPL",
  "exchange": "NASDAQ",
  "currency": "USD",
  "price": 210.15,
  "open": 208.50,
  "high": 211.00,
  "low": 207.90,
  "previous_close": 208.00,
  "change_amount": 2.15,
  "change_percent": 1.03,
  "volume": 12345678,
  "market_status": "open",
  "provider": "finnhub",
  "provider_timestamp": "2026-07-21T14:30:00Z",
  "received_at": "2026-07-21T14:30:02Z",
  "is_delayed": false,
  "delay_minutes": 0
}
```

## 15.2 Caching

- Cache symbol search for 24 hours.
- Cache company profile for 24 hours.
- Cache news for 15 minutes.
- Cache quotes according to provider limits, default 60 seconds.
- Save important quotes to `price_snapshots`.
- Avoid refreshing every card separately.
- Batch or deduplicate requests where possible.

## 15.3 Freshness labels

Display:

- Live
- Delayed by approximately N minutes
- Cached
- Stale
- Unavailable

Do not write “live” when the provider reports delayed data or when delay is unknown.

## 15.4 API-limit behavior

When rate-limited:

1. Use the newest valid cache.
2. Mark it cached/stale.
3. Do not request AI analysis as if the price were current.
4. Log the error without the API key.
5. Show when retry is possible if known.

---

# 16. OpenRouter prediction contract

## 16.1 Inputs sent to OpenRouter

The backend sends a compact data package, not raw unrestricted user input:

- Stock profile
- Current quote
- Historical indicators
- Recent news titles and summaries
- Sector/market context
- User position context
- Selected horizon
- The deterministic technical and market scores
- Required output schema
- Educational disclaimer

## 16.2 Required JSON output

```json
{
  "news_sentiment_score": 62,
  "signal": "watch",
  "risk_level": "medium",
  "summary": "Short explanation.",
  "positive_factors": [
    "Factor one"
  ],
  "negative_factors": [
    "Factor one"
  ],
  "invalidation_conditions": [
    "Condition one"
  ],
  "urgency": "watch"
}
```

Validate:

- Score: 0–100
- Signal: buy, hold, sell, watch
- Risk: low, medium, high
- Urgency: safe, watch, urgent
- Maximum array sizes
- Maximum string lengths

If invalid JSON is returned:

1. Retry once with a repair prompt.
2. If still invalid, use technical-only prediction.
3. Mark status `partial`.
4. Do not silently invent AI content.

## 16.3 AI safety language

Forbidden wording:

- Guaranteed
- Certain profit
- Cannot lose
- Definitely buy
- Definitely sell
- Risk-free

Preferred wording:

- Estimated
- Suggests
- May
- Could
- Based on available data
- Uncertain
- Educational analysis

---

# 17. n8n workflow specifications

## 17.1 Workflow A — Alert monitor

File:

`n8n/workflows/alert-monitor.json`

Schedule:

- Run every five minutes.
- PHP filters rules by each rule’s configured interval.

Nodes:

1. Schedule Trigger
2. HTTP Request: due alert rules
3. Loop/Batch
4. HTTP Request: evaluate alert
5. IF: triggered
6. Telegram Send Message
7. HTTP Request: save Telegram result
8. Error branch: automation log

Evaluation should occur in PHP so the same business rules are testable outside n8n.

## 17.2 Workflow B — Morning report

File:

`n8n/workflows/morning-report.json`

Schedule:

- Every 15 minutes
- PHP returns users whose local scheduled report is due and not yet sent today

Nodes:

1. Schedule Trigger
2. HTTP Request: due morning reports
3. Loop users
4. HTTP Request: report data
5. Telegram Send Message
6. Log result

## 17.3 Workflow C — Market-close report

File:

`n8n/workflows/market-close-report.json`

Schedule:

- Every 15 minutes
- PHP determines due exchanges/sessions

Must store a session key to avoid duplicate reports.

## 17.4 Workflow D — Prediction refresh

File:

`n8n/workflows/prediction-refresh.json`

Schedule:

- Configurable, default every six hours
- Refresh only:
  - Owned stocks
  - Watchlist stocks
  - Limited discovery universe
- Respect API limits
- Skip unchanged/fresh predictions

## 17.5 n8n to WAMP networking

When n8n runs in Docker on Windows, PHP endpoints may be reached through:

```text
http://host.docker.internal/papertrade-ai/public/api/internal/...
```

Codex must explain how to test this from an n8n HTTP Request node.

Every request includes:

```text
X-Internal-Api-Key: value-from-INTERNAL_N8N_API_KEY
```

---

# 18. Manual setup guide Codex must create

Codex must generate detailed files under `docs/`.

## 18.1 WAMP64 setup

Explain:

1. Install/start WAMP64.
2. Confirm the tray icon is green.
3. Confirm `http://localhost/` opens.
4. Put the project in the configured web root, commonly `C:\wamp64\www\papertrade-ai`.
5. Create `papertrade_ai` in phpMyAdmin.
6. Import `database/schema.sql`.
7. Run the seed script.
8. Configure Apache rewrite rules.
9. Enable required PHP extensions:
   - curl
   - pdo_mysql
   - openssl
   - json
   - mbstring
10. Test `/login`.

## 18.2 Market-data API setup

Explain:

1. Create an account with the selected provider.
2. Generate an API key.
3. Copy it into `.env`.
4. Run a test script or Setup connection test.
5. Search AAPL.
6. Verify:
   - Symbol result
   - Quote
   - Timestamp
   - Delay label
7. Explain rate-limit and plan errors.
8. Explain how to switch provider through `.env`.

Do not claim that a free plan guarantees every exchange or true real-time data.

## 18.3 OpenRouter setup

Explain:

1. Create an OpenRouter account.
2. Create an API key.
3. Add credits or choose an available free model.
4. Add the key to `.env`.
5. Configure model.
6. Run the Setup AI test.
7. Validate JSON response.
8. Explain invalid model, insufficient credit, rate limit, and invalid-key errors.

## 18.4 Telegram setup

Explain:

1. Open Telegram.
2. Find BotFather.
3. Create a bot with `/newbot`.
4. Copy bot token.
5. Store token in n8n credentials and/or server `.env`.
6. Send the bot a message.
7. Obtain the chat ID through a Telegram Trigger or Bot API update flow.
8. Save chat ID in Setup.
9. Press Test Telegram.
10. Verify the connection.
11. Explain why the bot cannot message a user who never started the chat.

## 18.5 n8n Docker setup

Explain:

1. Install Docker Desktop.
2. Start Docker Desktop.
3. Create a persistent n8n volume.
4. Run n8n with a documented Docker command or Compose file.
5. Open `http://localhost:5678`.
6. Create the n8n owner account.
7. Import the four workflow JSON files.
8. Create Telegram credentials.
9. Configure internal API base URL.
10. Activate workflows.
11. Test each workflow manually.
12. Explain that alerts stop when the PC, Docker, WAMP, or internet connection is off.

## 18.6 Troubleshooting

Include at least:

- WAMP icon not green
- Apache port conflict
- MySQL not starting
- `.htaccess` ignored
- PHP cURL missing
- PDO connection failure
- 404 routes
- CSRF failure
- Market API 401/403/429
- Missing quote/history
- OpenRouter invalid JSON
- OpenRouter insufficient credits
- Telegram chat not found
- Telegram bot blocked
- n8n cannot reach localhost
- n8n workflow inactive
- Docker unavailable
- Stale price warning
- Duplicate alerts
- Timezone mismatch

---

# 19. User-interface requirements

## 19.1 Visual style

Use a professional financial-dashboard style:

- Dark or light theme may be selected by Codex, but keep it consistent.
- Clear green/red movement indicators with icons, not color alone.
- Accessible contrast.
- Responsive cards.
- Tables scroll horizontally on mobile.
- Loading skeletons.
- Empty states.
- Toast notifications.
- Confirmation modals for buy/sell/reset.
- Data-source/freshness tooltips.

## 19.2 Accessibility

- Keyboard-accessible navbar and modals
- Visible focus styles
- Form labels
- ARIA labels for icon buttons
- Do not communicate gains/losses only by color
- Chart fallback summary text
- Error messages connected to fields

## 19.3 Number formatting

- Money: locale-aware with currency
- Quantity: up to eight decimal places, trim unnecessary zeros
- Percentage: two decimals by default
- Probability/confidence: whole number or one decimal
- Timestamps: user timezone, with provider timezone details available

---

# 20. Core business rules

## 20.1 Buy

Reject when:

- Quantity <= 0
- Quote unavailable or too stale
- Insufficient virtual cash
- Fractional shares disabled and quantity is not whole
- Stock inactive
- CSRF invalid

Within one SQL transaction:

1. Lock user settings/portfolio rows as needed.
2. Create transaction.
3. Update cash.
4. Recalculate/update holding.
5. Commit.
6. Roll back everything on failure.

## 20.2 Sell

Reject when:

- Quantity <= 0
- Quantity exceeds holding
- Quote unavailable or too stale
- Invalid fractional quantity
- CSRF invalid

Update realized profit/loss using weighted average cost.

## 20.3 Watchlist

- Search result can be added without buying.
- Prediction result can be added without buying.
- Owned stock can be added.
- Duplicate insertion returns success-like “already in watchlist,” not a server crash.
- Removing from watchlist never sells or deletes transactions.

## 20.4 Prices

- Do not recalculate historical simulated execution prices.
- New simulated trades use the latest accepted quote.
- Save execution timestamp and quote timestamp.
- Show a confirmation warning when a quote is delayed.

## 20.5 Predictions

- Save source timestamps.
- Expire predictions.
- Do not reuse a stale prediction without labeling it.
- Use deterministic score plus AI explanation.
- Do not display a percentage if required inputs are too incomplete; show “insufficient data.”

---

# 21. Error-handling requirements

Every external integration must return a normalized result:

```json
{
  "success": false,
  "error_code": "MARKET_RATE_LIMIT",
  "message": "Market data request limit reached.",
  "retryable": true,
  "cached_data_available": true
}
```

Required fallback behavior:

| Failure | Behavior |
|---|---|
| Market quote unavailable | Show cached value with timestamp or disable trade |
| Historical data unavailable | Use stored snapshots or show no-chart state |
| News unavailable | Generate technical-only partial prediction |
| OpenRouter unavailable | Show deterministic prediction without AI explanation |
| Telegram send fails | Save failed event and expose retry/status |
| n8n offline | Show automation offline/unknown status |
| Market closed | Show last quote; do not call it live |
| Invalid symbol | Do not save stock; show clear search error |
| Database failure | Roll back transaction and show safe generic error |

---

# 22. Build phases

## Phase 1 — Foundation

Create:

- Folder structure
- Environment loader
- Database connection
- Router
- Base controller/view
- Error handling
- Shared layout
- Navbar
- Login/logout

Checkpoint:

- Three accounts can log in.
- Unauthorized access redirects to login.
- Navbar contains exactly four main pages.

## Phase 2 — Database and paper portfolio

Create:

- Schema
- Seed script
- Repositories
- Portfolio service
- Buy/sell logic
- Transaction history
- Cash handling

Checkpoint:

- Buy and sell use virtual cash.
- Accounts remain isolated.
- Holdings match transaction history.

## Phase 3 — Market integration

Create:

- Provider interface
- Default provider
- Search
- Quote
- History
- Profile
- Cache/freshness labels

Checkpoint:

- Search returns real provider results.
- Add-to-watchlist works directly from search.
- Delayed/stale status is visible.

## Phase 4 — Four pages

Create:

- Dashboard
- Stocks
- Predictions shell
- Setup shell
- Responsive design
- Chart.js integration

Checkpoint:

- All four navbar pages are usable.
- Stock details do not create a fifth main page.

## Phase 5 — Prediction engine

Create:

- Technical indicators
- Heuristic score
- OpenRouter integration
- Structured validation
- Detailed prediction
- Discovery
- History/outcomes

Checkpoint:

- Percentage always has a horizon.
- Probability and confidence are separate.
- Discovery results have Add to watchlist and Buy buttons.

## Phase 6 — Telegram and n8n

Create:

- Internal API protection
- Alert workflow
- Morning workflow
- Close workflow
- Prediction workflow
- Telegram test
- Logs

Checkpoint:

- Test alert reaches Telegram.
- Cooldown prevents spam.
- Morning report uses the user timezone.

## Phase 7 — Security and polish

Create:

- CSRF
- Rate limiting
- Ownership checks
- Secret masking
- Accessibility
- Empty states
- Troubleshooting docs
- Acceptance tests

---

# 23. Acceptance checklist

## Authentication

- [ ] Three seeded accounts exist.
- [ ] All passwords are hashed.
- [ ] Each account can log in.
- [ ] Invalid login is rejected.
- [ ] Logout destroys the session.
- [ ] Users cannot access another user’s portfolio.

## Navigation

- [ ] Navbar has Dashboard, Stocks, Predictions, Setup.
- [ ] Active page is highlighted.
- [ ] Mobile navigation works.
- [ ] Login is not treated as a fifth authenticated page.

## Dashboard

- [ ] KPIs use current or labeled cached data.
- [ ] Two graphs are configurable.
- [ ] Recent transaction displays.
- [ ] Important stocks display.
- [ ] Recent alerts display.
- [ ] Empty portfolio does not crash.

## Stocks

- [ ] Owned tab works.
- [ ] Watchlist tab works.
- [ ] Search tab works.
- [ ] Every search result has Buy.
- [ ] Every search result has Add to watchlist.
- [ ] A stock can be watchlisted without being owned.
- [ ] Duplicate watchlist items are prevented.
- [ ] Buy reduces virtual cash.
- [ ] Sell increases virtual cash.
- [ ] Cannot oversell.
- [ ] Every stock has a chart/details view.
- [ ] Every owned/watchlist stock has a short prediction or a clear unavailable state.

## Predictions

- [ ] Owned-stock predictions display.
- [ ] Watchlist predictions display.
- [ ] Discovery includes non-owned candidates.
- [ ] Every discovery result has Add to watchlist.
- [ ] Every discovery result has simulated Buy.
- [ ] Every percentage has a timeframe.
- [ ] Probability and confidence are different fields.
- [ ] Prediction history is saved.
- [ ] Prediction outcomes can be evaluated.
- [ ] Disclaimer is visible.

## Setup

- [ ] Paper-account settings save.
- [ ] Dashboard graph settings save.
- [ ] Important stocks save.
- [ ] Telegram test works.
- [ ] Alert rule can be created, edited, paused, and deleted.
- [ ] `0.2` visibly means `0.2%` when percent type is selected.
- [ ] Cooldown works.
- [ ] Quiet hours work.
- [ ] Morning report time and timezone save.
- [ ] Market-close report can be enabled.
- [ ] Automation logs display.

## Integrations

- [ ] Market provider key is server-side.
- [ ] OpenRouter key is server-side.
- [ ] Telegram bot token is server-side/n8n credential.
- [ ] n8n internal key is required.
- [ ] API rate-limit fallback works.
- [ ] Stale values are labeled.
- [ ] n8n can reach WAMP through the configured host address.

## Security

- [ ] PDO prepared statements used.
- [ ] CSRF enabled on mutations.
- [ ] Sessions regenerate after login.
- [ ] Secrets are not logged.
- [ ] Buy/sell use database transactions.
- [ ] Admin-only settings are protected.
- [ ] `.env` is ignored by Git.

---

# 24. Definition of done

The MVP is complete when:

1. The user can log in with any of the three demo accounts.
2. The authenticated application has exactly four navbar pages.
3. Real external market data is displayed with truthful freshness labels.
4. A user can search for a stock and choose either:
   - Buy simulated
   - Add to watchlist
   - View details
   - View detailed prediction
5. A user can simulate buying and selling without real money.
6. Owned and watchlist stocks have charts and short predictions.
7. The Predictions page includes detailed analysis and non-owned opportunities.
8. Prediction percentages include a horizon and are presented as estimates.
9. The Setup page controls alerts, Telegram, reports, dashboard graphs, and paper-account settings.
10. n8n can send Telegram threshold alerts and scheduled reports.
11. External failures produce safe fallback states.
12. Codex-created documentation guides every manual setup action.

---

# 25. Recommended official references

Codex should use current official documentation while implementing and should verify endpoint availability against the user’s selected plans:

- WampServer: `https://www.wampserver.com/en/`
- n8n Docker installation: `https://docs.n8n.io/deploy/host-n8n/install-options/install-with-docker/`
- n8n Telegram node: `https://docs.n8n.io/integrations/builtin/app-nodes/n8n-nodes-base.telegram/`
- Telegram Bot API: `https://core.telegram.org/bots/api`
- OpenRouter quickstart: `https://openrouter.ai/docs/quickstart`
- Finnhub API documentation: `https://finnhub.io/docs/api`
- Twelve Data API documentation: `https://twelvedata.com/docs`
- Chart.js documentation: `https://www.chartjs.org/docs/latest/`

---

# 26. Final automatic implementation instruction to Codex

Execute the project in **automatic-generation-first mode**.

Do not stop after Phase 1. Build all code, configuration templates, SQL, scripts, documentation, and importable workflow files that can be generated without external secrets or GUI-only actions.

## 26.1 Required execution order

1. Inspect the local repository and available environment.
2. Generate the entire agreed folder structure.
3. Generate all backend, frontend, database, integration, testing, and documentation files.
4. Run syntax and static checks that are available locally.
5. Create the database setup and seed utilities.
6. Create the three demo accounts through the seed utility.
7. Create all four pages and the shared navbar.
8. Implement stock search, watchlist, paper buy/sell, portfolio calculations, and charts.
9. Implement prediction scoring, OpenRouter integration, and fallback behavior.
10. Implement internal automation endpoints.
11. Generate Docker Compose and four importable n8n workflows.
12. Generate Telegram message templates and test actions.
13. Generate health-check and integration-test scripts.
14. Generate `SETUP_HANDOFF.md` with the exact three-person lists from Section 27.
15. Generate `TEAM_STATUS.md` using the format from Section 28.
16. Run all tests possible without missing external keys.
17. Mark tests that require human setup as `BLOCKED_BY_SETUP`, not as code failures.
18. Immediately print the three-person assignment lists after generation.

## 26.2 Codex output after generation

Codex’s final response must contain, in this order:

1. **Generated automatically**
   - Concise list of completed files and systems.
2. **Automatic tests**
   - PASS/FAIL/BLOCKED_BY_SETUP table.
3. **Person 1 checklist**
   - Exact detailed checklist.
4. **Person 2 checklist**
   - Exact detailed checklist.
5. **Person 3 checklist**
   - Exact detailed checklist.
6. **All-team integration test**
   - Exact order and expected results.
7. **Known limitations**
   - Only genuine remaining limitations.
8. **First command to run**
   - The single next action for each person.

Do not respond only with general advice. Do not say “configure the API” without giving the precise field, file, test command, expected output, and error fix.

## 26.3 Definition of automatic completion

Codex has completed its part only when a teammate can clone or open the generated project and the remaining work is limited primarily to:

- Starting installed programs
- Creating external service accounts
- Copying secret keys
- Importing/activating workflows
- Running the generated setup/test commands
- Fixing machine-specific port or permission conflicts

---

# 27. Mandatory three-person setup handoff

Codex must generate `SETUP_HANDOFF.md` containing the following three separate assignments. It must customize paths, commands, and detected versions when it has access to the machine.

The three teammates must be able to work in parallel. Every checklist item must include:

- The action
- The exact command, URL, menu, or file
- The value to enter
- The expected result
- A checkbox
- A troubleshooting note
- The evidence to report in `TEAM_STATUS.md`

Do not put one required setup task in two people’s lists unless it is an explicit verification step.

## 27.1 Person 1 — WAMP64, PHP, MySQL, application, and login

### Mission

Make the PHP application and database work locally, create the three accounts, and verify the complete paper-trading database flow.

### Detailed checklist

#### A. Start and verify WAMP64

- [ ] Start WAMP64 as an administrator if required.
- [ ] Wait until the WAMP tray icon is green.
- [ ] Open:

```text
http://localhost/
```

Expected result:

- WAMP landing page or configured localhost page opens.

If it fails:

- Orange/red icon: inspect Apache/MySQL service status.
- Port conflict: check whether IIS, Skype, another Apache, or another MySQL service is using the required port.
- Record the actual Apache and MySQL ports.

Evidence for `TEAM_STATUS.md`:

```text
WAMP: PASS
Apache port: ...
MySQL port: ...
PHP version: ...
```

#### B. Place the project in the WAMP web root

Default path:

```text
C:\wamp64\www\papertrade-ai
```

- [ ] Confirm the actual WAMP web root.
- [ ] Clone/copy the generated project into that folder.
- [ ] Confirm this file exists:

```text
C:\wamp64\www\papertrade-ai\public\index.php
```

- [ ] Do not put the contents of `public` directly into the project root unless Apache is intentionally configured that way.

#### C. Run the requirement checker

From Windows Terminal or Command Prompt:

```bat
cd C:\wamp64\www\papertrade-ai
php scripts\php\check_requirements.php
```

Expected PASS items:

- PHP version
- PDO
- pdo_mysql
- cURL
- OpenSSL
- JSON
- mbstring
- writable storage directories

If `php` is not recognized:

- Use the full WAMP PHP executable path, for example:

```bat
C:\wamp64\bin\php\php8.x.x\php.exe scripts\php\check_requirements.php
```

Codex must customize the version path when detected.

#### D. Create `.env`

- [ ] Copy `.env.example` to `.env`.

Command:

```bat
copy .env.example .env
```

- [ ] Set only local application/database values initially:

```dotenv
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/papertrade-ai/public
APP_TIMEZONE=Europe/Prague

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=papertrade_ai
DB_USERNAME=root
DB_PASSWORD=
```

- [ ] Do not commit `.env`.
- [ ] Leave Person 2 and Person 3 integration keys blank until they provide them.

#### E. Create the database

Preferred generated command:

```bat
php scripts\php\setup_database.php
```

The script must:

1. Connect to MySQL.
2. Create `papertrade_ai` if permitted.
3. Apply `database/schema.sql`.
4. Run the seed process.
5. Create the three accounts.
6. Print a safe summary.

If database creation permission fails:

1. Open:

```text
http://localhost/phpmyadmin/
```

2. Create database:

```text
papertrade_ai
```

3. Collation:

```text
utf8mb4_unicode_ci
```

4. Run the setup script again.

Expected result:

```text
PASS Database connection
PASS Schema applied
PASS Three users seeded
PASS Portfolios created
PASS Demo data created
```

#### F. Verify the three accounts

Development credentials:

```text
admin@papertrade.local   / Admin123!
analyst@papertrade.local / Analyst123!
trader@papertrade.local  / Trader123!
```

- [ ] Open the login page.
- [ ] Log in with each account.
- [ ] Confirm each account reaches Dashboard.
- [ ] Confirm the navbar contains only:
  - Dashboard
  - Stocks
  - Predictions
  - Setup
- [ ] Log out after each test.

Expected result:

- All three logins succeed.
- Invalid password fails.
- Sessions do not mix accounts.

#### G. Verify Apache routing

Open:

```text
http://localhost/papertrade-ai/public/health.php
```

Then:

```text
http://localhost/papertrade-ai/public/login
```

If `/health.php` works but `/login` gives 404:

- Enable Apache `mod_rewrite`.
- Confirm `.htaccess` overrides are allowed.
- Restart Apache.
- Use the generated fallback route URL temporarily if provided.

#### H. Test database-only paper trading

Run:

```bat
php scripts\php\create_demo_data.php
php scripts\php\03-test-application.php
```

If the generated Windows wrapper exists, use:

```bat
scripts\windows\03-test-application.bat
```

Verify:

- [ ] User cash exists.
- [ ] Portfolio exists.
- [ ] Seed transactions exist.
- [ ] Holdings match transactions.
- [ ] Watchlist rows exist.
- [ ] Users see separate data.

#### I. Verify browser buy/sell after Person 2 enables prices

After Person 2 reports market API PASS:

- [ ] Log in as Trader Demo.
- [ ] Search AAPL.
- [ ] Add AAPL to watchlist.
- [ ] Buy a small quantity.
- [ ] Confirm virtual cash decreases.
- [ ] Confirm holding appears.
- [ ] Sell part of the quantity.
- [ ] Confirm cash increases.
- [ ] Confirm overselling is rejected.
- [ ] Confirm transaction history remains.

#### J. Person 1 completion report

Person 1 writes in `TEAM_STATUS.md`:

```text
PERSON 1 STATUS
WAMP: PASS/FAIL
Database: PASS/FAIL
Requirement checker: PASS/FAIL
Three logins: PASS/FAIL
Routing: PASS/FAIL
Paper buy/sell database flow: PASS/FAIL/BLOCKED_BY_PERSON_2
Blocking error:
Exact error message:
Screenshot or log location:
```

### Person 1 estimated setup time

- Normal: 1.5–2.5 hours
- With port/rewrite problems: 3–4 hours

---

## 27.2 Person 2 — Real market data, OpenRouter, predictions, and charts

### Mission

Create the external market-data and AI credentials, insert them safely, and prove that search, quotes, history, charts, and predictions work.

### Detailed checklist

#### A. Select the one MVP market provider

Use the provider already configured by Codex.

Default:

```text
Finnhub
```

For the deadline, use only one provider unless the required historical endpoint is unavailable.

- [ ] Create an account with the selected market-data provider.
- [ ] Generate an API key.
- [ ] Never send the key in chat, screenshots, Git, or `TEAM_STATUS.md`.

#### B. Add the market key

Open:

```text
C:\wamp64\www\papertrade-ai\.env
```

Set:

```dotenv
MARKET_DATA_PROVIDER=finnhub
MARKET_DATA_API_KEY=PASTE_KEY_HERE
MARKET_DATA_CACHE_SECONDS=60
MARKET_DATA_STALE_AFTER_SECONDS=900
```

- [ ] Save the file.
- [ ] Restart Apache if the environment loader caches values.

#### C. Run the market API test

Command:

```bat
cd C:\wamp64\www\papertrade-ai
php scripts\php\test_market_api.php AAPL
```

Expected checks:

```text
PASS Authentication
PASS Symbol search
PASS Current quote
PASS Company profile
PASS Timestamp normalization
PASS Delay/freshness label
PASS Historical data
```

If historical data fails but quote works:

- Record the provider message.
- Check whether the free plan includes candles.
- Use the generated local `price_snapshots` fallback.
- Do not block the whole MVP over a historical-plan limitation.
- Mark the chart source as cached/local history.

If status is 401/403:

- Verify the key.
- Verify no quotation marks or spaces were copied.
- Verify the correct provider is selected.

If status is 429:

- Wait for the limit window.
- Increase cache duration.
- Stop repeated page refreshing.
- Use the generated cached response.

#### D. Test stock search in the website

- [ ] Log in.
- [ ] Open Stocks.
- [ ] Open Search.
- [ ] Search:
  - AAPL
  - Microsoft
  - NVDA
- [ ] Confirm results show symbol/company.
- [ ] Confirm Buy exists.
- [ ] Confirm Add to watchlist exists.
- [ ] Add a stock that is not owned to the watchlist.
- [ ] Confirm duplicate addition does not create a second row.

Expected result:

- Real provider results appear.
- Price timestamp and freshness are shown.

#### E. Verify historical chart data

- [ ] Open a stock’s details.
- [ ] Select the implemented time range.
- [ ] Confirm Chart.js renders.
- [ ] Confirm the chart has a source and timestamp.
- [ ] Confirm unavailable history shows an honest fallback message.

Do not spend deadline time adding every chart range. One working range is sufficient for the MVP.

#### F. Create an OpenRouter account and key

- [ ] Create or log in to OpenRouter.
- [ ] Create an API key.
- [ ] Select a model available to the account.
- [ ] Prefer a model that supports reliable structured JSON.
- [ ] Add credit if the chosen model requires it.

Do not assume a model remains free. Store the selected model in configuration.

#### G. Add OpenRouter settings

In `.env`:

```dotenv
OPENROUTER_API_KEY=PASTE_KEY_HERE
OPENROUTER_MODEL=PASTE_MODEL_IDENTIFIER_HERE
OPENROUTER_SITE_URL=http://localhost/papertrade-ai/public
OPENROUTER_SITE_NAME="PaperTrade AI"
```

- [ ] Save.
- [ ] Restart Apache if necessary.

#### H. Run the OpenRouter test

Command:

```bat
php scripts\php\test_openrouter.php AAPL 7d
```

Expected result:

```text
PASS OpenRouter authentication
PASS Model available
PASS JSON response
PASS Schema validation
PASS Bounded scores
PASS Disclaimer-safe language
```

If model not found:

- Copy the exact model identifier.
- Update `OPENROUTER_MODEL`.

If insufficient credit:

- Select an available lower-cost/free model or add credit.

If invalid JSON:

- Confirm the generated retry/repair path runs.
- Confirm the system falls back to deterministic technical scoring.
- Mark AI explanation as partial rather than blocking predictions.

#### I. Verify predictions in the website

- [ ] Open an owned stock.
- [ ] Confirm a small prediction appears.
- [ ] Confirm it includes:
  - Signal
  - Estimated probability
  - Confidence
  - Risk
  - Horizon
- [ ] Confirm the percentage always shows a timeframe.
- [ ] Open Predictions.
- [ ] Confirm owned, watchlist, and opportunity sections.
- [ ] Confirm each opportunity has:
  - Add to watchlist
  - Simulated Buy
- [ ] Add one opportunity to the watchlist.
- [ ] Confirm it appears in Stocks → Watchlist.

#### J. Give Person 1 the readiness signal

Write in `TEAM_STATUS.md`:

```text
PERSON 2 STATUS
Market authentication: PASS/FAIL
Search: PASS/FAIL
Quote: PASS/FAIL
History/chart: PASS/FAIL/PARTIAL
OpenRouter: PASS/FAIL/PARTIAL
Short prediction: PASS/FAIL
Detailed prediction: PASS/FAIL
Opportunity Add to watchlist: PASS/FAIL
Provider delay shown as:
Blocking error:
Exact error message:
```

Do not include secret keys.

### Person 2 estimated setup time

- Normal: 1.5–3 hours
- With provider-plan or AI-response problems: 3–4.5 hours

---

## 27.3 Person 3 — Docker, n8n, Telegram, alerts, and reports

### Mission

Start n8n, create and connect the Telegram bot, import the generated workflows, connect n8n to WAMP, and prove a test alert and report can be delivered.

### Detailed checklist

#### A. Install/start Docker Desktop

- [ ] Install Docker Desktop if missing.
- [ ] Start Docker Desktop.
- [ ] Wait until Docker reports it is running.
- [ ] In a terminal, run:

```bat
docker version
docker compose version
```

Expected result:

- Client and server information display.
- Compose version displays.

If virtualization/WSL error occurs:

- Enable required Windows virtualization features.
- Restart the computer only if necessary.
- If Docker cannot be fixed within the deadline, use the documented npm n8n fallback on the host machine.

#### B. Start the generated n8n stack

From the project root:

```bat
docker compose -f docker-compose.n8n.yml up -d
```

Then:

```bat
docker compose -f docker-compose.n8n.yml ps
```

Expected result:

- n8n container status is running.

Open:

```text
http://localhost:5678
```

- [ ] Create the local n8n owner account if this is the first launch.
- [ ] Keep the password private.

#### C. Verify n8n can reach WAMP

First ensure Person 1 reports WAMP and routing PASS.

From an n8n HTTP Request node, test:

```text
http://host.docker.internal/papertrade-ai/public/health.php
```

For internal API routes, use:

```text
http://host.docker.internal/papertrade-ai/public/api/internal/...
```

Do not use `http://localhost/...` from inside Docker because it points to the n8n container.

If it fails:

- Confirm WAMP is running.
- Confirm the browser can open the same endpoint.
- Confirm Apache is listening on the expected port.
- Use `host.docker.internal:{APACHE_PORT}` when Apache is not on port 80.
- Allow Apache through Windows Firewall for private networks.
- Check the Docker Desktop host networking configuration.

#### D. Set the internal API key

Person 1 or the repository owner creates a long random value in `.env`:

```dotenv
INTERNAL_N8N_API_KEY=LONG_RANDOM_VALUE
```

In every n8n HTTP Request node, add header:

```text
X-Internal-Api-Key
```

Value:

```text
the same INTERNAL_N8N_API_KEY
```

- [ ] Never paste it into screenshots or `TEAM_STATUS.md`.
- [ ] Test the generated internal API script:

```bat
php scripts\php\test_internal_api.php
```

Expected result:

- Missing key rejected.
- Wrong key rejected.
- Correct key accepted.

#### E. Create the Telegram bot

In Telegram:

1. Open BotFather.
2. Send:

```text
/newbot
```

3. Enter a bot display name.
4. Enter a unique username ending in `bot`.
5. Copy the token privately.
6. Open the new bot.
7. Press Start or send a message.

Important:

- A bot generally cannot message the user until the user starts the conversation.

#### F. Create Telegram credentials in n8n

In n8n:

- [ ] Open Credentials.
- [ ] Create Telegram API credentials.
- [ ] Paste the bot token.
- [ ] Save.
- [ ] Test the credential if n8n offers a test.

Prefer storing the bot token in n8n credentials. Do not place it in frontend JavaScript.

#### G. Obtain the Telegram chat ID

Use the generated instructions/workflow:

- Option 1: temporary Telegram Trigger workflow.
- Option 2: Bot API updates test through a server-side helper.
- Option 3: generated Telegram test script.

- [ ] Send a message to the bot.
- [ ] Read the chat ID.
- [ ] Enter the chat ID in the website Setup page for the chosen user.
- [ ] Press Test Telegram.

Expected Telegram message:

```text
PaperTrade AI connection successful.
```

If “chat not found”:

- Start the conversation with the bot.
- Verify the chat ID.
- Verify the correct bot token.
- Verify the user has not blocked the bot.

#### H. Import generated workflows

Import:

```text
n8n/workflows/alert-monitor.json
n8n/workflows/morning-report.json
n8n/workflows/market-close-report.json
n8n/workflows/prediction-refresh.json
```

For the deadline, prioritize:

1. Alert monitor
2. Morning report
3. Prediction refresh
4. Market-close report

- [ ] Assign Telegram credentials to Telegram nodes.
- [ ] Set the WAMP internal API base URL.
- [ ] Add the internal API-key header.
- [ ] Save each workflow.
- [ ] Run each workflow manually before activation.

#### I. Test the percentage alert

In Setup:

- [ ] Create an alert for AAPL.
- [ ] Threshold type: Percent.
- [ ] Threshold value: `0.2`.
- [ ] Confirm the UI displays `0.2%`.
- [ ] Direction: Both.
- [ ] Use the generated Test alert function so the team does not wait for a real price movement.

Run the workflow manually.

Expected result:

- Telegram message received.
- Alert event saved.
- Delivery status becomes sent.
- Message includes price timestamp, probability, horizon, confidence, and disclaimer.

#### J. Test the morning report

- [ ] Enable morning report.
- [ ] Set a temporary test time or use the generated “Send test report now” action.
- [ ] Select important stocks.
- [ ] Trigger the workflow manually.

Expected result:

- Telegram receives:
  - Portfolio value
  - Recent movement
  - Important stocks
  - Prediction summary
  - Data timestamp

#### K. Activate minimum workflows

Before the deadline, activate at least:

- [ ] Alert monitor
- [ ] Morning report

Activate additional workflows only after these work.

#### L. Person 3 completion report

Write in `TEAM_STATUS.md`:

```text
PERSON 3 STATUS
Docker: PASS/FAIL
n8n UI: PASS/FAIL
n8n to WAMP: PASS/FAIL
Telegram bot: PASS/FAIL
Chat ID saved: PASS/FAIL
Test message: PASS/FAIL
Alert workflow: PASS/FAIL
Morning report: PASS/FAIL
Active workflows:
Blocking error:
Exact error message:
```

Do not include tokens or internal API keys.

### Person 3 estimated setup time

- Normal: 2–3.5 hours
- With Docker/networking problems: 4–5 hours

---

# 28. Team coordination and 10-hour run plan

Codex must generate `TEAM_STATUS.md` with a shared status table.

## 28.1 Shared rules

- Use one repository.
- Pull before starting.
- Commit small working changes.
- Do not rename database columns, routes, environment variables, or JSON fields without notifying everyone.
- Never commit `.env`.
- Never post API keys in chat.
- Record exact errors, not summaries such as “it does not work.”
- One person owns each integration.
- Merge only after the owner’s test passes.
- Styling is frozen until the end-to-end demonstration works.

## 28.2 Recommended parallel schedule

### Hour 0–0.5 — Codex generation and repository preparation

Codex:

- Generates the complete project.
- Generates setup scripts.
- Generates workflow JSON.
- Generates the three-person handoff.
- Runs available checks.

Team:

- Opens the generated project.
- Creates/opens the shared repository.
- Assigns Person 1, Person 2, and Person 3.

### Hour 0.5–2.5 — Three setups in parallel

Person 1:

- WAMP
- `.env`
- Database
- Schema/seed
- Login/routing

Person 2:

- Market account/key
- Quote/search/history
- OpenRouter account/key
- API tests

Person 3:

- Docker/n8n
- Telegram bot
- n8n-to-WAMP connection
- Workflow import

### Hour 2.5 — Mandatory checkpoint

Required status:

- Person 1: login and database PASS
- Person 2: real AAPL quote PASS
- Person 3: n8n UI and Telegram bot created

If one is blocked, all teammates help resolve that blocker before adding features.

### Hour 2.5–5 — Integration

Required flow:

```text
Login
→ Search AAPL
→ Add to watchlist
→ Buy simulated
→ View portfolio
→ View chart
→ Generate prediction
```

At Hour 5, this flow must work before continuing.

### Hour 5–7 — Automation

Required flow:

```text
Create 0.2% AAPL alert
→ Run test trigger
→ n8n calls PHP
→ Telegram receives alert
```

Then:

```text
Run test morning report
→ Telegram receives report
```

### Hour 7 — Feature freeze

Do not add new architectural features after Hour 7.

Allowed work:

- Fix blockers
- Improve error messages
- Add basic CSS
- Prepare demo data
- Test all three accounts
- Back up project/database

### Hour 7–9 — Full acceptance test

Run the exact demonstration from Section 29.

Fix only:

- Crashes
- Broken buttons
- Incorrect calculations
- Missing required watchlist actions
- Missing prediction timeframe
- Failed Telegram flow
- Authentication/data-isolation issues

### Hour 9–10 — Presentation safety

- Export database.
- Zip project without `.env` secrets.
- Save n8n workflow exports.
- Record a backup demonstration video.
- Capture screenshots of working pages.
- Confirm the Test alert button works.
- Prepare demo account credentials.
- Restart WAMP and n8n once to prove recovery.
- Do not make risky code changes.

## 28.3 Scope reduction rule

If the team is behind schedule, reduce scope in this order:

1. Postpone market-close report.
2. Use only one chart range.
3. Use only one market provider.
4. Use only US stocks and USD.
5. Use only the `7d` prediction horizon.
6. Use a fixed opportunity universe.
7. Disable prediction-outcome evaluation.
8. Simplify roles while keeping three accounts.
9. Use basic CSS.

Never remove:

- Three logins
- Four navbar pages
- Stock search
- Add to watchlist from search
- Simulated buy/sell
- Real market price
- Small and detailed prediction
- Estimated percentage with timeframe
- Telegram test alert
- Setup page

---

# 29. Mandatory all-team integration test

Run this test after all three individual setup lists report readiness.

## 29.1 Clean start

1. Restart WAMP.
2. Confirm Apache and MySQL are running.
3. Restart the n8n Docker stack.
4. Open the application in a private/incognito browser window.
5. Open n8n in a second tab.
6. Keep Telegram open.

## 29.2 Login test

1. Log in as Trader Demo.
2. Confirm Dashboard loads.
3. Confirm four navbar links.
4. Log out.
5. Log in as Analyst Demo.
6. Confirm account data differs or remains isolated.
7. Return to Trader Demo for the demonstration.

## 29.3 Stock and watchlist test

1. Open Stocks.
2. Search AAPL.
3. Confirm real quote and timestamp.
4. Click Add to watchlist.
5. Open Watchlist.
6. Confirm AAPL appears.
7. Search AAPL again.
8. Confirm duplicate watchlist insertion is prevented.

## 29.4 Simulated purchase test

1. Click Buy.
2. Enter a valid quantity.
3. Confirm total and fee.
4. Confirm the purchase.
5. Open Owned.
6. Confirm holding quantity, average cost, market value, and profit/loss.
7. Confirm virtual cash decreased.
8. Confirm transaction history exists.

## 29.5 Chart and prediction test

1. Open AAPL details.
2. Confirm chart or truthful fallback.
3. Confirm short prediction.
4. Confirm:
   - Estimated probability
   - Confidence
   - Risk
   - `7d` or configured horizon
5. Open Predictions.
6. Confirm detailed analysis.
7. Open Opportunities.
8. Add a non-owned stock to the watchlist.

## 29.6 Sell test

1. Sell part of AAPL.
2. Confirm proceeds and updated quantity.
3. Attempt to sell too much.
4. Confirm rejection.
5. Confirm watchlist status was not accidentally removed.

## 29.7 Telegram test

1. Open Setup.
2. Confirm Telegram verified.
3. Create/test a `0.2%` AAPL alert.
4. Trigger Test alert.
5. Confirm Telegram receives it.
6. Confirm database delivery status says sent.
7. Trigger test morning report.
8. Confirm Telegram receives the report.

## 29.8 Failure-state test

1. Temporarily use a clearly invalid search symbol.
2. Confirm a safe error.
3. Confirm no fake price is shown.
4. Confirm application remains usable.

## 29.9 Final PASS condition

The project is presentation-ready when every must-have step above passes or has a clearly demonstrated, honest fallback that does not break the rest of the flow.
