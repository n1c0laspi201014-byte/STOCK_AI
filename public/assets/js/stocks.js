document.addEventListener('DOMContentLoaded', () => {
  const tabButtons = [...document.querySelectorAll('[data-tab]')];
  const panels = [...document.querySelectorAll('[data-panel]')];
  const searchForm = document.getElementById('stock-search-form');
  const searchStatus = document.getElementById('search-status');
  const searchResults = document.getElementById('search-results');
  const tradeDialog = document.getElementById('trade-dialog');
  const tradeForm = document.getElementById('trade-form');
  const detailsDialog = document.getElementById('details-dialog');
  let activeDetailsSymbol = '';

  function switchTab(name) {
    tabButtons.forEach(button => { const selected = button.dataset.tab === name; button.setAttribute('aria-selected', String(selected)); });
    panels.forEach(panel => panel.hidden = panel.dataset.panel !== name);
    history.replaceState({}, '', `${location.pathname}?tab=${encodeURIComponent(name)}`);
  }
  tabButtons.forEach(button => button.addEventListener('click', () => switchTab(button.dataset.tab)));
  document.querySelectorAll('[data-switch-tab]').forEach(button => button.addEventListener('click', () => switchTab(button.dataset.switchTab)));

  searchForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const query = new FormData(searchForm).get('q').trim();
    searchStatus.textContent = `Searching for “${query}”…`;
    searchResults.replaceChildren();
    try {
      const result = await AppApi.request(`/api/market/search?q=${encodeURIComponent(query)}`);
      searchStatus.textContent = result.data.length ? `${result.data.length} result${result.data.length === 1 ? '' : 's'} · ${result.source || 'provider'}` : 'No matching stocks found.';
      result.data.forEach(stock => renderSearchResult(stock));
    } catch (error) {
      searchStatus.textContent = error.message;
      notify(error.message, 'error');
    }
  });

  function renderSearchResult(stock) {
    const card = document.createElement('article');
    card.className = 'search-result-card';
    card.dataset.stockId = stock.id;
    card.innerHTML = `<div class="stock-card-title"><div><strong>${escapeHtml(stock.symbol)}</strong><span>${escapeHtml(stock.company_name)}</span></div><span class="freshness">${escapeHtml(stock.provider || 'provider')}</span></div><div class="result-meta"><span>${escapeHtml(stock.exchange || 'Exchange unknown')}</span><span>${escapeHtml(stock.country || 'Country unknown')}</span></div><div class="stock-price" data-result-price>Loading quote…</div><div class="daily-change" data-result-change><span>Checking freshness</span></div><div class="stock-actions"><button class="button primary" data-action="trade" data-side="buy" data-symbol="${escapeHtml(stock.symbol)}">Buy</button><button class="button secondary" data-action="add-watchlist" data-stock-id="${Number(stock.id)}">Add to watchlist</button><button class="button ghost" data-action="details" data-symbol="${escapeHtml(stock.symbol)}" data-stock-id="${Number(stock.id)}">View details</button><button class="button ghost" data-action="predict" data-stock-id="${Number(stock.id)}">Detailed prediction</button></div>`;
    searchResults.append(card);
    AppApi.request(`/api/market/quote?symbol=${encodeURIComponent(stock.symbol)}&exchange=${encodeURIComponent(stock.exchange || '')}`).then(result => {
      const quote = result.data;
      card.querySelector('[data-result-price]').textContent = `${quote.currency || stock.currency || 'USD'} ${Number(quote.price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 })}`;
      const movement = card.querySelector('[data-result-change]');
      movement.innerHTML = `<span class="${Number(quote.change_percent) >= 0 ? 'gain' : 'loss'}">${Number(quote.change_percent) >= 0 ? '↗' : '↘'} ${Number(quote.change_percent).toFixed(2)}%</span><small>${escapeHtml(quote.freshness || 'Unknown freshness')} · ${escapeHtml(quote.market_status || 'unknown')} market</small>`;
      card.querySelector('[data-action="trade"]').dataset.price = quote.price;
    }).catch(error => {
      card.querySelector('[data-result-price]').textContent = 'Current quote unavailable.';
      card.querySelector('[data-result-change]').textContent = error.message;
    });
  }

  document.addEventListener('click', async event => {
    const button = event.target.closest('[data-action]');
    if (!button) return;
    const action = button.dataset.action;
    if (action === 'trade') return openTrade(button);
    if (action === 'details') return openDetails(button.dataset.symbol);
    button.disabled = true;
    try {
      if (action === 'add-watchlist') {
        const result = await AppApi.request('/api/watchlist', { method: 'POST', body: { stock_id: Number(button.dataset.stockId) } });
        button.textContent = 'In watchlist';
        notify(result.data.message, 'success', 'Watchlist');
      } else if (action === 'remove-watchlist') {
        const result = await AppApi.request(`/api/watchlist/${Number(button.dataset.stockId)}`, { method: 'DELETE' });
        notify(result.message, 'success', 'Watchlist');
        location.reload();
      } else if (action === 'predict') {
        button.textContent = 'Generating…';
        const result = await AppApi.request('/api/predictions/generate', { method: 'POST', body: { stock_id: Number(button.dataset.stockId), horizon: '7d' } });
        notify(`${result.data.symbol}: estimated ${Number(result.data.estimated_probability_up).toFixed(1)}% up over ${result.data.horizon}; ${Number(result.data.confidence_score).toFixed(1)}% confidence.`, 'success', 'Estimate generated');
        setTimeout(() => { location.href = `${AppApi.appUrl}/predictions`; }, 600);
      }
    } catch (error) {
      notify(error.message, 'error');
      button.disabled = false;
    }
  });

  async function openTrade(button) {
    const symbol = button.dataset.symbol;
    const side = button.dataset.side || 'buy';
    const submit = tradeForm.querySelector('[type="submit"]');
    tradeForm.reset();
    tradeForm.elements.symbol.value = symbol;
    tradeForm.elements.side.value = side;
    tradeForm.dataset.price = '';
    tradeForm.dataset.tradeable = 'false';
    tradeForm.dataset.side = side;
    tradeForm.dataset.available = button.dataset.available || '';
    submit.disabled = true;
    submit.textContent = 'Checking quote freshness...';
    document.getElementById('trade-title').textContent = `${side === 'buy' ? 'Buy' : 'Sell'} ${symbol} — simulated`;
    document.getElementById('trade-subtitle').textContent = side === 'buy' ? 'Virtual cash will decrease after server validation.' : 'Virtual cash will increase; the watchlist stays unchanged.';
    document.getElementById('trade-available').textContent = side === 'sell' ? `Available quantity: ${button.dataset.available || 'unknown'}` : '';
    document.getElementById('keep-watchlist-row').hidden = side === 'sell';
    document.getElementById('trade-price').textContent = 'Loading current quote…';
    tradeDialog.showModal();
    try {
      const result = await AppApi.request(`/api/market/quote?symbol=${encodeURIComponent(symbol)}`);
      const quote = result.data;
      tradeForm.dataset.price = quote.price;
      tradeForm.dataset.available = button.dataset.available || '';
      const rawTimestamp = quote.provider_timestamp || quote.received_at || '';
      const numericTimestamp = Number(rawTimestamp);
      const quoteTime = Number.isFinite(numericTimestamp) && numericTimestamp > 0
        ? numericTimestamp * (numericTimestamp < 1000000000000 ? 1000 : 1)
        : Date.parse(rawTimestamp);
      const ageMilliseconds = Number.isFinite(quoteTime) ? Date.now() - quoteTime : Infinity;
      const freshness = String(quote.freshness || '').toLowerCase();
      const quoteIsStale = freshness.includes('stale') || ageMilliseconds > 15 * 60 * 1000 || !Number(quote.price);
      tradeForm.dataset.tradeable = quoteIsStale ? 'false' : 'true';
      submit.disabled = true;
      submit.textContent = quoteIsStale ? 'Trading unavailable' : 'Confirm simulated order';
      setTimeout(() => {
        if (quoteIsStale) {
          document.getElementById('trade-freshness').textContent = 'Trading is unavailable because this quote is older than 15 minutes. Refresh the market data and try again.';
        }
        validateTradeForm();
      }, 0);
      document.getElementById('trade-price').textContent = `${quote.currency || 'USD'} ${Number(quote.price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 })}`;
      document.getElementById('trade-freshness').textContent = `${quote.freshness || 'Unknown freshness'} · quote time ${quote.provider_timestamp || 'unknown'}`;
    } catch (error) {
      tradeForm.dataset.tradeable = 'false';
      submit.disabled = true;
      submit.textContent = 'Trading unavailable';
      document.getElementById('trade-price').textContent = 'Current quote unavailable.';
      document.getElementById('trade-freshness').textContent = error.message;
    }
  }

  function validateTradeForm() {
    const quantity = Number(tradeForm.elements.quantity.value || 0);
    const price = Number(tradeForm.dataset.price || 0);
    const available = Number(tradeForm.dataset.available || 0);
    const side = tradeForm.dataset.side || 'buy';
    const submit = tradeForm.querySelector('[type="submit"]');
    const availability = document.getElementById('trade-available');
    const overselling = side === 'sell' && quantity > available;
    if (side === 'sell') {
      availability.textContent = overselling
        ? `You own ${available.toLocaleString()} shares. Reduce the quantity to avoid overselling.`
        : `Available to sell: ${available.toLocaleString()} shares.`;
      availability.className = overselling ? 'notice error' : 'muted';
    }
    submit.disabled = tradeForm.dataset.tradeable !== 'true' || !(quantity > 0) || !(price > 0) || overselling;
  }

  tradeForm?.elements.quantity?.addEventListener('input', () => {
    const quantity = Number(tradeForm.elements.quantity.value || 0);
    const price = Number(tradeForm.dataset.price || 0);
    document.getElementById('trade-estimate').textContent = price > 0 && quantity > 0 ? `Estimated gross: ${(quantity * price).toLocaleString(undefined, { style: 'currency', currency: 'USD' })}. Server fee and current quote are confirmed at submission.` : 'Estimated total will appear here.';
    validateTradeForm();
  });

  tradeForm?.addEventListener('submit', async event => {
    event.preventDefault();
    validateTradeForm();
    if (tradeForm.dataset.tradeable !== 'true') {
      notify('This quote is unavailable or stale, so the paper order cannot be submitted.', 'error', 'Trading unavailable');
      return;
    }
    const requestedQuantity = Number(tradeForm.elements.quantity.value || 0);
    const ownedQuantity = Number(tradeForm.dataset.available || 0);
    if (tradeForm.dataset.side === 'sell' && requestedQuantity > ownedQuantity) {
      notify(`You own ${ownedQuantity.toLocaleString()} shares. Reduce the sell quantity.`, 'error', 'Overselling prevented');
      return;
    }
    const data = AppApi.formData(tradeForm);
    const submit = tradeForm.querySelector('[type="submit"]');
    submit.disabled = true; submit.textContent = 'Confirming quote…';
    try {
      const result = await AppApi.request(`/api/portfolio/${data.side}`, { method: 'POST', body: data });
      notify(result.message, 'success', 'Paper trade recorded');
      tradeDialog.close();
      setTimeout(() => location.reload(), 700);
    } catch (error) {
      notify(error.message, 'error');
      submit.disabled = false; submit.textContent = 'Confirm simulated order';
    }
  });

  async function openDetails(symbol, range = '1m') {
    activeDetailsSymbol = symbol;
    document.getElementById('details-title').textContent = `${symbol} details`;
    document.getElementById('details-company').textContent = 'Loading company profile and quote…';
    document.getElementById('details-price').textContent = '';
    document.getElementById('details-facts').replaceChildren();
    document.getElementById('details-news').replaceChildren();
    detailsDialog.showModal();
    const [profile, quote, historyResult, news] = await Promise.allSettled([
      AppApi.request(`/api/market/profile?symbol=${encodeURIComponent(symbol)}`),
      AppApi.request(`/api/market/quote?symbol=${encodeURIComponent(symbol)}`),
      AppApi.request(`/api/market/history?symbol=${encodeURIComponent(symbol)}&range=${range}`),
      AppApi.request(`/api/market/news?symbol=${encodeURIComponent(symbol)}`)
    ]);
    if (profile.status === 'fulfilled') {
      document.getElementById('details-title').textContent = profile.value.data.symbol;
      document.getElementById('details-company').textContent = `${profile.value.data.company_name} · ${profile.value.data.exchange || 'Exchange unknown'} · ${profile.value.data.industry || 'Industry unknown'}`;
    }
    if (quote.status === 'fulfilled') {
      const q = quote.value.data;
      document.getElementById('details-price').innerHTML = `<strong>${escapeHtml(q.currency || 'USD')} ${Number(q.price).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:4})}</strong><span class="${Number(q.change_percent)>=0?'gain':'loss'}">${Number(q.change_percent)>=0?'↗':'↘'} ${Number(q.change_percent).toFixed(2)}% · ${escapeHtml(q.freshness || 'unknown')}</span>`;
      const facts = [['Open',q.open],['High',q.high],['Low',q.low],['Previous close',q.previous_close],['Market',q.market_status],['Provider',q.provider],['Updated',q.provider_timestamp],['Delay',q.is_delayed?(q.delay_minutes?`${q.delay_minutes} min`:'Delayed'):'Not reported']];
      document.getElementById('details-facts').innerHTML = facts.map(([label,value])=>`<div><span>${escapeHtml(label)}</span><strong>${escapeHtml(value??'Unavailable')}</strong></div>`).join('');
    } else document.getElementById('details-price').textContent = 'Current quote unavailable.';
    renderDetailsHistory(historyResult);
    if (news.status === 'fulfilled' && news.value.data.length) {
      document.getElementById('details-news').innerHTML = `<h3>Recent company news</h3>${news.value.data.map(item=>`<article><a href="${escapeAttribute(item.url)}" target="_blank" rel="noopener noreferrer"><strong>${escapeHtml(item.headline)}</strong></a><p>${escapeHtml(item.summary || '')}</p><small>${escapeHtml(item.source || '')} · ${escapeHtml(item.datetime || '')}</small></article>`).join('')}`;
    } else document.getElementById('details-news').innerHTML = '<p class="muted">Recent news is unavailable.</p>';
  }

  function renderDetailsHistory(result) {
    const status = document.getElementById('details-chart-status');
    if (result.status === 'fulfilled' && result.value.data.points.length) {
      FinancialCharts.line('stock-details-chart', result.value.data.points, `${activeDetailsSymbol} close`);
      status.textContent = `Actual market prices · ${result.value.data.source} · ${result.value.data.points.length} observations.`;
    } else {
      status.textContent = result.status === 'rejected' ? result.reason.message : 'Historical data is unavailable on the configured provider plan.';
    }
  }

  document.querySelectorAll('[data-range]').forEach(button => button.addEventListener('click', async () => {
    document.querySelectorAll('[data-range]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    if (!activeDetailsSymbol) return;
    document.getElementById('details-chart-status').textContent = 'Loading historical data…';
    try { renderDetailsHistory({ status: 'fulfilled', value: await AppApi.request(`/api/market/history?symbol=${encodeURIComponent(activeDetailsSymbol)}&range=${button.dataset.range}`) }); }
    catch (error) { renderDetailsHistory({ status: 'rejected', reason: error }); }
  }));

  const requestedSymbol = new URLSearchParams(location.search).get('symbol');
  if (requestedSymbol) openDetails(requestedSymbol).catch(error => notify(error.message, 'error'));

  function escapeHtml(value) { const div = document.createElement('div'); div.textContent = String(value ?? ''); return div.innerHTML; }
  function escapeAttribute(value) { return String(value || '#').replace(/"/g, '&quot;'); }
});
