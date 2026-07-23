<section class="panel chart-panel">
    <div class="panel-heading">
        <div><span class="eyebrow"><?= e($chartEyebrow ?? 'Portfolio chart') ?></span><h2><?= e($chartTitle ?? 'Chart') ?></h2></div>
        <?php if (!empty($chartMeta)): ?><span class="freshness cached"><?= e($chartMeta) ?></span><?php endif; ?>
    </div>
    <div class="chart-wrap"><canvas id="<?= e($chartId ?? 'chart') ?>" aria-label="<?= e($chartAria ?? $chartTitle ?? 'Financial chart') ?>" role="img"></canvas></div>
    <p class="chart-fallback" id="<?= e(($chartId ?? 'chart') . '-fallback') ?>"><?= e($chartFallback ?? 'Chart data will appear after prices have been accumulated.') ?></p>
</section>

