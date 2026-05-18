/**
 * @file
 * Behavior for all collapsable menus.
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.dropdownMenu = {
    attach: function (context, settings) {
      const dropdownLinks = context.querySelectorAll('.dropdown-menu__link');

      const toggleSubmenu = (link, event) => {
        if (event) {
          event.preventDefault();
        }
        const expanded = link.getAttribute('aria-expanded') === 'true';
        link.setAttribute('aria-expanded', !expanded);
        const submenu = link.nextElementSibling;
        submenu.setAttribute('aria-hidden', expanded);
      };

      dropdownLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          if (window.innerWidth < 1235) {
            toggleSubmenu(this, e);
          }
        });
      });

      if (window.innerWidth < 1235) {
        dropdownLinks.forEach(link => {
          if (
            link.classList.contains('is-active') ||
            link.nextElementSibling.querySelector('.is-active')
          ) {
            toggleSubmenu(link);
          }
        });
      }
    }
  };
})(Drupal);
