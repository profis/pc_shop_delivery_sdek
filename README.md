pc_shop_delivery_sdek
=====================

Delivery price calculation module for pc_shop plugin. Used to calculate "СДЭК" (http://www.edostavka.ru/) delivery service price by using their API.

Usage
=====

To use the plugin you must first enable it via module management dialogue. After that several configuration options will appear in the settings dialogue:

- sdek_countries: A comma separated list of country codes that are allowed for the user to select. By default it is filled with a list of all supported country codes: "by,kz,ru,ua". Changing order of codes in that serring will change the order in which they appear in country selection dropdown.
- sdek_login: An API login from their website. 
- sdek_password: An API password from their website.
- sdek_tariff_id: Id of the tariff from the list of their provided tariffs. Defaults to 11 (warehouse-door under 30kg express delivery for individuals).
- sdek_delivery_mode: Id of the delivery mode from the list of their provided modes. Defaults to 3 (warehouse-door).

Tariff and mode Ids may be found in a document provided with their sample calculator at http://www.edostavka.ru/integrator/ (last known working link: http://www.edostavka.ru/website/edostavka/upload/custom/files/cdek_calculator.zip).

Login and password are required only when using a e-shop tariff.
