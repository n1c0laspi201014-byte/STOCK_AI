SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'trader') NOT NULL DEFAULT 'trader',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    base_currency CHAR(3) NOT NULL DEFAULT 'USD',
    timezone VARCHAR(64) NOT NULL DEFAULT 'Europe/Brussels',
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
    default_horizon ENUM('1d', '7d', '30d') NOT NULL DEFAULT '7d',
    prediction_refresh_hours INT UNSIGNED NOT NULL DEFAULT 6,
    minimum_urgent_confidence DECIMAL(5,2) NOT NULL DEFAULT 70,
    news_analysis_enabled TINYINT(1) NOT NULL DEFAULT 1,
    max_news_items INT UNSIGNED NOT NULL DEFAULT 5,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stocks (
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
    UNIQUE KEY uq_stock_symbol_exchange (symbol, exchange_code),
    INDEX idx_stock_symbol (symbol),
    INDEX idx_stock_company (company_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL DEFAULT 'Main Paper Portfolio',
    base_currency CHAR(3) NOT NULL DEFAULT 'USD',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_portfolio_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
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
    quote_timestamp DATETIME NULL,
    note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transactions_portfolio_date (portfolio_id, executed_at),
    INDEX idx_transactions_stock_date (stock_id, executed_at),
    CONSTRAINT fk_transaction_portfolio FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    CONSTRAINT fk_transaction_stock FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS holdings (
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
    CONSTRAINT fk_holding_portfolio FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    CONSTRAINT fk_holding_stock FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS watchlist_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    stock_id BIGINT UNSIGNED NOT NULL,
    note VARCHAR(500) NULL,
    target_buy_price DECIMAL(20,8) NULL,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_watchlist_user_stock (user_id, stock_id),
    CONSTRAINT fk_watchlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_watchlist_stock FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS price_snapshots (
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
    CONSTRAINT fk_price_stock FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS predictions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    stock_id BIGINT UNSIGNED NOT NULL,
    horizon ENUM('1d', '7d', '30d') NOT NULL DEFAULT '7d',
    `signal` ENUM('buy', 'hold', 'sell', 'watch') NOT NULL,
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
    CONSTRAINT fk_prediction_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_prediction_stock FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prediction_outcomes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prediction_id BIGINT UNSIGNED NOT NULL UNIQUE,
    start_price DECIMAL(20,8) NOT NULL,
    end_price DECIMAL(20,8) NULL,
    actual_change_percent DECIMAL(10,4) NULL,
    outcome ENUM('correct', 'incorrect', 'neutral', 'pending') NOT NULL DEFAULT 'pending',
    evaluated_at DATETIME NULL,
    CONSTRAINT fk_outcome_prediction FOREIGN KEY (prediction_id) REFERENCES predictions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alert_rules (
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
    CONSTRAINT fk_alert_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_stock FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alert_events (
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
    CONSTRAINT fk_alert_event_rule FOREIGN KEY (alert_rule_id) REFERENCES alert_rules(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_event_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_event_stock FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_event_prediction FOREIGN KEY (prediction_id) REFERENCES predictions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS telegram_connections (
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
    CONSTRAINT fk_telegram_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dashboard_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    primary_chart_type ENUM('portfolio_value', 'stock_price', 'profit_loss') NOT NULL DEFAULT 'portfolio_value',
    primary_chart_stock_id BIGINT UNSIGNED NULL,
    secondary_chart_type ENUM('portfolio_allocation', 'stock_price', 'daily_performance') NOT NULL DEFAULT 'portfolio_allocation',
    secondary_chart_stock_id BIGINT UNSIGNED NULL,
    important_stock_ids JSON NULL,
    max_important_stocks INT UNSIGNED NOT NULL DEFAULT 4,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dashboard_pref_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_dashboard_primary_stock FOREIGN KEY (primary_chart_stock_id) REFERENCES stocks(id) ON DELETE SET NULL,
    CONSTRAINT fk_dashboard_secondary_stock FOREIGN KEY (secondary_chart_stock_id) REFERENCES stocks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS automation_logs (
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
    UNIQUE KEY uq_automation_execution (workflow_name, execution_key),
    CONSTRAINT fk_automation_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value VARCHAR(500) NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO system_settings (setting_key, setting_value)
VALUES ('automations_paused', '0')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

SET FOREIGN_KEY_CHECKS = 1;
