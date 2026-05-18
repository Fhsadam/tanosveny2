document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contactForm');
  if (!form) return;
  form.addEventListener('submit', (event) => {
    const errors = [];
    const data = new FormData(form);
    const name = (data.get('name') || '').trim();
    const email = (data.get('email') || '').trim();
    const subject = (data.get('subject') || '').trim();
    const message = (data.get('message') || '').trim();
    if (name.length < 2) errors.push('A név legalább 2 karakter legyen.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Érvényes e-mail címet adjon meg.');
    if (subject.length < 3) errors.push('A tárgy legalább 3 karakter legyen.');
    if (message.length < 10) errors.push('Az üzenet legalább 10 karakter legyen.');
    const box = form.querySelector('.client-errors');
    box.textContent = errors.join(' ');
    if (errors.length > 0) event.preventDefault();
  });
});
