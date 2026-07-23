<?php
$latestPredictions = [];
foreach ($predictions as $item) if (!isset($latestPredictions[(int)$item['stock_id']])) $latestPredictions[(int)$item['stock_id']] = $item;
$currency = (string)($portfolio['summary']['base_currency'] ?? 'USD');
?>
<header class="page-heading">
    <div><span class="eyebrow">Research clearly. Trade virtually.</span><h1>Stocks, without the noise.</h1><p>Search by company or ticker, inspect actual provider data, then practise with deliberate paper Buy and Sell actions.</p></div>
    <div class="cash-pill"><span>Virtual cash</span><strong><?= e(money((float)($portfolio['summary']['current_cash']??0),$currency)) ?></strong><small>Available for simulated buys</small></div>
</header>

<nav class="segmented-tabs" aria-label="Stock sections" role="tablist">
    <button role="tab" data-tab="owned" aria-selected="<?= $initialTab==='owned'?'true':'false' ?>">Owned <span><?= count($portfolio['holdings']) ?></span></button>
    <button role="tab" data-tab="watchlist" aria-selected="<?= $initialTab==='watchlist'?'true':'false' ?>">Watchlist <span><?= count($watchlist) ?></span></button>
    <button role="tab" data-tab="search" aria-selected="<?= $initialTab==='search'?'true':'false' ?>">Search <span>⌕</span></button>
</nav>

