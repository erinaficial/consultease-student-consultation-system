// "Find Faculty" page: live search that groups open slots by faculty member, without reloading the page.
const API_URL = '../api/slots_search.php';
const DELAY_MS = 300;                          // wait until the user pauses typing (debounce)

export function initSlotSearch() {
  const form = document.getElementById('slot-search');
  const list = document.getElementById('faculty-list');
  const empty = document.getElementById('slots-empty');
  const panel = document.getElementById('booking-panel');
  const slotId = document.getElementById('slot-id');
  const slotSummary = document.getElementById('selected-slot-summary');
  const topic = document.getElementById('topic');
  const changeTime = document.getElementById('change-slot');
  if (!form || !list || !empty || !panel || !slotId || !slotSummary || !topic || !changeTime) return;

  let clearSelectedSlot = null;

  const resetBookingStep = () => {
    panel.hidden = true;
    form.hidden = false;
    list.hidden = false;
    empty.hidden = list.children.length > 0;
    slotId.value = '';
    slotSummary.textContent = '';
    topic.value = '';
    clearSelectedSlot?.();
    clearSelectedSlot = null;
  };

  // One card per faculty member, with a dropdown for that faculty's open slots.
  const makeCard = ({ faculty, department, slots }) => {
    const card = document.createElement('div');
    card.className = 'faculty-card';

    const info = document.createElement('div');
    const name = document.createElement('div');
    name.className = 'faculty-name';
    name.textContent = faculty;                // textContent: never interpreted as HTML
    const dept = document.createElement('div');
    dept.className = 'text-muted small';
    dept.textContent = department || 'No department';
    info.append(name, dept);

    const selection = document.createElement('div');
    selection.className = 'slot-selection d-flex flex-wrap align-items-center gap-2';

    const selectLabel = document.createElement('label');
    selectLabel.className = 'visually-hidden';
    selectLabel.htmlFor = `slot-select-${slots[0].faculty_id}`;
    selectLabel.textContent = `Available times for ${faculty}`;

    const select = document.createElement('select');
    select.id = `slot-select-${slots[0].faculty_id}`;
    select.className = 'form-select';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Choose a time...';
    select.append(placeholder);

    const slotsById = new Map();
    slots.forEach((slot) => {
      const { id, date_label, time_label, meeting_mode } = slot;
      const modeLabel = meeting_mode === 'onsite' ? 'Onsite' : 'Online';
      const option = document.createElement('option');
      option.value = String(id);
      option.textContent = `${date_label} · ${time_label} — ${modeLabel}`;
      select.append(option);
      slotsById.set(String(id), slot);
    });

    const modeBadge = document.createElement('span');
    modeBadge.className = 'badge rounded-pill fw-semibold fs-6 px-2 py-1 mode-badge';
    modeBadge.hidden = true;

    const continueButton = document.createElement('button');
    continueButton.type = 'button';
    continueButton.className = 'btn btn-outline-primary';
    continueButton.textContent = 'Continue';
    continueButton.disabled = true;

    const updateSelection = () => {
      const selectedSlot = slotsById.get(select.value);
      continueButton.disabled = !selectedSlot;
      modeBadge.hidden = !selectedSlot;
      modeBadge.textContent = selectedSlot
        ? (selectedSlot.meeting_mode === 'onsite' ? 'Onsite' : 'Online')
        : '';
      modeBadge.className = `badge rounded-pill fw-semibold fs-6 px-2 py-1 mode-badge ${
        selectedSlot?.meeting_mode === 'onsite' ? 'text-bg-success' : 'text-bg-primary'
      }`;
    };

    select.addEventListener('change', updateSelection);
    continueButton.addEventListener('click', () => {
      const selectedSlot = slotsById.get(select.value);
      if (!selectedSlot) return;

      slotId.value = String(selectedSlot.id);
      slotSummary.textContent = `${faculty} · ${selectedSlot.date_label} · ${selectedSlot.time_label}`;
      form.hidden = true;
      list.hidden = true;
      empty.hidden = true;
      panel.hidden = false;
      topic.focus();
      clearSelectedSlot = () => {
        select.value = '';
        updateSelection();
        select.focus();
      };
    });

    selection.append(selectLabel, select, modeBadge, continueButton);
    card.append(info, selection);
    return card;
  };

  // The server returns a flat list ordered by faculty; group it with a Map
  const groupByFaculty = (slots) => {
    const groups = new Map();
    slots.forEach((slot) => {
      if (!groups.has(slot.faculty_id)) {
        groups.set(slot.faculty_id, { faculty: slot.faculty, department: slot.department, slots: [] });
      }
      groups.get(slot.faculty_id).slots.push(slot);
    });
    return [...groups.values()];
  };

  const render = (slots) => {
    resetBookingStep();
    list.replaceChildren(...groupByFaculty(slots).map(makeCard));
    empty.textContent = 'No open slots match your search.';
    empty.hidden = slots.length > 0;
  };

  changeTime.addEventListener('click', resetBookingStep);

  const search = async () => {
    const params = new URLSearchParams({
      q: form.elements.q.value.trim(),
      department: form.elements.department.value,
      date: form.elements.date.value,
    });
    try {
      const res = await fetch(`${API_URL}?${params}`, { headers: { Accept: 'application/json' } });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const { slots } = await res.json();
      render(slots);
    } catch (err) {
      console.error('Search failed:', err);
      empty.textContent = 'Unable to reach the server. Please try again.';
      empty.hidden = false;
    }
  };

  let timer;
  form.elements.q.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(search, DELAY_MS);
  });
  form.elements.department.addEventListener('change', search);
  form.elements.date.addEventListener('change', search);
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    search();
  });

  search();                                    // show every open slot when the page opens
}
