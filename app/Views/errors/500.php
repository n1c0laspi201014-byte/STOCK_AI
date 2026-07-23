<main class="error-page"><span class="eyebrow">Setup or server error</span><h1>The request could not be completed.</h1><p><?= config('app.debug') && isset($exception) ? e($exception->getMessage()) : 'Check WAMP, MySQL, and the project setup steps, then try again.' ?></p><a class="button primary" href="<?= e(auth_user() ? url('/dashboard') : url('/login')) ?>">Return safely</a></main>

