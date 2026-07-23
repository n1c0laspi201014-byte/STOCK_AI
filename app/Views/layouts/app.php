<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\App\Support\Csrf::token()) ?>">
    <meta name="app-url" content="<?= e(config('app.url')) ?>">
    <meta name="theme-color" content="#f4f7f5">
    <title><?= e($pageTitle ?? 'STOCK AI') ?> · <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/reset.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/navbar.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/' . ($activePage ?? 'dashboard') . '.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/responsive.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/bootstrap-theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/futuristic.css')) ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js" defer></script>
    <script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>" defer></script>
    <script src="<?= e(asset('js/api.js')) ?>" defer></script>
    <script src="<?= e(asset('js/charts.js')) ?>" defer></script>
    <script src="<?= e(asset('js/notifications.js')) ?>" defer></script>
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
    <?php if (!empty($pageScript)): ?><script src="<?= e(asset('js/' . $pageScript)) ?>" defer></script><?php endif; ?>
</head>
<body class="app-body" data-bs-theme="light" data-page="<?= e($activePage ?? '') ?>">
<?php require base_path('app/Views/partials/navbar.php'); ?>
<main class="app-main container-fluid app-container" id="main-content" tabindex="-1">
    <?php require base_path('app/Views/partials/flash.php'); ?>
    <?= $content ?>
</main>
<?php require base_path('app/Views/partials/footer.php'); ?>
<?php require base_path('app/Views/partials/ai-assistant.php'); ?>
<div id="toast-region" class="toast-region" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
