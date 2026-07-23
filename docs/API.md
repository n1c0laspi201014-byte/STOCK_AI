# JSON API

Browser endpoints require an authenticated PHP session. Every mutation also requires the CSRF token from the page's `csrf-token` meta element in `X-CSRF-TOKEN`. The current session user owns the resource; browser-supplied `user_id` is ignored.

## Market

`GET /api/market/search?q=AAPL`, `/quote?symbol=AAPL`, `/history?symbol=AAPL&range=1m`, `/profile`, `/news`, `/status`; `POST /api/market/refresh`.

## Portfolio and watchlist

`GET /api/portfolio`, `/api/portfolio/transactions`; `POST /api/portfolio/buy`, `/sell`, `/reset`; `GET|POST /api/watchlist`; `PATCH|DELETE /api/watchlist/{stockId}`.

Buy/sell JSON uses `symbol`, positive `quantity`, optional `exchange`, and optional `keep_watchlisted`. The server fetches and freshness-checks the quote.

## Predictions and settings

Prediction endpoints: `/api/predictions/owned`, `/watchlist`, `/discovery`, `/history`, `/generate`, `/discover`. Settings endpoints follow the contract in `config/routes.php`, including alert CRUD/test and Telegram test.

## Internal automation

Every `/api/internal/automation/*` request requires `X-Internal-Api-Key`. Never call these endpoints from browser JavaScript. Errors use normalized fields: `success`, `error_code`, `message`, `retryable`, and where relevant `cached_data_available`.

