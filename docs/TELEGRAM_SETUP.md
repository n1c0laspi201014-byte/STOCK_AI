# Telegram setup

1. Open Telegram, find BotFather, and send `/newbot`.
2. Choose a display name and a unique username ending in `bot`.
3. Copy the token privately. Store it in n8n Telegram credentials; optionally set server-side `TELEGRAM_BOT_TOKEN` for the Setup-page direct test.
4. Open the new bot and press Start or send it a message. A bot generally cannot message a user before the user starts the conversation.
5. Obtain the chat ID with a temporary n8n Telegram Trigger or the Bot API `getUpdates` flow. Never expose the bot token in screenshots.
6. In PaperTrade AI > Setup > Telegram, enter the chat ID and optional username, save, then click **Send test message**.

Expected message: `PaperTrade AI connection successful.` plus the educational disclaimer. `chat not found` usually means the chat was not started, the ID belongs to a different bot, or the user blocked the bot.

