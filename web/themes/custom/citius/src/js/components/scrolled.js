/**
 * @file
 * Provides scrolled component logic.
 */

(function (Drupal, once) {
  'use strict';
  Drupal.behaviors.scrolled = {
    attach: function (context, settings) {
      const body = document.getElementsByTagName('body')[0];
      const delay = 50;
      window.onscroll = () => {
        if (document.documentElement.scrollTop > delay) {
          body.classList.add('scrolled');
        } else if (document.documentElement.scrollTop === 0) {
          body.classList.remove('scrolled');
        }
      };
    },
  };

})(Drupal, once);
