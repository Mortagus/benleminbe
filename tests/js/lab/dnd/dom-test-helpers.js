export class TestElement {
    constructor(tagName = 'div', classNames = []) {
        this.tagName = tagName.toLowerCase();
        this.children = [];
        this.parentElement = null;
        this.textContent = '';
        this.hidden = false;
        this.dataset = {};
        this.attributes = new Map();
        this.eventListeners = new Map();
        this.classList = createClassList(classNames);
        this.tabIndex = undefined;
        this.title = '';
        this.draggable = false;
        this.value = '';
        this.max = '';
        this.disabled = false;
        this.validity = { badInput: false };
        this.wasFocused = false;
    }

    appendChild(child) {
        if (child.tagName === 'fragment') {
            child.children.forEach(fragmentChild => this.appendChild(fragmentChild));
            child.children = [];

            return child;
        }

        child.parentElement = this;
        this.children.push(child);

        return child;
    }

    append(...children) {
        children.forEach(child => this.appendChild(child));
    }

    replaceChildren(...children) {
        this.children.forEach(child => {
            child.parentElement = null;
        });
        this.children = [];
        this.append(...children);
    }

    querySelector(selector) {
        return this.querySelectorAll(selector)[0] ?? null;
    }

    querySelectorAll(selector) {
        return findMatchingDescendants(this, selector);
    }

    setAttribute(name, value) {
        this.attributes.set(name, String(value));
    }

    getAttribute(name) {
        return this.attributes.get(name) ?? null;
    }

    removeAttribute(name) {
        this.attributes.delete(name);
    }

    addEventListener(type, listener) {
        const listeners = this.eventListeners.get(type) ?? [];
        listeners.push(listener);
        this.eventListeners.set(type, listeners);
    }

    dispatchEvent(event) {
        const normalizedEvent = event;

        if (!normalizedEvent.target) {
            normalizedEvent.target = this;
        }

        if (typeof normalizedEvent.preventDefault !== 'function') {
            normalizedEvent.preventDefault = () => {};
        }

        if (typeof normalizedEvent.stopPropagation !== 'function') {
            normalizedEvent.stopPropagation = () => {
                normalizedEvent.__propagationStopped = true;
            };
        }

        normalizedEvent.__propagationStopped ??= false;

        (this.eventListeners.get(normalizedEvent.type) ?? [])
            .forEach(listener => listener(normalizedEvent));

        if (!normalizedEvent.__propagationStopped && this.parentElement) {
            this.parentElement.dispatchEvent(normalizedEvent);
        }
    }

    focus() {
        this.wasFocused = true;
    }

    click() {
        this.wasClicked = true;
        this.dispatchEvent({ type: 'click' });
    }

    cloneNode(deep = false) {
        const clone = new TestElement(this.tagName, [...this.classList.values()]);
        clone.textContent = this.textContent;
        clone.hidden = this.hidden;
        clone.dataset = { ...this.dataset };
        clone.attributes = new Map(this.attributes);
        clone.tabIndex = this.tabIndex;
        clone.title = this.title;
        clone.draggable = this.draggable;
        clone.value = this.value;
        clone.max = this.max;
        clone.disabled = this.disabled;
        clone.validity = { ...this.validity };

        if (deep) {
            this.children.forEach(child => clone.appendChild(child.cloneNode(true)));
        }

        return clone;
    }

    get firstElementChild() {
        return this.children[0] ?? null;
    }
}

export function createInput(value = '', options = {}) {
    const input = new TestElement('input');
    input.value = value;
    input.max = options.max ?? '';
    input.disabled = options.disabled ?? false;
    input.validity = { badInput: options.badInput ?? false };

    return input;
}

export function createDocumentDouble(elementsById = {}) {
    return {
        createElement: tagName => new TestElement(tagName),
        createDocumentFragment: () => new TestElement('fragment'),
        getElementById: id => elementsById[id] ?? null,
        querySelector: () => null,
        querySelectorAll: () => [],
    };
}

export function createTurnOrderTemplate() {
    const item = new TestElement('li', ['turn-order-item']);

    const controls = new TestElement('div', ['turn-order-item__controls']);
    controls.appendChild(createMoveButton('previous'));
    controls.appendChild(createMoveButton('next'));

    item.appendChild(controls);
    const badges = new TestElement('div', ['turn-order-item__badges']);
    const legendaryBadge = new TestElement('span', ['turn-order-item__legendary-badge']);
    legendaryBadge.hidden = true;
    badges.appendChild(legendaryBadge);
    item.appendChild(badges);
    item.appendChild(new TestElement('div', ['turn-order-item__image-placeholder']));
    item.appendChild(new TestElement('div', ['turn-order-item__name']));

    const stats = new TestElement('div', ['turn-order-item__stats']);
    stats.appendChild(new TestElement('span', ['turn-order-item__initiative']));
    stats.appendChild(new TestElement('span', ['turn-order-item__armor-class']));
    stats.appendChild(new TestElement('span', ['turn-order-item__hit-points']));
    item.appendChild(stats);

    const hitPointsEditor = new TestElement('div', ['turn-order-item__hit-points-editor']);
    const hitPointsInput = createInput();
    hitPointsInput.dataset.turnHitPointsInput = '';
    hitPointsEditor.appendChild(hitPointsInput);
    hitPointsEditor.appendChild(createApplyButton());
    item.appendChild(hitPointsEditor);

    const hitPointsFeedback = new TestElement('p', ['turn-order-item__hit-points-feedback']);
    hitPointsFeedback.dataset.turnHitPointsFeedback = '';
    hitPointsFeedback.hidden = true;
    item.appendChild(hitPointsFeedback);

    const badge = new TestElement('div', ['turn-order-item__badge']);
    badge.hidden = true;
    item.appendChild(badge);

    return {
        content: {
            firstElementChild: item,
        },
    };
}

