<?php
/**
 * @file MiniPaviCli.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Novembre 2023
 *
 * Communication avec la passerelle MiniPavi
 *
 */
 
namespace MiniPavi;
 
const VERSION = '1.0';

define('VDT_LEFT', chr(hexdec('08')));
define('VDT_RIGHT', chr(hexdec('09')));
define('VDT_DOWN', chr(hexdec('0A')));
define('VDT_UP', chr(hexdec('0B')));
define('VDT_CR', chr(hexdec('0D')));
define('VDT_CRLF', chr(hexdec('0D')).chr(hexdec('0A')));
define('VDT_CLR', chr(hexdec('0C')));
define('VDT_G0', chr(hexdec('0F')));
define('VDT_G1', chr(hexdec('0E')));
define('VDT_G2', chr(hexdec('19')));
define('VDT_POS', chr(hexdec('1F')));
define('VDT_REP', chr(hexdec('12')));
define('VDT_CURON', chr(hexdec('11')));
define('VDT_CUROFF', chr(hexdec('14')));
define('VDT_CLRLN', chr(hexdec('18')));
define('VDT_SZNORM', chr(hexdec('1B')).chr(hexdec('4C')));
define('VDT_SZDBLH', chr(hexdec('1B')).chr(hexdec('4D')));
define('VDT_SZDBLW', chr(hexdec('1B')).chr(hexdec('4E')));
define('VDT_SZDBLHW', chr(hexdec('1B')).chr(hexdec('4F')));
define('VDT_TXTBLACK', chr(hexdec('1B')).'@');
define('VDT_TXTRED', chr(hexdec('1B')).'A');
define('VDT_TXTGREEN', chr(hexdec('1B')).'B');
define('VDT_TXTYELLOW', chr(hexdec('1B')).'C');
define('VDT_TXTBLUE', chr(hexdec('1B')).'D');
define('VDT_TXTMAGENTA', chr(hexdec('1B')).'E');
define('VDT_TXTCYAN', chr(hexdec('1B')).'F');
define('VDT_TXTWHITE', chr(hexdec('1B')).'G');
define('VDT_BGBLACK', chr(hexdec('1B')).'P');
define('VDT_BGRED', chr(hexdec('1B')).'Q');
define('VDT_BGGREEN', chr(hexdec('1B')).'R');
define('VDT_BGYELLOW', chr(hexdec('1B')).'S');
define('VDT_BGBLUE', chr(hexdec('1B')).'T');
define('VDT_BGMAGENTA', chr(hexdec('1B')).'U');
define('VDT_BGCYAN', chr(hexdec('1B')).'V');
define('VDT_BGWHITE', chr(hexdec('1B')).'W');
define('VDT_BLINK', chr(hexdec('1B')).'H');
define('VDT_FIXED', chr(hexdec('1B')).'I');
define('VDT_STOPUNDERLINE', chr(hexdec('1B')).'Y');
define('VDT_STARTUNDERLINE', chr(hexdec('1B')).'Z');
define('VDT_FDNORM', chr(hexdec('1B')).'\\');
define('VDT_FDINV', chr(hexdec('1B')).']');


define('PRO_MIN',chr(hexdec('1B')).chr(hexdec('3A')).chr(hexdec('69')).chr(hexdec('45')));
define('PRO_MAJ',chr(hexdec('1B')).chr(hexdec('3A')).chr(hexdec('6A')).chr(hexdec('45')));
define('PRO_LOCALECHO_OFF',chr(hexdec('1B')).chr(hexdec('3B')).chr(hexdec('60')).chr(hexdec('58')).chr(hexdec('51')));
define('PRO_LOCALECHO_ON',chr(hexdec('1B')).chr(hexdec('3B')).chr(hexdec('61')).chr(hexdec('58')).chr(hexdec('51')));


// Touche de fonctione acceptables pour une saisie utilisateur
define('MSK_SOMMAIRE', 1);
define('MSK_ANNULATION', 2);
define('MSK_RETOUR', 4);
define('MSK_REPETITION', 8);
define('MSK_GUIDE', 16);
define('MSK_CORRECTION', 32);
define('MSK_SUITE', 64);
define('MSK_ENVOI', 128);


class MiniPaviCli {
	
