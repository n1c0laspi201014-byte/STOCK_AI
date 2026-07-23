(() => {
  window.notify = (message, tone = 'success', title = tone === 'error' ? 'Could not complete that' : 'Done') => {
    const region = document.getElementById('toast-region');
    if (!region) return;
    const toast = document.createElement('div');
    toast.className = `toast ${tone}`;
    const strong = document.createElement('strong');
    const span = document.createElement('span');
    strong.textContent = title;
    span.textContent = message;
    toast.append(strong, span);
    region.append(toast);
    setTimeout(() => toast.remove(), 6000);
  };
})();

