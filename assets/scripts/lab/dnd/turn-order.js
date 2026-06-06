// DOM controller for the turn order panel.
// It renders the already-built turn order and handles played state, quick hit
// point edits, focus, keyboard movement, button movement, and drag and drop.
import {
    clearValidationState,
    showValidationErrors,
} from './validation.js';
import { formatInitiative } from './initiative.js';
import {
    formatCombatStatusLabel,
    formatConditionLabel,
    getCombatStatusCatalog,
    getConditionCatalog,
    normalizeConditionPayload,
} from './conditions.js';

let draggedTurnId = null;
const turnOrderItemTemplate = document.getElementById('turnOrderItemTemplate');

export class TurnOrderPanel {
    constructor(encounter, callbacks = {}) {
        this.encounter = encounter;
        this.callbacks = callbacks;
        this.generateTurnOrderButton = document.getElementById('generateTurnOrder');
        this.turnOrderPanel = document.querySelector('.dnd-panel--turn-order');
        this.turnOrderValidationSummary = document.getElementById('turnOrderValidationSummary');
        this.turnOrderKeyboardHelpButton = document.getElementById('toggleTurnOrderKeyboardHelp');
        this.turnOrderKeyboardHelp = document.getElementById('turnOrderKeyboardHelp');
        this.combatRoundStatus = document.getElementById('combatRoundStatus');
        this.combatTurnStatus = document.getElementById('combatTurnStatus');
        this.combatNextTurnButton = document.getElementById('combatNextTurn');
        this.combatStartNewRoundButton = document.getElementById('combatStartNewRound');
        this.combatResetTurnProgressButton = document.getElementById('combatResetTurnProgress');
        this.combatResetEncounterButton = document.getElementById('resetEncounter');
        this.turnOrderPlaceholder = document.getElementById('turnOrderPlaceholder');
        this.turnOrderList = document.getElementById('turnOrderList');
        this.turnOrderLiveRegion = document.getElementById('turnOrderLiveRegion');
        this.expandedCombatStateTurnId = null;
        this.pendingFocusTarget = null;
    }

    start() {
        this.bindGenerateTurnOrderButton();
        this.bindCombatControlButtons();
        bindKeyboardHelp(this.turnOrderKeyboardHelpButton, this.turnOrderKeyboardHelp);
        this.renderCombatControls();
    }

    bindGenerateTurnOrderButton() {
        this.generateTurnOrderButton?.addEventListener('click', () => {
            this.callbacks.onGenerateTurnOrder?.();
        });
    }

    bindCombatControlButtons() {
        this.combatNextTurnButton?.addEventListener('click', () => {
            this.callbacks.onAdvanceTurn?.();
        });

        this.combatStartNewRoundButton?.addEventListener('click', () => {
            this.callbacks.onStartNewRound?.();
        });

        this.combatResetTurnProgressButton?.addEventListener('click', () => {
            this.callbacks.onResetTurnProgress?.();
        });

        this.combatResetEncounterButton?.addEventListener('click', () => {
            this.callbacks.onResetEncounter?.();
        });
    }

    clearValidation() {
        clearValidationState(this.turnOrderPanel);
    }

    refresh(options = {}) {
        this.rememberFocusTarget(options);
        this.renderCombatControls();
        this.renderTurnOrder();
        this.restorePendingFocus();
    }

    rememberFocusTarget(options) {
        if (options.focusTurnId) {
            this.pendingFocusTarget = {
                turnId: options.focusTurnId,
                selector: options.focusSelector
                    ?? (options.focusHitPointsInput
                        ? '[data-turn-hit-points-input]'
                        : null),
            };
            return;
        }

        if (options.focusFirst) {
            this.pendingFocusTarget = {
                turnId: this.encounter.turnOrder[0]?.id ?? null,
                selector: null,
            };
        }
    }

