import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

class Element {
  constructor(tagName) {
    this.tagName = tagName;
    this.children = [];
    this.listeners = {};
    this.hidden = false;
    this.value = '';
    this.className = '';
    this.type = '';
    this._textContent = '';
  }

  append(...children) {
    this.children.push(...children);
  }

  replaceChildren(...children) {
    this.children = [...children];
  }

  addEventListener(type, listener) {
    this.listeners[type] = listener;
  }

  dispatch(type, event = {}) {
    this.listeners[type]?.({ preventDefault() {}, ...event });
  }

  focus() {}

  set textContent(value) {
    this._textContent = String(value);
    this.children = [];
  }

  get textContent() {
    return this._textContent + this.children.map((child) => child.textContent).join('');
  }
}

const find = (element, predicate) => {
  if (predicate(element)) return element;
  for (const child of element.children) {
    const match = find(child, predicate);
    if (match) return match;
  }
  return null;
};

const findAll = (element, predicate) => {
  const matches = predicate(element) ? [element] : [];
  for (const child of element.children) matches.push(...findAll(child, predicate));
  return matches;
};

test('slot dropdown reveals topic only after Continue and allows changing the slot', async () => {
  const source = await readFile(new URL('../public/js/slot-search.js', import.meta.url), 'utf8');
  const moduleUrl = `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`;

  const searchForm = new Element('form');
  searchForm.elements = {
    q: new Element('input'),
    department: new Element('select'),
    date: new Element('input'),
  };
  const list = new Element('div');
  const empty = new Element('p');
  const panel = new Element('div');
  panel.hidden = true;
  const slotId = new Element('input');
  const summary = new Element('p');
  const topic = new Element('input');
  const changeTime = new Element('button');
  const elements = new Map([
    ['slot-search', searchForm],
    ['faculty-list', list],
    ['slots-empty', empty],
    ['booking-panel', panel],
    ['slot-id', slotId],
    ['selected-slot-summary', summary],
    ['topic', topic],
    ['change-slot', changeTime],
  ]);

  globalThis.document = {
    getElementById: (id) => elements.get(id) ?? null,
    createElement: (tagName) => new Element(tagName),
    createTextNode: (text) => {
      const node = new Element('#text');
      node.textContent = text;
      return node;
    },
  };
  globalThis.fetch = async () => ({
    ok: true,
    json: async () => ({
      slots: [
        {
          id: 11,
          faculty_id: 2,
          faculty: 'Prof. Reyes',
          department: 'Engineering',
          date_label: 'Sat, Nov 14',
          time_label: '4:12 PM - 5:00 PM',
          meeting_mode: 'onsite',
        },
        {
          id: 12,
          faculty_id: 2,
          faculty: 'Prof. Reyes',
          department: 'Engineering',
          date_label: 'Mon, Nov 16',
          time_label: '10:00 AM - 10:30 AM',
          meeting_mode: 'online',
        },
      ],
    }),
  });

  const { initSlotSearch } = await import(moduleUrl);
  initSlotSearch();
  await new Promise((resolve) => setImmediate(resolve));

  const select = find(list, (element) => element.tagName === 'select');
  assert.ok(select, 'results should use a dropdown instead of clickable time chips');
  assert.equal(panel.hidden, true, 'topic step should start hidden');

  const options = findAll(select, (element) => element.tagName === 'option');
  assert.match(options[1].textContent, /Onsite/);
  assert.match(options[2].textContent, /Online/);

  select.value = '11';
  select.dispatch('change');
  const modeBadge = find(list, (element) => element.className.includes('mode-badge'));
  assert.equal(modeBadge.textContent, 'Onsite');
  assert.match(modeBadge.className, /text-bg-success/);
  assert.equal(panel.hidden, true, 'choosing a time alone should not reveal the topic');

  select.value = '12';
  select.dispatch('change');
  assert.equal(modeBadge.textContent, 'Online');
  assert.match(modeBadge.className, /text-bg-primary/);

  select.value = '11';
  select.dispatch('change');
  const continueButton = find(list, (element) => element.textContent === 'Continue');
  assert.ok(continueButton);
  assert.equal(continueButton.type, 'button', 'Continue must not submit the form');
  continueButton.dispatch('click');
  assert.equal(panel.hidden, false);
  assert.equal(slotId.value, '11');
  assert.match(summary.textContent, /Sat, Nov 14/);

  changeTime.dispatch('click');
  assert.equal(panel.hidden, true);
  assert.equal(slotId.value, '');
  assert.equal(searchForm.hidden, false);
  assert.equal(select.value, '');
});

test('updated search scripts use versioned URLs to avoid stale browser caches', async () => {
  const footer = await readFile(new URL('../includes/footer.php', import.meta.url), 'utf8');
  const app = await readFile(new URL('../public/js/app.js', import.meta.url), 'utf8');

  assert.match(footer, /<script type="module" src="js\/app\.js\?v=[^"]+">/);
  assert.match(app, /from '\.\/slot-search\.js\?v=[^']+'/);
});
