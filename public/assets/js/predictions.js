document.addEventListener('DOMContentLoaded', () => {
  const tabs = [...document.querySelectorAll('[data-prediction-tab]')];
  const panels = [...document.querySelectorAll('[data-prediction-panel]')];
  const buyDialog = document.getElementById('prediction-buy-dialog');
  const buyForm = document.getElementById('prediction-buy-form');
  tabs.forEach(button => button.addEventListener('click', () => {
    tabs.forEach(tab => tab.setAttribute('aria-selected', String(tab === button)));
    panels.forEach(panel => panel.hidden = panel.dataset.predictionPanel !== button.dataset.predictionTab);
  }));

  document.addEventListener('click', async event => {
    const button = event.target.closest('[data-prediction-action]');
    if (!button) return;
    const action = button.dataset.predictionAction;
    if (action === 'buy') {
      buyForm.reset(); buyForm.elements.symbol.value = button.dataset.symbol;
      document.getElementById('prediction-buy-symbol').textContent = `${button.dataset.symbol} · the server will validate a current quote.`;
      buyDialog.showModal(); return;
    }
    button.disabled = true;
    try {
      if (action === 'watch') {
        const result = await AppApi.request('/api/watchlist', { method: 'POST', body: { stock_id: Number(button.dataset.stockId) } });
        button.textContent = 'In watchlist'; notify(result.data.message, 'success', 'Watchlist');
      } else if (action === 'refresh') {
        button.textContent = 'Generating…';
        const result = await AppApi.request('/api/predictions/generate', { method: 'POST', body: { stock_id: Number(button.dataset.stockId), horizon: '7d' } });
        notify(`Estimated ${Number(result.data.estimated_probability_up).toFixed(1)}% up over ${result.data.horizon}; confidence ${Number(result.data.confidence_score).toFixed(1)}%.`, 'success', `${result.data.symbol} estimate`);
        setTimeout(() => location.reload(), 700);
      }
    } catch (error) { notify(error.message, 'error'); button.disabled = false; }
  });

  buyForm?.addEventListener('submit', async event => {
    event.preventDefault(); const data = AppApi.formData(buyForm); const button = buyForm.querySelector('[type="submit"]'); button.disabled = true;
    try { const result = await AppApi.request('/api/portfolio/buy', { method: 'POST', body: data }); notify(result.message, 'success', 'Paper trade recorded'); buyDialog.close(); }
    catch (error) { notify(error.message, 'error'); button.disabled = false; }
  });

  document.getElementById('discover-predictions')?.addEventListener('click', async event => {
    event.currentTarget.disabled = true; event.currentTarget.textContent = 'Analysing limited universe…';
    try { await AppApi.request('/api/predictions/discover', { method: 'POST', body: {} }); notify('Opportunity estimates refreshed.', 'success'); setTimeout(()=>location.reload(),600); }
    catch (error) { notify(error.message, 'error'); event.currentTarget.disabled = false; event.currentTarget.textContent = 'Refresh opportunity estimates'; }
  });
});

