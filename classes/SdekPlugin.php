<?php

class SdekPlugin {
	static $data = null;

	/**
	 * "pc_shop/cart/calculate_prices" event handler.
	 *
	 * Validates delivery specific form information and calculates delivery price.
	 *
	 * @param array $params An associative array containing cart and order information that should be used for calculation.
	 * - 'data' array: A reference to an associative array representing current cart state that should be filled with calculated data.
	 * - 'order_data' array: An associative array containing information on current order.
	 * - 'coupon_data' array: An associative array containing information on applied discount coupon.
	 * - 'delivery_option_data' array: An associative array containing information on the currently selected delivery method.
	 * - 'delivery_form_data' array: An associative array containing values of fields filled in the form specific to currently selected delivery method.
	 * - 'logger' PC_shop_cart: (DEPRECATED) An instance which may be used for debug logging.
	 */
	function calculateCartPrice($params) {
		global $cfg, $core, $cache;

		$data = &$params['data'];

		if( isset($params['delivery_form_data']['city']) && $params['delivery_form_data']['city'] ) {
			/** @var PC_shop_site $shop */
			$shop = $core->Get_object('PC_shop_site');

			$limit = $this->getCityCODLimit($params['delivery_form_data']['city']);
			$data['delivery_info']['cod_limit'] = $limit;

			// print_pre($params['data']['items']);
			// print_pre($params['data']['products']);

			$maxX = 0; // package width
			$maxY = 0; // package length
			$totalZ = 0; // package height
			$totalSizeWeight = 0;
			$totalVolume = 0;
			$totalVolumeWeight = 0;

			foreach( $params['data']['items'] as $cartItem ) {
				$product = $shop->products->applyAttributes($params['data']['products'][$cartItem['product_id']], $cartItem['attributes']);
				unset($product['attributes'], $product['attribute_index'], $product['combinations'], $product['price_combinations'], $product['resources'], $product['text'], $product['description']);
				if( !$product['weight'] )
					continue;

				/*
				$measurements = array(
					'weight' => $product['weight'],
					'width' => $product['width'],
					'height' => $product['height'],
					'length' => $product['length'],
					'volume' => $product['volume'],
				);
				print_pre($measurements);
				*/

				if( $product['width'] > 0 && $product['height'] > 0 && $product['length'] > 0 ) {
					if ($product['width'] <= $product['height'] && $product['width'] <= $product['length']) {
						$x = $product['height'];
						$y = $product['length'];
						$z = $product['width'];
					} else if ($product['height'] <= $product['width'] && $product['height'] <= $product['length']) {
						$x = $product['width'];
						$y = $product['length'];
						$z = $product['height'];
					} else {
						$x = $product['width'];
						$y = $product['height'];
						$z = $product['length'];
					}

					$maxX = max($maxX, min($x, $y));
					$maxY = max($maxY, max($x, $y));
					$totalZ += $z * $cartItem['basket_quantity'];
					$totalSizeWeight += $product['weight'] * $cartItem['basket_quantity'];
				}
				else if( $product['volume'] ) {
					$totalVolume += $product['volume'] * $cartItem['basket_quantity'];
					$totalVolumeWeight += $product['weight'] * $cartItem['basket_quantity'];
				}
			}

			$senderCity = 44;
			$destinationCity = $params['delivery_form_data']['city'];
			$date = date('Y-m-d');

			$tariffId = v($cfg['pc_shop_delivery_sdek']['sdek_tariff_id'], null);
			$deliveryMode = v($cfg['pc_shop_delivery_sdek']['sdek_delivery_mode'], null);
			if( !$tariffId && !$deliveryMode )
				$deliveryMode = 3; // by default use warehouse-house

			$key = md5("{$senderCity}.{$destinationCity}.{$date}.{$tariffId}.{$deliveryMode}.{$totalSizeWeight}.{$maxX}.{$maxY}.{$totalZ}.{$totalVolumeWeight}.{$totalVolume}");
			if( ($calcData = $cache->get($key)) === null ) {
				$calc = new CalculatePriceDeliveryCdek();
				if( $cfg['pc_shop_delivery_sdek']['sdek_login'] )
					$calc->setAuth($cfg['pc_shop_delivery_sdek']['sdek_login'], $cfg['pc_shop_delivery_sdek']['sdek_password']);
				$calc->setSenderCityId($senderCity);
				$calc->setReceiverCityId($destinationCity);
				$calc->setDateExecute($date);
				if( $tariffId )
					$calc->setTariffId($tariffId);
				if( $deliveryMode )
					$calc->setModeDeliveryId($deliveryMode);

				if( $totalSizeWeight > 0 )
					$calc->addGoodsItemBySize($totalSizeWeight, $maxY / 10, $maxX / 10, $totalZ / 10); // divided by 10 because it must be in cm.
				if( $totalVolumeWeight > 0 )
					$calc->addGoodsItemByVolume($totalVolumeWeight, $totalVolume);

				if( $calc->calculate() ) {
					$calcData = $calc->getResult();
				}
				else
					$calcData = $calc->getError();
				$cache->set($key, $calcData, 3600);
			}

			$data['delivery_info']['package'] = array(
				'totalSizeWeight' => $totalSizeWeight,
				'dimensions' => array($maxX, $maxY, $totalZ),
				'totalVolumeWeight' => $totalVolumeWeight,
				'volume' => $totalVolume,
			);

			if( isset($calcData['result']) ) {
				$result = $calcData['result'];
				$price = $result['price'];
				$cod = array_key_exists('cashOnDelivery', $result) ? $result['cashOnDelivery'] : null;
				$baseCur = $shop->price->get_base_currency();

				if( $baseCur != $result['currency'] ) {
					$price = $shop->price->get_converted_price_in_base_currency($price, $result['currency'], true);
					if( $cod !== null )
						$cod = $shop->price->get_converted_price_in_base_currency($cod, $result['currency'], true);
				}

				$data['order_delivery_price'] = $shop->price->get_price_in_user_currency($price);
				if( $cod !== null )
					$data['order_cod_price'] = $shop->price->get_price_in_user_currency($cod);

				$data['delivery_info']['period_min'] = $result['deliveryPeriodMin'];
				$data['delivery_info']['period_max'] = $result['deliveryPeriodMax'];
				$data['delivery_info']['date_min'] = $result['deliveryDateMin'];
				$data['delivery_info']['date_max'] = $result['deliveryDateMax'];

				if( (!$price && $result['price']) || (!$cod && array_key_exists('cashOnDelivery', $result) && $result['cashOnDelivery']) ) {
					$data['errors'][] = $core->Get_variable('error_impossible_to_calculate', null, 'pc_shop_delivery_sdek');
				}
			}
			else if( isset($calcData['error']) && is_array($calcData['error']) ) {
				foreach( $calcData['error'] as $error )
					$data['errors'][] = $error['text'];
			}
		}
		else {
			$data['errors'][] = $core->Get_variable('error_city_required', null, 'pc_shop_delivery_sdek');
		}
	}