    renderCombatControls() {
        const hasTurnOrder = this.encounter.hasTurnOrder?.() ?? this.encounter.turnOrder.length > 0;
        const isRoundComplete = this.encounter.isRoundComplete?.() ?? (
            hasTurnOrder && this.encounter.turnOrder.every(actor => actor.done)
        );
        const activeTurn = this.encounter.getActiveTurn?.() ?? this.encounter.turnOrder.find(actor => !actor.done) ?? null;
        const hasEncounterContent = hasTurnOrder || this.encounter.monsters.length > 0 || this.encounter.players.length > 0;

        if (this.combatRoundStatus) {
            this.combatRoundStatus.textContent = `Round ${this.encounter.currentRound}`;
        }

        if (this.combatTurnStatus) {
            this.combatTurnStatus.classList.remove(
                'turn-order-combat-status__turn--ended',
                'turn-order-combat-status__turn--empty',
            );

            if (!hasTurnOrder) {
                this.combatTurnStatus.textContent = 'Aucun ordre du tour généré.';
                this.combatTurnStatus.classList.add('turn-order-combat-status__turn--empty');
            } else if (isRoundComplete) {
                this.combatTurnStatus.textContent = 'Round terminé. Cliquer sur Nouveau round pour repartir.';
                this.combatTurnStatus.classList.add('turn-order-combat-status__turn--ended');
            } else {
                this.combatTurnStatus.textContent = `Acteur actif : ${activeTurn?.name ?? 'Inconnu'}`;
            }
        }

        if (this.combatNextTurnButton) {
            this.combatNextTurnButton.disabled = !hasTurnOrder || isRoundComplete;
        }

        if (this.combatStartNewRoundButton) {
            this.combatStartNewRoundButton.disabled = !hasTurnOrder;
        }

        if (this.combatResetTurnProgressButton) {
            this.combatResetTurnProgressButton.disabled = !hasTurnOrder;
        }

        if (this.combatResetEncounterButton) {
            this.combatResetEncounterButton.disabled = !hasEncounterContent;
        }
    }

    renderTurnOrder() {
        renderRoundOrder(
            this.turnOrderList,
            this.turnOrderPlaceholder,
            this.encounter.turnOrder,
            {
                onToggleTurnDone: (turnId) => {
                    this.encounter.toggleTurnDone(turnId);
                    this.pendingFocusTarget = {
                        turnId,
                        selector: null,
                    };
                    this.refresh();
                },
                onMoveTurn: (draggedTurnId, targetTurnId, placement) => {
                    this.encounter.moveTurn(draggedTurnId, targetTurnId, placement);
                    this.pendingFocusTarget = {
                        turnId: draggedTurnId,
                        selector: null,
                    };
                    this.refresh();
                },
                onApplyHitPointsChange: (turnId, rawValue) => this.callbacks.onApplyHitPointsChange?.(turnId, rawValue),
                onAddCondition: (turnId, payload) => this.callbacks.onAddCondition?.(turnId, payload),
                onRemoveCondition: (turnId, conditionId) => this.callbacks.onRemoveCondition?.(turnId, conditionId),
                onSetCombatStatus: (turnId, status) => this.callbacks.onSetCombatStatus?.(turnId, status),
                onToggleCombatStateEditor: (turnId) => {
                    this.toggleCombatStateEditor(turnId);
                },
                onAnnounce: (message) => {
                    announceTurnOrderChange(this.turnOrderLiveRegion, message);
                },
            },
            {
                expandedCombatStateTurnId: this.expandedCombatStateTurnId,
            },
        );
    }

    restorePendingFocus() {
        if (!this.pendingFocusTarget?.turnId) {
            this.pendingFocusTarget = null;
            return;
        }

        const { turnId, selector } = this.pendingFocusTarget;

        if (selector) {
            focusTurnItemElement(this.turnOrderList, turnId, selector);
        } else {
            focusTurnItem(this.turnOrderList, turnId);
        }

        this.pendingFocusTarget = null;
    }

    showEncounterValidationErrors(validationResult) {
        showValidationErrors(
            validationResult,
            this.turnOrderValidationSummary,
            'Impossible de générer l’ordre du tour.',
        );
    }

    toggleCombatStateEditor(turnId) {
        const isOpeningCurrentEditor = this.expandedCombatStateTurnId !== turnId;

        this.expandedCombatStateTurnId = isOpeningCurrentEditor ? turnId : null;
        this.pendingFocusTarget = {
            turnId,
            selector: isOpeningCurrentEditor
                ? '[data-turn-condition-select]'
                : '[data-turn-conditions-toggle]',
        };
        this.refresh();
    }

    closeCombatStateEditor() {
        this.expandedCombatStateTurnId = null;
    }

    announce(message) {
        announceTurnOrderChange(this.turnOrderLiveRegion, message);
    }
}

export function renderRoundOrder(turnOrderList, turnOrderPlaceholder, roundOrder, callbacks, options = {}) {
    turnOrderList.replaceChildren();

    if (roundOrder.length === 0) {
        turnOrderPlaceholder.hidden = false;
        turnOrderList.hidden = true;

        return;
    }

    turnOrderPlaceholder.hidden = true;
    turnOrderList.hidden = false;

    const firstActiveIndex = roundOrder.findIndex(actor => !actor.done);

    roundOrder.forEach((actor, index) => {
        turnOrderList.appendChild(renderTurnOrderItem(
            roundOrder,
            actor,
            index,
            firstActiveIndex,
            callbacks,
            options,
        ));
    });
}

