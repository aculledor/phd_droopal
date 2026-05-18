(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.citiusPageAside = {
    attach: function (context, settings) {
      once('citius-page-aside', '.aside-menu__toggle', context).forEach(function (element) {
        element.addEventListener('click', function (e) {
          e.preventDefault();
          const expanded = element.getAttribute('aria-expanded') === 'true';

          context.querySelectorAll('.aside-menu__toggle').forEach(function (el) {
            el.setAttribute('aria-expanded', !expanded);
          });

          const target = document.getElementById(element.dataset.target);
          const target_bg = target.previousElementSibling;

          if (target) {
            target.setAttribute('aria-hidden', expanded);
          }
          if (target_bg) {
            target_bg.setAttribute('aria-hidden', expanded);
          }
        });
      });
    }
  };

})(Drupal, once);
