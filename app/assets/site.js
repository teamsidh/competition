(() => {
  const slides = [...document.querySelectorAll('.carousel-slide')];
  const dots = [...document.querySelectorAll('.carousel-dots button')];
  const carousel = document.querySelector('.carousel');
  if (!slides.length || !carousel) return;
  let active = 0;
  let timer;
  function show(index) {
    active = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => slide.classList.toggle('is-active', i === active));
    dots.forEach((dot, i) => {
      dot.classList.toggle('is-active', i === active);
      if (i === active) dot.setAttribute('aria-current', 'true');
      else dot.removeAttribute('aria-current');
    });
  }
  function stop() { clearInterval(timer); }
  function start() {
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      stop();
      timer = setInterval(() => show(active + 1), 5500);
    }
  }
  dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); start(); }));
  carousel.querySelectorAll('[data-direction]').forEach((button) => button.addEventListener('click', () => {
    show(active + (button.dataset.direction === 'next' ? 1 : -1));
    start();
  }));
  carousel.addEventListener('mouseenter', stop);
  carousel.addEventListener('mouseleave', start);
  carousel.addEventListener('focusin', stop);
  carousel.addEventListener('focusout', (event) => { if (!carousel.contains(event.relatedTarget)) start(); });
  document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
  start();
})();
