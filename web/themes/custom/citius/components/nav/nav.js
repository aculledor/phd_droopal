/**
 * @file
 * Behavior for all collapsable menus.
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.nav = {
    attach: function (context, settings) {
      const menuItems = once('nav', '.nav__item--has-children > .nav__link');

      const toggleSubmenu = (link, event) => {
        if (event) {
          event.preventDefault();
        }
        const isOpen = link.parentElement.classList.contains('open');
        menuItems.forEach(menuItem => {
          menuItem.parentElement.classList.remove('open');
          menuItem.setAttribute('aria-expanded', 'false');
        });
        const expanded = link.getAttribute('aria-expanded') === 'true';
        link.setAttribute('aria-expanded', !expanded);
        const submenu = link.nextElementSibling;
        submenu.setAttribute('aria-hidden', expanded);
        if (!isOpen) {
          link.parentElement.classList.add('open');
        }
      };

      menuItems.forEach(link => {
        link.addEventListener('click', function(e) {
          toggleSubmenu(this, e);
        });
      });

      if (window.innerWidth < 1235) {
        menuItems.forEach(link => {
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