function renderTurnOrderItem(roundOrder, actor, index, firstActiveIndex, callbacks, options) {
    const li = turnOrderItemTemplate.content
        .firstElementChild
        .cloneNode(true);
    const actorPosition = index + 1;

    populateTurnOrderItem(li, actor, {
        isActive: index === firstActiveIndex,
        position: actorPosition,
        total: roundOrder.length,
    });
    bindTurnOrderItemControls(li, roundOrder, actor, index, callbacks, options);

    return li;
}

function populateTurnOrderItem(li, actor, options) {
    const actorSide = getActorSide(actor);
    const actorDescription = getActorDescription(actor, options.position, options.total);

    li.dataset.actorType = actor.type ?? '';
    li.dataset.actorSide = actorSide;

    if (options.isActive) {
        li.classList.add('turn-order-item--active');
    }

    li.classList.add(`turn-order-item--side-${actorSide}`);

    if (actor.done) {
        li.classList.add('turn-order-item--done');
    }

    if (actor.isLegendary) {
        li.classList.add('turn-order-item--legendary');
    }

    if (actor.currentHitPoints === 0) {
        li.classList.add('turn-order-item--zero-hit-points');
    }

    if ((actor.combatStatus ?? 'normal') !== 'normal') {
        li.classList.add(`turn-order-item--combat-status-${getCombatStatusCssSuffix(actor.combatStatus)}`);
    }

    li.tabIndex = 0;
    li.dataset.actorId = actor.id;
    li.setAttribute('aria-label', actorDescription);
    li.title = 'Entrée/Espace : joué. Flèches : déplacer. PV rapides : -7, +5 ou 12 puis Entrée.';

    const legendaryBadge = li.querySelector('.turn-order-item__legendary-badge');
    if (legendaryBadge) {
        legendaryBadge.hidden = !actor.isLegendary;
        legendaryBadge.textContent = actor.isLegendary ? 'Boss' : '';
        legendaryBadge.title = actor.isLegendary ? 'Boss légendaire' : '';
    }

    li.querySelector('.turn-order-item__image-placeholder').textContent = getActorInitial(actor);
    li.querySelector('.turn-order-item__name').textContent = actor.name;
    li.querySelector('.turn-order-item__initiative').textContent = `Init. ${formatInitiative(actor)}`;
    li.querySelector('.turn-order-item__armor-class').textContent = `CA ${actor.armorClass}`;
    li.querySelector('.turn-order-item__hit-points').textContent = `PV ${actor.currentHitPoints} / ${actor.baseHitPoints}`;
    populateTurnOrderHitPointsEditor(li, actor);
    populateTurnCombatStateEditor(li, actor, options);

    const badge = li.querySelector('.turn-order-item__badge');
    badge.hidden = !options.isActive;
}

function bindTurnOrderItemControls(li, roundOrder, actor, index, callbacks, options) {
    bindMoveButton(
        li.querySelector('[data-turn-move="previous"]'),
        roundOrder,
        actor,
        index,
        'previous',
        callbacks,
    );
    bindMoveButton(
        li.querySelector('[data-turn-move="next"]'),
        roundOrder,
        actor,
        index,
        'next',
        callbacks,
    );
    bindTurnOrderItemEvents(li, roundOrder, actor, index, callbacks, options);
}

function bindTurnOrderItemEvents(li, roundOrder, actor, index, callbacks, options) {
    bindTurnToggleEvents(li, actor, callbacks);
    bindTurnKeyboardEvents(li, roundOrder, actor, index, callbacks);
    bindTurnDragAndDropEvents(li, roundOrder, actor, callbacks);
    bindTurnHitPointsEditor(li, actor, callbacks);
    bindTurnCombatStateEditor(li, actor, callbacks, options);
}

