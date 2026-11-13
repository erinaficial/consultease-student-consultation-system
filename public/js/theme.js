// Dark / light mode. The choice is saved in localStorage (Web Storage), so it survives page refreshes.
const STORAGE_KEY = 'theme';

const getTheme = () => {
  try {
    return localStorage.getItem(STORAGE_KEY) ||
      (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  } catch (err) {
    return 'light';
  }
};

const applyTheme = (theme, button) => {
  document.documentElement.setAttribute('data-bs-theme', theme);
  if (button) {
    button.textContent = theme === 'dark' ? '☀ Light' : '🌙 Dark';
    button.setAttribute('aria-label', `Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`);
  }
};

export function initTheme() {
  const button = document.getElementById('theme-toggle');
  applyTheme(getTheme(), button);

  button?.addEventListener('click', () => {
    const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    try {
      localStorage.setItem(STORAGE_KEY, next);
    } catch (err) {
      // Storage can be blocked by the browser; the theme still changes for this page.
    }
    applyTheme(next, button);
  });
}
