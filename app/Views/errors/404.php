<main class="error-page"><span class="eyebrow">404</span><h1>That page is not part of this paper portfolio.</h1><p>The authenticated application has only Dashboard, Stocks, Predictions, and Setup.</p><a class="button primary" href="<?= e(auth_user() ? url('/dashboard') : url('/login')) ?>">Return safely</a></main>

