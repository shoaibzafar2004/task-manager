import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Sortable from 'sortablejs';

const csrf = document.querySelector('meta[name="csrf-token"]').content;

async function request(method, url, body) {
    const response = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
            // Marks the request as AJAX so Laravel doesn't record it as the page to go "back" to.
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    if (!response.ok) {
        const error = new Error(`Request failed (${response.status})`);
        error.status = response.status;
        throw error;
    }

    return response.json();
}

function toast(message, type = 'success') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
}

function setPriority(card, priority) {
    card.dataset.priority = priority;
    const badge = card.querySelector('.prio-badge');
    badge.dataset.priority = priority;
    badge.textContent = `#${priority}`;
}

/** Collapse a card out of its list, then remove it. */
function animateOut(card) {
    return new Promise((resolve) => {
        card.style.maxHeight = `${card.offsetHeight}px`;
        card.offsetHeight; // force reflow so the transition starts from the measured height
        card.classList.add('is-leaving');
        card.addEventListener('transitionend', (e) => e.propertyName === 'max-height' && resolve());
        setTimeout(resolve, 700); // safety net if transitionend never fires
    }).then(() => card.remove());
}

/**
 * Completing a task and loading the next batch both depend on the current priorities,
 * so they run one at a time to keep the `after` cursor accurate.
 */
let pending = Promise.resolve();
function serially(job) {
    pending = pending.then(job, job);
    return pending;
}

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Mirror the server closing the priority gap a completed or deleted task leaves, so badges
 * and the next batch's `after` cursor stay right. Call it once the server has confirmed.
 */
function closePriorityGap(card) {
    card.dataset.removed = '';
    const removed = Number(card.dataset.priority);
    card.parentElement.querySelectorAll('.task-card').forEach((other) => {
        const priority = Number(other.dataset.priority);
        if (other !== card && priority > removed) setPriority(other, priority - 1);
    });
}

/** Strike through, slide out and drop a completed card from the list. */
async function removeCompletedCard(card) {
    card.querySelector('.task-check').checked = true;
    card.classList.add('is-done');
    // Let the strike-through register before the card slides away.
    await wait(350);
    await animateOut(card);
    decrementTaskCount();
}

function decrementTaskCount() {
    let total = 0;
    document
        .querySelectorAll('[data-task-count]')
        .forEach((el) => (total = el.textContent = Number(el.textContent) - 1));
    document.getElementById('empty-state')?.classList.toggle('hidden', total > 0);
}

/** "Load more" for both lists: fetches the next batch when the marker scrolls into view. */
function initLoadMore() {
    document.querySelectorAll('[data-load-more]').forEach((marker) => {
        const list = document.getElementById(marker.dataset.list);
        const button = marker.querySelector('button');
        const spinner = marker.querySelector('.load-more-spinner');
        let loading = false;

        const nextUrl = () => {
            const url = new URL(marker.dataset.nextUrl, window.location.href);
            // Active tasks are keyed on the last loaded priority, read at request time.
            if (list.id === 'task-list') {
                const last = [...list.querySelectorAll('.task-card:not([data-removed])')].at(-1);
                url.searchParams.set('after', last ? last.dataset.priority : 0);
            }
            return url;
        };

        const load = () => {
            if (loading || !marker.dataset.nextUrl) return;
            loading = button.disabled = true;
            spinner.classList.remove('hidden');

            serially(async () => {
                try {
                    const { html, next_url } = await request('GET', nextUrl());
                    list.insertAdjacentHTML('beforeend', html);
                    marker.dataset.nextUrl = next_url ?? '';
                    marker.classList.toggle('hidden', !next_url);
                } catch {
                    toast('Could not load more tasks.', 'error');
                } finally {
                    loading = button.disabled = false;
                    spinner.classList.add('hidden');
                    // Re-observe so a marker that is still on screen triggers the next batch.
                    observer.unobserve(marker);
                    observer.observe(marker);
                }
            });
        };

        const observer = new IntersectionObserver((entries) => entries[0].isIntersecting && load(), {
            rootMargin: '300px',
        });
        observer.observe(marker);
        button.addEventListener('click', load);
    });
}