function bindTurnHitPointsEditor(li, actor, callbacks) {
    const editor = li.querySelector('.turn-order-item__hit-points-editor');
    const input = editor?.querySelector('[data-turn-hit-points-input]');
    const applyButton = editor?.querySelector('[data-turn-hit-points-apply]');
    const feedback = li.querySelector('[data-turn-hit-points-feedback]');

    if (!editor || !input || !applyButton) {
        return;
    }

    editor.addEventListener('click', event => {
        event.stopPropagation();
    });

    input.value = '';
    input.setAttribute('aria-label', `Modifier les PV de ${actor.name}`);
    input.setAttribute('title', `Saisir -7, +5 ou 12 pour ${actor.name}`);
    input.addEventListener('click', event => {
        event.stopPropagation();
    });
    input.addEventListener('input', () => {
        clearTurnHitPointsFeedback(input, feedback);
    });
    input.addEventListener('keydown', event => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        applyTurnHitPointsChange(actor, input, feedback, callbacks);
    });

    applyButton.setAttribute('aria-label', `Appliquer la modification des PV de ${actor.name}`);
    applyButton.setAttribute('title', `Appliquer la modification des PV de ${actor.name}`);
    applyButton.addEventListener('click', event => {
        event.stopPropagation();
        applyTurnHitPointsChange(actor, input, feedback, callbacks);
    });
}

function populateTurnOrderHitPointsEditor(li, actor) {
    const editor = li.querySelector('.turn-order-item__hit-points-editor');
    const input = editor?.querySelector('[data-turn-hit-points-input]');
    const feedback = li.querySelector('[data-turn-hit-points-feedback]');

    if (input) {
        input.value = '';
        input.setAttribute('aria-describedby', `${actor.id}-turn-hit-points-feedback`);
        input.removeAttribute('aria-invalid');
        input.classList.remove('dnd-field--invalid');
    }

    if (feedback) {
        feedback.id = `${actor.id}-turn-hit-points-feedback`;
        feedback.hidden = true;
        feedback.textContent = '';
    }
}

function bindTurnCombatStateEditor(li, actor, callbacks, options) {
    const controls = getTurnCombatStateControls(li);

    if (!controls) {
        return;
    }

    controls.toggleButton.addEventListener('click', event => {
        event.stopPropagation();
        callbacks.onToggleCombatStateEditor?.(actor.id);
    });

    controls.panel.addEventListener('click', event => {
        event.stopPropagation();
    });

    controls.conditionSelect.addEventListener('change', event => {
        event.stopPropagation();
        updateExhaustionLevelVisibility(
            controls.conditionSelect,
            controls.durationWrapper,
            controls.levelWrapper,
            controls.durationInput,
            controls.levelInput,
        );
        clearTurnCombatStateFeedback(controls.feedback);
    });

    controls.conditionSelect.addEventListener('keydown', event => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        applyTurnConditionChange(actor, {
            conditionSelect: controls.conditionSelect,
            durationInput: controls.durationInput,
            levelInput: controls.levelInput,
            noteInput: controls.noteInput,
            feedback: controls.feedback,
            callbacks,
        });
    });

    controls.durationInput.addEventListener('input', () => {
        clearTurnCombatStateFeedback(controls.feedback);
    });

    controls.durationInput.addEventListener('keydown', event => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        applyTurnConditionChange(actor, {
            conditionSelect: controls.conditionSelect,
            durationInput: controls.durationInput,
            levelInput: controls.levelInput,
            noteInput: controls.noteInput,
            feedback: controls.feedback,
            callbacks,
        });
    });

    controls.levelInput.addEventListener('input', () => {
        clearTurnCombatStateFeedback(controls.feedback);
    });

    controls.levelInput.addEventListener('keydown', event => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        applyTurnConditionChange(actor, {
            conditionSelect: controls.conditionSelect,
            durationInput: controls.durationInput,
            levelInput: controls.levelInput,
            noteInput: controls.noteInput,
            feedback: controls.feedback,
            callbacks,
        });
    });

    controls.noteInput.addEventListener('input', () => {
        clearTurnCombatStateFeedback(controls.feedback);
    });

    controls.noteInput.addEventListener('keydown', event => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        applyTurnConditionChange(actor, {
            conditionSelect: controls.conditionSelect,
            durationInput: controls.durationInput,
            levelInput: controls.levelInput,
            noteInput: controls.noteInput,
            feedback: controls.feedback,
            callbacks,
        });
    });

    controls.statusSelect.addEventListener('change', event => {
        event.stopPropagation();
        applyTurnCombatStatusChange(actor, controls.statusSelect, controls.feedback, callbacks);
    });

    controls.addButton.addEventListener('click', event => {
        event.stopPropagation();
        applyTurnConditionChange(actor, {
            conditionSelect: controls.conditionSelect,
            durationInput: controls.durationInput,
            levelInput: controls.levelInput,
            noteInput: controls.noteInput,
            feedback: controls.feedback,
            callbacks,
        });
    });

    populateTurnCombatStateEditor(li, actor, options);

    controls.conditionList.querySelectorAll('[data-turn-condition-remove]').forEach(button => {
        button.addEventListener('click', event => {
            event.stopPropagation();
            applyTurnConditionRemoval(actor, button.dataset.turnConditionRemove, controls.feedback, callbacks);
        });
    });
}

