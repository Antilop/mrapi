<?php

namespace Antilop\MrApi;

use \nusoap_client;
use \Psr\Log\LoggerAwareTrait;

/**
 * 
 */
class MrApiClient
{
	use LoggerAwareTrait;
	
	public static $params_client = array();
	protected static $_client;
	protected static $web_site_id = '';
	protected static $web_site_key = '';
	protected static $security_exclude = array(
		'Texte'
	);
	protected static  $url_ws;

	public function __construct($url_ws, $site_id, $site_key) {
		date_default_timezone_set('Europe/Paris');
		$this->url_ws = $url_ws;
		$client = new nusoap_client($this->url_ws.'Web_Services.asmx?WSDL', true);
		$client->soap_defencoding = 'utf-8';
		
		$this->_client = &$client;
		$this->web_site_id = $site_id;
		$this->web_site_key = $site_key;
	}
	
	protected function setParamsClient($params)
	{
		$this->params_client = array_merge(array('Enseigne'=>$this->web_site_id),$params);
		$this->addKeySecurity();
	}
	
	protected function addKeySecurity()
	{
		$tmp = array_filter(array_keys($this->params_client), function($k) { return !in_array($k, self::$security_exclude); });
		$params = array_intersect_key($this->params_client, array_flip($tmp));
		$code = implode('', $params);
		$code .= $this->web_site_key;
		$this->params_client["Security"] = strtoupper(md5($code));
	}
	
	protected function callMethod($method_name)
	{
		$response = $this->_client->call(
			$method_name,
			$this->params_client,
			$this->url_ws,
			$this->url_ws.$method_name
		);
		
		if ($this->logger) {
			$call_message = array_merge(array('callMethod' => $method_name), $this->params_client);
			$call_message = serialize($call_message);
			$code_status = array();
			if (isset($response[$method_name.'Result']['STAT'])) {
				$code_status = array('code_status' => $response[$method_name.'Result']['STAT']);
			}
			$this->logger->notice($call_message, $code_status);
		}
		
		$check_error = $this->checkError();
		if ($check_error != '') {
			if ($this->logger) {
				$call_message = $check_error;
				$this->logger->error($call_message);
			}
			return $check_error;
		}
		
		return $response[$method_name.'Result'];
	}
	
	protected function checkError()
	{
		$rc = '';
		if ($this->_client->fault) {
			$rc = 'Fault (Expect - The request contains an invalid SOAP body)';
		} else {
			$err = $this->_client->getError();
			if ($err) {
				$rc = 'Error : ' . $err;
			}
		}
		
		return $rc;
	}
	
	protected function getErrorMsg($code_error)
	{
		$params = array(
			'STAT_ID' => $code_error,
			'Langue' => 'FR',
		);
		$this->setParamsClient($params);
		$response = $this->callMethod('WSI2_STAT_Label');
		
		return array('error'=>$response);
	}
	
	public function getPointRelais($params)
	{
		$this->setParamsClient($params);
		$response = $this->callMethod('WSI3_PointRelais_Recherche');
		
		if (is_array($response)) {
			$code_stat = $response['STAT'];
			if ($code_stat === '0') {
				$result = $response['PointsRelais'];
			} else {
				$result = $this->getErrorMsg($code_stat);
			}
		} else {
			$result = array('error' => $response);
		}
		
		return $result;
	}
	
	public function getEtiquette($params)
	{
		$this->setParamsClient($params);
		$response = $this->callMethod('WSI2_CreationEtiquette');
		
		if (is_array($response)) {
			$code_stat = $response['STAT'];
			if ($code_stat === '0') {
				$result['ExpeditionNum'] = $response['ExpeditionNum'];
				$result['URL_Etiquette_A4'] = 'http://www.mondialrelay.com'.$response['URL_Etiquette'];
				$result['URL_Etiquette_A5'] = 'http://www.mondialrelay.com'.str_replace('format=A4', 'format=A5', $response['URL_Etiquette']);
				$result['URL_Etiquette_10x15'] = 'http://www.mondialrelay.com'.str_replace('format=A4', 'format=10x15', $response['URL_Etiquette']);
			} else {
				$result = $this->getErrorMsg($code_stat);
			}
		} else {
			$result = array('error' => $response);
		}
		
		return $result;
	}
	
	public function getTracingColis($params)
	{
		$this->setParamsClient($params);
		$response = $this->callMethod('WSI2_TracingColisDetaille');
		
		if (is_array($response)) {
			$code_stat = $response['STAT'];
			$libelle = $response['Libelle01'];
			if (in_array($code_stat, array('80','81','82'))) {
				$result = $response;
			} else {
				$result = $this->getErrorMsg($code_stat);
			}
		} else {
			$result = array('error' => $response);
		}
		
		return $result;
	}
}