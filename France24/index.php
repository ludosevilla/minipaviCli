<?php
/**
 * @file index.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Novembre 2023
 *
 * Script de lecture de flux RSS France 24
 * A des fins de démonstration
 * 
 */

require "../lib/MiniPaviCli.php";	// A modifier
require "france24Functions.php";

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR);
ini_set('display_errors',0);

try {
	MiniPavi\MiniPaviCli::start();

	if (MiniPavi\MiniPaviCli::$fctn == 'CNX') {
		// Initialisation
		$step = 0;
		$context = array();
		MiniPavi\MiniPaviCli::$content=array();
		trigger_error("[MiniFrance24] CNX");
		
	} else {
		$step=(int)@MiniPavi\MiniPaviCli::$urlParams->step;		// Etape du script à executer, indiqué dans le paramètre 'url' de la requête http
		$context = unserialize(MiniPavi\MiniPaviCli::$context);		// Récupération du contexte utilisateur
	}

	
	if (MiniPavi\MiniPaviCli::$fctn == 'FIN' || MiniPavi\MiniPaviCli::$fctn == 'FCTN?') {
			// Deconnexion
			trigger_error("[MiniFrance24] DECO");
			exit;
	}
	
	
	$vdt='';		// Contenu à envoyer au Minitel de l'utilisateur
	$cmd=null;		// Commande à executer au niveau de MiniPAVI
	$directCall = false;
	while(true) {
		switch ($step) {
			case 0:
				// Accueil
				$vdt = MiniPavi\MiniPaviCli::clearScreen().PRO_MIN.PRO_LOCALECHO_OFF;
				$vdt.= file_get_contents('FRANCE24.VDT');
				$vdt.=MiniPavi\MiniPaviCli::setPos(21,8);
				$vdt.=VDT_G0.VDT_TXTYELLOW.VDT_BGBLUE.VDT_SZDBLH.VDT_BLINK." Service de";
				$vdt.=MiniPavi\MiniPaviCli::setPos(21,10);
				$vdt.=VDT_G0.VDT_TXTYELLOW.VDT_BGBLUE.VDT_SZDBLH.VDT_BLINK.MiniPavi\MiniPaviCli::toG2(" démonstration");
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,24);
				$vdt.=VDT_G0.VDT_TXTWHITE.VDT_BGBLACK.MiniPavi\MiniPaviCli::toG2(" Pour lire les dépêches, tapez ").VDT_FDINV." Suite ".VDT_FDNORM.VDT_CLRLN;

				$step=5;
				$directCall=false;
				break 2;	// On arrête le script et on attend une saisie utilisateur ($directCall = false)
				
			case 5:
				if ( MiniPavi\MiniPaviCli::$fctn == 'REPETITION') {
					$step = 0;
					break;
				}
				if ( MiniPavi\MiniPaviCli::$fctn != 'SUITE') {
					break 2;
				}
				
			case 10:
				// Affichage des différentes rubriques
				$vdt=VDT_CLR.VDT_CUROFF.MiniPavi\MiniPaviCli::setPos(1,2);
				$vdt.=VDT_SZDBLH.VDT_TXTWHITE.VDT_BGBLUE.' FRANCE 24'.VDT_TXTBLACK.chr(hexdec('7D')).VDT_TXTWHITE.VDT_BGRED.' LES NEWS DE LA PLANETE'.VDT_CLRLN;
				
				for ($i=3;$i<24;$i++) {
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,$i);				
					$vdt.=VDT_BGYELLOW.MiniPavi\MiniPaviCli::repeatChar(' ',40);
				}
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(3,4).VDT_G1.VDT_STARTUNDERLINE;								
				$vdt.=VDT_BGYELLOW.VDT_TXTRED.MiniPavi\MiniPaviCli::repeatChar('p',36).'0';				
						
				for($i=0;$i<15;$i++) {
					$vdt.=VDT_DOWN.VDT_LEFT.'5';
				}
				$vdt.=VDT_LEFT.VDT_DOWN.'!'.VDT_LEFT.VDT_LEFT;
				for($i=0;$i<36;$i++) {
					$vdt.='#'.VDT_LEFT.VDT_LEFT;
				}
				$vdt.='"'.VDT_LEFT.VDT_UP;
				for($i=0;$i<15;$i++) {
					$vdt.='j'.VDT_UP.VDT_LEFT;
				}
				$vdt.="`";
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(4,7);
				for ($i=0;$i<7;$i++ ){
					$vdt.=MiniPavi\MiniPaviCli::setPos(4,6+(2*$i));
					$vdt.=VDT_BGBLACK.VDT_TXTWHITE.' '.VDT_SZDBLW.($i+1).VDT_SZNORM.' '.VDT_BGYELLOW.' ';
				}
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(8,6);
				$vdt.=VDT_BGGREEN.VDT_TXTBLACK.' '.VDT_SZDBLW."Monde".VDT_SZNORM.' ';

				$vdt.=MiniPavi\MiniPaviCli::setPos(8,8);
				$vdt.=VDT_BGGREEN.VDT_TXTBLACK.' '.VDT_SZDBLW."Europe".VDT_SZNORM.' ';

				$vdt.=MiniPavi\MiniPaviCli::setPos(8,10);
				$vdt.=VDT_BGGREEN.VDT_TXTBLACK.' '.VDT_SZDBLW."France".VDT_SZNORM.' ';
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(8,12);
				$vdt.=VDT_BGGREEN.VDT_TXTBLACK.' '.VDT_SZDBLW."Afrique".VDT_SZNORM.' ';
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(8,14);
				$vdt.=VDT_BGGREEN.VDT_TXTBLACK.' '.VDT_SZDBLW."Moyen-Orient".VDT_SZNORM.' ';

				$vdt.=MiniPavi\MiniPaviCli::setPos(8,16);
				$vdt.=VDT_BGGREEN.VDT_TXTBLACK.' '.VDT_SZDBLW.MiniPavi\MiniPaviCli::toG2("Amériques").VDT_SZNORM.' ';

				$vdt.=MiniPavi\MiniPaviCli::setPos(8,18);
				$vdt.=VDT_BGGREEN.VDT_TXTBLACK.' '.VDT_SZDBLW."Asie Pacifique".VDT_SZNORM.' ';
				
				
				$vdt.=MiniPavi\MiniPaviCli::setPos(7,21);
				$vdt.=VDT_BGYELLOW.VDT_TXTBLUE."L'ensemble des informations";
				$vdt.=MiniPavi\MiniPaviCli::setPos(8,22);
				$vdt.=VDT_BGYELLOW.VDT_TXTBLUE."provient du site france24.com";

				$vdt.=MiniPavi\MiniPaviCli::setPos(10,24);
				$vdt.=VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(" Entrez un numéro:  + ").VDT_FDINV." Envoi ".VDT_FDNORM.VDT_CLRLN;

			case 15:
				// Initialisation de la zone de saisie
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(28,24,1,MSK_ENVOI|MSK_SOMMAIRE,true,' ','');
				$step=20;
				$directCall=false;
				break 2;
				
			case 20:
				// Traitement de la saisie
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					$step = 0;
					break;
				}
				$choix = (int)(@MiniPavi\MiniPaviCli::$content[0]);
				if ($choix<1 || $choix>7) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Choix incorrect!');
					$step=15;
					break;
				}	
				
				$tNews = getNews($choix-1);
				if (!is_array($tNews) || count($tNews)<1) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Aucune info!');
					$step=15;
					break;
				}
				
				$context['tnews']=$tNews;
				$context['title']=$urlFlux[$choix-1]['name'];
				
				
			case 25:
				// Affichage des titres d'un thème donné, zone fixe
				$vdt=VDT_CLR.VDT_CUROFF.MiniPavi\MiniPaviCli::setPos(1,2);
				$vdt.=VDT_SZDBLH.VDT_TXTWHITE.VDT_BGBLUE.' FRANCE 24'.VDT_TXTBLACK.chr(hexdec('7D')).VDT_TXTWHITE.VDT_BGRED.' '.MiniPavi\MiniPaviCli::toG2($context['title']).VDT_CLRLN;

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,24);
				$vdt.=VDT_TXTRED.VDT_FDINV." Lire une news:  +".VDT_FDNORM." Envoi ".VDT_FDINV.' ou '.VDT_FDNORM." Sommaire ".VDT_FDINV.VDT_CLRLN;
				
				$context['currnews']=0;
				$context['page']=0;
				
			case 26:
				// Affichage des titres (5 maximum), zone variable
				$vdt.=VDT_CUROFF;
				$start = $context['page']*5;
				$stop = $start+5;
				
				for($j=$start,$i=0;$j<$stop;$j++) {
					
					if (isset($context['tnews'][$i])) {
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,4+($i*4));
						$vdt.=VDT_TXTWHITE.VDT_BGBLUE." ".($j+1).VDT_TXTBLACK.chr(hexdec('7D')).VDT_TXTBLACK.VDT_BGCYAN.' '.MiniPavi\MiniPaviCli::toG2(substr($context['tnews'][$j]['titre'],0,34)).VDT_CLRLN;
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,5+($i*4));
						$vdt.=VDT_TXTWHITE.MiniPavi\MiniPaviCli::toG2(substr($context['tnews'][$j]['desc'],0,120)).VDT_CLRLN;
					}
					$i++;
				}
				
			case 30:
				// Initialisation de la zone de saisie
				$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd(16,24,2,MSK_ENVOI|MSK_SUITE|MSK_RETOUR|MSK_SOMMAIRE,true,' ','');
				$directCall=false;
				$step = 35;
				break 2;
				
			case 35:
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					$step = 10;
					break;
				}
				// L'utilisateur veut changer de page
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE' || MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {
					$numNews = count($context['tnews']);
					$nextCurrNews = ($context['page']+1)*5;
					if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
						if ($nextCurrNews>=$numNews) {
							$vdt=MiniPavi\MiniPaviCli::writeLine0('Dernière page');
							$step=30;
							break;
						}
						$context['page']++;
					} else {
						if ($context['page']==0) {
							$vdt=MiniPavi\MiniPaviCli::writeLine0('Première page');
							$step=30;
							break;
						}
						$context['page']--;
					}
					for($i=0;$i<21;$i++) {
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,3+$i).VDT_CLRLN;
					}
					$step = 26;
					break;
				}
				// L'utilisateur veut lire une news
				$choix = (int)(@MiniPavi\MiniPaviCli::$content[0]);
				if ($choix<1 || $choix>count($context['tnews'])) {
					$vdt=MiniPavi\MiniPaviCli::writeLine0('Choix incorrect!');
					$step=30;
					break;
				}	
				$context['currnews']=$choix-1;
				
			case 40:
				// Affichage d'une news. Partie fixe
				$vdt=VDT_CLR.VDT_CUROFF.MiniPavi\MiniPaviCli::setPos(1,2);
				$vdt.=VDT_SZDBLH.VDT_TXTWHITE.VDT_BGBLUE.' FRANCE 24'.VDT_TXTBLACK.chr(hexdec('7D')).VDT_TXTWHITE.VDT_BGRED.' '.MiniPavi\MiniPaviCli::toG2($context['title']).VDT_CLRLN;

				$vdt.=MiniPavi\MiniPaviCli::setPos(1,24);
				$vdt.=VDT_TXTRED." Suite ".VDT_FDINV.' '.VDT_FDNORM." Retour ".VDT_FDINV.' ou '.VDT_FDNORM." Sommaire ".VDT_FDINV.VDT_CLRLN;
				
			case 41:
				// Affichage d'une news. Partie variable
				$vdt.=VDT_CUROFF;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
				$vdt.=VDT_TXTWHITE.VDT_SZDBLH.MiniPavi\MiniPaviCli::toG2(substr($context['tnews'][$context['currnews']]['titre'],0,40)).VDT_CLRLN;
				$vdt.=MiniPavi\MiniPaviCli::setPos(1,5);
				$vdt.=VDT_TXTYELLOW.MiniPavi\MiniPaviCli::toG2(substr($context['tnews'][$context['currnews']]['desc'],0,800)).VDT_CLRLN;
			
				$step = 45;
				break 2;
				
			case 45:
				// Affichage d'une news. Traitement de la saisie (uniquement touche de fonction)
				if (MiniPavi\MiniPaviCli::$fctn == 'SOMMAIRE') {
					$step = 25;
					break;
				}
				if (MiniPavi\MiniPaviCli::$fctn == 'SUITE' || MiniPavi\MiniPaviCli::$fctn == 'RETOUR') {				
					// On passe à la news suivante ou précédente
					$numNews = count($context['tnews']);
					if (MiniPavi\MiniPaviCli::$fctn == 'SUITE') {
						if ($context['currnews']+1 >= $numNews) {
							$vdt=MiniPavi\MiniPaviCli::writeLine0('Dernière news');
							$step=45;
							break 2;
						}
						$context['currnews']++;
					} else {
						if ($context['currnews']-1 <0) {
							$vdt=MiniPavi\MiniPaviCli::writeLine0('Première news');
							$step=45;
							break 2;
						}
						$context['currnews']--;
					}
					for($i=0;$i<20;$i++) {
						$vdt.=MiniPavi\MiniPaviCli::setPos(1,3+$i).VDT_CLRLN;
					}
					$step = 41;
					break;
				}
				// On ne fait rien
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

	$nextPage=$prot."://".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'].'?step='.$step;

	MiniPavi\MiniPaviCli::send($vdt,$nextPage,serialize($context),true,$cmd,$directCall);
} catch (Exception $e) {
	throw new Exception('Erreur MiniPavi '.$e->getMessage());
}
exit;
?>