<section class="tab-panel" data-panel="owned" <?= $initialTab!=='owned'?'hidden':'' ?>>
    <div class="section-heading"><div><h2>Owned positions</h2><p>Weighted average cost and latest locally saved or provider price.</p></div></div>
    <?php if($portfolio['holdings']): ?><div class="holdings-list">
        <?php foreach($portfolio['holdings'] as $holding): $prediction=$latestPredictions[(int)$holding['stock_id']]??null; ?>
        <article class="holding-card">
            <div class="holding-identity"><div class="ticker-tile"><?= e(substr($holding['symbol'],0,2)) ?></div><div><strong><?= e($holding['symbol']) ?></strong><span><?= e($holding['company_name']) ?> · <?= e($holding['exchange_code']) ?></span><small><?= !empty($holding['is_delayed'])?'Delayed/cached':'Latest saved quote' ?> · <?= e($holding['received_at']??'unavailable') ?></small></div></div>
            <div class="holding-metrics"><div><span>Quantity</span><strong><?= e(rtrim(rtrim(number_format((float)$holding['quantity'],8,'.',''),'0'),'.')) ?></strong></div><div><span>Avg. cost</span><strong><?= e(money((float)$holding['average_cost'],$holding['currency'])) ?></strong></div><div><span>Current</span><strong><?= $holding['current_price']!==null?e(money((float)$holding['current_price'],$holding['currency'])):'Unavailable' ?></strong></div><div><span>Market value</span><strong><?= $holding['market_value']!==null?e(money((float)$holding['market_value'],$holding['currency'])):'Unavailable' ?></strong></div><div><span>Unrealized P/L</span><strong class="<?= (float)($holding['unrealized_profit_loss']??0)>=0?'gain':'loss' ?>"><?= $holding['unrealized_profit_loss']!==null?e(money((float)$holding['unrealized_profit_loss'],$holding['currency'])):'Unavailable' ?></strong></div><div><span>Allocation</span><strong><?= e(number_format((float)$holding['allocation_percent'],1)) ?>%</strong></div></div>
            <div class="holding-prediction"><?php require base_path('app/Views/partials/prediction-badge.php'); ?></div>
            <div class="holding-actions">
                <button class="button secondary" data-action="details" data-symbol="<?= e($holding['symbol']) ?>" data-stock-id="<?= (int)$holding['stock_id'] ?>">Chart & details</button>
                <button class="button primary" data-action="trade" data-side="buy" data-symbol="<?= e($holding['symbol']) ?>" data-price="<?= e($holding['current_price']??'') ?>">Buy more</button>
                <button class="button danger" data-action="trade" data-side="sell" data-symbol="<?= e($holding['symbol']) ?>" data-price="<?= e($holding['current_price']??'') ?>" data-available="<?= e($holding['quantity']) ?>">Sell</button>
                <button class="button ghost" data-action="predict" data-stock-id="<?= (int)$holding['stock_id'] ?>">Refresh estimate</button>
            </div>
        </article>
        <?php endforeach; ?>
    </div><?php else: ?><div class="empty-state"><strong>You do not own any stocks yet.</strong><p>Use Search to review a stock, add it to your watchlist, or make a simulated purchase.</p><button class="button primary" data-switch-tab="search">Search stocks</button></div><?php endif; ?>

    <section class="panel transaction-table-panel"><div class="panel-heading"><div><span class="eyebrow">Permanent paper record</span><h2>Transaction history</h2></div></div>
        <?php if($portfolio['transactions']): ?><div class="table-scroll table-responsive"><table class="table table-dark table-hover align-middle"><thead><tr><th>Date</th><th>Stock</th><th>Side</th><th>Quantity</th><th>Execution</th><th>Fee</th><th>Cash effect</th></tr></thead><tbody><?php foreach($portfolio['transactions'] as $transaction): ?><tr><td><?= e(date('d M Y H:i',strtotime($transaction['executed_at']))) ?></td><td><strong><?= e($transaction['symbol']) ?></strong><small><?= e($transaction['company_name']) ?></small></td><td><span class="signal <?= e($transaction['transaction_type']) ?>"><?= e(strtoupper($transaction['transaction_type'])) ?></span></td><td><?= e(rtrim(rtrim(number_format((float)$transaction['quantity'],8,'.',''),'0'),'.')) ?></td><td><?= e(money((float)$transaction['execution_price'],$transaction['currency'])) ?></td><td><?= e(money((float)$transaction['fee'],$transaction['currency'])) ?></td><td class="<?= (float)$transaction['net_cash_effect']>=0?'gain':'loss' ?>"><?= e(money((float)$transaction['net_cash_effect'],$transaction['currency'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="muted">No transactions recorded.</p><?php endif; ?>
    </section>
</section>

<section class="tab-panel" data-panel="watchlist" <?= $initialTab!=='watchlist'?'hidden':'' ?>>
    <div class="section-heading"><div><h2>Watchlist</h2><p>Stocks can stay here whether or not you own them.</p></div><button class="button primary" data-switch-tab="search">Add another stock</button></div>
    <?php if($watchlist): ?><div class="watch-grid"><?php foreach($watchlist as $stock): $prediction=$latestPredictions[(int)$stock['stock_id']]??null; ?>
        <article class="stock-card">
            <div class="stock-card-title"><div><strong><?= e($stock['symbol']) ?></strong><span><?= e($stock['company_name']) ?></span></div><span class="owned-badge"><?= !empty($stock['is_owned'])?'Owned + watched':'Watch only' ?></span></div>
            <div class="stock-price"><?= $stock['current_price']!==null?e(money((float)$stock['current_price'],$stock['currency'])):'Current quote unavailable.' ?></div>
            <div class="daily-change"><?php if($stock['current_price']!==null&&$stock['previous_close']):$change=((float)$stock['current_price']-(float)$stock['previous_close'])/(float)$stock['previous_close']*100;?><span class="<?= $change>=0?'gain':'loss' ?>"><?= e(percent($change)) ?> today</span><?php else:?><span>Daily change unavailable</span><?php endif; ?><small><?= e($stock['provider']??'No source') ?> · <?= e($stock['received_at']??'No timestamp') ?></small></div>
            <?php require base_path('app/Views/partials/prediction-badge.php'); ?>
            <div class="stock-actions"><button class="button primary" data-action="trade" data-side="buy" data-symbol="<?= e($stock['symbol']) ?>" data-price="<?= e($stock['current_price']??'') ?>">Simulate buy</button><button class="button secondary" data-action="details" data-symbol="<?= e($stock['symbol']) ?>" data-stock-id="<?= (int)$stock['stock_id'] ?>">Details</button><button class="button ghost" data-action="remove-watchlist" data-stock-id="<?= (int)$stock['stock_id'] ?>">Remove</button></div>
        </article>
    <?php endforeach; ?></div><?php else: ?><div class="empty-state"><strong>Your watchlist is empty.</strong><p>Search results and discovery candidates can be followed without buying.</p><button class="button primary" data-switch-tab="search">Find a stock</button></div><?php endif; ?>
</section>

<section class="tab-panel" data-panel="search" <?= $initialTab!=='search'?'hidden':'' ?>>
    <div class="search-hero"><div><span class="eyebrow">Real provider search with local fallback</span><h2>Research before you simulate.</h2><p>Search by ticker or company name. A provider key is required for real external results and current quotes.</p></div><form id="stock-search-form" role="search"><label for="stock-search">Symbol or company</label><div><input id="stock-search" name="q" type="search" placeholder="Try AAPL or Microsoft" autocomplete="off" required><button class="button primary" type="submit">Search</button></div></form></div>
    <div id="search-status" class="search-status" aria-live="polite">Enter a company or symbol to begin.</div>
    <div id="search-results" class="search-results"></div>
</section>

<dialog id="trade-dialog" class="app-dialog" aria-labelledby="trade-title">
    <form method="dialog" class="dialog-close-form"><button aria-label="Close trade dialog">×</button></form>
    <div><span class="eyebrow">Paper order · no real trade</span><h2 id="trade-title">Simulated order</h2><p id="trade-subtitle"></p></div>
    <form id="trade-form" class="stack-form">
        <input type="hidden" name="symbol"><input type="hidden" name="side"><input type="hidden" name="exchange">
        <div class="quote-box"><span>Latest accepted quote</span><strong id="trade-price">Loading…</strong><small id="trade-freshness">Quote must be current enough to trade.</small></div>
        <label>Quantity<input type="number" name="quantity" min="0.00000001" step="0.00000001" required></label>
        <div class="trade-safety"><strong>Paper-order protection</strong><span>Freshness is checked before trading. Sell quantity cannot exceed the shares you own.</span></div>
        <p id="trade-available" class="muted" role="status" aria-live="polite"></p><p id="trade-estimate" class="order-estimate">Estimated total will appear here.</p>
        <label class="check-row" id="keep-watchlist-row"><input type="checkbox" name="keep_watchlisted" value="1"> Keep/add this stock in my watchlist</label>
        <button class="button primary large full" type="submit">Confirm simulated order</button>
        <small>Delayed quotes are clearly labeled. Stale or unavailable quotes disable trading.</small>
    </form>
</dialog>

<dialog id="details-dialog" class="app-dialog details-dialog" aria-labelledby="details-title">
    <form method="dialog" class="dialog-close-form"><button aria-label="Close stock details">×</button></form>
    <div class="details-heading"><div><span class="eyebrow">Stock details · stays inside Stocks</span><h2 id="details-title">Loading…</h2><p id="details-company"></p></div><div id="details-price" class="details-price"></div></div>
    <div class="range-selector" aria-label="Chart range"><?php foreach(['1d'=>'1D','7d'=>'7D','1m'=>'1M','3m'=>'3M','1y'=>'1Y'] as $range=>$label):?><button type="button" data-range="<?= e($range) ?>" <?= $range==='1m'?'aria-pressed="true"':'' ?>><?= e($label) ?></button><?php endforeach;?></div>
    <div class="chart-wrap details-chart"><canvas id="stock-details-chart" role="img" aria-label="Historical stock price"></canvas></div><p id="details-chart-status" class="chart-fallback">Historical data is loading.</p>
    <div id="details-facts" class="details-facts"></div><div id="details-news" class="details-news"></div>
</dialog>

<script type="application/json" id="stocks-page-data"><?= json_encode(['watchlist_ids'=>array_map('intval',array_column($watchlist,'stock_id'))],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
