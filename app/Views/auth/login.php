<main class="auth-shell container-fluid">
    <div class="auth-layout row min-vh-100 g-0 align-items-stretch">
        <section class="auth-intro col-12 col-lg-6">
            <div class="brand large">
                <span class="brand-mark" aria-hidden="true">P</span>
                <span><strong><?= e(config('app.name')) ?></strong><small>No real money. Real market lessons.</small></span>
            </div>
            <div class="auth-message">
                <span class="eyebrow">School project · simulated trading</span>
                <h1>Market clarity.<br><span>Without the real risk.</span></h1>
                <p>Research real market data, build a paper portfolio, and review explainable AI-assisted estimates—all without placing a real trade.</p>
            </div>
            <div class="auth-proof" aria-label="Platform highlights">
                <span>Real market data*</span>
                <span>Paper accounts</span>
                <span>Explainable estimates</span>
            </div>
        </section>

        <section class="auth-panel col-12 col-lg-6 d-flex align-items-center justify-content-center">
            <div class="auth-card w-100" aria-labelledby="login-heading">
                <div>
                    <span class="eyebrow">Welcome back</span>
                    <h2 id="login-heading">Sign in to your paper account</h2>
                    <p>Choose a development account or enter your credentials.</p>
                </div>

                <?php if (!empty($error)): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?>

                <form action="<?= e(url('/login')) ?>" method="post" class="stack-form">
                    <input type="hidden" name="_csrf" value="<?= e(\App\Support\Csrf::token()) ?>">
                    <label>Email address<input class="form-control" type="email" name="email" autocomplete="username" required placeholder="trader@papertrade.local"></label>
                    <label>Password<input class="form-control" type="password" name="password" autocomplete="current-password" required placeholder="••••••••••"></label>
                    <button class="button primary large full" type="submit">Open dashboard <span aria-hidden="true">→</span></button>
                </form>

                <div class="auth-switch"><span>New to STOCK AI?</span><a class="button secondary" href="<?= e(url('/register')) ?>">Create your account</a></div>

                <?php if (config('app.env') === 'development'): ?>
                    <div class="demo-accounts">
                        <div><strong>Development accounts</strong><span>Click to fill</span></div>
                        <div class="demo-account-grid">
                            <button type="button" data-demo-email="admin@papertrade.local" data-demo-password="Admin123!"><span>Admin</span><small>Full settings</small></button>
                            <button type="button" data-demo-email="trader@papertrade.local" data-demo-password="Trader123!"><span>Trader</span><small>Research and paper trading</small></button>
                        </div>
                        <p>Change every development password before any public deployment.</p>
                    </div>
                    <script>document.querySelectorAll('[data-demo-email]').forEach(button=>button.addEventListener('click',()=>{document.querySelector('[name=email]').value=button.dataset.demoEmail;document.querySelector('[name=password]').value=button.dataset.demoPassword;}));</script>
                <?php endif; ?>

                <small class="auth-footnote">* Availability and delay depend on the configured provider and plan.</small>
            </div>
        </section>
    </div>
</main>
