document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.api-form').forEach(form => form.addEventListener('submit', async event => {
    event.preventDefault(); const button = form.querySelector('[type="submit"]'); button.disabled = true;
    try { const result = await AppApi.request(form.dataset.endpoint, { method: form.dataset.method || 'PATCH', body: AppApi.formData(form) }); notify(result.message || 'Settings saved.', 'success', 'Settings'); }
    catch (error) { notify(error.message, 'error'); }
    finally { button.disabled = false; }
  }));

  const telegramForm = document.getElementById('telegram-form');
  telegramForm?.addEventListener('submit', async event => {
    event.preventDefault();
    try { const result = await AppApi.request('/api/settings/telegram/verify', { method: 'POST', body: AppApi.formData(telegramForm) }); notify(result.message, 'success', 'Telegram'); }
    catch (error) { notify(error.message, 'error'); }
  });
  document.getElementById('test-telegram')?.addEventListener('click', async event => {
    event.currentTarget.disabled = true;
    try { const result = await AppApi.request('/api/settings/telegram/test', { method: 'POST', body: AppApi.formData(telegramForm) }); notify(result.message, 'success', 'Telegram verified'); }
    catch (error) { notify(error.message, 'error'); }
    finally { event.currentTarget.disabled = false; }
  });

  const thresholdType = document.getElementById('threshold-type');
  thresholdType?.addEventListener('change', () => document.getElementById('threshold-unit').textContent = thresholdType.value === 'percent' ? '%' : 'currency');
  document.getElementById('alert-form')?.addEventListener('submit', async event => {
    event.preventDefault(); const button = event.currentTarget.querySelector('[type="submit"]'); button.disabled = true;
    try { const result = await AppApi.request('/api/settings/alerts', { method: 'POST', body: AppApi.formData(event.currentTarget) }); notify(result.message, 'success', 'Alert rule'); setTimeout(()=>location.reload(),600); }
    catch (error) { notify(error.message, 'error'); button.disabled = false; }
  });

  document.getElementById('alert-list')?.addEventListener('click', async event => {
    const button = event.target.closest('[data-alert-action]'); if (!button) return; button.disabled = true;
    try {
      const id = Number(button.dataset.alertId);
      if (button.dataset.alertAction === 'delete') { if (!confirm('Delete this alert rule?')) { button.disabled = false; return; } await AppApi.request(`/api/settings/alerts/${id}`, { method: 'DELETE' }); notify('Alert rule deleted.', 'success'); setTimeout(()=>location.reload(),500); }
      if (button.dataset.alertAction === 'test') { const result = await AppApi.request(`/api/settings/alerts/${id}/test`, { method: 'POST', body: {} }); notify(result.message || 'Test alert sent.', 'success', 'Telegram test alert'); button.disabled = false; }
      if (button.dataset.alertAction === 'toggle') { const data = JSON.parse(button.dataset.alert); data.is_enabled = !Number(data.is_enabled); await AppApi.request(`/api/settings/alerts/${id}`, { method: 'PATCH', body: data }); notify(data.is_enabled ? 'Alert enabled.' : 'Alert paused.', 'success'); setTimeout(()=>location.reload(),500); }
    } catch (error) { notify(error.message, 'error'); button.disabled = false; }
  });

  document.querySelectorAll('[data-automation]').forEach(button => button.addEventListener('click', async () => {
    button.disabled = true;
    try { const result = await AppApi.request(`/api/settings/automations/${button.dataset.automation}`, { method: 'POST', body: {} }); notify(result.message, 'success', 'Automation'); }
    catch (error) { notify(error.message, 'error'); }
    finally { button.disabled = false; }
  }));

  const resetDialog = document.getElementById('reset-dialog');
  document.getElementById('open-reset')?.addEventListener('click', () => resetDialog.showModal());
  document.getElementById('reset-form')?.addEventListener('submit', async event => {
    event.preventDefault(); const button = event.currentTarget.querySelector('[type="submit"]'); button.disabled = true;
    try { const result = await AppApi.request('/api/portfolio/reset', { method: 'POST', body: AppApi.formData(event.currentTarget) }); notify(result.message, 'success', 'Portfolio reset'); resetDialog.close(); setTimeout(()=>location.href=`${AppApi.appUrl}/dashboard`,700); }
    catch (error) { notify(error.message, 'error'); button.disabled = false; }
  });
});