	/**
	 * "sdek.getDeliveryForm" callback handler that is initiated via PC_core::Init_callback()
	 *
	 * @param array $params Associative array containing required function parameters
	 * - 'cart' array: An associative array containing current cart information.
	 * - 'order' array: An associative array containing current order information.
	 * - 'form_data' array: An associative array containing values of previously filled fields.
	 * @return array An associative array containing list of fields specific to the delivery method implemented by this plugin.
	 */
	function getDeliveryForm($params) {
		global $core;

		$form = array();

		$countries = $this->getCountries();
		if( count($countries) == 1 ) {
			$params['form_data']['country'] = key($countries);
		}
		else {
			$form['country'] = array(
				'label' => $core->Get_variable('country', null, 'pc_shop_delivery_sdek'),
				'type' => 'select',
				'options' => $this->getCountries(),
				'empty' => $core->Get_variable('country_empty', null, 'pc_shop_delivery_sdek'),
			);
		}

		if( v($params['form_data']['country']) )
			$form['city'] = array(
				'label' => $core->Get_variable('city', null, 'pc_shop_delivery_sdek'),
				'type' => 'select',
				'options' => $this->getCities(v($params['form_data']['country'])),
				'empty' => $core->Get_variable('city_empty', null, 'pc_shop_delivery_sdek'),
			);

		return $form;
	}

	function getCityCODLimit($cityId) {
		$this->loadData();
		return array_key_exists($cityId, self::$data['limits']) ? self::$data['limits'][$cityId] : 0;
	}

	function getCountries() {
		global $core;
		$this->loadData();
		$countryCodes = explode(',', v($core->cfg['pc_shop_delivery_sdek']['sdek_countries'], 'by,kz,ru,ua'));
		$countries = array();
		foreach( $countryCodes as $code ) {
			if( isset(self::$data['countries'][$code]) )
				$countries[$code] = self::$data['countries'][$code];
		}
		return $countries;
	}

	function getCities($countryId) {
		$this->loadData();
		return isset(self::$data['cities'][$countryId]) ? self::$data['cities'][$countryId] : array();
	}

	function loadData() {
		if( self::$data === null )
			self::$data = require(dirname(dirname(__FILE__)) . '/data/data.php');
	}
}