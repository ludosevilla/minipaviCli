<?php
/**
 * @file miniChat.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Novembre 2023
 *
 * Script de service de dialogue en direct basique pour Minitel via passerelle MiniPAVI
 * A des fins de démonstration
 * 
 */

require "../lib/MiniPaviCli.php";// A MODIFIER
require "miniChatFunctions.php";

const MINICHAT_MAXCONN = 100;	// Nombre de connectés maximum
const MINICHAT_NUMPARPAGE = 16;	// Nombre de connectés apparaissant sur chaque page
const MINICHAT_TIMEOUT = 900;	// Temps maximum sans action avant d'être supprimé du chat

// Attention à autoriser le script en lecture/écriture (et création) sur ces fichiers !!
const MINICHAT_PATH_MSGLIST = 'messages.list';	// Fichier de la liste des message
const MINICHAT_PATH_CNXLIST = 'connected.list';	// Fichier de la liste des connectés
//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR);
ini_set('display_errors',0);



try {
	
	
	
	
	MiniPavi\MiniPaviCli::start();

	if (MiniPavi\MiniPaviCli::$fctn == 'CNX') {
		$step = 0;
		$context = array();
		MiniPavi\MiniPaviCli::$content=array();
		trigger_error("[MiniChat] CNX");
	} else {
		setStep((int)@MiniPavi\MiniPaviCli::$urlParams->step);		// Etape du script à executer, indiqué dans le paramètre 'url' de la requête http
		$context = unserialize(MiniPavi\MiniPaviCli::$context);		// Récupération du contexte utilisateur
	}

	
	if (MiniPavi\MiniPaviCli::$fctn == 'FIN' || MiniPavi\MiniPaviCli::$fctn == 'FCTN?') {
			// Deconnexion (attention, bug peut être appellé plusieurs fois de suite..)
			trigger_error("[MiniChat] DECO");
			removeConnected(MiniPavi\MiniPaviCli::$uniqueId);
			exit;
	}
	
	$found=updateLastAction(MiniPavi\MiniPaviCli::$uniqueId);		// On garde le timestamp du dernier accès, pour faire un nettoyage de temps en temps, au cas ou.
	if (!$found && $step>20) {	// L'utilisateur n'est pas dans la liste des connectés et est sur une étape après l'identification: on le renvoi au début
		$step = 0;
		$context=array();
	}
	chatClean();	// Nettoyage des vieilles connexions éventuelles
	
	$vdt='';		// Contenu à envoyer au Minitel de l'utilisateur
	$cmd=null;		// Commande à executer au niveau de MiniPAVI
	while(true) {
		switch ($step) {
			case 0:
				// Accueil: affichage partie fixe
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('MiniChatAcc.vdt');
				$vdt.=MiniPavi\MiniPaviCli::setPos(2,18);
				$vdt.=VDT_TXTWHITE.'Pseudonyme:..........';
				$vdt.=' '.VDT_FDINV.' Suite '.VDT_FDNORM;
				$vdt.=MiniPavi\MiniPaviCli::setPos(2,19);
				$vdt.=VDT_TXTBLUE.MiniPavi\MiniPaviCli::toG2('Nom sous lequel vous apparaissez');
				
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(2,21);
				$vdt.=VDT_TXTWHITE.'CV:......................';
				$vdt.=MiniPavi\MiniPaviCli::setPos(2,22);
				$vdt.=VDT_TXTBLUE.MiniPavi\MiniPaviCli::toG2('Court texte de présentation');
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,23);
				$vdt.=VDT_TXTRED;
				$vdt.=MiniPavi\MiniPaviCli::repeatChar('_',40);
				$vdt.=MiniPavi\MiniPaviCli::setPos(25,24);
				$vdt.=VDT_TXTWHITE.'Valider ';
				$vdt.=VDT_TXTBLACK.VDT_BGGREEN.' Envoi '.VDT_TXTWHITE;
				setStep(1);
				
				break;		// On continue le script
			case 1:
				// Accueil: initialisation de la zone de saisie du pseudo
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(13,18,10,MSK_ENVOI|MSK_SUITE,true,'.','',@$context['pseudo']);
				setStep(10);
				$directCall=false;
				break 2;	// On arrête le script et on attend une saisie utilisateur ($directCall = false)
			case 10:
				// Accueil: traitement de la saisie de la touche de fonction pourt la 1ere zone de saisie (pseudo)
				
				
				$context['pseudo']=@MiniPavi\MiniPaviCli::$content[0];
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
					// initialisation de la zone de saisie du cv
					$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(5,21,22,MSK_ENVOI|MSK_RETOUR,true,'.','',@$context['cv']);
					setStep(11);
					$directCall=false;
					break 2;
				}
				// C'est donc ENVOI : traitement de la saisie des 2 zones (pseudo + cv)
				setStep(20);
				break;
			case 11:
				// Accueil: traitement de la saisie de la touche de fonction pourt la 2ème zone de saisie (cv)
				$context['cv']=@MiniPavi\MiniPaviCli::$content[0];
				if (MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
					// Retour à la zone de saisie du pseudo
					setStep(1);
					break;
				}
				// C'est donc ENVOI : traitement de la saisie des 2 zones (pseudo + cv)
				// Les 2 lignes suivantes sont inutiles car l'étape 20 suit, mais pour plus de compréhension on les met.
				setStep(20);
				break;
			case 20:
				// Accueil: traitement de la saisie du contenu des zones de saisie
				if (strlen(@$context['pseudo'])<3) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Votre pseudo doit faire 3 car. min.');
					setStep(1);
					break;
				}
				$tCnx = getConnectedList($numCnx);
				
				if ($numCnx>=MINICHAT_MAXCONN) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Le chat est déjà plein ! Désolé !');
					setStep(1);
					break;
					
				}
				foreach($tCnx as $cnx) {
					if (@$cnx['id'] != '' && @$cnx['name'] == trim($context['pseudo'])) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Ce pseudo est déjà pris !');
						setStep(1);
						break 2;
					}
				}
				addConnected(MiniPavi\MiniPaviCli::$uniqueId,$context['pseudo'],@$context['cv']);
				trigger_error("[MiniChat] ".($numCnx+1)." connecte(s)");
				setStep(21);
				break;
			case 21:
				// Liste des connectés : affichage partie fixe
				$context['page']=0;
				$context['pagestartindex']=0;
				$context['linetoclear']=MINICHAT_NUMPARPAGE;
				$vdt= MiniPavi\MiniPaviCli::clearScreen();
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,2).VDT_TXTYELLOW.VDT_BGBLUE.VDT_SZDBLH.' Le Dialogue en Direct !'.VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,4).VDT_TXTBLACK.VDT_BGGREEN.VDT_SZNORM.' Num.|    Pseudo|CV'.VDT_CLRLN.VDT_TXTWHITE; 
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,21).VDT_BGBLUE.' '.VDT_CLRLN;
				$vdt.=VDT_CRLF.VDT_TXTWHITE.VDT_BGBLACK.MiniPavi\MiniPaviCli::toG2('Ecrire : numéro .. +').VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE;
				$vdt.=VDT_CRLF.VDT_TXTWHITE.'Lire vos messages   '.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE.VDT_BGBLACK;
				$vdt.=VDT_CRLF.VDT_TXTWHITE.'Pages'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Suite '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE.VDT_BGBLACK;				
				$vdt.=VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Retour '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE;								
				$vdt.=' Sortir'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Sommaire '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE;				
				setStep(22);				
				
				break;
			case 22:
				// Liste des connectés : affichage partie variable (liste des connectés)
				$vdt.=VDT_CUROFF;
				
				for($i=0;$i<$context['linetoclear'];$i++) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,5+$i).MiniPavi\MiniPaviCli::repeatChar(' ',5).'|'.MiniPavi\MiniPaviCli::repeatChar(' ',10).'| '.VDT_CLRLN; 
				}
			
				
				$tCnx = getConnectedList($numCnx,true);
				
				if ($numCnx<2) 
					$txt="Vous êtes seul".VDT_CLRLN;
				else $txt="$numCnx connectés!".VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(26,1).MiniPavi\MiniPaviCli::toG2($txt);
				$vdt.=MiniPavi\MiniPaviCli::setPos(26,2).'Page '.($context['page']+1).'/'.ceil(($numCnx/MINICHAT_NUMPARPAGE));
				

				$start = MINICHAT_NUMPARPAGE * $context['page'];
				for($i=0;$i<$start;$i++) {
					next($tCnx);
				}
				
				$j=0;
				
				do {
					if (($cnx = current($tCnx)) === false)
						break;
					$i = key($tCnx);
					if ($cnx['id'] == MiniPavi\MiniPaviCli::$uniqueId)
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,5+$j).VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(sprintf('%5d',($i+1))).'|'.VDT_TXTRED.MiniPavi\MiniPaviCli::toG2(sprintf('%10s',ucfirst(strtolower($cnx['name'])))).VDT_TXTWHITE.'|'.VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(ucfirst(strtolower(sprintf('%-22s',$cnx['cv'])))).VDT_CLRLN;
					else
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,5+$j).VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(sprintf('%5d',($i+1))).'|'.MiniPavi\MiniPaviCli::toG2(sprintf('%10s',ucfirst(strtolower($cnx['name'])))).'|'.VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2(ucfirst(strtolower(sprintf('%-22s',$cnx['cv'])))).VDT_CLRLN;
					$j++;
					next($tCnx);
					if ($j==MINICHAT_NUMPARPAGE) {
						break;
					}
				} while(true);
				$toclear = $context['linetoclear']-$j;
				
				$context['linetoclear']=$j;
				
				for(;$j<$toclear;$j++)
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,5+$j).MiniPavi\MiniPaviCli::repeatChar(' ',5).'|'.MiniPavi\MiniPaviCli::repeatChar(' ',10).'|'.MiniPavi\MiniPaviCli::repeatChar(' ',22).VDT_CLRLN;
				setStep(23);
				
				break;
			case 23:
				// Liste des connectés : création de la zone de saisie et affichage nombre de messages en attente
				$tMsg = getMsgFor(@MiniPavi\MiniPaviCli::$uniqueId);
				$numMsg = count($tMsg);
				if ($numMsg<1) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,21).VDT_TXTCYAN.VDT_BGBLUE.' Aucun message en attente'.VDT_CLRLN;
				} else {
					if ($numMsg == 1)
						$s=''; else $s='s';
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,21).VDT_TXTCYAN.VDT_BGBLUE." $numMsg message$s en attente".VDT_CLRLN;
				}
				
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(17,22,2,MSK_ENVOI|MSK_SUITE|MSK_RETOUR|MSK_REPETITION|MSK_SOMMAIRE,true,'.','','');
				setStep(24);
				$directCall=false;
				break 2;
			case 24:
				// Liste des connectés : traitement de l'entrée utilisateur
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					// Sortie du chat
					removeConnected(MiniPavi\MiniPaviCli::$uniqueId);
					setStep(0);
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					// Réaffichage de la partie variable de la page
					$vdt=MiniPavi\MiniPaviCli::writeLine0(' ');
					setStep(22);
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
					// Passage à la page suivante si possible
					$tCnx = getConnectedList($numCnx);
					if (($context['page']+1)*MINICHAT_NUMPARPAGE >= $numCnx) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Dernière page atteinte!');
						setStep(22); // Et on rafraichit la liste
						break;

					}
					$context['page']++;
					$vdt=MiniPavi\MiniPaviCli::writeLine0('');
					setStep(22);					
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
					// Passage à la page précédente si possible
					$tCnx = getConnectedList($numCnx);
					if (($context['page']-1)<0) {
						$vdt=MiniPavi\MiniPaviCli::writeLine0('Première page atteinte!');
						setStep(22);	// Et on rafraichit la liste
						break;
					}
					$context['page']--;
					$vdt=MiniPavi\MiniPaviCli::writeLine0('');
					setStep(22);
					break;
				}
				if (trim(@MiniPavi\MiniPaviCli::$content[0])=='') {
					// ENVOI seul: lecture des messages
					setStep(50);
					break;
				}
				$numDest = (int)@MiniPavi\MiniPaviCli::$content[0];
				if ($numDest<=0) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Saisie incorrecte!');
					setStep(23);					
					break;
				}
				$numDest--;
				$tCnx = getConnectedList($numCnx,true);
				if (!isset($tCnx[$numDest]) || $tCnx[$numDest]['id']=='') {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Destinataire inconnu!');
					setStep(23);
					break;
				}
				$context['sendiddest'] = $tCnx[$numDest]['id'];
				$context['sendnamedest'] = $tCnx[$numDest]['name'];
				unset($context['sendidmsg']);
				setStep(60);				
				break;
				
				
			case 50:
				// Lecture des messages: Affichage partie fixe
				$tMsg = getMsgFor(@MiniPavi\MiniPaviCli::$uniqueId);
				if (count($tMsg)<1) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Aucun message en attente!');
					if (@$context['frommsgsend']==1) {
						setStep(21);
						unset($context['frommsgsend']);
					} else
						setStep(23);
					break;
					
				}

				$vdt= MiniPavi\MiniPaviCli::clearScreen();
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,1).VDT_TXTYELLOW.VDT_BGBLUE.' Message de '.VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,8).VDT_TXTYELLOW.VDT_BGBLUE.' Vous lui aviez dit :'.VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,15).VDT_TXTYELLOW.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Votre réponse :').VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,22).VDT_BGBLUE.' '.VDT_CLRLN;
				$vdt.=VDT_CRLF.VDT_TXTWHITE.VDT_BGBLACK.'Changer de ligne'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Suite '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE;			
				$vdt.=VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Retour '.VDT_FDNORM.VDT_STOPUNDERLINE.VDT_TXTWHITE.' ';
				$vdt.=VDT_CRLF.VDT_TXTWHITE.'Envoi message'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_STOPUNDERLINE;
				$vdt.=VDT_TXTWHITE.' Sortir'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Sommaire '.VDT_FDNORM.VDT_STOPUNDERLINE;

				setStep(51);				
				break;
			case 51:
				// Lecture des messages: Affichage du message
				$vdt.=VDT_CUROFF.VDT_TXTWHITE;
				$vdt.=MiniPavi\MiniPaviCli::setPos(13,1);
				$vdt.=MiniPavi\MiniPaviCli::toG2(ucfirst(strtolower($tMsg[0]['nameexp'])).' à ').date('H:i');
				
				foreach (@$tMsg[0]['content'] as $k=>$line) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,2+$k);
					$vdt.=MiniPavi\MiniPaviCli::toG2($line);
				}
				
				$vdt.=VDT_CUROFF;
				
				if (is_array($tMsg[0]['prevcontent']) && count($tMsg[0]['prevcontent'])>0) {
					foreach (@$tMsg[0]['prevcontent'] as $k=>$line) {
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,9+$k);
						$vdt.=VDT_TXTMAGENTA.MiniPavi\MiniPaviCli::toG2($line);
					}
				}
				
				$context['sendidmsg']=$tMsg[0]['idmsg'];	// On garde l'identifiant du message
				unset($context['sendiddest']);				// Suppression de l'éventuelle id destinataire initialisé lors de l'envoi direct d'un message

				setStep(52);				
				break;
			case 52:
				// Lecture-écriture des messages : création de la zone de saisie
				$cmd=MiniPavi\MiniPaviCli::createInputMsgCmd(1,16,40,6,MSK_ENVOI|MSK_SOMMAIRE,true,' ');
				setStep(53);
				$directCall=false;
				break 2;
			case 53:
				// Lecture-écriture des messages : traitement de la saisie utilisateur
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					// Abandon de saisie, retour à la liste
					unset($context['sendidmsg']);
					unset($context['sendiddest']);
					setStep(21);
					break;
				}
				
				if (isset($context['sendidmsg'])) {
					// Réponse à un message
					$tMsg = getMsgFor(0,$context['sendidmsg']);
					if (count($tMsg)!=1) {
						// Message à disparu, ne devrait pas arriver
						unset($context['sendidmsg']);
						setStep(21);
						break;
					}
					delMsg($tMsg[0]['idmsg']);
					
					
					if (trim(implode(@MiniPavi\MiniPaviCli::$content)!='')) {
						// Le message n'est pas vide
						setMsg(@MiniPavi\MiniPaviCli::$uniqueId,$tMsg[0]['idexp'],$context['pseudo'],@MiniPavi\MiniPaviCli::$content,$tMsg[0]['content']);
						// On prévient le destinataire						
						$cmd = MiniPavi\MiniPaviCli::createPushServiceMsgCmd(array('Vous avez recu un message!'),array($tMsg[0]['idexp']));
					}
				} else {
					// Envoi d'un nouveau message
					setMsg(@MiniPavi\MiniPaviCli::$uniqueId,$context['sendiddest'],$context['pseudo'],@MiniPavi\MiniPaviCli::$content);					
					// On prévient le destinataire
					$cmd = MiniPavi\MiniPaviCli::createPushServiceMsgCmd(array('Vous avez recu un message!'),array($context['sendiddest']));
				}
				
				
				

				if (isset($context['sendidmsg'])) {
					// La saisie du message fait suite à la lecture d'un message reçu: on passa au message suivant
					unset($context['sendidmsg']);
					unset($context['sendiddest']);
					setStep(50);
					$directCall=true;
					$context['frommsgsend']=1;
					// On ne pars pas directement à l'étape 50 (message suivant) car autrement la commande d'envoi push ne partira pas
					// On envoi donc à MiniPavi avec demande d'appel direct de l'url suivante (sans attendre de saisie utilisateur)
					break 2;
					
				} else {
					// La saisie du message fait suite à la création d'un nouveau message: on retourne à la liste
					unset($context['sendiddest']);
					unset($context['sendnamedest']);
					setStep(21);
					$directCall=true;
					// On ne pars pas directement à l'étape 21 (liste) car autrement la commande d'envoi push ne partira pas
					// On envoi donc à MiniPavi avec demande d'appel direct de l'url suivante (sans attendre de saisie utilisateur)
					break 2;
				}

			case 60:
				// Envoi d'un nouveau messages: Affichage partie fixe
				$vdt= MiniPavi\MiniPaviCli::clearScreen();
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,15).VDT_TXTYELLOW.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Message à : '.ucfirst(strtolower($context['sendnamedest'])) ).VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,22).VDT_BGBLUE.' '.VDT_CLRLN;
				$vdt.=VDT_CRLF.VDT_TXTWHITE.VDT_BGBLACK.'Changer de ligne'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Suite '.VDT_FDNORM.VDT_STOPUNDERLINE;			
				$vdt.=VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Retour '.VDT_FDNORM.VDT_STOPUNDERLINE.' ';
				$vdt.=VDT_CRLF.VDT_TXTWHITE.'Envoi message'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Envoi '.VDT_FDNORM.VDT_STOPUNDERLINE;
				$vdt.=VDT_TXTWHITE.' Sortir'.VDT_STARTUNDERLINE.' '.VDT_TXTGREEN.VDT_FDINV.' Sommaire '.VDT_FDNORM.VDT_STOPUNDERLINE;

				setStep(52);	// La saisie d'un mouveau message est commune avec la réponse à un message
				break;
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

	$nextPage=$prot."://".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'].'?step='.$step;

	MiniPavi\MiniPaviCli::send($vdt,$nextPage,serialize($context),true,$cmd,$directCall);
	
} catch (Exception $e) {
	throw new Exception('Erreur MiniPavi '.$e->getMessage());
}
exit;
?>