function initTaskList() {
    const list = document.getElementById('task-list');
    if (!list) return;

    Sortable.create(list, {
        handle: '.drag-handle',
        animation: 180,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: async ({ oldIndex, newIndex }) => {
            if (oldIndex === newIndex) return;

            const ids = [...list.querySelectorAll('.task-card')].map((card) => Number(card.dataset.id));
            try {
                const { priorities } = await request('POST', list.dataset.reorderUrl, { ids });
                list.querySelectorAll('.task-card').forEach((card) => setPriority(card, priorities[card.dataset.id]));
            } catch {
                toast('Could not save the new order. Reloading…', 'error');
                setTimeout(() => window.location.reload(), 1200);
            }
        },
    });

    list.addEventListener('change', async (event) => {
        const checkbox = event.target.closest('.task-check');
        if (!checkbox) return;

        const card = checkbox.closest('.task-card');
        checkbox.disabled = true;
        card.classList.add('is-done');

        const completed = await serially(async () => {
            try {
                await request('PATCH', checkbox.dataset.toggleUrl);
            } catch {
                card.classList.remove('is-done');
                return false;
            }

            closePriorityGap(card);
            return true;
        });

        if (!completed) {
            checkbox.checked = false;
            checkbox.disabled = false;
            toast('Could not complete the task.', 'error');
            return;
        }

        await removeCompletedCard(card);
        toast('Task completed 🎉');
    });
}

/** Lets checklist boxes in a task's rendered details (on the page or in the popup) be ticked in place. */
function initChecklist(root = document, onProgress = () => {}) {
    const details = root.querySelector('[data-checklist-url]');
    if (!details) return;

    const bar = root.querySelector('[data-checklist-bar]');
    const count = root.querySelector('[data-checklist-count]');

    details.querySelectorAll('input[type="checkbox"]').forEach((box, index) => {
        box.disabled = false;
        box.classList.add('cursor-pointer');

        box.addEventListener('change', () => {
            const checked = box.checked;
            box.disabled = true;

            // One at a time, so each request carries the version returned by the previous one.
            serially(async () => {
                try {
                    const { version, done, total } = await request('PATCH', details.dataset.checklistUrl, {
                        index,
                        checked,
                        version: details.dataset.checklistVersion,
                    });
                    details.dataset.checklistVersion = version;
                    if (bar) bar.style.width = `${Math.round((done / total) * 100)}%`;
                    if (count) count.textContent = `${done} of ${total} done`;
                    onProgress(done, total);
                } catch (error) {
                    box.checked = !checked;
                    if (error.status === 409) {
                        toast('This task was changed elsewhere. Reloading…', 'error');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        toast('Could not update the checklist.', 'error');
                    }
                } finally {
                    box.disabled = false;
                }
            });
        });
    });
}

const BADGE_COMPLETE = ['bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-500/15', 'dark:text-emerald-300'];
const BADGE_PARTIAL = ['bg-slate-100', 'text-slate-600', 'dark:bg-slate-800', 'dark:text-slate-400'];

function updateChecklistBadge(taskId, done, total) {
    document.querySelectorAll(`[data-id="${taskId}"] [data-checklist-badge]`).forEach((badge) => {
        badge.querySelector('[data-checklist-badge-count]').textContent = `${done}/${total}`;
        badge.title = `${done} of ${total} checklist items done`;
        badge.classList.remove(...BADGE_COMPLETE, ...BADGE_PARTIAL);
        badge.classList.add(...(done === total ? BADGE_COMPLETE : BADGE_PARTIAL));
    });
}

function initHistory() {
    document.getElementById('history-list')?.addEventListener('click', async (event) => {
        const button = event.target.closest('.reopen-btn');
        if (!button) return;

        button.disabled = true;
        try {
            await request('PATCH', button.dataset.reopenUrl);
            await animateOut(button.closest('li'));
            toast('Task reopened and moved back to the list.');
        } catch {
            button.disabled = false;
            toast('Could not reopen the task.', 'error');
        }
    });
}

Alpine.data('toasts', (initial) => ({
    items: [],
    init() {
        if (initial) this.push({ message: initial });
    },
    push({ message, type = 'success' }) {
        const toast = { id: Date.now() + Math.random(), message, type, visible: true };
        this.items.push(toast);
        setTimeout(() => (this.items.find((t) => t.id === toast.id).visible = false), 3000);
        setTimeout(() => (this.items = this.items.filter((t) => t.id !== toast.id)), 3400);
    },
}));

