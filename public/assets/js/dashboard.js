document.addEventListener('DOMContentLoaded', async () => {
  const element = document.getElementById('dashboard-data');
  if (!element) return;
  const data = JSON.parse(element.textContent);
  const holdings = data.portfolio.holdings || [];
  const portfolioHistory = data.portfolio_history || { points: [], providers: [] };
  const primary = data.preferences.primary_chart_type || 'portfolio_value';
  const secondary = data.preferences.secondary_chart_type || 'portfolio_allocation';

  await render('dashboard-chart-primary', primary, data.preferences.primary_chart_stock_id);
  await render('dashboard-chart-secondary', secondary, data.preferences.secondary_chart_stock_id);

  async function render(id, type, stockId) {
    const fallback = document.getElementById(`${id}-fallback`);
    if (type === 'stock_price' && stockId) {
      const stock = holdings.find(item => Number(item.stock_id) === Number(stockId));
      if (!stock) { if (fallback) fallback.textContent = 'The selected stock is not currently owned. Choose another in Setup.'; return; }
      try {
        const result = await AppApi.request(`/api/market/history?symbol=${encodeURIComponent(stock.symbol)}&range=1m`);
        FinancialCharts.line(id, result.data.points, `${stock.symbol} price`);
        if (fallback) fallback.textContent = `Actual ${stock.symbol} market prices · ${result.data.source} · ${result.data.points.length} observations.`;
      } catch (error) { if (fallback) fallback.textContent = error.message; }
      return;
    }
    if (type === 'portfolio_allocation') {
      const pricedHoldings = holdings.filter(item => item.market_value !== null && item.market_value !== undefined);
      if (!pricedHoldings.length) { if (fallback) fallback.textContent = 'Actual allocation will appear after provider prices are recorded.'; return; }
      FinancialCharts.create(id, { type: 'doughnut', data: { labels: pricedHoldings.map(item => item.symbol), datasets: [{ data: pricedHoldings.map(item => Number(item.market_value)), backgroundColor: ['#20b97b','#5b7cfa','#d69b2d','#ef6a62','#9e8cff','#49bfc9','#d58fd5','#94c95d'], borderColor: '#ffffff', borderWidth: 3 }] }, options: { scales: {} } });
      if (fallback) fallback.textContent = 'Actual allocation marked with the latest recorded provider price; purchase cost is never substituted.';
      return;
    }
    if (type === 'daily_performance' || type === 'profit_loss') {
      const pricedHoldings = holdings.filter(item => item.unrealized_profit_loss !== null && item.unrealized_profit_loss !== undefined);
      if (!pricedHoldings.length) { if (fallback) fallback.textContent = 'Profit/loss will appear after an actual provider quote is recorded.'; return; }
      FinancialCharts.create(id, { type: 'bar', data: { labels: pricedHoldings.map(item => item.symbol), datasets: [{ label: 'Unrealized P/L', data: pricedHoldings.map(item => Number(item.unrealized_profit_loss)), backgroundColor: pricedHoldings.map(item => Number(item.unrealized_profit_loss) >= 0 ? '#20b97b' : '#ef6a62'), borderRadius: 8 }] }, options: {} });
      if (fallback) fallback.textContent = 'Unrealized P/L uses weighted paper cost and the latest actual provider quote.';
      return;
    }
    if (!portfolioHistory.points?.length) { if (fallback) fallback.textContent = 'Portfolio history will appear after an actual provider quote or paper transaction is recorded.'; return; }
    FinancialCharts.line(id, portfolioHistory.points, `Portfolio value (${portfolioHistory.currency || 'USD'})`);
    const providers = portfolioHistory.providers?.length ? portfolioHistory.providers.join(', ') : 'recorded execution prices';
    if (fallback) fallback.textContent = `Actual 30-day paper-portfolio value reconstructed from stockdata transactions and recorded ${providers} quotes; ${portfolioHistory.points.length} observations.`;
  }
});