function populateTurnCombatStateEditor(li, actor, options = {}) {
    const controls = getTurnCombatStateControls(li);

    if (!controls) {
        return;
    }

    const conditions = Array.isArray(actor.conditions) ? actor.conditions : [];
    const combatStatus = actor.combatStatus ?? 'normal';
    const isEditorOpen = options?.expandedCombatStateTurnId === actor.id;
    const conditionCount = conditions.length + (combatStatus !== 'normal' ? 1 : 0);

    controls.toggleButton.textContent = conditionCount > 0
        ? `Conditions (${conditionCount})`
        : 'Conditions';
    controls.toggleButton.setAttribute('aria-expanded', String(isEditorOpen));
    controls.toggleButton.title = 'Gérer les conditions et l’état de combat';

    controls.panel.hidden = !isEditorOpen;
    controls.panel.setAttribute('aria-hidden', String(!isEditorOpen));

    renderConditionCatalogSelect(controls.conditionSelect, actor);
    renderCombatStatusCatalogSelect(controls.statusSelect, combatStatus, actor);
    updateExhaustionLevelVisibility(
        controls.conditionSelect,
        controls.durationWrapper,
        controls.levelWrapper,
        controls.durationInput,
        controls.levelInput,
    );

    controls.durationInput.value = '';
    controls.levelInput.value = '';
    controls.noteInput.value = '';

    renderCombatStateSummary(controls.stateSummary, actor);
    renderConditionBadges(controls.conditionList, actor);
    clearTurnCombatStateFeedback(controls.feedback);
}

function getTurnCombatStateControls(li) {
    const toggleButton = li.querySelector('[data-turn-conditions-toggle]');
    const panel = li.querySelector('[data-turn-conditions-panel]');
    const stateSummary = li.querySelector('[data-turn-state-summary]');
    const conditionList = li.querySelector('[data-turn-conditions-list]');
    const conditionSelect = panel?.querySelector('[data-turn-condition-select]');
    const durationWrapper = panel?.querySelector('[data-turn-condition-rounds-wrapper]');
    const durationInput = panel?.querySelector('[data-turn-condition-rounds]');
    const levelInput = panel?.querySelector('[data-turn-condition-level]');
    const levelWrapper = panel?.querySelector('[data-turn-condition-level-wrapper]');
    const noteInput = panel?.querySelector('[data-turn-condition-note]');
    const statusSelect = panel?.querySelector('[data-turn-combat-status-select]');
    const addButton = panel?.querySelector('[data-turn-condition-add]');
    const feedback = panel?.querySelector('[data-turn-conditions-feedback]');

    if (
        !toggleButton || !panel || !stateSummary || !conditionList || !conditionSelect
        || !durationInput || !levelInput || !levelWrapper || !noteInput
        || !statusSelect || !addButton || !feedback
    ) {
        return null;
    }

    return {
        toggleButton,
        panel,
        stateSummary,
        conditionList,
        conditionSelect,
        durationWrapper,
        durationInput,
        levelInput,
        levelWrapper,
        noteInput,
        statusSelect,
        addButton,
        feedback,
    };
}

function applyTurnHitPointsChange(actor, input, feedback, callbacks) {
    const result = callbacks.onApplyHitPointsChange?.(actor.id, input.value);

    if (!result) {
        return;
    }

    if (!result.ok) {
        showTurnHitPointsFeedback(input, feedback, result.message);
        callbacks.onAnnounce?.(result.message);
        input.focus();
        return;
    }

    clearTurnHitPointsFeedback(input, feedback);
    input.value = '';
    callbacks.onAnnounce?.(result.message);
}

function showTurnHitPointsFeedback(input, feedback, message) {
    if (input) {
        input.classList.add('dnd-field--invalid');
        input.setAttribute('aria-invalid', 'true');
    }

    if (!feedback) {
        return;
    }

    feedback.textContent = message;
    feedback.hidden = false;
}

function clearTurnHitPointsFeedback(input, feedback) {
    if (input) {
        input.classList.remove('dnd-field--invalid');
        input.removeAttribute('aria-invalid');
    }

    if (!feedback) {
        return;
    }

    feedback.textContent = '';
    feedback.hidden = true;
}

