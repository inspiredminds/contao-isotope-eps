[![](https://img.shields.io/packagist/v/inspiredminds/contao-isotope-eps.svg)](https://packagist.org/packages/inspiredminds/contao-isotope-eps)
[![](https://img.shields.io/packagist/dt/inspiredminds/contao-isotope-eps.svg)](https://packagist.org/packages/inspiredminds/contao-isotope-eps)

Contao Isotope eps
==================

Austrian [eps](https://eps-ueberweisung.at/) payment method for Contao Isotope.

The payment method has the following additional settings:

* **User ID**: The user ID (merchant ID) associated with the eps contract.
* **Secret**: The secret (merchant PIN) associated with the eps contract.
* **IBAN**: The IBAN of the receiving account associated with the eps contract (will be taken from the shop configuration if not specified).
* **BIC**: The BIC of the receiving account associated with the eps contract (will be taken from the shop configuration if not specified).
* **Account name**: Account holder name of the receiving account associated with the eps contract (will be taken from the shop configuration if not specified).
* **Test mode**: Enables the test mode (note that different credentials are typically needed for the test mode).

The initiated bank transfer will use the unique ID of the Isotope order as the reference number (e.g. `12ab345c67d8e9.12345678`).
