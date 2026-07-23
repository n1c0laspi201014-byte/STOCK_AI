(() => {
  const appUrl = document.querySelector('meta[name="app-url"]')?.content?.replace(/\/$/, '') || '';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  async function request(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = { Accept: 'application/json', ...(options.headers || {}) };
    if (!['GET', 'HEAD'].includes(method)) headers['X-CSRF-TOKEN'] = csrf;
    let body = options.body;
    if (body && !(body instanceof FormData) && typeof body !== 'string') {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(body);
    }
    let response;
    try {
      response = await fetch(`${appUrl}/${String(path).replace(/^\//, '')}`, { ...options, method, headers, body, credentials: 'same-origin' });
    } catch (error) {
      throw new Error('Network request failed. Confirm WAMP and your internet connection.');
    }
    const data = await response.json().catch(() => ({ success: false, message: 'The server returned an invalid response.' }));
    if (!response.ok || data.success === false) {
      const error = new Error(data.message || `Request failed with HTTP ${response.status}.`);
      error.code = data.error_code;
      error.retryable = data.retryable;
      error.data = data;
      throw error;
    }
    return data;
  }

  function formData(form) {
    const output = {};
    const elements = [...form.elements].filter(element => element.name && !element.disabled);
    for (const element of elements) {
      if (element.type === 'checkbox') {
        if (element.name.endsWith('[]')) {
          const key = element.name.slice(0, -2);
          output[key] ||= [];
          if (element.checked) output[key].push(element.value);
        } else {
          output[element.name] = element.checked;
        }
      } else if (element.type !== 'submit' && element.type !== 'button') {
        output[element.name] = element.value;
      }
    }
    return output;
  }

  window.AppApi = { request, formData, appUrl, csrf };
})();

