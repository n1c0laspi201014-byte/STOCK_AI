# Troubleshooting

| Symptom | Likely cause | Exact fix/test |
|---|---|---|
| WAMP icon not green | Apache/MySQL failed | Use WAMP service tests; inspect its Apache/MySQL logs. |
| Apache port conflict | IIS/Skype/another server owns port 80 | Change Apache port or stop the conflicting service; update `APP_URL` and n8n base URL. |
| MySQL not starting | Port/data-directory/service issue | Inspect WAMP MySQL log; verify 3306 and backup data before repairs. |
| `.htaccess` ignored / route 404 | rewrite/override disabled | Enable `rewrite_module`, set `AllowOverride All`, restart Apache. |
| PHP cURL missing | Extension disabled | Enable `curl` in the active WAMP PHP version; restart Apache. |
| PDO connection failure | MySQL stopped or wrong `.env` | Keep `DB_DATABASE=stockdata`; run `setup_database.php`. |
| CSRF failure | Old page/session | Refresh the page and retry; do not reuse a token across sessions. |
| Market 401/403 | Bad key or plan | Recopy the key without quotes/spaces; check provider plan. |
| Market 429 | Rate limit | Stop repeated refreshes, wait, and use labeled cache. |
| Quote/history missing | Symbol or plan endpoint unavailable | Test AAPL; configure fallback or use local snapshots; never fabricate data. |
| OpenRouter invalid JSON | Model/schema support | Use exact model with structured output; repair retries once, then partial fallback. |
| OpenRouter insufficient credits | Account balance/model cost | Select an available model or add credit. |
| Telegram chat not found | Bot chat not started/wrong ID | Press Start in the same bot, obtain the ID again, retest. |
| Telegram bot blocked | User blocked bot | Unblock/start it; ensure correct credential. |
| n8n cannot reach localhost | Container-local address used | Use `host.docker.internal` and WAMP's actual port. |
| n8n workflow inactive | Imported but not activated | Assign Telegram credentials, manual-test, then activate. |
| Docker unavailable | Desktop/WSL/virtualization off | Start Docker Desktop or follow n8n's npm fallback documentation. |
| Stale price warning | Last valid quote older than limit | Refresh after market provider recovers; trading stays disabled while stale. |
| Duplicate alerts | Cooldown/key mismatch/workflow duplicated | Keep one active monitor; verify event callbacks and cooldown. |
| Timezone mismatch | User/n8n/PHP zones differ | Align `.env`, user Setup timezone, Compose `TZ`, and `GENERIC_TIMEZONE`. |

