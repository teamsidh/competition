(() => {
  const form = document.querySelector('#registration-form');
  const summary = document.querySelector('#error-summary');
  if (summary) summary.focus();

  const printButton = document.querySelector('[data-print]');
  if (printButton) printButton.addEventListener('click', () => window.print());
  if (!form) return;

  const messages = {
    full_name: 'Enter your full name.',
    email: 'Enter a valid email address.',
    mobile: 'Enter your WhatsApp or mobile number.',
    college_name: 'Enter your college or institution name.',
    college_city: 'Enter your college city.',
    year_of_study: 'Select your year of study.',
    consent: 'Please agree to the registration data use notice.'
  };

  function clearError(field) {
    field.removeAttribute('aria-invalid');
    const error = document.getElementById(`${field.id}-error`);
    if (error) error.remove();
    field.removeAttribute('aria-describedby');
  }

  function setError(field, message) {
    let error = document.getElementById(`${field.id}-error`);
    if (!error) {
      error = document.createElement('span');
      error.id = `${field.id}-error`;
      error.className = 'field-error';
      const container = field.closest('.field, .consent-block');
      container.appendChild(error);
    }
    error.textContent = message;
    field.setAttribute('aria-invalid', 'true');
    field.setAttribute('aria-describedby', error.id);
  }

  function validateField(field) {
    if (field.type !== 'checkbox') field.value = field.value.trim();
    if (field.id === 'mobile' && field.value) {
      const compact = field.value.replace(/[\s().-]+/g, '');
      const indian = /^(?:\+?91)?[6-9][0-9]{9}$/.test(compact);
      const international = !compact.startsWith('+91') && /^\+[1-9][0-9]{7,14}$/.test(compact);
      if (!indian && !international) {
        setError(field, 'Enter a valid mobile number. Indian numbers can be entered as 10 digits or with +91.');
        return false;
      }
    }
    if (!field.checkValidity()) {
      let message = messages[field.id] || 'Check this field.';
      if (field.validity.tooShort) message = `Use at least ${field.minLength} characters.`;
      setError(field, message);
      return false;
    }
    clearError(field);
    return true;
  }

  const fields = [...form.querySelectorAll('input[required], select[required]')];
  fields.forEach((field) => {
    field.addEventListener(field.type === 'checkbox' || field.tagName === 'SELECT' ? 'change' : 'input', () => {
      if (field.hasAttribute('aria-invalid')) validateField(field);
    });
  });

  form.addEventListener('submit', (event) => {
    const invalid = fields.filter((field) => !validateField(field));
    if (invalid.length) {
      event.preventDefault();
      invalid[0].focus();
      return;
    }
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    button.querySelector('span').textContent = 'Submitting…';
  });
})();
