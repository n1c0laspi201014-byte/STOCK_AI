<?php $summary = $portfolio['summary']; $currency = (string) ($summary['base_currency'] ?? 'USD'); ?>
<header class="page-heading">
    <div><span class="eyebrow">Your paper portfolio</span><h1>Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', (string) auth_user()['name'])[0]) ?>.</h1><p>Real market context, clear paper performance, and just enough insight to make the next lesson count.</p></div>
    <div class="page-heading-meta"><span>Portfolio</span><strong><?= e($summary['name'] ?? 'Main Paper Portfolio') ?></strong><small>Updated <?= e(date('H:i')) ?> · <?= e(config('app.timezone')) ?></small></div>
</header>

<section class="panel portfolio-hero" aria-label="Portfolio balance and performance">
    <div>
        <span class="eyebrow">Portfolio value</span>
        <div class="portfolio-value"><?= e(money((float) ($summary['portfolio_value'] ?? 0), $currency)) ?></div>
        <p class="portfolio-lede">No real money. Real market lessons. Prices are marked from the latest saved provider data and labeled when delayed.</p>
    </div>
    <div class="hero-stat-grid">
        <div class="hero-stat"><span>Virtual cash</span><strong><?= e(money((float) ($summary['current_cash'] ?? 0), $currency)) ?></strong><small>Ready for paper trades</small></div>
        <div class="hero-stat"><span>Invested cost</span><strong><?= e(money((float) ($summary['total_invested_cost'] ?? 0), $currency)) ?></strong><small>Weighted position cost</small></div>
        <div class="hero-stat"><span>Unrealized P/L</span><strong class="<?= (float) ($summary['unrealized_profit_loss'] ?? 0) >= 0 ? 'gain' : 'loss' ?>"><?= e(money((float) ($summary['unrealized_profit_loss'] ?? 0), $currency)) ?></strong><small>Latest marked prices</small></div>
        <div class="hero-stat"><span>Realized P/L</span><strong class="<?= (float) ($summary['realized_profit_loss'] ?? 0) >= 0 ? 'gain' : 'loss' ?>"><?= e(money((float) ($summary['realized_profit_loss'] ?? 0), $currency)) ?></strong><small>Completed paper sales</small></div>
    </div>
</section>

<section class="kpi-grid" aria-label="Portfolio key figures">
    <?php
    $kpis = [
        ['Portfolio value', money((float) ($summary['portfolio_value'] ?? 0), $currency), 'Cash plus marked holdings', 'neutral'],
        ['Virtual cash', money((float) ($summary['current_cash'] ?? 0), $currency), 'Available for simulated trades', 'neutral'],
        ['Invested cost', money((float) ($summary['total_invested_cost'] ?? 0), $currency), 'Weighted position cost', 'neutral'],
        ['Unrealized P/L', money((float) ($summary['unrealized_profit_loss'] ?? 0), $currency), '↕ Based on cached/latest prices', (float) ($summary['unrealized_profit_loss'] ?? 0) >= 0 ? 'positive' : 'negative'],
        ['Realized P/L', money((float) ($summary['realized_profit_loss'] ?? 0), $currency), '↕ From completed paper sales', (float) ($summary['realized_profit_loss'] ?? 0) >= 0 ? 'positive' : 'negative'],
        ["Today's change", 'Unavailable', 'Requires two current session snapshots', 'neutral'],
        ['Owned stocks', (string) ($summary['owned_count'] ?? 0), 'Open paper positions', 'neutral'],
        ['Watchlist', (string) ($summary['watchlist_count'] ?? 0), 'Ideas followed without buying', 'neutral'],
    ];
    foreach ($kpis as [$label, $value, $description, $tone]): ?>
        <article class="kpi-card <?= e($tone) ?>"><span><?= e($label) ?></span><strong><?= e($value) ?></strong><small><?= e($description) ?></small><time datetime="<?= e($summary['data_timestamp'] ?? date(DATE_ATOM)) ?>"><?= e(date('H:i')) ?></time></article>
    <?php endforeach; ?>
</section>

<section class="dashboard-grid charts-grid">
    <?php $chartId='dashboard-chart-primary'; $chartEyebrow='Graph 1 · configurable'; $chartTitle=ucwords(str_replace('_',' ',(string)($preferences['primary_chart_type']??'portfolio_value'))); $chartMeta='Actual market data'; $chartFallback='The chart uses recorded provider prices and paper transactions; it never invents missing market history.'; require base_path('app/Views/partials/chart-shell.php'); ?>
    <?php $chartId='dashboard-chart-secondary'; $chartEyebrow='Graph 2 · configurable'; $chartTitle=ucwords(str_replace('_',' ',(string)($preferences['secondary_chart_type']??'portfolio_allocation'))); $chartMeta='Actual market data'; $chartFallback='Change the graph selection from Setup. Empty portfolios remain valid.'; require base_path('app/Views/partials/chart-shell.php'); ?>
</section>