function renderConditionCatalogSelect(select, actor) {
    const currentValue = select.value || (getConditionCatalog()[0]?.slug ?? '');

    select.replaceChildren();

    getConditionCatalog().forEach(condition => {
        const option = document.createElement('option');
        option.value = condition.slug;
        option.textContent = condition.label;
        select.appendChild(option);
    });

    select.value = currentValue;
    select.setAttribute('aria-label', `Choisir une condition pour ${actor.name}`);
}

function renderCombatStatusCatalogSelect(select, combatStatus, actor) {
    select.replaceChildren();

    getCombatStatusCatalog().forEach(status => {
        const option = document.createElement('option');
        option.value = status.value;
        option.textContent = status.label;
        select.appendChild(option);
    });

    select.value = combatStatus ?? 'normal';
    select.setAttribute('aria-label', `Choisir l’état de combat pour ${actor.name}`);
}

function updateExhaustionLevelVisibility(conditionSelect, durationWrapper, levelWrapper, durationInput, levelInput) {
    const isExhaustion = conditionSelect.value === 'exhaustion';

    levelWrapper.hidden = !isExhaustion;
    durationWrapper.hidden = isExhaustion;

    if (!isExhaustion) {
        levelInput.value = '';
        return;
    }

    durationInput.value = '';
}

function renderCombatStateSummary(summary, actor) {
    const conditions = Array.isArray(actor.conditions) ? actor.conditions : [];
    const combatStatus = actor.combatStatus ?? 'normal';

    summary.replaceChildren();

    if (actor.currentHitPoints === 0) {
        summary.appendChild(createCombatStateBadge(
            'PV 0',
            'turn-order-item__combat-state-badge--zero-hit-points',
            'PV actuels à 0',
        ));
    }

    if (combatStatus !== 'normal') {
        summary.appendChild(createCombatStateBadge(
            formatCombatStatusLabel(combatStatus),
            `turn-order-item__combat-state-badge--${getCombatStatusCssSuffix(combatStatus)}`,
            `État de combat : ${formatCombatStatusLabel(combatStatus)}`,
        ));
    }

    conditions.forEach(condition => {
        summary.appendChild(createCombatStateBadge(
            formatConditionLabel(condition),
            'turn-order-item__combat-state-badge--condition',
            condition.note || formatConditionLabel(condition),
        ));
    });
}

function renderConditionBadges(list, actor) {
    const conditions = Array.isArray(actor.conditions) ? actor.conditions : [];

    list.replaceChildren();

    if (conditions.length === 0) {
        list.hidden = true;
        return;
    }

    list.hidden = false;

    conditions.forEach(condition => {
        const badge = document.createElement('div');
        badge.classList.add('turn-order-item__condition-badge');
        badge.title = condition.note || formatConditionLabel(condition);

        const label = document.createElement('span');
        label.classList.add('turn-order-item__condition-label');
        label.textContent = formatConditionLabel(condition);
        badge.appendChild(label);

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.classList.add('turn-order-item__condition-remove', 'dnd-icon-button');
        removeButton.dataset.turnConditionRemove = condition.id;
        removeButton.setAttribute('aria-label', `Retirer ${formatConditionLabel(condition)}`);
        removeButton.title = `Retirer ${formatConditionLabel(condition)}`;
        removeButton.textContent = '×';
        badge.appendChild(removeButton);

        list.appendChild(badge);
    });
}

function createCombatStateBadge(label, className, title) {
    const badge = document.createElement('span');
    badge.classList.add('turn-order-item__combat-state-badge');

    if (className) {
        badge.classList.add(className);
    }

    badge.textContent = label;
    if (title) {
        badge.title = title;
    }

    return badge;
}

function applyTurnConditionChange(actor, controls) {
    const conditionPayload = normalizeConditionPayload({
        slug: controls.conditionSelect.value,
        remainingRounds: controls.durationInput.value,
        level: controls.levelInput.value,
        note: controls.noteInput.value,
    });

    if (!conditionPayload) {
        const message = getConditionApplyErrorMessage(controls.conditionSelect.value);
        showTurnCombatStateFeedback(controls.feedback, message);
        controls.callbacks.onAnnounce?.(message);
        return;
    }

    const result = controls.callbacks.onAddCondition?.(actor.id, conditionPayload);

    if (!result) {
        return;
    }

    if (!result.ok) {
        showTurnCombatStateFeedback(controls.feedback, result.message);
        controls.callbacks.onAnnounce?.(result.message);
        return;
    }

    clearTurnCombatStateFeedback(controls.feedback);
    controls.durationInput.value = '';
    controls.levelInput.value = '';
    controls.noteInput.value = '';
    controls.conditionSelect.focus();
    controls.callbacks.onAnnounce?.(result.message);
}

