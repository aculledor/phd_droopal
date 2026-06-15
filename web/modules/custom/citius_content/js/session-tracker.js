/**
 * Session tracker.
 */
class SessionTracker {

  TIMESHIFT = 5000;

  actions = {
    start: 'start',
    calibrateStandingHeight: 'calibrate_standing_height',
    calibrateSquatHeight: 'calibrate_squat_height',
    pause: 'pause',
    stop: 'stop',
    restart: 'restart',
    finish: 'finish',
  };

  deviceActions = {
    start: 'start',
    pause: 'pause',
    stop: 'stop',
    reboot: 'reboot',
    resume: 'resume',
    finish: 'finish',
    calibrateStandingHeight: 'calibrate_standing_height',
    calibrateSquatHeight: 'calibrate_squat_height',
  };

  states = {
    execution: 'execution',
    pause: 'pause',
    finished: 'finished',
    scheduled: 'scheduled',
  };

  visibleActions = {
    scheduled: [this.actions.start, this.actions.calibrateStandingHeight, this.actions.calibrateSquatHeight],
    execution: [this.actions.pause, this.actions.stop, this.actions.restart, this.actions.finish],
    pause: [this.actions.start, this.actions.calibrateStandingHeight, this.actions.calibrateSquatHeight, this.actions.stop, this.actions.restart, this.actions.finish],
    finished: [this.actions.restart],
  }

  resultsPoller = null;

  /**
   * Keep this annotation for type hints.
   *
   * @param {string} sessionId
   * @param {object} settings
   * @param {NodeList} buttons
   * @param {NodeList} exercises
   * @param {Node} statusElement
   * @param {Node} errorPlaceholder
   * @param {string} state
   */
  constructor(
    sessionId,
    settings,
    buttons,
    exercises,
    statusElement,
    errorPlaceholder,
    state = this.states.scheduled,
  ) {
    this.state = state;
    this.sessionId = sessionId;
    this.settings = settings;
    this.statusElement = statusElement;
    this.errorPlaceholder = errorPlaceholder;
    this.buttons = {};
    this.timer = null;
    buttons.forEach((button) => {
      const action = button.dataset.action;
      this.buttons[action] = button;
    });
    this.exercises = {};
    exercises.forEach((exercise) => {
      const exerciseId = exercise.dataset.exercise;
      this.exercises[exerciseId] = exercise;
    });
    this.currentExercise = Object.keys(this.exercises)[0];
    this.init();
  }

  init() {
    this.addButtonListeners();
    this.updateElements();
  }

  updateElements() {
    this.updateButtonsVisibility();
    this.updateExercisesState();
  }

  startTimer = () => {
    // Desactivado: el avance de ejercicios ya no lo decide Drupal por duración.
    // La fuente de verdad son los resultados recibidos desde /api/session-results.
  }

  startResultsPolling = () => {
    this.stopResultsPolling();

    this.resultsPoller = setInterval(() => {
      if (this.state === this.states.execution) {
        this.updateSessionResults();
      }
    }, 2000);
  }

  stopResultsPolling = () => {
    if (this.resultsPoller) {
      clearInterval(this.resultsPoller);
      this.resultsPoller = null;
    }
  }

  getExpectedResults = (exerciseId) => {
    const exerciseSettings = this.settings.exercises[exerciseId];

    if (exerciseSettings && typeof exerciseSettings === 'object') {
      return Number(exerciseSettings.expectedResults || 0);
    }

    const row = this.exercises[exerciseId];
    return Number(row?.dataset?.expectedResults || 0);
  }

  getObtainedResults = (exerciseId) => {
    const row = this.exercises[exerciseId];
    const resultsColumn = row?.querySelector('.session__results-column');

    if (!resultsColumn) {
      return 0;
    }

    const datasetValue = Number(resultsColumn.dataset.resultsCount || 0);

    if (datasetValue > 0) {
      return datasetValue;
    }

    const textValue = Number(resultsColumn.textContent.trim());
    return Number.isFinite(textValue) ? textValue : 0;
  }

  isExerciseComplete = (exerciseId) => {
    const expected = this.getExpectedResults(exerciseId);
    const obtained = this.getObtainedResults(exerciseId);

    if (!expected) {
      return true;
    }

    return obtained >= expected;
  }

  getFirstIncompleteExercise = () => {
    return Object.keys(this.exercises).find((exerciseId) => {
      return !this.isExerciseComplete(exerciseId);
    });
  }

  updateCurrentExerciseFromResults = () => {
    const nextExercise = this.getFirstIncompleteExercise();

    if (nextExercise) {
      this.currentExercise = nextExercise;
    }

    this.updateExercisesState();
  }

  isSessionComplete = () => {
    return Object.keys(this.exercises).every((exerciseId) => {
      return this.isExerciseComplete(exerciseId);
    });
  }

  finishIfAllResultsReceived = async () => {
    if (this.state !== this.states.execution) {
      return;
    }

    if (!this.isSessionComplete()) {
      return;
    }

    this.state = this.states.finished;
    clearTimeout(this.timer);
    this.stopResultsPolling();
    this.updateElements();

    await this.saveSessionStatus(this.deviceActions.finish);
  }

