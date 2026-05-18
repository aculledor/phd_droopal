/**
 * Session tracker.
 */
class SessionTracker {

  TIMESHIFT = 5000;

  actions = {
    start: 'start',
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
  };

  states = {
    execution: 'execution',
    pause: 'pause',
    finished: 'finished',
    scheduled: 'scheduled',
  };

  visibleActions = {
    scheduled: [this.actions.start],
    execution: [this.actions.pause, this.actions.stop, this.actions.restart, this.actions.finish],
    pause: [this.actions.start, this.actions.stop, this.actions.restart, this.actions.finish],
    finished: [this.actions.restart],
  }

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
    const duration = this.settings.exercises[this.currentExercise];
    if (duration) {
      this.timer = setTimeout(() => {
        const nextIndex = Object.keys(this.exercises).indexOf(this.currentExercise) + 1;
        const exerciseId = this.currentExercise;
        setTimeout(() => {
          this.updateSessionResults(exerciseId);
        }, this.TIMESHIFT);
        if (nextIndex < Object.keys(this.exercises).length) {
          this.currentExercise = Object.keys(this.exercises)[nextIndex];
          this.updateElements();
          this.startTimer();
        }
        else {
          this.state = this.states.finished;
          this.updateElements();
          this.saveSessionStatus(this.deviceActions.stop);
        }
      }, duration * 1000);
    }
  }

  startSession = () => {
    const action = this.state === this.states.pause ? this.deviceActions.resume : this.deviceActions.start;
    this.state = this.states.execution;
    this.updateElements();
    this.saveSessionStatus(action);
    this.startTimer();
  };

  pauseSession = () => {
    this.state = this.states.pause;
    this.updateElements();
    this.saveSessionStatus(this.deviceActions.pause);
    clearTimeout(this.timer);
  };

  stopSession = () => {
    this.state = this.states.scheduled;
    this.updateElements();
    this.saveSessionStatus(this.deviceActions.stop);
    clearTimeout(this.timer);
    this.cleanResults();
  };

  resetSession = () => {
    this.state = this.states.execution;
    clearTimeout(this.timer);
    this.currentExercise = Object.keys(this.exercises)[0];
    this.updateElements();
    this.saveSessionStatus(this.deviceActions.reboot);
    this.startTimer();
    this.cleanResults();
  };

  finishSession = () => {
    this.state = this.states.finished;
    this.updateElements();
    this.saveSessionStatus(this.deviceActions.stop);
    clearTimeout(this.timer);
  };

  cleanResults = () => {
    Object.entries(this.exercises).forEach(([key, exercise]) => {
      const results = exercise.nextElementSibling;
      if (results && results.classList.contains('session__exercise-results')) {
        results.remove();
      }
      const resultsColumn = exercise.querySelector('.session__results-column');
      if (resultsColumn) {
        resultsColumn.innerHTML = '';
      }
    })
  }

  buttonCallbacksByAction = {
    [this.actions.start]: this.startSession,
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

  updateSessionResults = async () => {
    const url = `/api/session-results/${this.sessionId}`;
    const result = await fetch(url);
    const response = await result.json();
    if (Array.isArray(response)) {
      response.forEach((item) => {
        if (item.markup) {
          const id = item.exercise_id;
          const tableRow = this.exercises[id];
          const oldResults = this.exercises[id].nextElementSibling;
          if (oldResults && oldResults.classList.contains('session__exercise-results')) {
            oldResults.remove();
          }
          const newResults = document.createElement('template');
          newResults.innerHTML = item.markup;
          tableRow.insertAdjacentElement('afterend', newResults.content.firstElementChild);
          if (item.result_column) {
            const resultColumn = tableRow.querySelector('.session__results-column');
            resultColumn.parentElement.innerHTML = item.result_column;
            tableRow.querySelector('.session__results-column button')?.addEventListener('click', () => {
              tableRow.classList.toggle('open');
            })
          }
        }
      });
    }

  }

}
