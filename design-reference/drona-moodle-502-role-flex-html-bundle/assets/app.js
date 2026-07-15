(() => {
  const sidebar = document.querySelector('.sidebar');
  let overlay = document.querySelector('.mobile-overlay');
  if (sidebar && !overlay) {
    overlay = document.createElement('div'); overlay.className = 'mobile-overlay';
    document.body.appendChild(overlay);
  }
  const setDrawer = open => {
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    overlay?.classList.toggle('show', open);
    document.body.style.overflow = open ? 'hidden' : '';
  };
  document.querySelectorAll('.menu').forEach(button => {
    button.setAttribute('aria-label','Toggle navigation');
    button.addEventListener('click', () => setDrawer(!sidebar?.classList.contains('open')));
  });
  overlay?.addEventListener('click', () => setDrawer(false));
  document.addEventListener('keydown', event => { if (event.key === 'Escape') setDrawer(false); });
  document.querySelectorAll('.nav a').forEach(link => link.addEventListener('click', () => {
    if (window.matchMedia('(max-width: 760px)').matches) setDrawer(false);
  }));
  document.querySelectorAll('[data-demo]').forEach(button => button.addEventListener('click', () =>
    alert('Static prototype: connect this action to Moodle APIs during implementation.')));
  document.querySelectorAll('form').forEach(form => form.addEventListener('submit', event => event.preventDefault()));
  const current = location.pathname.split('/').pop() || 'public-home.html';
  document.querySelectorAll(`a[href="${current}"]`).forEach(link => link.setAttribute('aria-current','page'));
})();

// Modern landing interactions: accessible navigation, reveal, counters, slider and shared footer.
(() => {
  const publicMenu = document.querySelector('.public-menu');
  const publicLinks = document.querySelector('.modern-nav .public-links');
  publicMenu?.addEventListener('click', () => {
    const open = publicLinks?.classList.toggle('open');
    publicMenu.setAttribute('aria-expanded', String(Boolean(open)));
  });

  const reveals = [...document.querySelectorAll('.reveal')];
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
    }), { threshold: .12 });
    reveals.forEach(item => observer.observe(item));
  } else reveals.forEach(item => item.classList.add('is-visible'));

  document.querySelectorAll('[data-counter]').forEach(counter => {
    const target = Number(counter.dataset.counter || 0);
    let started = false;
    const run = () => {
      if (started) return; started = true;
      const start = performance.now();
      const tick = now => {
        const p = Math.min(1, (now - start) / 1300);
        counter.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString();
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    if ('IntersectionObserver' in window) new IntersectionObserver((entries, obs) => { if (entries[0].isIntersecting) { run(); obs.disconnect(); } }).observe(counter); else run();
  });

  document.querySelectorAll('[data-slider]').forEach(slider => {
    const slides = [...slider.querySelectorAll('.experience-slide')];
    const dots = [...slider.querySelectorAll('[data-slide]')];
    let index = 0; let timer;
    const show = next => {
      index = (next + slides.length) % slides.length;
      slides.forEach((slide, i) => slide.classList.toggle('active', i === index));
      dots.forEach((dot, i) => dot.classList.toggle('active', i === index));
    };
    const auto = () => { clearInterval(timer); timer = setInterval(() => show(index + 1), 7000); };
    slider.querySelector('[data-prev]')?.addEventListener('click', () => { show(index - 1); auto(); });
    slider.querySelector('[data-next]')?.addEventListener('click', () => { show(index + 1); auto(); });
    dots.forEach(dot => dot.addEventListener('click', () => { show(Number(dot.dataset.slide)); auto(); }));
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) auto();
  });

  const backTop = document.querySelector('.back-top');
  backTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  if (!document.querySelector('.lms-footer') && document.querySelector('.main')) {
    const footer = document.createElement('footer');
    footer.className = 'global-mini-footer';
    footer.innerHTML = '<span>© 2026 Drona Learning Cloud</span><nav><a href="public-home.html">Public site</a><a href="role-login.html">Switch role</a><a href="all-pages.html">Page catalogue</a><a href="preferences.html">Help & accessibility</a></nav>';
    document.querySelector('.main')?.appendChild(footer);
  }
})();

// Interactive course overview controls.
(() => {
  document.querySelectorAll('.course-menu-button').forEach(button => {
    button.addEventListener('click', event => {
      event.stopPropagation();
      const popover = button.parentElement.querySelector('.course-menu-popover');
      document.querySelectorAll('.course-menu-popover.open').forEach(item => { if (item !== popover) item.classList.remove('open'); });
      const open = popover?.classList.toggle('open');
      button.setAttribute('aria-expanded', String(Boolean(open)));
    });
  });
  document.addEventListener('click', () => document.querySelectorAll('.course-menu-popover.open').forEach(item => item.classList.remove('open')));
  const grid = document.querySelector('[data-course-grid]');
  document.querySelectorAll('[data-course-view]').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('[data-course-view]').forEach(item => item.classList.remove('active'));
    button.classList.add('active');
    grid?.classList.remove('list','summary');
    if (button.dataset.courseView !== 'grid') grid?.classList.add(button.dataset.courseView);
  }));
})();


// Administrator course card action menus.
document.querySelectorAll('.admin-course-menu-button').forEach(button => {
  button.addEventListener('click', event => {
    event.stopPropagation();
    const menu = button.parentElement.querySelector('.admin-course-menu');
    document.querySelectorAll('.admin-course-menu.open').forEach(item => { if (item !== menu) item.classList.remove('open'); });
    menu?.classList.toggle('open');
    button.setAttribute('aria-expanded', String(menu?.classList.contains('open')));
  });
});
document.addEventListener('click', () => {
  document.querySelectorAll('.admin-course-menu.open').forEach(item => item.classList.remove('open'));
  document.querySelectorAll('.admin-course-menu-button[aria-expanded="true"]').forEach(item => item.setAttribute('aria-expanded','false'));
});


// Administrator course catalogue view switcher.
(() => {
  const grid = document.querySelector('[data-admin-course-grid]');
  document.querySelectorAll('[data-admin-course-view]').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-admin-course-view]').forEach(item => item.classList.remove('active'));
      button.classList.add('active');
      grid?.classList.toggle('list', button.dataset.adminCourseView === 'list');
      try { localStorage.setItem('drona-admin-course-view', button.dataset.adminCourseView || 'grid'); } catch (_) {}
    });
  });
  if (grid) {
    let saved = 'grid';
    try { saved = localStorage.getItem('drona-admin-course-view') || 'grid'; } catch (_) {}
    const button = document.querySelector(`[data-admin-course-view="${saved}"]`);
    button?.click();
  }
})();