	static public $uniqueId='';		// Identifiant unique de la connexion
	static public $remoteAddr='';	// IP de l'utilisateur
	static public $content=array();	// Contenu saisi
	static public $fctn='';			// Touche de fonction utilisée (ou CNX ou FIN)
	static public $urlParams='';	// Paramètres fournis lors de l'appel à l'url du service
	static public $context='';		// 65000 caractres libres d'utilisation et rappellés à chaque accès.
	static public $typeSocket;		// Type de connexion ('websocket' ou 'other')
	
	/*************************************************
	// Reçoit les données envoyées depuis MiniPavi
	**************************************************/
	
	static function start() {
		if (strpos(@$_SERVER['HTTP_USER_AGENT'],'MiniPAVI') === false) {
			echo "<!DOCTYPE html><head></head><body><h2>Cette page ne peut être appellée que via la psserelle MiniPAVI<br/><br/>This page can only be reached using the MiniPAVI gateway.</h2><hr/></body>";
			exit;
		}
		$rawPostData = file_get_contents("php://input");
		try {
			$requestData = json_decode($rawPostData,false,5,JSON_THROW_ON_ERROR);
				
			self::$uniqueId = @$requestData->PAVI->uniqueId;
			self::$remoteAddr = @$requestData->PAVI->remoteAddr;
			self::$typeSocket = @$requestData->PAVI->typesocket;
			self::$content = @$requestData->PAVI->content;
			self::$context = @$requestData->PAVI->context;
			self::$fctn = @$requestData->PAVI->fctn;
			if (isset($requestData->URLPARAMS))
				self::$urlParams = @$requestData->URLPARAMS;

		}  catch (Exception $e) {
			throw new Exception('Erreur json decode '.$e->getMessage());
		}
	}
	
	/*************************************************
	// Envoi des données à MiniPavi 
	// content: contenu de la page vdt
	// next: prochaine url à être appellée après saisie de l'utilisateur (ou déconnexion)
	// echo: active l'echo
	// commande: envoi une commande à MiniPavi
	// directcall: appel directement la prochaine url sans attendre une action utilisateur (limité à 1 utilisation à la fois)
	**************************************************/
	
	static function send($content,$next,$context='',$echo=true,$cmd=null,$directCall=false) {
		$rep['version']=VERSION;
		$rep['content']=@base64_encode($content);
		$rep['context']=mb_convert_encoding(mb_substr($context,0,65000),'UTF-8');
		if ($echo)	$rep['echo']='on';
		else $rep['echo']='off';
		if ($directCall)	$rep['directcall']='yes';
		else $rep['directcall']='no';
		
		$rep['next']=$next;
		
		if ($cmd && is_array($cmd))
			$rep = array_merge($rep,$cmd);
		$rep = json_encode($rep);
		trigger_error("[MiniPaviCli] ".$rep);
		echo $rep."\n";
	}
	
	/*************************************************
	// Cré une commande 'InputTxt' de saisie d'une ligne, validée par Envoi
	// posX: position X du champs de saisie
	// posY: position Y du champs de saisie
	// length: longueur du champs de saisie
	// validWith: valide la saisie uniquement avec les touches de fonctions indiquées
	// cursor: active l'affichage du curseur
	// spaceChar: caractère utilisée pour afficher le champs de saisie
	// char: caractère à afficher à chaque saisie (à la place du caractère saisi)
	// preFill: texte de pré-remplissage
	**************************************************/
	
	static function createInputTxtCmd($posX=1,$posY=1,$length=1,$validWith=MSK_ENVOI,$cursor=true,$spaceChar=' ',$char='',$preFill='') {
		$posX = (int)$posX;
		$posY = (int)$posY;
		$length = (int)$length;
		
		if ($posX<1 || $posX>40)
			$posX=1;
		if ($posY<0 || $posY>24)
			$posY=1;
		$maxLength = 41 - $posX;
		if ($length<1 || $length>$maxLength)
			$length=1;
		
		if (isset($preFill) && mb_strlen($preFill)>$length)
			$preFill = mb_substr($preFill,0,$length);
		else if (!isset($preFill)) $preFill='';
		
		$cmd=array();
		$cmd['COMMAND']['name']='InputTxt';
		$cmd['COMMAND']['param']['x']=$posX;
		$cmd['COMMAND']['param']['y']=$posY;
		$cmd['COMMAND']['param']['l']=$length;
		$cmd['COMMAND']['param']['char']=$char;
		$cmd['COMMAND']['param']['spacechar']=$spaceChar;
		$cmd['COMMAND']['param']['prefill']=$preFill;
		if ($cursor)
			$cmd['COMMAND']['param']['cursor']='on';
		else $cmd['COMMAND']['param']['cursor']='off';
		$cmd['COMMAND']['param']['validwith']=(int)$validWith;
		return $cmd;
	}

	
	/*************************************************
	// Cré une commande 'InputMsg' de saisie d'une ligne, validée par n'mporte quelle touche de fonction (sauf Annulation et Correction)
	// posX: position X de la zone de saisie
	// posY: position Y de la zone de saisie
	// w: longueur de la zone de saisie
	// h: hauteur de la zone de saisie
	// validWith: valide la saisie uniquement avec les touches de fonctions indiquées	
	// cursor: active l'affichage du curseur
	// spaceChar: caractère utilisée pour afficher la zone de saisie
	// preFill: tableau du texte de pré-remplissage de chaque ligne
	**************************************************/
	
