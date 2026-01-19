document.addEventListener('DOMContentLoaded', () => {
  const fadeTargets = document.querySelectorAll(
    '.davaks-criterio, .davaks-editorial, .davaks-product-grid, .davaks-product-grid-2, .davaks-trust, .davaks-cat-faq, .davaks-closing'
  );

  fadeTargets.forEach(el => el.classList.add('davaks-fade'));

  const observer = new IntersectionObserver(
    entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('davaks-in-view');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 }
  );

  fadeTargets.forEach(el => observer.observe(el));

  const heroContent = document.querySelectorAll('.davaks-hero-content');
  heroContent.forEach(el => {
    el.classList.add('davaks-hero-animate');
  });

  const heroImages = document.querySelectorAll('.davaks-hero-bg img');
  const parallax = () => {
    const y = window.scrollY || window.pageYOffset;
    heroImages.forEach(img => {
      img.style.transform = `translateY(${Math.min(y * 0.08, 40)}px)`;
    });
  };

  if (heroImages.length) {
    window.addEventListener('scroll', () => window.requestAnimationFrame(parallax), { passive: true });
  }

  document.querySelectorAll('.filter-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      const group = pill.closest('.filter-group');
      if (!group) return;
      group.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('is-active'));
      pill.classList.add('is-active');
    });
  });

  const fadeImages = new Set([
    ...document.querySelectorAll('img[loading="lazy"]'),
    ...document.querySelectorAll('.davaks-hero-bg img'),
    ...document.querySelectorAll('.davaks-editorial img'),
    ...document.querySelectorAll('.davaks-product-grid img'),
    ...document.querySelectorAll('.davaks-product-grid-2 img')
  ]);

  fadeImages.forEach(img => {
    img.classList.add('davaks-img-fade');
    if (img.complete) {
      img.classList.add('is-loaded');
      return;
    }
    img.addEventListener('load', () => img.classList.add('is-loaded'));
  });
});
