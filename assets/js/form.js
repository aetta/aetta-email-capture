(function () {
  'use strict';

  function showMessage(form, text, isSuccess) {
    var existing = form.querySelector('.aettaec-msg');
    if (existing) existing.remove();

    var box = document.createElement('div');
    box.className = 'aettaec-msg ' + (isSuccess ? 'aettaec-success' : 'aettaec-error');
    box.setAttribute('role', isSuccess ? 'status' : 'alert');
    box.textContent = text;
    form.insertBefore(box, form.firstChild);
  }

  function bindForm(form) {
    var refField = form.querySelector('input[name="aettaec_ref"]');
    if (refField && !refField.value) refField.value = document.referrer || '';

    form.addEventListener('submit', function (event) {
      var ajaxUrl = form.getAttribute('data-ajax');
      if (!ajaxUrl || !window.fetch || !window.FormData) return;

      event.preventDefault();

      var button = form.querySelector('button[type="submit"]');
      form.classList.add('aettaec-busy');
      if (button) button.disabled = true;

      fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new FormData(form)
      })
        .then(function (response) { return response.json(); })
        .then(function (result) {
          var message = (result && result.data && result.data.message) || '';
          showMessage(form, message, !!(result && result.success));
          if (result && result.success) form.reset();
        })
        .catch(function () {
          // Network/parse failure: fall back to the classic non-AJAX submit.
          HTMLFormElement.prototype.submit.call(form);
        })
        .finally(function () {
          form.classList.remove('aettaec-busy');
          if (button) button.disabled = false;
        });
    });
  }

  function init() {
    document.querySelectorAll('.aettaec-form').forEach(bindForm);
  }

  if (document.readyState !== 'loading') init();
  else document.addEventListener('DOMContentLoaded', init);
})();