	static function createInputMsgCmd($posX=1,$posY=1,$width=1,$height=1,$validWith=MSK_ENVOI,$cursor=true,$spaceChar=' ',$preFill=array()) {
		$posX = (int)$posX;
		$posY = (int)$posY;
		$width = (int)$width;
		$height = (int)$height;
		if (!is_array($preFill))
			$preFill = array();
		
		if ($posX<1 || $posX>40)
			$posX=1;
		if ($posY<1 || $posY>24)
			$posY=1;
		
		$maxWidth = 41 - $posX;
		if ($width<1 || $width>$maxWidth)
			$width=$maxWidth;
		
		$maxHeight = 25 - $posY;
		if ($height<1 || $height>$maxHeight)
			$height=$maxHeight;
		if (is_array($preFill) && count($preFill)>0) {
			array_splice($preFill, $height);
			foreach($preFill as $numLine=>$line) {
				$preFill[$numLine] = mb_substr($line,0,$width);
			}
		}
		
		$cmd=array();
		$cmd['COMMAND']['name']='InputMsg';
		$cmd['COMMAND']['param']['x']=$posX;
		$cmd['COMMAND']['param']['y']=$posY;
		$cmd['COMMAND']['param']['w']=$width;
		$cmd['COMMAND']['param']['h']=$height;
		$cmd['COMMAND']['param']['spacechar']=$spaceChar;
		$cmd['COMMAND']['param']['prefill']=$preFill;
		if ($cursor)
			$cmd['COMMAND']['param']['cursor']='on';
		else $cmd['COMMAND']['param']['cursor']='off';
		$cmd['COMMAND']['param']['validwith']=(int)$validWith;
		return $cmd;
	}


	
	/*************************************************
	// Envoi un message push en ligne "0" aux utilisateurs désignés
	// tMessage: tableau des messages à envoyer
	// tUniqueId: tableau des identifiants uniques des destinataires
	**************************************************/
	
	static function createPushServiceMsgCmd($tMessage=array(),$tUniqueId=array()) {
		if (!is_array($tMessage) || count($tMessage)<1)
			return false;
		if (!is_array($tUniqueId) || count($tUniqueId)<1)
			return false;
		$cmd=array();
		$cmd['COMMAND']['name']='PushServiceMsg';
		$cmd['COMMAND']['param']['message'] = array();
		$cmd['COMMAND']['param']['uniqueids'] = array();
		$k=0;
		foreach($tMessage as $key=>$message) {
			$cmd['COMMAND']['param']['message'][$k]=$message;
			$cmd['COMMAND']['param']['uniqueids'][$k]=$tUniqueId[$key];
			$k++;
		}
	
		return $cmd;
	}

	
	/*************************************************
	// Positionne le curseur de l'utilisateur
	**************************************************/

	static function setPos($col,$line) {
		// col : de 1 a 40
		// line : de 0 à 24
		return VDT_POS.chr(64+$line).chr(64+$col);
	}

	
	/*************************************************
	// Ecrit en ligne 0 puis le curseur revient à la position courante
	**************************************************/
	
	static function writeLine0($txt) {
		$txt = self::toG2($txt);
		$vdt = self::setPos(1,0).$txt.VDT_CLRLN."\n";
		return $vdt;
	}

	

	/*************************************************
	// Efface totalement l'écran
	**************************************************/

