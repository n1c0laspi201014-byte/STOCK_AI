<?php $user = auth_user(); ?>
<header class="topbar navbar navbar-expand-xl">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="topbar-inner container-fluid app-container">
        <a class="brand navbar-brand" href="<?= e(url('/dashboard')) ?>" aria-label="STOCK AI dashboard">
            <span class="brand-mark" aria-hidden="true">P</span>
            <span><strong><?= e(config('app.name')) ?></strong><small>No real money. Real market lessons.</small></span>
        </a>
        <button class="navbar-toggler nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#primary-navigation" aria-expanded="false" aria-controls="primary-navigation" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="primary-navigation" class="collapse navbar-collapse topbar-collapse">
            <nav class="primary-nav navbar-nav ms-xl-auto" aria-label="Main navigation">
                <?php
                $navigation = ['dashboard' => 'Dashboard', 'stocks' => 'Stocks', 'predictions' => 'Predictions', 'setup' => 'Setup'];
                $navigationIcons = ['dashboard' => '⌂', 'stocks' => '⌁', 'predictions' => '✦', 'setup' => '⚙'];
                foreach ($navigation as $key => $label):
                    $icon = $navigationIcons[$key];
                ?>
                    <a href="<?= e(url('/' . $key)) ?>" class="nav-link <?= ($activePage ?? '') === $key ? 'active' : '' ?>" <?= ($activePage ?? '') === $key ? 'aria-current="page"' : '' ?>><span class="nav-symbol" aria-hidden="true"><?= e($icon) ?></span><span><?= e($label) ?></span></a>
                <?php endforeach; ?>
            </nav>
            <div class="market-tools">
                <span class="market-badge unknown" id="market-status" title="Provider market status">Market unknown</span>
                <span class="paper-badge">Paper trading</span>
                <span class="refresh-time" id="refresh-time">Not refreshed</span>
                <button class="icon-button" type="button" id="market-refresh" aria-label="Refresh market status">↻</button>
            </div>
            <details class="user-menu">
                <summary><span class="avatar" aria-hidden="true"><?= e(strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 1))) ?></span><span><?= e($user['name'] ?? '') ?><small><?= e(ucfirst((string) ($user['role'] ?? ''))) ?></small></span></summary>
                <div class="user-menu-panel">
                    <strong><?= e($user['name'] ?? '') ?></strong>
                    <small><?= e($user['email'] ?? '') ?></small>
                    <form action="<?= e(url('/logout')) ?>" method="post">
                        <input type="hidden" name="_csrf" value="<?= e(\App\Support\Csrf::token()) ?>">
                        <button class="button secondary full" type="submit">Log out</button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>
