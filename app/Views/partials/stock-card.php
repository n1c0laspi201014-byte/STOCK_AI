<article class="stock-card">
    <div class="stock-card-title"><div><strong><?= e($stock['symbol']) ?></strong><span><?= e($stock['company_name']) ?></span></div><span class="freshness <?= !empty($stock['is_delayed']) ? 'delayed' : 'cached' ?>"><?= e($stock['provider'] ?? 'Unavailable') ?></span></div>
    <div class="stock-price"><?= $stock['current_price'] !== null ? e(money((float) $stock['current_price'], (string) $stock['currency'])) : 'Quote unavailable' ?></div>
    <div class="stock-actions"><?= $actions ?? '' ?></div>
</article>

