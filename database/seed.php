<?php
declare(strict_types=1);

use App\Config\Database;

require_once dirname(__DIR__) . '/bootstrap/app.php';

$pdo = null;
$started = false;
try {
    $pdo = Database::connection();
    $started = !$pdo->inTransaction();
    if ($started) {
        $pdo->beginTransaction();
    }
    // Remove the retired development account. Any non-demo legacy analyst was
    // migrated to trader by setup_database.php before the role enum is narrowed.
    $pdo->exec("DELETE FROM users WHERE email = 'analyst@papertrade.local'");
    $accounts = [
        ['Admin Demo', 'admin@papertrade.local', 'Admin123!', 'admin'],
        ['Trader Demo', 'trader@papertrade.local', 'Trader123!', 'trader'],
    ];
    $insertUser = $pdo->prepare('INSERT IGNORE INTO users (name,email,password_hash,role) VALUES (:name,:email,:password_hash,:role)');
    foreach ($accounts as [$name, $email, $password, $role]) {
        $insertUser->execute(['name' => $name, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
    }

    $stocks = [
        ['AAPL', 'NASDAQ', 'Apple Inc.', 'Technology', 210.15, 208.00],
        ['MSFT', 'NASDAQ', 'Microsoft Corporation', 'Technology', 445.30, 442.10],
        ['NVDA', 'NASDAQ', 'NVIDIA Corporation', 'Semiconductors', 172.40, 169.80],
        ['AMZN', 'NASDAQ', 'Amazon.com, Inc.', 'Consumer Cyclical', 224.75, 222.20],
        ['GOOGL', 'NASDAQ', 'Alphabet Inc.', 'Communication Services', 191.10, 189.70],
        ['META', 'NASDAQ', 'Meta Platforms, Inc.', 'Communication Services', 708.20, 701.60],
        ['TSLA', 'NASDAQ', 'Tesla, Inc.', 'Automotive', 329.50, 333.00],
        ['AMD', 'NASDAQ', 'Advanced Micro Devices, Inc.', 'Semiconductors', 157.80, 155.40],
    ];
    $insertStock = $pdo->prepare('INSERT INTO stocks (symbol,exchange_code,company_name,currency,country,industry,provider_symbol) VALUES (:symbol,:exchange,:company,"USD","US",:industry,:provider_symbol) ON DUPLICATE KEY UPDATE company_name=VALUES(company_name),industry=VALUES(industry)');
    foreach ($stocks as [$symbol, $exchange, $company, $industry]) {
        $insertStock->execute(['symbol' => $symbol, 'exchange' => $exchange, 'company' => $company, 'industry' => $industry, 'provider_symbol' => $symbol]);
    }

    $stockRows = $pdo->query('SELECT id,symbol FROM stocks')->fetchAll();
    $stockIds = array_column($stockRows, 'id', 'symbol');
    $users = $pdo->query('SELECT id,email FROM users WHERE email IN ("admin@papertrade.local","trader@papertrade.local") ORDER BY FIELD(email, "admin@papertrade.local", "trader@papertrade.local")')->fetchAll();
    $createSettings = $pdo->prepare('INSERT IGNORE INTO user_settings (user_id,timezone) VALUES (:user_id,:timezone)');
    $createPortfolio = $pdo->prepare('INSERT IGNORE INTO portfolios (user_id) VALUES (:user_id)');
    $createDashboard = $pdo->prepare('INSERT IGNORE INTO dashboard_preferences (user_id,important_stock_ids) VALUES (:user_id,:important)');
    foreach ($users as $index => $user) {
        $userId = (int) $user['id'];
        $createSettings->execute(['user_id' => $userId, 'timezone' => config('app.timezone', 'Europe/Brussels')]);
        $createPortfolio->execute(['user_id' => $userId]);
        $createDashboard->execute(['user_id' => $userId, 'important' => json_encode([(int) $stockIds['AAPL'], (int) $stockIds['MSFT'], (int) $stockIds['NVDA']])]);
        $portfolioQuery = $pdo->prepare('SELECT id FROM portfolios WHERE user_id=:user_id');
        $portfolioQuery->execute(['user_id' => $userId]);
        $portfolioId = (int) $portfolioQuery->fetchColumn();
        $countQuery = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE portfolio_id=:portfolio_id');
        $countQuery->execute(['portfolio_id' => $portfolioId]);
        $count = (int) $countQuery->fetchColumn();
        if ($count === 0) {
            $owned = $index % 2 === 0 ? [['AAPL', 10.0, 185.00], ['MSFT', 5.0, 410.00]] : [['NVDA', 12.0, 140.00], ['AMZN', 6.0, 195.00]];
            $spent = 0.0;
            foreach ($owned as [$symbol, $quantity, $price]) {
                $gross = $quantity * $price;
                $spent += $gross;
                $transaction = $pdo->prepare('INSERT INTO transactions (portfolio_id,stock_id,transaction_type,quantity,execution_price,fee,gross_amount,net_cash_effect,executed_at,quote_timestamp,note) VALUES (:portfolio,:stock,"buy",:quantity,:price,0,:gross,:cash_effect,DATE_SUB(NOW(),INTERVAL :executed_days DAY),DATE_SUB(NOW(),INTERVAL :quote_days DAY),"Seeded demo transaction")');
                $transaction->execute(['portfolio' => $portfolioId, 'stock' => $stockIds[$symbol], 'quantity' => $quantity, 'price' => $price, 'gross' => $gross, 'cash_effect' => -$gross, 'executed_days' => 5 + $index, 'quote_days' => 5 + $index]);
                $holding = $pdo->prepare('INSERT INTO holdings (portfolio_id,stock_id,quantity,average_cost,total_cost,first_bought_at,last_transaction_at) VALUES (:portfolio,:stock,:quantity,:price,:gross,DATE_SUB(NOW(),INTERVAL :first_days DAY),DATE_SUB(NOW(),INTERVAL :last_days DAY))');
                $holding->execute(['portfolio' => $portfolioId, 'stock' => $stockIds[$symbol], 'quantity' => $quantity, 'price' => $price, 'gross' => $gross, 'first_days' => 5 + $index, 'last_days' => 5 + $index]);
            }
            $cash = 100000 - $spent;
            $cashUpdate = $pdo->prepare('UPDATE user_settings SET current_cash=:cash WHERE user_id=:user_id');
            $cashUpdate->execute(['cash' => $cash, 'user_id' => $userId]);
        }

        $ownedSymbols = $index % 2 === 0 ? ['AAPL', 'MSFT'] : ['NVDA', 'AMZN'];
        $watchSymbols = array_slice(array_values(array_diff(['AAPL','MSFT','NVDA','AMZN','GOOGL','META','TSLA','AMD'], $ownedSymbols)), 0, 3);
        $watch = $pdo->prepare('INSERT IGNORE INTO watchlist_items (user_id,stock_id,note) VALUES (:user_id,:stock_id,"Seeded demo watchlist")');
        foreach ($watchSymbols as $symbol) {
            $watch->execute(['user_id' => $userId, 'stock_id' => $stockIds[$symbol]]);
        }
        $alertCountQuery = $pdo->prepare('SELECT COUNT(*) FROM alert_rules WHERE user_id=:user_id');
        $alertCountQuery->execute(['user_id' => $userId]);
        $alertCount = (int) $alertCountQuery->fetchColumn();
        if ($alertCount === 0) {
            $alert = $pdo->prepare('INSERT INTO alert_rules (user_id,stock_id,name,threshold_type,threshold_value,direction,reference_type,reference_price,market_hours_only) VALUES (:user_id,:stock_id,:name,"percent",:threshold,"both","previous_close",:reference,0)');
            $alert->execute(['user_id' => $userId, 'stock_id' => $stockIds['AAPL'], 'name' => 'AAPL 2% move', 'threshold' => 2.0, 'reference' => 208.00]);
            $alert->execute(['user_id' => $userId, 'stock_id' => $stockIds['NVDA'], 'name' => 'NVDA 3% move', 'threshold' => 3.0, 'reference' => 169.80]);
        }
    }

    $snapshot = $pdo->prepare('INSERT INTO price_snapshots (stock_id,price,open_price,high_price,low_price,previous_close,volume,provider,provider_timestamp,is_delayed,delay_minutes) SELECT :stock_id,:price,:open_price,:high_price,:low_price,:previous_close,:volume,"seed-demo",NOW(),1,1440 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM price_snapshots WHERE stock_id=:stock_id_check)');
    foreach ($stocks as [$symbol, , , , $price, $previous]) {
        $snapshot->execute(['stock_id' => $stockIds[$symbol], 'price' => $price, 'open_price' => $previous, 'high_price' => $price * 1.01, 'low_price' => $previous * .99, 'previous_close' => $previous, 'volume' => 1000000, 'stock_id_check' => $stockIds[$symbol]]);
    }

    if ($started) {
        $pdo->commit();
    }
    echo "PASS Admin and Trader users seeded\nPASS Portfolios created\nPASS Demo data created\n";
} catch (Throwable $exception) {
    if ($started && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "FAIL Seed process\n  Tested: stockdata connection, Admin and Trader users, portfolios, demo trades/watchlists/alerts\n  Likely fix: start WAMP MySQL, verify DB_* in .env, and run scripts/php/setup_database.php.\n  Error: " . $exception->getMessage() . PHP_EOL);
    exit(1);
}
