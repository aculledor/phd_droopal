(function(Drupal) {

  Drupal.behaviors.collapsibleCard = {
    attach(context) {
      once('collapsibleCard', '.collapsible-card:not(.uncollapsible)', context).forEach((el) => {
        const buttons = el.querySelectorAll('.collapsible-card__toggle, .collapsible-card__header-title');
        buttons.forEach((button) => {
          button.addEventListener('click', () => {
            el.classList.toggle('open');
          });
        })
      });
    },
  };

})(Drupal);
