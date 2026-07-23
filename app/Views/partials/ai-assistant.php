<details class="ai-assistant">
    <summary aria-label="Open STOCK AI assistant">
        <span aria-hidden="true">✦</span>
        <span>Ask STOCK AI</span>
    </summary>
    <section class="ai-assistant-panel" aria-label="STOCK AI assistant">
        <header>
            <span class="assistant-orb" aria-hidden="true">P</span>
            <div>
                <strong>Ask STOCK AI</strong>
                <small>Friendly guidance, grounded in your paper workspace.</small>
            </div>
        </header>
        <div class="assistant-message">
            <span class="assistant-label">STOCK AI</span>
            <p>What would you like to understand? I can take you to real market facts, explain estimates, or help connect the Telegram chatbot.</p>
        </div>
        <nav class="assistant-actions" aria-label="Assistant shortcuts">
            <a href="<?= e(url('/stocks?tab=search')) ?>"><span>⌕</span><span><strong>Research a stock</strong><small>Search a company or ticker</small></span></a>
            <a href="<?= e(url('/predictions')) ?>"><span>✦</span><span><strong>Explain an estimate</strong><small>Facts first, uncertainty visible</small></span></a>
            <a href="<?= e(url('/setup#telegram')) ?>"><span>↗</span><span><strong>Ask on your phone</strong><small>Use the connected Telegram AI chatbot</small></span></a>
        </nav>
        <p class="assistant-disclaimer">Paper trading only. Educational information—not financial advice.</p>
    </section>
</details>
