# Mandatory all-team acceptance checklist

## Clean start

- [ ] Restart WAMP; Apache and MySQL green.
- [ ] Restart n8n Compose stack; open site, n8n, and Telegram.
- [ ] `health.php` reports healthy.

## Authentication and navigation

- [ ] Trader login reaches Dashboard; invalid password fails.
- [ ] Navbar has only Dashboard, Stocks, Predictions, Setup; active/mobile navigation works.
- [ ] Logout destroys the session; Admin and Trader data remain isolated.

## Stock, watchlist, and paper trade

- [ ] Search AAPL; verify real quote, timestamp, freshness, Buy, Add to watchlist, details, prediction.
- [ ] Add to watchlist with zero owned shares; duplicate insert remains one row.
- [ ] Buy valid quantity; confirm fee/total, cash decrease, holding/weighted cost/history.
- [ ] Open in-page details; chart or honest plan/local-snapshot fallback; profile/quote/news states safe.
- [ ] Sell part; cash increases and watchlist remains. Oversell is rejected.
- [ ] Invalid symbol shows safe error and no fake price.

## Predictions

- [ ] Short estimate has signal, estimated probability, separate confidence, risk, and `1d`/`7d`/`30d` horizon.
- [ ] Predictions page covers owned, watchlist, non-owned opportunities, and history.
- [ ] Every opportunity has Add to watchlist and Simulated buy.
- [ ] Prediction saved; outcome starts pending; disclaimer visible.

## Setup, Telegram, automation

- [ ] Paper-account and both dashboard graph settings save.
- [ ] Important stocks save and alter Dashboard.
- [ ] Telegram test message arrives and verifies connection.
- [ ] Alert can be created, paused/enabled, tested, and deleted.
- [ ] Percentage `0.2` visibly means `0.2%`; cooldown/quiet hours enforced.
- [ ] Telegram Hub alert branch sends Telegram and saves event/delivery status.
- [ ] Morning time/timezone saves; test report arrives.
- [ ] Market-close report can be enabled; logs visible to Admin.
- [ ] Missing/wrong internal API key is rejected; n8n reaches WAMP via host address.

## Security and final pass

- [ ] `.env` ignored; keys absent from page source/logs/Git.
- [ ] CSRF rejects mutation without token; sessions regenerate after login.
- [ ] Account ownership and Admin-only controls reject unauthorized access.
- [ ] Buy/sell rollback on failure and use PDO transactions/prepared SQL.
- [ ] Final PASS: every must-have works or has a demonstrated honest fallback that leaves the app usable.
