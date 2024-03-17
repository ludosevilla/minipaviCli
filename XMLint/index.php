<?php
/**
 * @file index.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Février 2024
 *
 * XMLint: Script d'interpretation XML videotex pour MiniPavi
 * 
 */

require "../lib/MiniPaviCli.php";	// A modifier
require "XMLfunctions.php";

define('XMLINT_VER', '1.0');

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR);
ini_set('display_errors',0);
libxml_use_internal_errors(true);

$startXml = false;	// A true si début d'interpretation d'une page
$vdt='';			// Contenu à envoyer au Minitel de l'utilisateur
$cmd=null;			// Commande à executer au niveau de MiniPAVI
$directCall = false;


try {
	MiniPavi\MiniPaviCli::start();

	if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
		// Initialisation

		$step = 0;
		$context = array();
		MiniPavi\MiniPaviCli::$content=array();
		trigger_error("[XMLint] CNX");

		// on peut avoir un paramètre xurl avec l'url du fichier XML, sinon on la demande

		$context['url'] = @MiniPavi\MiniPaviCli::$urlParams->xurl;
		
		$url=filter_var($context['url'], FILTER_VALIDATE_URL);
		if (!$url) 	$step = 0;	// Demande une url
		else $step =10;	// Validation url
		
		$vdt= MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
		$vdt.= MiniPavi\MiniPaviCli::writeLine0("Interpreteur XML MiniPAVI ".XMLINT_VER);

	} else {
		
		$context = unserialize(MiniPavi\MiniPaviCli::$context);		// Récupération du contexte		
		
		if (array_key_exists('validxml',$context)) {
			$objXML = simplexml_load_string($context['validxml'],null,LIBXML_NOCDATA|LIBXML_NOBLANKS);
			if ($objXML !== false) {
				$step=100;		// interprétation du XML				
			} else
				$step=(int)@MiniPavi\MiniPaviCli::$urlParams->step;
		} else
			$step=(int)@MiniPavi\MiniPaviCli::$urlParams->step;
		
	}

	
	if (MiniPavi\MiniPaviCli::$fctn == 'FIN' || MiniPavi\MiniPaviCli::$fctn == 'FCTN?') {
			// Deconnexion
			trigger_error("[XMLint] DECO");
			exit;
	}
	
	
	
	while(true) {
		switch ($step) {
			case 0:
				// Accueil
				$vdt.= MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('fond.vdt');
				$vdt.= MiniPavi\MiniPaviCli::setPos(6,2);
				$vdt.= VDT_BGBLUE.VDT_TXTWHITE.' INTERPRETEUR XML MINIPAVI '.XMLINT_VER;

				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,6);
				$vdt.="URL du fichier XML (http://..)";
				
				
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(14,"Toutes les infos sont disponibles sur",VDT_TXTMAGENTA);	
				$vdt.=MiniPavi\MiniPaviCli::writeCentered(15,"www.minipavi.fr",VDT_TXTWHITE);	
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(21,24);
				$vdt.=VDT_BGBLUE.VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(" Valider ").VDT_STARTUNDERLINE.' '.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_STOPUNDERLINE.' '.VDT_CLRLN;
				
				
			case 1:
			
				$cmd=MiniPavi\MiniPaviCli::createInputMsgCmd(1,8,40,2,MSK_ENVOI|MSK_REPETITION,true,'.',MiniPavi\MiniPaviCli::$content);
				$context['url']='';
				$context['validxml']='';
				$context['currentpage']='';
				$step = 2;
				break 2;
				
			case 2:
			
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {			
					$context['url']='';
					$step = 0;
					break;
			
				}
				foreach(@MiniPavi\MiniPaviCli::$content as $val) 
					@$context['url'].=$val;
			
				if (strlen($context['url'])<10) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0("Saisissez une adresse http");
					$context['url']='';
					$step = 1;
					break;
				}
				
				trigger_error("[XMLint] urlXML = ".$context['url']);
				$url=filter_var($context['url'], FILTER_VALIDATE_URL);
				if (!$url) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0("Syntaxe de l'url incorrecte");
					$context['url']='';
					$step = 1;
					break;
				}
			
				$step =10;
				break;
				
				
				
			case 10:
				// Validation de l'url fournie dans $context['url'];
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL,$url );
				$xmlStr = curl_exec($ch);
				curl_close($ch);
				
				$objXML = simplexml_load_string($xmlStr,null,LIBXML_NOCDATA|LIBXML_NOBLANKS);

				$xml = explode("\n", $xmlStr);

				if ($objXML === false) {
					// Ce n'est pas du XML...
					
					$vdt=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #001'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Format XML invalide').VDT_CLRLN;	
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,3);
					
					$errors = libxml_get_errors();
					$cnt = 0;
					foreach ($errors as $error) {
						$vdt.=display_xml_error($error, $xml);
						$cnt++;
						if ($cnt == 4) break;
					}

					libxml_clear_errors();
					
					if (count($errors)>4) {
							$vdt.=VDT_CRLF.VDT_FDINV.MiniPavi\MiniPaviCli::toG2(" Et ".(count($errors)-4)." erreurs supplémentaires...").VDT_FDINV;
					}
					$context['url']='';
					$step = 0;
					
					break 2;
				}
				
				if ($objXML->getName() != 'service') {
					$vdt=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #002'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Element "service" introuvable').VDT_CLRLN;	
					break 2;
				}
				
				// La syntaxe est correcte
				
				if (!$objXML->debut['nom'] || trim($objXML->debut['nom'])=='') {
					$vdt=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #003'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Aucun élément "debut" valide').VDT_CLRLN;	
					break 2;
				}
				
				$context['validxml'] = $xmlStr;
				$context['currentpage'] = (string)$objXML->debut['nom'];
				$startXml = true;									// Début du service
				
				
			case 100:
				// Intepretation du XML

				$page = getPage($objXML,$context['currentpage']);
				
				if (!$page) {
					// Page courante introuvable
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #004'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Page ['.$context['currentpage'].'] indéfinie').VDT_CLRLN;	
					$context['validxml'] = '';
					$context['currentpage'] = '';
					break 2;
				}

				$ecran = getPageElement($page,'ecran');				
				$entree = getPageElement($page,'entree');				
				$action = getPageElement($page,'action');	
				
				if (!$ecran || !$entree || !$action) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #005'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Page ['.$context['currentpage'].'] incomplet').VDT_CLRLN;	
					$context['validxml'] = '';
					$context['currentpage'] = '';
					break 2;
				}


				// Si $startXML == true, on interprète les elements "ecran" et "entree", sinon "action"
				
				if ($startXml) {
					$r = processEcranElement($ecran,$vdt);
					
					if (!$r) {
						$context['validxml'] = '';
						$context['currentpage'] = '';
						break 2;
					}
					
					$r = processEntreeElement($entree,$cmd,$vdt);
					
					if (!$r) {
						$context['validxml'] = '';
						$context['currentpage'] = '';
						break 2;
					}
					
					break 2;
				}
				
				$r = processActionElement($action,trim(@MiniPavi\MiniPaviCli::$content[0]),MiniPavi\MiniPaviCli::$fctn,$next,$l0,$vdt);

				if (!$r) {
					$context['validxml'] = '';
					$context['currentpage'] = '';
					break 2;
				}
				
				if ($next != '' ) {
					$context['currentpage'] = $next;
					$startXml = true;
					break;
				}
				$vdt=MiniPavi\MiniPaviCli::writeLine0($l0);
				$r = processEntreeElement($entree,$cmd,$vdt);
				
				if (!$r) {
					$context['validxml'] = '';
					$context['currentpage'] = '';
					break 2;
				}
				
				break 2;				

		}
	}
	
	
	
	// Url à appeller lors de la prochaine saisie utilisateur (ou sans attendre si directCall=true)
	if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
		$prot='https';
	} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
		$prot='https';
	} elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
		$prot='https';
	} elseif (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443) {
		$prot='https';
	} else
		$prot='http';

	$nextPage=$prot."://".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'].'?step='.$step;;

	MiniPavi\MiniPaviCli::send($vdt,$nextPage,serialize($context),true,$cmd,$directCall);
} catch (Exception $e) {
	throw new Exception('Erreur MiniPavi '.$e->getMessage());
}
exit;
?>