  startSession = () => {
    const action = this.state === this.states.pause ? this.deviceActions.resume : this.deviceActions.start;

    if (action === this.deviceActions.start) {
      this.resetExerciseRows();
      this.currentExercise = Object.keys(this.exercises)[0];
    }

    this.state = this.states.execution;
    this.updateElements();
    this.saveSessionStatus(action);
    this.updateSessionResults();
    this.startResultsPolling();
  };

  pauseSession = () => {
    this.state = this.states.pause;
    this.updateElements();
    this.saveSessionStatus(this.deviceActions.pause);
    clearTimeout(this.timer);
    this.stopResultsPolling();
  };

  stopSession = () => {
    this.state = this.states.scheduled;
    clearTimeout(this.timer);
    this.stopResultsPolling();

    this.saveSessionStatus(this.deviceActions.stop);
    this.resetExerciseRows();
    this.currentExercise = Object.keys(this.exercises)[0];
    this.updateElements();
  };

  resetSession = () => {
    this.state = this.states.execution;
    clearTimeout(this.timer);
    this.stopResultsPolling();

    this.currentExercise = Object.keys(this.exercises)[0];
    this.resetExerciseRows();
    this.updateElements();

    this.saveSessionStatus(this.deviceActions.reboot);
    this.updateSessionResults();
    this.startResultsPolling();
  };

  finishSession = () => {
    this.state = this.states.finished;
    clearTimeout(this.timer);
    this.stopResultsPolling();

    this.updateElements();
    this.saveSessionStatus(this.deviceActions.finish);
  };

  calibrateStandingHeight = () => {
    this.saveSessionStatus(this.deviceActions.calibrateStandingHeight);
  };

  calibrateSquatHeight = () => {
    this.saveSessionStatus(this.deviceActions.calibrateSquatHeight);
  };

  buttonCallbacksByAction = {
    [this.actions.start]: this.startSession,
    [this.actions.calibrateStandingHeight]: this.calibrateStandingHeight,
    [this.actions.calibrateSquatHeight]: this.calibrateSquatHeight,
    [this.actions.pause]: this.pauseSession,
    [this.actions.stop]: this.stopSession,
    [this.actions.restart]: this.resetSession,
    [this.actions.finish]: this.finishSession,
  };

  addButtonListeners() {
    Object.entries(this.buttons).forEach(([action, button]) => {
      button.addEventListener('click', this.buttonCallbacksByAction[action]);
    });
  }

  updateButtonsVisibility() {
    Object.entries(this.buttons).forEach(([action, button]) => {
      button.classList.toggle('hidden', !this.visibleActions[this.state].includes(action));
    });
  }

  updateExercisesState() {
    Object.entries(this.exercises).forEach(([exerciseId, exercise]) => {
      exercise.classList.toggle('active', exerciseId === this.currentExercise && this.state !== this.states.scheduled && this.state !== this.states.finished);
    });
  }

  saveSessionStatus = async (action) => {
    const tokenResponse = await fetch('/session/token');
    const csrfToken = await tokenResponse.text();
    const data = {
      status: this.state,
      id: this.sessionId,
      action,
    };
    const url = `/api/session-status`;
    const result = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
      },
      body: JSON.stringify(data),
    });
    const response = await result.json();
    if (response.status_label) {
      this.statusElement.textContent = response.status_label;
    }
    if (response.status !== 'success' && response.error_message) {
      this.errorPlaceholder.textContent = response.error_message;
    }
    else {
      this.errorPlaceholder.textContent = '';
    }
  }

  resetExerciseRows = () => {
    Object.entries(this.exercises).forEach(([key, exercise]) => {
      exercise.classList.remove('active', 'open');

      const results = exercise.nextElementSibling;
      if (results && results.classList.contains('session__exercise-results')) {
        results.remove();
      }

      const status = exercise.querySelector('.session__exercise-status');
      if (status) {
        status.classList.remove('success', 'failure', 'missed', 'pending');
        status.setAttribute('title', '');
        status.setAttribute('aria-label', '');
      }

      const resultsColumn = exercise.querySelector('.session__results-column');
      if (resultsColumn) {
        const wrapper = resultsColumn.parentElement;
        wrapper.innerHTML = '<div class="session__results-column" data-results-count="0">-</div>';
      }
    });
  }

  updateSessionResults = async () => {
    const url = `/api/session-results/${this.sessionId}`;
    const result = await fetch(url);
    const response = await result.json();

    if (Array.isArray(response)) {
      response.forEach((item) => {
        if (item.markup) {
          const id = item.exercise_id;
          const tableRow = this.exercises[id];

          if (!tableRow) {
            return;
          }

          const oldResults = tableRow.nextElementSibling;

          if (oldResults && oldResults.classList.contains('session__exercise-results')) {
            oldResults.remove();
          }

          const newResults = document.createElement('template');
          newResults.innerHTML = item.markup;
          tableRow.insertAdjacentElement('afterend', newResults.content.firstElementChild);

          if (item.result_column) {
            const resultColumn = tableRow.querySelector('.session__results-column');

            if (resultColumn) {
              resultColumn.parentElement.innerHTML = item.result_column;
            }

            tableRow.querySelector('.session__results-column button')?.addEventListener('click', () => {
              tableRow.classList.toggle('open');
            });
          }
        }
      });
    }

    this.updateCurrentExerciseFromResults();
    await this.finishIfAllResultsReceived();
  }

}
