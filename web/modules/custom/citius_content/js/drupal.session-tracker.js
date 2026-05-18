/**
 * @file
 * Session tracker behavior.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.sessionTracker = {
    attach: function (context, settings) {
      once('sessionTracker', '.session-tracker').forEach((el) => {
        const buttons = el.querySelectorAll('.session__buttons button');
        const exercisesTable = el.querySelector('.session__exercises');
        const sessionId = exercisesTable.dataset.session;
        const sessionState = exercisesTable.dataset.state;
        const settings = drupalSettings.sessionTracker?.[`session_${sessionId}`];
        const exercises = exercisesTable.querySelectorAll('.session__exercise');
        const statusElement = el.querySelector('.field-name--field-session-state .field-item');
        const errorPlaceholder = el.querySelector('.session__error-placeholder');
        new SessionTracker(sessionId, settings, buttons, exercises, statusElement, errorPlaceholder, sessionState);

        exercises.forEach((row) => {
          const button = row.querySelector('.session__results-column button');
          if (button) {
            button.addEventListener('click', () => {
              row.classList.toggle('open');
            });
          }
        });
      });
    },
  };
})(Drupal, once);
