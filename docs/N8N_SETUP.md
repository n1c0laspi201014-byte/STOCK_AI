# n8n Docker setup

1. Install/start Docker Desktop. Verify `docker version` and `docker compose version`.
2. Replace the `.env` internal-key placeholder with a long random value. Do not publish it.
3. From the project root run `docker compose -f docker-compose.n8n.yml up -d`, then `docker compose -f docker-compose.n8n.yml ps`.
4. Open `http://localhost:5678` and create the local owner account.
5. Import `n8n/workflows/papertrade-telegram-hub.json`.
6. Create Telegram API credentials and select them on every Telegram node; imported credential placeholders intentionally cannot contain your token.
7. Confirm `PAPERTRADE_INTERNAL_BASE_URL=http://host.docker.internal/papertrade-ai/public` and that `PAPERTRADE_INTERNAL_API_KEY` matches `.env`.
8. Run `php scripts/php/test_internal_api.php`. Missing/wrong keys must be rejected and the correct key accepted.
9. Test all four Hub branches, then publish only `PaperTrade AI – Telegram Hub`. Assign the same `PaperTrade Telegram` credential to all four Telegram nodes.

The Hub checks morning-report eligibility each minute so different users can choose different local delivery times; duplicate protection permits one message per user per local date. Market close runs at 4:00 PM in `America/New_York` on weekdays and is limited to once per market day. Configured stock alerts are checked every five minutes, with a fresh prediction generated only after a threshold crosses. Incoming questions use Telegram `getUpdates` polling because WAMP is local and cannot expose a production webhook. Users can send a ticker (`/stock NVDA`, `$MSFT`) or company name (`/stock NVIDIA`, `Apple`, `what about Tesla?`); ambiguous names return a short ticker list.

Pre-consolidation exports are stored in `n8n/backups/2026-07-23-before-telegram-hub/`. Do not export decrypted credentials or commit bot tokens.

From inside Docker, `localhost` means the n8n container, so PHP must be reached through `host.docker.internal`. If WAMP uses a non-80 port, include it. Alerts stop when the PC, WAMP, Docker, n8n, or internet connection is off.
