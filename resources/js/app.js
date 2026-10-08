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

function refreshCount(list) {
    const count = list.querySelectorAll('.task-card').length;
    document.querySelectorAll('[data-task-count]').forEach((el) => (el.textContent = count));
    document.getElementById('empty-state')?.classList.toggle('hidden', count > 0);
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
        const removed = Number(card.dataset.priority);
        checkbox.disabled = true;
        card.classList.add('is-done');

        try {
            await request('PATCH', checkbox.dataset.toggleUrl);
        } catch {
            checkbox.checked = false;
            checkbox.disabled = false;
            card.classList.remove('is-done');
            toast('Could not complete the task.', 'error');
            return;
        }

        // Let the strike-through register before the card slides away.
        await new Promise((r) => setTimeout(r, 350));
        await animateOut(card);

        list.querySelectorAll('.task-card').forEach((other) => {
            const p = Number(other.dataset.priority);
            if (p > removed) setPriority(other, p - 1);
        });
        refreshCount(list);
        toast('Task completed 🎉');
    });
}

function initHistory() {
    document.querySelectorAll('.reopen-btn').forEach((button) => {
        button.addEventListener('click', async () => {
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

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

initTaskList();
initHistory();
