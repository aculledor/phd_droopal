(function (Drupal, once) {
  Drupal.behaviors.devicesAutoRefresh = {
    attach(context) {
      once('devices-auto-refresh', 'body', context).forEach(() => {
        const path = window.location.pathname;
        if (!/\/dispositivos\/?$/.test(path)) {
          return;
        }

        window.setInterval(() => {
          window.location.reload();
        }, 10000);
      });
    },
  };
})(Drupal, once);