export function createMonsterItemTemplate() {
    const item = new TestElement('li', ['monster-item']);

    const main = new TestElement('div', ['monster-main']);
    main.appendChild(new TestElement('select', ['monster-select']));

    const meta = new TestElement('div', ['monster-meta']);
    meta.appendChild(new TestElement('span', ['monster-type']));
    meta.appendChild(new TestElement('span', ['monster-size']));
    meta.appendChild(new TestElement('span', ['monster-cr']));
    meta.appendChild(new TestElement('span', ['monster-alignment']));
    meta.appendChild(new TestElement('span', ['monster-legendary']));
    main.appendChild(meta);

    item.appendChild(main);

    const stats = new TestElement('div', ['monster-stats']);
    stats.appendChild(new TestElement('span', ['monster-stat', 'monster-armor-class']));

    const hitPoints = new TestElement('label', ['monster-hp']);
    hitPoints.appendChild(createInput());
    hitPoints.appendChild(new TestElement('span', ['monster-hit-points-max']));
    stats.appendChild(hitPoints);

    stats.appendChild(new TestElement('span', ['monster-stat', 'monster-initiative-modifier']));
    stats.appendChild(new TestElement('span', ['monster-stat', 'monster-initiative']));
    item.appendChild(stats);

    const content = new TestElement('fragment');
    content.appendChild(item);

    return {
        content,
    };
}

export function createMonsterOptionTemplate() {
    const content = new TestElement('fragment');
    content.appendChild(new TestElement('option'));

    return {
        content,
    };
}

function createApplyButton() {
    const button = new TestElement('button');
    button.dataset.turnHitPointsApply = '';

    return button;
}

export function createPlayerItemTemplate() {
    const content = new TestElement('fragment');
    const item = createPlayerItem({
        name: createInput(),
        side: createSelect('party'),
        'armor-class': createInput(),
        'current-hit-points': createInput(),
        'base-hit-points': createInput(),
        initiative: createInput(),
    });
    const actions = new TestElement('div', ['player-actions']);
    const detailsButton = new TestElement('button', ['player-details-button']);
    detailsButton.dataset.playerDetailsOpen = '';
    detailsButton.disabled = true;
    actions.appendChild(detailsButton);
    actions.appendChild(new TestElement('button', ['player-remove-button']));
    item.appendChild(actions);
    content.appendChild(item);

    return {
        content,
    };
}

export function createPlayerItem(fields) {
    const item = new TestElement('li', ['player-item']);
    const inputs = Object.entries(fields).map(([fieldName, input]) => {
        input.dataset.playerField = fieldName;
        item.appendChild(input);

        return input;
    });

    item.querySelectorAll = selector => {
        if (selector === 'input') {
            return inputs.filter(input => input.tagName === 'input');
        }

        if (selector === 'select') {
            return inputs.filter(input => input.tagName === 'select');
        }

        return findMatchingDescendants(item, selector);
    };

    return item;
}

function createSelect(value = '') {
    const select = new TestElement('select');
    select.value = value;

    return select;
}

function createMoveButton(direction) {
    const button = new TestElement('button', ['turn-order-item__move-button']);
    button.dataset.turnMove = direction;

    return button;
}

function createClassList(classNames) {
    const classes = new Set(classNames);

    return {
        add: (...names) => names.forEach(name => classes.add(name)),
        remove: (...names) => names.forEach(name => classes.delete(name)),
        contains: name => classes.has(name),
        values: () => classes.values(),
        [Symbol.iterator]: () => classes.values(),
    };
}

function findMatchingDescendants(root, selector) {
    const selectors = selector.trim().split(/\s+/);
    let candidates = getDescendants(root);

    selectors.forEach(currentSelector => {
        candidates = candidates.flatMap(candidate => {
            if (matchesSelector(candidate, currentSelector)) {
                return [candidate];
            }

            return getDescendants(candidate)
                .filter(descendant => matchesSelector(descendant, currentSelector));
        });
    });

    return candidates;
}

function getDescendants(element) {
    return element.children.flatMap(child => [
        child,
        ...getDescendants(child),
    ]);
}

function matchesSelector(element, selector) {
    if (selector.startsWith('.')) {
        return element.classList.contains(selector.slice(1));
    }

    const dataAttributeMatch = selector.match(/^\[data-([a-z-]+)(?:="([^"]+)")?\]$/);

    if (dataAttributeMatch) {
        const [, attributeName, expectedValue] = dataAttributeMatch;
        const datasetKey = attributeName.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
        const actualValue = element.dataset[datasetKey];

        return expectedValue === undefined
            ? actualValue !== undefined
            : actualValue === expectedValue;
    }

    return element.tagName === selector.toLowerCase();
}
