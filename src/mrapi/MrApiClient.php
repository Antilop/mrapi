<?php

namespace Antilop\MrApi;

use \nusoap_client;

/**
 * 
 */
class MrApiClient
{
	use Psr\Log\LoggerAwareTrait;
	
	public static $params_client = array();
	protected static $_client;
	protected static $web_site_id = '';
	protected static $web_site_key = '';
	protected static $security_exclude = array(
		'Texte'
	);
	protected static $text_error_msg = array(
		'1' => 'Enseigne invalide',
		'2' => 'Numéro d\'enseigne vide ou inexistant',
		'3' => 'Numéro de compte enseigne invalide',
		'4' => '',
		'5' => 'Numéro de dossier enseigne invalide',
		'6' => '',
		'7' => 'Numéro de client enseigne invalide',
		'8' => 'Mot de passe ou hachage invalide',
		'9' => 'Ville non reconnu ou non unique',
		'10' => 'Type de collecte invalide',
		'11' => 'Numéro de Relais de Collecte invalide',
		'12' => 'Pays de Relais de collecte invalide',
		'13' => 'Type de livraison invalide',
		'14' => 'Numéro de Relais de livraison invalide',
		'15' => 'Pays de Relais de livraison invalide',
		'16' => '',
		'17' => '',
		'18' => '',
		'19' => '',
		'20' => 'Poids du colis invalide',
		'21' => 'Taille (Longueur + Hauteur) du colis invalide',
		'22' => 'Taille du Colis invalide',
		'23' => '',
		'24' => 'Numéro d\'expédition ou de suivi invalide',
		'25' => '',
		'26' => 'Temps de montage invalide',
		'27' => 'Mode de collecte ou de livraison invalide',
		'28' => 'Mode de collecte invalide',
		'29' => 'Mode de livraison invalide',
		'30' => 'Adresse (L1) invalide',
		'31' => 'Adresse (L2) invalide',
		'32' => '',
		'33' => 'Adresse (L3) invalide',
		'34' => 'Adresse (L4) invalide',
		'35' => 'Ville invalide',
		'36' => 'Code postal invalide',
		'37' => 'Pays invalide',
		'38' => 'Numéro de téléphone invalide',
		'39' => 'Adresse e-mail invalide',
		'40' => 'Paramètres manquants',
		'41' => '',
		'42' => 'Montant CRT invalide',
		'43' => 'Devise CRT invalide',
		'44' => 'Valeur du colis invalide',
		'45' => 'Devise de la valeur du colis invalide',
		'46' => 'Plage de numéro d\'expédition épuisée',
		'47' => 'Nombre de colis invalide',
		'48' => 'Multi-Colis Relais Interdit',
		'49' => 'Action invalide',
		'50' => '',
		'51' => '',
		'52' => '',
		'53' => '',
		'54' => '',
		'55' => '',
		'56' => '',
		'57' => '',
		'58' => '',
		'59' => '',
		'60' => 'Champ texte libre invalide (Ce code erreur n\'est pas invalidant)',
		'61' => 'Top avisage invalide',
		'62' => 'Instruction de livraison invalide',
		'63' => 'Assurance invalide',
		'64' => 'Temps de montage invalide',
		'65' => 'Top rendez-vous invalide',
		'66' => 'Top reprise invalide',
		'67' => 'Latitude invalide',
		'68' => 'Longitude invalide',
		'69' => 'Code Enseigne invalide',
		'70' => 'Numéro de Point Relais invalide',
		'71' => 'Nature de point de vente non valide',
		'72' => '',
		'73' => '',
		'74' => 'Langue invalide',
		'75' => '',
		'76' => '',
		'77' => '',
		'78' => 'Pays de Collecte invalide',
		'79' => 'Pays de Livraison invalide',
		'80' => 'Code tracing : Colis enregistré',
		'81' => 'Code tracing : Colis en traitement chez Mondial Relay',
		'82' => 'Code tracing : Colis livré',
		'83' => 'Code tracing : Anomalie',
		'84' => '(Réservé Code Tracing)',
		'85' => '(Réservé Code Tracing)',
		'86' => '(Réservé Code Tracing)',
		'87' => '(Réservé Code Tracing)',
		'88' => '(Réservé Code Tracing)',
		'89' => '(Réservé Code Tracing)',
		'90' => '',
		'91' => '',
		'92' => '',
		'93' => 'Aucun élément retourné par le plan de tri',
		'94' => 'Colis Inexistant',
		'95' => 'Compte Enseigne non activé',
		'96' => 'Type d\'enseigne incorrect en Base',
		'97' => 'Clé de sécurité invalide',
		'98' => 'Erreur générique (Paramètres invalides)',
		'99' => 'Erreur générique du service',
	);
	
	public function __construct($site_id, $site_key) {
		date_default_timezone_set('Europe/Paris');
		$client = new nusoap_client("http://api.mondialrelay.com/Web_Services.asmx?WSDL", true);
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
		$response_service = $this->_client->call(
			$method_name,
			$this->params_client,
			'http://api.mondialrelay.com/',
			'http://api.mondialrelay.com/'.$method_name
		);
		
		if ($this->logger) {
			$this->logger->notice('We sent something');
		}
		
		$check_error = $this->checkErrorCall();
		if ($check_error != '') {
			return $check_error;
		}
		
		return $response_service[$method_name.'Result'];
	}
	
	protected function checkErrorCall()
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
	
	protected function msgErrorResponseService($code)
	{
		return $this->text_error_msg[$code];
	}
	
	public function getPointRelais($params)
	{
		$this->setParamsClient($params);
		$response_service = $this->callMethod('WSI3_PointRelais_Recherche');
		
		if (is_array($response_service)) {
			$code_stat = $response_service['STAT'];
			if ($code_stat === '0') {
				$result = $response_service['PointsRelais'];
			} else {
				$result = array('error' => $this->msgErrorResponseService($code_stat));
			}
		} else {
			$result = array('error' => $response_service);
		}
		
		return $result;
	}
	
	public function getEtiquette($params)
	{
		$this->setParamsClient($params);
		$response_service = $this->callMethod('WSI2_CreationEtiquette');
		
		if (is_array($response_service)) {
			$code_stat = $response_service['STAT'];
			if ($code_stat === '0') {
				$result['ExpeditionNum'] = $response_service['ExpeditionNum'];
				$result['URL_Etiquette'] = str_replace('/ww2', 'http://www.mondialrelay.com', $response_service['URL_Etiquette']);
			} else {
				$result = array('error' => $this->msgErrorResponseService($code_stat));
			}
		} else {
			$result = array('error' => $response_service);
		}
		
		return $result;
	}
	
	public function getTracingColis($params)
	{
		$this->setParamsClient($params);
		$response_service = $this->callMethod('WSI2_TracingColisDetaille');
		
		if (is_array($response_service)) {
			$code_stat = $response_service['STAT'];
			$libelle = $response_service['Libelle01'];
			if ($libelle != '') {
				$result = $response_service;
			} else {
				$result = array('error' => $this->msgErrorResponseService($code_stat));
			}
		} else {
			$result = array('error' => $response_service);
		}
		
		return $result;
	}
}