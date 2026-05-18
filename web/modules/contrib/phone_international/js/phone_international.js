(Drupal => {
  Drupal.behaviors.PhoneInternational = {
    attach: () => {
      once("phoneInternational", ".phone_international-number").forEach((field) => {

        const country = field.getAttribute("data-country");
        const geolocation = parseInt(field.getAttribute("data-geo"));
        const exclude = field.getAttribute("data-exclude");
        const only = field.getAttribute("data-only");
        const preferred = field.getAttribute("data-preferred");

        const options = {
          initialCountry: (geolocation === 1) ? "auto" : country,
          excludeCountries: exclude ? exclude.split("-") : [],
          onlyCountries: only ? only.split("-") : [],
          geoIpLookup: callback => {
            fetch(new Request("//ipinfo.io/json"))
              .then(response => {
                if (response.status === 200) {
                  return response.json();
                }
              }).then(resp => {
                const countryCode = (resp && resp.country) ? resp.country : country;
                callback(countryCode);
              }).catch(error => {
                console.error(error);
              });
          },
          preferredCountries: preferred ? preferred.split("-") : [],
          nationalMode: true,
          autoPlaceholder: "aggressive",
          formatOnDisplay: true,
          hiddenInput: "full_number",
          utilsScript: drupalSettings.phone_international.path + "/js/utils.js",
        };

        Drupal.behaviors.PhoneInternational.alterPhoneLibraryOptions(field, options);

        // Initialize the phone library.
        const iti = window.intlTelInput(field, options);

        // Set drupal selector and value for the hidden input and delete the
        // one provided by form element.
        const drupal_selector = field.parentNode.nextElementSibling.getAttribute("data-drupal-selector");
        const value = field.parentNode.nextElementSibling.getAttribute("value");
        field.parentNode.nextElementSibling.remove();
        field.nextElementSibling.setAttribute("data-drupal-selector", drupal_selector);
        field.nextElementSibling.setAttribute("value", value);

        // Add event lister to update the hidden input value on keyup to make
        // sure that the hidden input value is set for all ajax request
        // (the intl-tel-input library update the value on form submit only).
        field.addEventListener("keyup", () => field.nextElementSibling.value = iti.getNumber());
      });
    },
    /**
     * Allow to alter phone library options.
     *
     * @param {Object} field
     *   The field element.
     * @param {Object} options
     *   The list of options to init intlTelInput.
     *
     * @return void
     */
    alterPhoneLibraryOptions: (field, options) => {}
  }
})(Drupal);
