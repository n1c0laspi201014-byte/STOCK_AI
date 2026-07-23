<main class="auth-shell container-fluid">
    <div class="auth-layout row min-vh-100 g-0 align-items-stretch">
        <section class="auth-intro col-12 col-lg-5">
            <div class="brand large">
                <span class="brand-mark" aria-hidden="true">P</span>
                <span><strong><?= e(config('app.name')) ?></strong><small>No real money. Real market lessons.</small></span>
            </div>
            <div class="auth-message">
                <span class="eyebrow">Self-service paper account</span>
                <h1>Build your view.<br><span>Learn your way.</span></h1>
                <p>Choose your virtual balance, timezone, reporting preferences, and estimate horizon. No real brokerage account or real money is involved.</p>
            </div>
            <div class="auth-proof" aria-label="Account highlights">
                <span>Private login</span>
                <span>Personal settings</span>
                <span>Virtual money only</span>
            </div>
        </section>

        <section class="auth-panel col-12 col-lg-7 d-flex align-items-center justify-content-center">
            <div class="auth-card auth-register-card w-100" aria-labelledby="register-heading">
                <div>
                    <span class="eyebrow">Create account</span>
                    <h2 id="register-heading">Start your personalized paper portfolio</h2>
                    <p>You can change these preferences later from Setup.</p>
                </div>

                <?php if (!empty($error)): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?>

                <form action="<?= e(url('/register')) ?>" method="post" class="stack-form registration-form">
                    <input type="hidden" name="_csrf" value="<?= e(\App\Support\Csrf::token()) ?>">
                    <div class="registration-grid">
                        <label>Display name<input class="form-control" type="text" name="name" maxlength="100" autocomplete="name" required value="<?= e($old['name'] ?? '') ?>"></label>
                        <label>Email address<input class="form-control" type="email" name="email" maxlength="190" autocomplete="email" required value="<?= e($old['email'] ?? '') ?>"></label>
                        <label>Password<input class="form-control" type="password" name="password" minlength="10" autocomplete="new-password" required><small>At least 10 characters with a letter and number.</small></label>
                        <label>Confirm password<input class="form-control" type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required></label>
                        <label>Portfolio name<input class="form-control" type="text" name="portfolio_name" maxlength="120" placeholder="My Paper Portfolio" value="<?= e($old['portfolio_name'] ?? '') ?>"></label>
                        <label>Timezone<input class="form-control" type="text" name="timezone" list="registration-timezones" required value="<?= e($old['timezone'] ?? config('app.timezone', 'Europe/Brussels')) ?>"><datalist id="registration-timezones"><?php foreach ($timezones as $timezone): ?><option value="<?= e($timezone) ?>"><?php endforeach; ?></datalist></label>
                        <label>Paper currency<select class="form-select" name="base_currency"><option value="USD" <?= ($old['base_currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option><option value="EUR" <?= ($old['base_currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR</option></select></label>
                        <label>Starting virtual cash<input class="form-control" type="number" name="starting_cash" min="1000" max="1000000" step="100" required value="<?= e($old['starting_cash'] ?? '100000') ?>"></label>
                        <label>Default estimate horizon<select class="form-select" name="default_horizon"><option value="1d" <?= ($old['default_horizon'] ?? '') === '1d' ? 'selected' : '' ?>>Next trading day</option><option value="7d" <?= ($old['default_horizon'] ?? '7d') === '7d' ? 'selected' : '' ?>>Next 7 days</option><option value="30d" <?= ($old['default_horizon'] ?? '') === '30d' ? 'selected' : '' ?>>Next 30 days</option></select></label>
                        <label>Morning report time<input class="form-control" type="time" name="morning_report_time" value="<?= e($old['morning_report_time'] ?? '07:30') ?>"></label>
                    </div>
                    <div class="registration-options">
                        <label class="check-row"><input type="checkbox" name="allow_fractional_shares" value="1" <?= !array_key_exists('allow_fractional_shares', $old) || !empty($old['allow_fractional_shares']) ? 'checked' : '' ?>> Allow fractional paper shares</label>
                        <label class="check-row"><input type="checkbox" name="morning_report_enabled" value="1" <?= !empty($old['morning_report_enabled']) ? 'checked' : '' ?>> Enable my daily Telegram morning report after I connect Telegram</label>
                        <label class="check-row"><input type="checkbox" name="market_close_report_enabled" value="1" <?= !empty($old['market_close_report_enabled']) ? 'checked' : '' ?>> Enable my weekday US market-close report</label>
                    </div>
                    <button class="button primary large full" type="submit">Create my paper account <span aria-hidden="true">→</span></button>
                </form>

                <div class="auth-switch"><span>Already have an account?</span><a class="button secondary" href="<?= e(url('/login')) ?>">Sign in</a></div>
                <small class="auth-footnote">This creates a local STOCK AI account. It never opens a brokerage account.</small>
            </div>
        </section>
    </div>
</main>
