export function initMeetingDetailsForm() {
  document.querySelectorAll('form[data-meeting-details-form]').forEach((form) => {
    const mode = form.querySelector('[name="meeting_mode"]');
    const details = form.querySelector('[name="meeting_details"]');
    const label = form.querySelector('[data-meeting-details-label]');
    if (!mode || !details || !label) return;

    const updateLabel = () => {
      if (mode.value === 'onsite') {
        label.textContent = 'Room or location';
        details.placeholder = 'Enter a room or location';
      } else if (mode.value === 'online') {
        label.textContent = 'HTTPS meeting link';
        details.placeholder = 'https://...';
      } else {
        label.textContent = 'Room or meeting link';
        details.placeholder = 'Choose a mode first';
      }
    };

    mode.addEventListener('change', updateLabel);
    updateLabel();
  });
}
