// FinPulse - shared vanilla JS

// Tailwind config (must run before components render)
if (window.tailwind) {
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] },
        colors: {
          brand: {
            DEFAULT: '#a78bfa',
            from: '#a78bfa',
            to: '#22d3ee',
          },
        },
        boxShadow: {
          glow: '0 0 0 1px rgba(167,139,250,.35), 0 0 24px -4px rgba(167,139,250,.45)',
        },
        keyframes: {
          fadeUp: { '0%': { opacity: 0, transform: 'translateY(8px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
          progress: { '0%': { width: '0%' }, '100%': { width: 'var(--target,0%)' } },
        },
        animation: {
          fadeUp: 'fadeUp .5s ease-out both',
          progress: 'progress 1.2s cubic-bezier(.2,.8,.2,1) forwards',
        },
      },
    },
  };
}

document.documentElement.classList.add('dark');

// ===== Toast =====
window.toast = function (message, type = 'success') {
  let host = document.getElementById('toast-host');
  if (!host) {
    host = document.createElement('div');
    host.id = 'toast-host';
    host.className = 'fixed bottom-6 right-6 z-[100] flex flex-col gap-2';
    document.body.appendChild(host);
  }
  const colors = {
    success: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200',
    error: 'border-red-500/30 bg-red-500/10 text-red-200',
    info: 'border-zinc-700 bg-zinc-900 text-zinc-100',
  };
  const t = document.createElement('div');
  t.className = `pointer-events-auto min-w-[260px] max-w-sm rounded-lg border ${colors[type] || colors.info} px-4 py-3 text-sm shadow-xl backdrop-blur animate-fadeUp`;
  t.textContent = message;
  host.appendChild(t);
  setTimeout(() => {
    t.style.transition = 'opacity .3s, transform .3s';
    t.style.opacity = '0';
    t.style.transform = 'translateY(8px)';
    setTimeout(() => t.remove(), 320);
  }, 2600);
};

// ===== Sidebar toggle =====
document.addEventListener('click', (e) => {
  const openBtn = e.target.closest('[data-sidebar-open]');
  const closeBtn = e.target.closest('[data-sidebar-close]');
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  if (!sidebar) return;
  if (openBtn) {
    sidebar.classList.remove('-translate-x-full');
    backdrop?.classList.remove('hidden');
  }
  if (closeBtn || e.target === backdrop) {
    sidebar.classList.add('-translate-x-full');
    backdrop?.classList.add('hidden');
  }
});

// ===== Animate progress bars on load =====
window.addEventListener('load', () => {
  document.querySelectorAll('[data-progress]').forEach((el) => {
    const pct = Math.max(0, Math.min(100, parseFloat(el.dataset.progress)));
    el.style.setProperty('--target', pct + '%');
    el.classList.add('animate-progress');
  });
});