function applyTurnConditionRemoval(actor, conditionId, feedback, callbacks) {
    const result = callbacks.onRemoveCondition?.(actor.id, conditionId);

    if (!result) {
        return;
    }

    if (!result.ok) {
        showTurnCombatStateFeedback(feedback, result.message);
        callbacks.onAnnounce?.(result.message);
        return;
    }

    clearTurnCombatStateFeedback(feedback);
    callbacks.onAnnounce?.(result.message);
    return;
}

function applyTurnCombatStatusChange(actor, statusSelect, feedback, callbacks) {
    const result = callbacks.onSetCombatStatus?.(actor.id, statusSelect.value);

    if (!result) {
        return;
    }

    if (!result.ok) {
        showTurnCombatStateFeedback(feedback, result.message);
        callbacks.onAnnounce?.(result.message);
        return;
    }

    clearTurnCombatStateFeedback(feedback);
    statusSelect.focus();
    callbacks.onAnnounce?.(result.message);
}

function clearTurnCombatStateFeedback(feedback) {
    if (!feedback) {
        return;
    }

    feedback.textContent = '';
    feedback.hidden = true;
}

function showTurnCombatStateFeedback(feedback, message) {
    if (!feedback) {
        return;
    }

    feedback.textContent = message;
    feedback.hidden = false;
}

function getConditionApplyErrorMessage(conditionSlug) {
    if (conditionSlug === 'exhaustion') {
        return 'Le niveau d’épuisement doit être compris entre 1 et 6.';
    }

    return 'La condition ou sa durée est invalide.';
}

function bindTurnToggleEvents(li, actor, callbacks) {
    li.addEventListener('click', () => {
        callbacks.onToggleTurnDone(actor.id);
    });
}

function bindTurnKeyboardEvents(li, roundOrder, actor, index, callbacks) {
    li.addEventListener('keydown', event => {
        if (event.target !== li) {
            return;
        }

        if (['Enter', ' '].includes(event.key)) {
            event.preventDefault();
            callbacks.onToggleTurnDone(actor.id);
            return;
        }

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            moveActorWithKeyboard(roundOrder, actor, index, 'previous', callbacks);
            return;
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            moveActorWithKeyboard(roundOrder, actor, index, 'next', callbacks);
        }
    });
}

function bindTurnDragAndDropEvents(li, roundOrder, actor, callbacks) {
    li.draggable = true;

    li.addEventListener('dragstart', () => {
        draggedTurnId = actor.id;
        li.classList.add('turn-order-item--dragging');
    });

    li.addEventListener('dragend', () => {
        draggedTurnId = null;
        li.classList.remove('turn-order-item--dragging');
    });

    li.addEventListener('dragover', (event) => {
        event.preventDefault();
        li.classList.add('turn-order-item--drag-over');
    });

    li.addEventListener('dragleave', () => {
        li.classList.remove('turn-order-item--drag-over');
    });

    li.addEventListener('drop', (event) => {
        event.preventDefault();

        li.classList.remove('turn-order-item--drag-over');

        if (!draggedTurnId || draggedTurnId === actor.id) {
            return;
        }

        callbacks.onMoveTurn(
            draggedTurnId,
            actor.id,
            getDropPlacement(roundOrder, draggedTurnId, actor.id),
        );
    });
}

function getActorInitial(actor) {
    return actor.name.trim().charAt(0).toUpperCase() || '?';
}

function getActorSide(actor) {
    if (['party', 'ally', 'hostile', 'neutral'].includes(actor.side)) {
        return actor.side;
    }

    return actor.type === 'monster' ? 'hostile' : 'party';
}

function getActorSideLabel(side) {
    const labels = {
        party: 'PJ',
        ally: 'Allié',
        hostile: 'Hostile',
        neutral: 'Neutre',
    };

    return labels[side] ?? 'PJ';
}

function getCombatStatusCssSuffix(status) {
    return String(status ?? 'normal').replaceAll('_', '-');
}

function bindMoveButton(button, roundOrder, actor, index, direction, callbacks) {
    if (!button) {
        return;
    }

    button.tabIndex = -1;

    const target = getAdjacentTurn(roundOrder, index, direction);
    const label = getMoveButtonLabel(actor, target, direction);

    button.setAttribute('aria-label', label);
    button.title = label;

    if (!target) {
        button.setAttribute('aria-disabled', 'true');
    }

    button.addEventListener('click', event => {
        event.stopPropagation();

        if (!target) {
            return;
        }

        callbacks.onMoveTurn(
            actor.id,
            target.id,
            getMovePlacement(direction),
        );
        callbacks.onAnnounce?.(getMoveAnnouncement(actor, target, direction));
    });
}