Alpine.data('markdownEditor', (previewUrl) => ({
    tab: 'write',
    html: '',
    loading: false,
    length: 0,

    init() {
        this.length = this.$refs.input.value.length;
        this.$nextTick(() => this.resize());
    },

    resize() {
        const input = this.$refs.input;
        input.style.height = 'auto';
        input.style.height = `${input.scrollHeight + 2}px`;
    },

    write() {
        this.tab = 'write';
        this.$nextTick(() => {
            this.resize();
            this.$refs.input.focus();
        });
    },

    async preview() {
        this.tab = 'preview';
        this.loading = true;
        try {
            ({ html: this.html } = await request('POST', previewUrl, { text: this.$refs.input.value }));
        } catch {
            this.html = '<p>Could not render the preview.</p>';
        } finally {
            this.loading = false;
        }
    },

    /** Replace the range [start, end) with text, keeping the browser's undo history where possible. */
    replace(start, end, text, selectFrom, selectTo) {
        const input = this.$refs.input;
        input.focus();
        input.setSelectionRange(start, end);
        if (!document.execCommand('insertText', false, text)) {
            input.setRangeText(text, start, end, 'end');
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        input.setSelectionRange(start + selectFrom, start + selectTo);
    },

    /** Surround the selection (or a placeholder) with a marker such as ** or _. */
    wrap(marker, placeholder = 'text') {
        const { selectionStart: start, selectionEnd: end, value } = this.$refs.input;
        const selected = value.slice(start, end) || placeholder;
        this.replace(start, end, `${marker}${selected}${marker}`, marker.length, marker.length + selected.length);
    },

    /** Add a prefix to every selected line, or remove it if all of them already have it. */
    prefixLines(prefix) {
        const { selectionStart, selectionEnd, value } = this.$refs.input;
        const start = selectionStart === 0 ? 0 : value.lastIndexOf('\n', selectionStart - 1) + 1;
        const lineEnd = value.indexOf('\n', selectionEnd);
        const end = lineEnd === -1 ? value.length : lineEnd;

        const lines = value.slice(start, end).split('\n');
        const toggleOff = lines.every((line) => line.startsWith(prefix));
        const text = lines.map((line) => (toggleOff ? line.slice(prefix.length) : prefix + line)).join('\n');

        this.replace(start, end, text, text.length, text.length);
    },

    link() {
        const { selectionStart: start, selectionEnd: end, value } = this.$refs.input;
        const label = value.slice(start, end) || 'link text';
        const url = 'https://';
        // Select the URL so it can be typed over straight away.
        this.replace(start, end, `[${label}](${url})`, label.length + 3, label.length + 3 + url.length);
    },

    code() {
        const { selectionStart: start, selectionEnd: end, value } = this.$refs.input;
        const selected = value.slice(start, end);
        if (selected.includes('\n')) {
            this.replace(start, end, `\`\`\`\n${selected}\n\`\`\``, 4, 4 + selected.length);
        } else {
            this.wrap('`', 'code');
        }
    },
}));

const MODAL_ACTIONS = {
    complete: { method: 'PATCH', message: 'Task completed 🎉' },
    reopen: { method: 'PATCH', message: 'Task reopened and moved back to the list.' },
    delete: { method: 'DELETE', message: 'Task deleted.' },
    restore: { method: 'PATCH', message: 'Task restored.' },
};

/**
 * Opens tasks in a popup over the list or History. The address bar shows the task's URL,
 * so Back closes the popup and the link can be shared; loading that URL directly shows
 * the full task page instead.
 */
Alpine.data('taskModal', () => ({
    open: false,
    loading: false,
    fetching: false,
    pushed: false,
    trigger: null,
    pageTitle: document.title,

    init() {
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[data-task-link]');
            // Let modified clicks (new tab, new window) behave like normal links.
            if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            event.preventDefault();
            this.show(link.href, link);
        });

        // Lets other parts of the page (e.g. a reminder notification) open a task.
        window.addEventListener('open-task', (event) => this.show(event.detail.url, null));

        window.addEventListener('popstate', (event) => {
            if (event.state?.taskModal) {
                this.show(window.location.href, null, false);
            } else if (this.open) {
                this.hide();
            }
        });

        const content = this.$refs.content;
        content.addEventListener('click', (event) => event.target.closest('[data-modal-close]') && this.close());
        content.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-modal-action]');
            if (!form || form.dataset.confirm) return; // confirmed forms arrive via the event below
            event.preventDefault();
            this.act(form);
        });
        content.addEventListener('confirmed', (event) => this.act(event.target));
    },

    async show(url, trigger, push = true) {
        if (this.fetching || (push && this.open)) return;
        this.fetching = true;
        this.trigger = trigger ?? this.trigger;
        if (push) {
            this.pageTitle = document.title;
            history.pushState({ taskModal: true }, '', url);
        }
        // Either we just added this history entry or Forward brought us back to it; closing goes Back.
        this.pushed = true;

        // Open straight away with a spinner only if the task is slow to arrive.
        const slow = setTimeout(() => (this.loading = this.open = true), 150);
        try {
            const { html, title } = await request('GET', url);
            this.$refs.content.innerHTML = html;
            document.title = `${title} · ${this.pageTitle.split(' · ').at(-1)}`;
            const taskId = this.$refs.content.querySelector('[data-task-id]').dataset.taskId;
            initChecklist(this.$refs.content, (done, total) => updateChecklistBadge(taskId, done, total));
        } catch {
            clearTimeout(slow);
            this.fetching = false;
            toast('Could not open the task.', 'error');
            this.close();
            return;
        }

        clearTimeout(slow);
        this.fetching = false;
        this.loading = false;
        this.open = true;
        document.body.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.content.querySelector('[data-modal-close]')?.focus());
    },

    /** Close via history when we added an entry, so Back and the close button agree. */
    close() {
        if (this.pushed) {
            history.back();
        } else {
            this.hide();
        }
    },

    hide() {
        this.open = false;
        this.pushed = false;
        document.title = this.pageTitle;
        document.body.classList.remove('overflow-hidden');
        this.trigger?.isConnected && this.trigger.focus();
    },

    trapFocus(event) {
        const focusable = [
            ...this.$refs.panel.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])',
            ),
        ].filter((element) => element.offsetParent !== null);
        const first = focusable[0];
        const last = focusable.at(-1);

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },

    /** Complete, reopen, delete or restore from the popup, then update the list behind it. */
    async act(form) {
        const action = MODAL_ACTIONS[form.dataset.modalAction];
        const taskId = this.$refs.content.querySelector('[data-task-id]').dataset.taskId;
        const card = document.querySelector(`#task-list .task-card[data-id="${taskId}"]`);
        const item = document.querySelector(`#history-list [data-id="${taskId}"]`);
        const buttons = this.$refs.content.querySelectorAll('footer button');
        buttons.forEach((button) => (button.disabled = true));

        const done = await serially(async () => {
            try {
                await request(action.method, form.action);
            } catch {
                return false;
            }
            if (card) closePriorityGap(card);
            return true;
        });

        if (!done) {
            buttons.forEach((button) => (button.disabled = false));
            toast('Something went wrong. Please try again.', 'error');
            return;
        }

        this.close();
        await wait(200); // let the popup finish closing first

        if (card && action === MODAL_ACTIONS.complete) {
            await removeCompletedCard(card);
        } else if (card) {
            await animateOut(card);
            decrementTaskCount();
        } else if (item) {
            await animateOut(item);
        }
        toast(action.message);
    },
}));