<section class="dashboard-grid detail-grid">
    <article class="panel transaction-panel">
        <div class="panel-heading"><div><span class="eyebrow">Latest activity</span><h2>Recent paper trade</h2></div><a href="<?= e(url('/stocks?tab=owned')) ?>">All transactions →</a></div>
        <?php if ($latest_transaction): ?>
            <div class="transaction-hero"><span class="transaction-icon <?= e($latest_transaction['transaction_type']) ?>"><?= $latest_transaction['transaction_type'] === 'buy' ? '↘' : '↗' ?></span><div><strong><?= e(strtoupper((string)$latest_transaction['transaction_type'])) ?> <?= e($latest_transaction['symbol']) ?></strong><span><?= e($latest_transaction['company_name']) ?></span></div><div><strong><?= e(number_format((float)$latest_transaction['quantity'], 8, '.', '')) ?></strong><span>shares</span></div><div><strong><?= e(money((float)$latest_transaction['execution_price'], $latest_transaction['currency'])) ?></strong><span>execution</span></div></div>
            <div class="transaction-footer"><span>Total <?= e(money((float)$latest_transaction['gross_amount'], $latest_transaction['currency'])) ?></span><time><?= e(date('d M Y, H:i', strtotime($latest_transaction['executed_at']))) ?></time></div>
        <?php else: ?><div class="empty-state"><strong>No simulated trades yet.</strong><p>Search for a stock and use Buy to create your first paper transaction.</p><a class="button primary" href="<?= e(url('/stocks?tab=search')) ?>">Search stocks</a></div><?php endif; ?>
    </article>
    <article class="panel performer-panel">
        <div class="panel-heading"><div><span class="eyebrow">Position range</span><h2>Best & worst performers</h2></div></div>
        <?php foreach ([['Best', $best_performer, 'positive'], ['Worst', $worst_performer, 'negative']] as [$label, $holding, $tone]): ?>
            <?php if ($holding): ?><a class="performer-row" href="<?= e(url('/stocks?symbol=' . rawurlencode($holding['symbol']))) ?>"><span class="rank <?= e($tone) ?>"><?= e(substr($label,0,1)) ?></span><div><strong><?= e($holding['symbol']) ?></strong><small><?= e($holding['company_name']) ?></small></div><div class="<?= e((float)($holding['unrealized_profit_loss']??0)>=0?'gain':'loss') ?>"><strong><?= e(money((float)($holding['unrealized_profit_loss']??0),$holding['currency'])) ?></strong><small>unrealized</small></div><span aria-hidden="true">→</span></a><?php else: ?><p class="muted">No owned positions.</p><?php endif; ?>
        <?php endforeach; ?>
    </article>
</section>

<section class="panel important-panel">
    <div class="panel-heading"><div><span class="eyebrow">Selected in Setup</span><h2>Important stocks</h2></div><a href="<?= e(url('/setup')) ?>">Configure →</a></div>
    <?php if ($important_stocks): ?><div class="important-grid">
        <?php foreach($important_stocks as $stock): ?><article><div><strong><?= e($stock['symbol']) ?></strong><span><?= e($stock['company_name']) ?></span></div><strong><?= $stock['current_price']!==null?e(money((float)$stock['current_price'],$stock['currency'])):'Quote unavailable' ?></strong><small><?= e(number_format((float)$stock['quantity'],8,'.','')) ?> owned · <?= e($stock['provider']??'No price source') ?></small><a href="<?= e(url('/stocks?symbol='.rawurlencode($stock['symbol']))) ?>">Open details →</a></article><?php endforeach; ?>
    </div><?php else: ?><div class="empty-state compact"><p>Select important stocks from owned or watchlist items in Setup.</p></div><?php endif; ?>
</section>

<section class="panel alerts-panel">
    <div class="panel-heading"><div><span class="eyebrow">Automation history</span><h2>Recent alerts</h2></div><a href="<?= e(url('/setup#alerts')) ?>">Manage alerts →</a></div>
    <?php if($recent_alerts): ?><div class="table-scroll table-responsive"><table class="table table-dark table-hover align-middle"><thead><tr><th>Stock</th><th>Change</th><th>Urgency</th><th>Telegram</th><th>Time</th></tr></thead><tbody><?php foreach($recent_alerts as $alert): ?><tr><td><strong><?= e($alert['symbol']) ?></strong></td><td class="<?= (float)$alert['change_percent']>=0?'gain':'loss' ?>"><?= e(percent((float)$alert['change_percent'])) ?></td><td><span class="signal <?= e($alert['urgency']) ?>"><?= e(strtoupper($alert['urgency'])) ?></span></td><td><?= e(ucfirst($alert['telegram_status'])) ?></td><td><?= e(date('d M H:i',strtotime($alert['triggered_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state compact"><p>No alert events have been recorded.</p></div><?php endif; ?>
</section>

<script type="application/json" id="dashboard-data"><?= json_encode(['portfolio'=>$portfolio,'portfolio_history'=>$portfolio_history,'preferences'=>$preferences], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
