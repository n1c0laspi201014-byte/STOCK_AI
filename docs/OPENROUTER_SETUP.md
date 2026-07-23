# OpenRouter setup

1. Create an OpenRouter account and API key. Select an available model that supports structured JSON; availability and price can change.
2. Put the values in `.env`:

```dotenv
OPENROUTER_API_KEY=PASTE_KEY_HERE
OPENROUTER_MODEL=provider/exact-model-id
OPENROUTER_SITE_URL=http://localhost/papertrade-ai/public
OPENROUTER_SITE_NAME="PaperTrade AI"
```

3. Run `php scripts/php/test_openrouter.php AAPL 7d`.
4. Expected: authentication, model, JSON, schema, bounds, and safe-language checks pass.

The backend requests strict structured output, validates every field, retries once for repair, and falls back to deterministic technical scoring with `partial` status. Invalid model: copy the exact identifier. Insufficient credit: choose an available lower-cost model or add credit. 429: wait for the limit window. The LLM never supplies the displayed probability by itself.

