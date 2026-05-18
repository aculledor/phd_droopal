(function (Drupal, once) {
  Drupal.behaviors.customButtonBehavior = {
    attach: function (context, settings) {
      once('individualBehaviorButtons', '.paragraphs-individual-behavior-button', context).forEach(function (button) {
        button.addEventListener('click', function (event) {
          event.preventDefault();
          const targetDiv = this.parentElement.parentElement.parentElement;
          if (targetDiv.classList.contains('behavior-active')) {
            targetDiv.classList.remove('behavior-active');
            targetDiv.classList.add('content-active');
            this.value = Drupal.t('Configuration');
          } else {
            targetDiv.classList.add('behavior-active');
            targetDiv.classList.remove('content-active');
            this.value = Drupal.t('Content');
          }
        });
      });
    }
  };
})(Drupal, once);