	static function clearScreen() {
			$vdt = self::setPos(1,0).' '.self::repeatChar(' ',39).self::setPos(1,1).VDT_CLR.VDT_CUROFF;
			return $vdt;
		
	}

	
	/*************************************************
	// Repète num fois le caractère char
	**************************************************/

	static function repeatChar($char,$num) {
		return $char.VDT_REP.chr(63+$num);
	}
	
	/*************************************************
	// Ecrit un texte centré, précédé des attributs $attr
	**************************************************/	
	
	static function writeCentered($line,$text,$attr='') {
		$vdt = self::setPos((40-mb_strlen($text))/2,$line);
		$vdt.= $attr.self::toG2($text);
		return $vdt;
	}

	/*************************************************
	// Conversion de caractères spéciaux
	**************************************************/

	static function toG2($str) {
		$str=mb_ereg_replace("’","'", $str);
		$str=preg_replace('/[\x00-\x1F\x81\x8D\x8F\x90\x9D]/', ' ', $str);

		$tabAcc=array('/é/','/è/','/à/','/ç/','/ê/','/É/','/È/','/À/','/Ç/','/Ê/',
		'/β/','/ß/','/œ/','/Œ/','/ü/','/û/','/ú/','/ù/','/ö/','/ô/','/ó/','/ò/','/ï/','/î/','/í/','/ì/','/ë/','/ä/',
		'/â/','/á/','/£/','/°/','/±/','/←/','/↑/','/→/','/↓/','/¼/','/½/','/¾/','/Â/');
		
		$tabG2=array(VDT_G2.chr(hexdec('42')).'e',
		VDT_G2.chr(hexdec('41')).'e',
		VDT_G2.chr(hexdec('41')).'a',
		VDT_G2.chr(hexdec('4B')).chr(hexdec('63')),
		VDT_G2.chr(hexdec('43')).'e',
		VDT_G2.chr(hexdec('42')).'E',
		VDT_G2.chr(hexdec('41')).'E',
		VDT_G2.chr(hexdec('41')).'A',
		VDT_G2.chr(hexdec('4B')).chr(hexdec('63')),
		VDT_G2.chr(hexdec('43')).'E',
		VDT_G2.chr(hexdec('7B')),		
		VDT_G2.chr(hexdec('7B')),		
		VDT_G2.chr(hexdec('7A')),		
		VDT_G2.chr(hexdec('6A')),		
		VDT_G2.chr(hexdec('48')).chr(hexdec('75')),		
		VDT_G2.chr(hexdec('43')).chr(hexdec('75')),		
		VDT_G2.chr(hexdec('42')).chr(hexdec('75')),		
		VDT_G2.chr(hexdec('41')).chr(hexdec('75')),		
		VDT_G2.chr(hexdec('48')).chr(hexdec('6F')),		
		VDT_G2.chr(hexdec('43')).chr(hexdec('6F')),		
		VDT_G2.chr(hexdec('42')).chr(hexdec('6F')),		
		VDT_G2.chr(hexdec('41')).chr(hexdec('6F')),		
		VDT_G2.chr(hexdec('48')).chr(hexdec('69')),		
		VDT_G2.chr(hexdec('43')).chr(hexdec('69')),		
		VDT_G2.chr(hexdec('42')).chr(hexdec('69')),		
		VDT_G2.chr(hexdec('41')).chr(hexdec('69')),		
		VDT_G2.chr(hexdec('48')).chr(hexdec('65')),		
		VDT_G2.chr(hexdec('48')).chr(hexdec('61')),		
		VDT_G2.chr(hexdec('43')).chr(hexdec('61')),		
		VDT_G2.chr(hexdec('42')).chr(hexdec('61')),
		VDT_G2.chr(hexdec('23')),		
		VDT_G2.chr(hexdec('30')),		
		VDT_G2.chr(hexdec('31')),		
		VDT_G2.chr(hexdec('2C')),		
		VDT_G2.chr(hexdec('2D')),		
		VDT_G2.chr(hexdec('2E')),		
		VDT_G2.chr(hexdec('2F')),		
		VDT_G2.chr(hexdec('3C')),		
		VDT_G2.chr(hexdec('3D')),		
		VDT_G2.chr(hexdec('3E')),
		VDT_G2.chr(hexdec('43')).'A'
		);
		
		return preg_replace($tabAcc, $tabG2, $str);	
	}
	
}
?>