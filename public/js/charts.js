// Admin overview: a stacked bar chart of consultations per month and status (Chart.js + Fetch API).
const API_URL = '../api/admin_stats.php';

// Read a palette color (CSS variable) so the chart follows the active theme
const cssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

const statusColors = () => ({
  pending: '#e0a800',
  approved: cssVar('--brand'),
  completed: cssVar('--brand-deep'),
  declined: '#dc3545',
  cancelled: '#6c757d',
});

export async function initCharts() {
  const canvas = document.getElementById('status-chart');
  const message = document.getElementById('chart-message');
  if (!canvas || typeof Chart === 'undefined') return;     // not on the overview page

  try {
    const res = await fetch(API_URL, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const { labels, series } = await res.json();

    Chart.defaults.color = getComputedStyle(document.body).color;   // readable text in light and dark mode
    const colors = statusColors();

    const datasets = Object.entries(series).map(([status, data]) => ({
      label: status.charAt(0).toUpperCase() + status.slice(1),
      data,
      backgroundColor: colors[status] ?? '#999999',
    }));

    new Chart(canvas, {
      type: 'bar',
      data: { labels, datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { stacked: true },
          y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
        },
        plugins: { legend: { position: 'bottom' } },
      },
    });
  } catch (err) {
    console.error('Could not load chart data:', err);
    if (message) message.textContent = 'Unable to load the chart. Please refresh the page.';
  }
}
