// Notification bell: loads notifications with the Fetch API and refreshes them every 30 seconds.
const API_URL = '../api/notifications.php';
const REFRESH_MS = 30000;

export function initNotifications() {
  const root = document.getElementById('notif-root');
  if (!root) return;                          // not logged in, so there is no bell on this page

  const badge = document.getElementById('notif-badge');
  const list = document.getElementById('notif-list');
  const markButton = document.getElementById('notif-mark');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  const showMessage = (text) => {
    const li = document.createElement('li');
    li.className = 'list-group-item small text-muted';
    li.textContent = text;
    list.replaceChildren(li);
  };

  const render = (unread, items) => {
    badge.textContent = unread > 99 ? '99+' : String(unread);
    badge.classList.toggle('d-none', unread === 0);

    if (items.length === 0) {
      showMessage('No notifications yet.');
      return;
    }

    const rows = items.map(({ message, is_read, created_at }) => {
      const li = document.createElement('li');
      li.className = 'list-group-item small' + (Number(is_read) === 0 ? ' fw-semibold' : '');

      const text = document.createElement('div');
      text.textContent = message;             // textContent (not innerHTML), so the text can never run as code

      const time = document.createElement('div');
      time.className = 'text-muted fw-normal';
      time.textContent = new Date(created_at.replace(' ', 'T')).toLocaleString([], {
        month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
      });

      li.append(text, time);
      return li;
    });
    list.replaceChildren(...rows);
  };

  const load = async () => {
    try {
      const res = await fetch(API_URL, { headers: { Accept: 'application/json' } });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const { unread, items } = await res.json();
      render(unread, items);
    } catch (err) {
      console.error('Could not load notifications:', err);
      showMessage('Unable to load notifications.');
    }
  };

  markButton.addEventListener('click', async () => {
    const body = new FormData();
    body.append('action', 'mark_read');
    body.append('csrf', csrf);                // the same CSRF token that protects our forms
    try {
      const res = await fetch(API_URL, { method: 'POST', body });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      await load();
    } catch (err) {
      console.error('Could not mark notifications as read:', err);
    }
  });

  load();
  setInterval(load, REFRESH_MS);              // auto-refresh
}
