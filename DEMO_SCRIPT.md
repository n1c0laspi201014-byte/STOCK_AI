# Presentation demo script

1. Show `health.php` healthy and log in as Trader Demo.
2. Point out exactly four navbar pages and the market freshness badge.
3. Open Stocks > Search; search AAPL; show quote/timestamp/freshness.
4. Add AAPL to watchlist twice; show the duplicate-safe response.
5. Open details, select a chart range, and name the provider/local-history fallback honestly.
6. Simulate buying a small AAPL quantity. Say explicitly that no real order is placed.
7. Show reduced virtual cash, weighted position cost, holding, and transaction history.
8. Generate the AAPL `7d` estimate. Explain probability versus confidence and partial fallback.
9. Open Predictions; show owned/watchlist/opportunities/history and the disclaimer. Add one opportunity to watchlist.
10. Sell part of AAPL; show cash/quantity update. Attempt to oversell and show safe rejection.
11. Open Setup; test Telegram; create/test AAPL `0.2%` alert; show Telegram and delivery result.
12. Run the Telegram Hub morning-report branch manually; show Telegram.
13. Log out and log in as Admin to demonstrate per-user isolation and the Admin-only automation log controls.

If any external provider is unavailable, show the labeled cached/stale/no-chart/technical-only state; never claim missing data is live.