function moveActorWithKeyboard(roundOrder, actor, index, direction, callbacks) {
    const target = getAdjacentTurn(roundOrder, index, direction);

    if (!target) {
        return;
    }

    callbacks.onMoveTurn(
        actor.id,
        target.id,
        getMovePlacement(direction),
    );
    callbacks.onAnnounce?.(getMoveAnnouncement(actor, target, direction));
}

function getAdjacentTurn(roundOrder, index, direction) {
    return direction === 'previous'
        ? roundOrder[index - 1]
        : roundOrder[index + 1];
}

function getMovePlacement(direction) {
    return direction === 'previous' ? 'before' : 'after';
}

function getMoveButtonLabel(actor, target, direction) {
    if (!target) {
        return `${actor.name} ne peut pas être déplacé ${direction === 'previous' ? 'avant' : 'après'}`;
    }

    return direction === 'previous'
        ? `Déplacer ${actor.name} avant`
        : `Déplacer ${actor.name} après`;
}

function getMoveAnnouncement(actor, target, direction) {
    return direction === 'previous'
        ? `${actor.name} déplacé avant ${target.name}.`
        : `${actor.name} déplacé après ${target.name}.`;
}

function getActorDescription(actor, position, total) {
    const turnStatus = actor.done ? 'joué' : 'à jouer';
    const actorSide = getActorSide(actor);
    const actorRoles = [getActorSideLabel(actorSide)];
    const combatState = describeCombatState(actor);

    if (actor.isLegendary) {
        actorRoles.push('boss légendaire');
    }

    return `${actorRoles.join(', ')}. ${actor.name}, position ${position} sur ${total}, initiative ${actor.initiative}, CA ${actor.armorClass}, PV ${actor.currentHitPoints} sur ${actor.baseHitPoints}, ${turnStatus}${combatState ? `, ${combatState}` : ''}. Entrée ou espace pour basculer joué. Flèches gauche et droite pour déplacer.`;
}

function describeCombatState(actor) {
    const states = [];

    if ((actor.combatStatus ?? 'normal') !== 'normal') {
        states.push(formatCombatStatusLabel(actor.combatStatus));
    }

    if (actor.currentHitPoints === 0) {
        states.push('PV à 0');
    }

    if (Array.isArray(actor.conditions) && actor.conditions.length > 0) {
        states.push(`conditions ${actor.conditions.map(formatConditionLabel).join(', ')}`);
    }

    return states.join(', ');
}

function focusTurnItem(turnOrderList, turnId) {
    const turnItem = Array.from(turnOrderList.querySelectorAll('.turn-order-item'))
        .find(item => item.dataset.actorId === turnId);

    turnItem?.focus();
}

function focusTurnItemElement(turnOrderList, turnId, selector) {
    const turnItem = Array.from(turnOrderList.querySelectorAll('.turn-order-item'))
        .find(item => item.dataset.actorId === turnId);

    turnItem?.querySelector(selector)?.focus();
}

function announceTurnOrderChange(liveRegion, message) {
    if (!liveRegion) {
        return;
    }

    liveRegion.textContent = '';
    const scheduleFrame = typeof globalThis.requestAnimationFrame === 'function'
        ? globalThis.requestAnimationFrame.bind(globalThis)
        : (callback) => globalThis.setTimeout(callback, 0);

    scheduleFrame(() => {
        liveRegion.textContent = message;
    });
}

function bindKeyboardHelp(button, helpPanel) {
    if (!button || !helpPanel) {
        return;
    }

    button.addEventListener('click', () => {
        const shouldShowHelp = helpPanel.hidden;

        helpPanel.hidden = !shouldShowHelp;
        button.setAttribute('aria-expanded', String(shouldShowHelp));
        button.setAttribute(
            'aria-label',
            shouldShowHelp
                ? 'Masquer l’aide clavier'
                : 'Afficher l’aide clavier',
        );
    });
}

function getDropPlacement(roundOrder, draggedTurnId, targetActorId) {
    const draggedIndex = roundOrder.findIndex(actor => actor.id === draggedTurnId);
    const targetIndex = roundOrder.findIndex(actor => actor.id === targetActorId);

    if (draggedIndex === -1 || targetIndex === -1) {
        return 'before';
    }

    return draggedIndex < targetIndex ? 'after' : 'before';
}