Alpine.data('confirmDialog', () => ({
    open: false,
    busy: false,
    title: '',
    message: '',
    button: '',
    form: null,
    returnFocus: null,

    init() {
        // Capture phase, so the dialog intercepts every opted-in form before it submits.
        document.addEventListener(
            'submit',
            (event) => {
                const form = event.target.closest('form[data-confirm]');
                if (!form) return;

                event.preventDefault();
                this.ask(form);
            },
            true,
        );

        // Pages restored from the back/forward cache would otherwise reopen mid-delete.
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                this.open = this.busy = false;
            }
        });
    },

    ask(form) {
        this.form = form;
        this.title = form.dataset.confirmTitle || 'Are you sure?';
        this.message = form.dataset.confirm;
        this.button = form.dataset.confirmButton || 'Confirm';
        this.busy = false;
        this.returnFocus = document.activeElement;
        this.open = true;
        // Default to the safe choice, like the native dialog.
        this.$nextTick(() => this.$refs.cancel.focus());
    },

    cancel() {
        if (this.busy) return;
        this.open = false;
        this.returnFocus?.focus();
    },

    accept() {
        // Forms in the task popup run their action in place, so hand control back to it.
        if (this.form.dataset.modalAction) {
            this.open = false;
            this.form.dispatchEvent(new CustomEvent('confirmed', { bubbles: true }));
            return;
        }

        this.busy = true;
        // form.submit() skips the submit event, so this doesn't re-open the dialog.
        this.form.submit();
    },
}));

