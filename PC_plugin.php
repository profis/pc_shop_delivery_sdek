<?php
/**
 * @var PC_core $core
 */

Register_class_autoloader('SdekPlugin', dirname(__FILE__).'/classes/SdekPlugin.php');
Register_class_autoloader('CalculatePriceDeliveryCdek', dirname(__FILE__).'/classes/CalculatePriceDeliveryCdek.php');

$pluginInstance = $core->Get_object('SdekPlugin');
$core->Register_callback('sdek.calculateDeliveryPrice', array($pluginInstance, 'calculateCartPrice'));
$core->Register_callback('sdek.getDeliveryForm', array($pluginInstance, 'getDeliveryForm'));
