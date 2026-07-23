(() => {
  const nav = document.getElementById('primary-navigation');
  nav?.querySelectorAll('.nav-link').forEach((link) => link.addEventListener('click', () => {
    if (window.innerWidth < 1200 && window.bootstrap?.Collapse) {
      window.bootstrap.Collapse.getOrCreateInstance(nav, { toggle: false }).hide();
    }
  }));

  const refresh = document.getElementById('market-refresh');
  const badge = document.getElementById('market-status');
  const time = document.getElementById('refresh-time');
  async function refreshStatus(showToast = false) {
    if (!badge) return;
    badge.textContent = 'Checking market…';
    try {
      const result = await AppApi.request('/api/market/status?exchange=US');
      const status = result.data?.status || 'unknown';
      badge.textContent = `Market ${status}`;
      badge.className = `market-badge ${status}`;
      if (time) time.textContent = `Refreshed ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
      if (showToast) notify(`Market status: ${status}.`, 'success', 'Market refreshed');
    } catch (error) {
      badge.textContent = 'Market unknown';
      badge.className = 'market-badge unknown';
      if (showToast) notify(error.message, 'error');
    }
  }
  refresh?.addEventListener('click', () => refreshStatus(true));
  if (badge) refreshStatus(false);
})();