/** How often an open tab checks for tasks that have become due. */
const REMINDER_INTERVAL = 15 * 60 * 1000;
const REMINDER_KEY = 'reminders';

function readReminderState() {
    try {
        return JSON.parse(localStorage.getItem(REMINDER_KEY)) ?? {};
    } catch {
        return {};
    }
}

function saveReminderState(state) {
    try {
        localStorage.setItem(REMINDER_KEY, JSON.stringify(state));
    } catch {
        // Storage can be unavailable (private mode); reminders then just aren't remembered.
    }
}

/**
 * The bell in the header. When switched on, the open tab checks every 15 minutes for tasks
 * due today or overdue and shows a browser notification for each one, once per day.
 */
Alpine.data('reminders', (url) => ({
    enabled: false,
    permission: 'Notification' in window ? Notification.permission : 'unsupported',
    timer: null,

    get state() {
        if (this.permission === 'unsupported' || this.permission === 'denied') return 'blocked';
        return this.enabled && this.permission === 'granted' ? 'on' : 'off';
    },
    get on() {
        return this.state === 'on';
    },
    get label() {
        return {
            on: 'Reminders on: you’ll be notified about tasks due today',
            off: 'Turn on reminders for tasks due today',
            blocked: 'Reminders are blocked in this browser’s notification settings',
        }[this.state];
    },

    init() {
        this.enabled = readReminderState().enabled === true;
        this.schedule();
    },

    async toggle() {
        if (this.state === 'blocked') {
            toast('Notifications are blocked. Allow them for this site in your browser settings.', 'error');
            return;
        }
        if (this.on) {
            this.enabled = false;
            saveReminderState({ ...readReminderState(), enabled: false });
            this.schedule();
            toast('Reminders turned off.');
            return;
        }

        if (this.permission !== 'granted') {
            this.permission = await Notification.requestPermission();
        }
        if (this.permission !== 'granted') {
            toast('Reminders need permission to show notifications.', 'error');
            return;
        }

        this.enabled = true;
        saveReminderState({ ...readReminderState(), enabled: true });
        toast('Reminders on. You’ll be notified about tasks due today while this tab is open.');
        this.schedule();
    },

    schedule() {
        clearInterval(this.timer);
        if (!this.on) return;
        this.check();
        this.timer = setInterval(() => this.check(), REMINDER_INTERVAL);
    },

    async check() {
        let tasks;
        try {
            ({ tasks } = await request('GET', url));
        } catch {
            return; // try again at the next interval
        }

        // Remember which tasks were already announced today, across tabs and reloads.
        const today = new Date().toDateString();
        const state = readReminderState();
        const shown = state.date === today ? (state.shown ?? []) : [];
        const fresh = tasks.filter((task) => !shown.includes(task.id));
        if (fresh.length === 0) return;

        if (fresh.length > 3) {
            new Notification(`${fresh.length} tasks need attention`, {
                body: fresh
                    .slice(0, 3)
                    .map((task) => `• ${task.title}`)
                    .concat('…')
                    .join('\n'),
                tag: 'task-reminders',
            }).onclick = () => window.focus();
        } else {
            fresh.forEach((task) => {
                new Notification(task.title, { body: task.due, tag: `task-${task.id}` }).onclick = () => {
                    window.focus();
                    window.dispatchEvent(new CustomEvent('open-task', { detail: { url: task.url } }));
                };
            });
        }

        saveReminderState({ ...state, date: today, shown: [...shown, ...fresh.map((task) => task.id)] });
    },
}));

Alpine.data('themeToggle', () => ({
    dark: document.documentElement.classList.contains('dark'),
    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        try {
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        } catch {
            // Storage can be unavailable (private mode); the toggle still works for this page.
        }
    },
}));

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

initTaskList();
initHistory();
initLoadMore();
initChecklist();
