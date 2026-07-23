(() => {
  const instances = new Map();
  const palette = { accent: '#20b97b', danger: '#ef6a62', blue: '#5b7cfa', warning: '#d69b2d', grid: 'rgba(28,54,45,.08)', text: '#687873' };

  function create(id, config) {
    const canvas = document.getElementById(id);
    if (!canvas || typeof Chart === 'undefined') return null;
    instances.get(id)?.destroy();
    const defaults = {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: { legend: { labels: { color: palette.text, usePointStyle: true, boxWidth: 8, font: { family: '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif' } } }, tooltip: { backgroundColor: 'rgba(255,255,255,.98)', titleColor: '#17211e', bodyColor: '#53645e', borderColor: '#d6e1dc', borderWidth: 1, padding: 12, displayColors: false } },
      scales: { x: { ticks: { color: palette.text, maxTicksLimit: 8 }, grid: { display: false } }, y: { ticks: { color: palette.text }, grid: { color: palette.grid } } }
    };
    config.options = merge(defaults, config.options || {});
    const chart = new Chart(canvas, config);
    instances.set(id, chart);
    return chart;
  }

  function line(id, points, label = 'Price') {
    return create(id, { type: 'line', data: { labels: points.map(p => p.timestamp), datasets: [{ label, data: points.map(p => Number(p.close)), borderColor: palette.accent, backgroundColor: 'rgba(32,185,123,.11)', borderWidth: 2.25, fill: true, pointRadius: points.length > 80 ? 0 : 2, pointBackgroundColor: '#ffffff', pointBorderColor: palette.accent, tension: .28 }] }, options: {} });
  }

  function merge(target, source) {
    const output = { ...target };
    Object.entries(source).forEach(([key, value]) => { output[key] = value && typeof value === 'object' && !Array.isArray(value) ? merge(output[key] || {}, value) : value; });
    return output;
  }

  window.FinancialCharts = { create, line, palette };
})();
