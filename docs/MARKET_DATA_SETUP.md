# Market-data setup

The primary provider is Finnhub; Twelve Data is an optional fallback. Provider coverage, delay, history access, and rate limits depend on the current account plan.

1. Create a Finnhub account and generate a private API key.
2. Put only the key value in server-side `.env`:

```dotenv
MARKET_DATA_PROVIDER=finnhub
MARKET_DATA_API_KEY=PASTE_KEY_HERE
MARKET_DATA_CACHE_SECONDS=60
MARKET_DATA_STALE_AFTER_SECONDS=900
```

3. Restart Apache if required, then run `php scripts/php/test_market_api.php AAPL`.
4. Expected: authentication/search/quote/profile/timestamp checks pass. History may fail if the plan excludes stock candles; the app then uses saved `price_snapshots` or shows an honest no-chart state.
5. In the site, open Stocks > Search, search AAPL, and verify symbol, quote timestamp, provider, and freshness.

Errors: 401/403 means key/plan; 429 means rate limit, so wait and rely on cache. Missing history is not replaced with invented candles. To switch to Twelve Data, set `MARKET_DATA_PROVIDER=twelvedata` and use the matching key variable.

