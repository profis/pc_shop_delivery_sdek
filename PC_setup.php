<?php
function pc_shop_delivery_sdek_install($controller) {
	global $core;

	$pluginName = 'pc_shop_delivery_sdek';

	$model = new PC_shop_delivery_option_model();
	$model->insert(
		array(
			'code' => 'sdek',
		), array(
			'lt' => array(
				'name' => 'Pristatymo servisas "SDEK"'
			),
			'en' => array(
				'name' => '"SDEK" delivery service'
			),
			'ru' => array(
				'name' => 'Служба доставки «СДЭК»'
			)
		),
		array(
			'ignore' => true
		)
	);
	
	$core->Set_config_if('sdek_login', '', 'pc_shop_delivery_sdek');
	$core->Set_config_if('sdek_password', '', 'pc_shop_delivery_sdek');
	$core->Set_config_if('sdek_countries', 'by,kz,ru,ua', 'pc_shop_delivery_sdek');
	$core->Set_config_if('sdek_tariff_id', '11', 'pc_shop_delivery_sdek');
	$core->Set_config_if('sdek_delivery_mode', '3', 'pc_shop_delivery_sdek');
	$core->Set_config_if('sdek_sender_city', '44', 'pc_shop_delivery_sdek');

	$core->Set_variable_if('ru', 'error_impossible_to_calculate', 'Невозможно посчитать', $pluginName);
	$core->Set_variable_if('ru', 'error_city_required', 'Выберите город доставки', $pluginName);
	$core->Set_variable_if('ru', 'country', 'Страна', $pluginName);
	$core->Set_variable_if('ru', 'city', 'Город', $pluginName);
	$core->Set_variable_if('ru', 'country_empty', '-- выберите --', $pluginName);
	$core->Set_variable_if('ru', 'city_empty', '-- выберите --', $pluginName);
	$core->Set_variable_if('ru', 'error_internal', 'Произошла внутренняя ошибка при попытке рассчитать цену доставки службой «СДЭК»: {error} Пожалуйста сообщите об этой ошибке администрации интернет-магазина.', $pluginName);

	$core->Set_variable_if('en', 'error_impossible_to_calculate', 'Impossible to calculate', $pluginName);
	$core->Set_variable_if('en', 'error_city_required', 'Choose the destination city', $pluginName);
	$core->Set_variable_if('en', 'country', 'Country', $pluginName);
	$core->Set_variable_if('en', 'city', 'City', $pluginName);
	$core->Set_variable_if('en', 'country_empty', '-- choose --', $pluginName);
	$core->Set_variable_if('en', 'city_empty', '-- choose --', $pluginName);
	$core->Set_variable_if('ru', 'error_internal', 'There was an internal error while trying to calculate "SDEK" delivery service price: {error} Please inform online shop administration about this error.', $pluginName);

	return true;
}

function pc_shop_delivery_sdek_uninstall($controller) {
	$model = new PC_shop_delivery_option_model();
	$model->delete(array('where' => array('code' => 'sdek')));
	return true;
}