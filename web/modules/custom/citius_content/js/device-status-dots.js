(function (Drupal, once) {
  Drupal.behaviors.deviceStatusDots = {
    attach(context) {
      once('device-status-dots', '[data-device-status-id]', context).forEach((dot) => {
        const setStatus = (online) => {
          dot.classList.toggle('device-status-dot--online', online);
          dot.classList.toggle('device-status-dot--offline', !online);

          dot.style.backgroundColor = online ? '#027A48' : '#B42318';

          const label = online ? 'Online' : 'Offline';
          dot.setAttribute('title', label);
          dot.setAttribute('aria-label', label);
        };

        const update = async () => {
          const deviceId = dot.dataset.deviceStatusId;

          if (!deviceId) {
            setStatus(false);
            return;
          }

          try {
            const response = await fetch(`/api/device-status/${encodeURIComponent(deviceId)}`);
            const data = await response.json();
            setStatus(!!data.online);
          }
          catch (e) {
            setStatus(false);
          }
        };

        update();
        setInterval(update, 10000);
      });
    },
  };
})(Drupal, once);