// Main JavaScript entry point (ES modules): loads each feature from its own file.
import { initTheme } from './theme.js';
import { initNotifications } from './notifications.js';
import { initSlotSearch } from './slot-search.js?v=20261113-2';
import { initCharts } from './charts.js';
import { initMeetingDetailsForm } from './meeting-details-form.js';

initTheme();
initNotifications();
initSlotSearch();
initCharts();
initMeetingDetailsForm();
