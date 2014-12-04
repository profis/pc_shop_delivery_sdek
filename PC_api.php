<?php
/**
 * @var PC_core $core
 * @var PC_site $site
 * @var PC_database $db
 * @var PC_routes $routes
 */

switch( $routes->Get_last() ) {
	case 'getCityList':
		$sdek = new SdekPlugin();
		$countries = $sdek->getCountries();
		if( !isset($_REQUEST['countryId']) || !isset($countries[$_REQUEST['countryId']]) ) {
			@header('HTTP/1.1 404 File Not Found', true, 404);
			exit;
		}

		if( isset($_REQUEST['lang']) && preg_match('#^[a-z]{2}$#', $_REQUEST['lang']) )
			$site->ln = $_REQUEST['lang'];

		header('Content-Type: application/json');
		echo '[';
		$idx = 0;
		foreach( $sdek->getCities($_REQUEST['countryId']) as $k => $v ) {
			if( $idx++ )
				echo ',';
			echo json_encode(array(
				'id' => $k,
				'name' => $v,
			));
		}
		echo ']';
		break;
}