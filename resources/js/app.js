import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Sortable from 'sortablejs';

const csrf = document.querySelector('meta[name="csrf-token"]').content;

async function request(method, url, body) {
    const response = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
        body: body ? JSON.stringify(body) : undefined,
    });

    if (!response.ok) {
        throw new Error(`Request failed (${response.status})`);
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

function decrementTaskCount() {
    let total = 0;
    document.querySelectorAll('[data-task-count]').forEach((el) => (total = el.textContent = Number(el.textContent) - 1));
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
                const last = [...list.querySelectorAll('.task-card:not([data-completed])')].at(-1);
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

        const observer = new IntersectionObserver((entries) => entries[0].isIntersecting && load(), { rootMargin: '300px' });
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

            // Mirror the server closing the gap right away, so the next batch starts at the right place.
            card.dataset.completed = '';
            const removed = Number(card.dataset.priority);
            list.querySelectorAll('.task-card').forEach((other) => {
                const p = Number(other.dataset.priority);
                if (other !== card && p > removed) setPriority(other, p - 1);
            });
            return true;
        });

        if (!completed) {
            checkbox.checked = false;
            checkbox.disabled = false;
            toast('Could not complete the task.', 'error');
            return;
        }

        // Let the strike-through register before the card slides away.
        await new Promise((r) => setTimeout(r, 350));
        await animateOut(card);
        decrementTaskCount();
        toast('Task completed 🎉');
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
        this.busy = true;
        // form.submit() skips the submit event, so this doesn't re-open the dialog.
        this.form.submit();
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
