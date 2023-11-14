<?php
/**
 * @file miniChatFunctions.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Novembre 2023
 *
 * Fonctions utlisées dans le script miniChat.php
 * 
 */

// Fonction de tri des messages
function _sortMsg($a,$b) {
	return $a['time']>$b['time'];
}

// Récupère les messages pour le destinataire $id (ou tous si $id=0), ou le message $idMsg si $idMsg!=''
function getMsgFor($id=0,$idMsg='') {
	$tRes = array();

		
	$f = @fopen(MINICHAT_PATH_MSGLIST,'r');
	if ($f) {
		flock($f,LOCK_EX);
		$r = fgets($f);
		if ($r === false) {
			fclose($f);
			return $tRes;
		}
		if (strlen($r)>0) {
			$tResTmp = unserialize($r);
		}
		if ($idMsg=='') {
			if($id>0) {
				foreach($tResTmp as $k=>$msg) {
					if ($msg['iddest'] == $id)
						$tRes[]=$tResTmp[$k];
				}
			}
			else $tRes = $tResTmp;
			usort($tRes, "_sortMsg");
		} else {
			foreach($tResTmp as $k=>$msg) {
				if ($msg['idmsg'] == $idMsg) {
					$tRes[]=$tResTmp[$k];
					break;
				}
			}
		}
	fclose($f);		
	}
	return $tRes;
}

// Ajoute un nouveau message
function setMsg($idExp,$idDest,$nameExp,$content,$prevContent=array()) {
	if (!file_exists(MINICHAT_PATH_MSGLIST))
		$f = fopen(MINICHAT_PATH_MSGLIST,'w+');
	else
		$f = fopen(MINICHAT_PATH_MSGLIST,'r+');
	if ($f) {
		$tMsg=array();
		flock($f,LOCK_EX);
		$r = fgets($f);
		if ($r!== false && strlen($r)>0) {
			$tMsg = unserialize($r);
		}
		$i=count($tMsg);
		//print_r($tMsg);
		$tMsg[$i]['idmsg']=uniqid();
		$tMsg[$i]['time']=time();
		$tMsg[$i]['idexp']=$idExp;
		$tMsg[$i]['nameexp']=trim($nameExp);
		$tMsg[$i]['iddest']=$idDest;
		$tMsg[$i]['content']=$content;
		$tMsg[$i]['prevcontent']=$prevContent;
		//print_r($tMsg);
		ftruncate($f,0);
		rewind($f);		
		fputs($f,serialize($tMsg));
		fclose($f);		
		return true;
	}
	return false;
}

// Supprime le messages dont le idmsg=$idMsg (ou dont le iddest=$idMsg si $isIdDest=true)
function delMsg($idMsg,$isIdDest=false) {
	if (!file_exists(MINICHAT_PATH_MSGLIST))
		$f = fopen(MINICHAT_PATH_MSGLIST,'w+');
	else
		$f = fopen(MINICHAT_PATH_MSGLIST,'r+');
	if ($f) {
		$tMsg=array();
		flock($f,LOCK_EX);
		$r = fgets($f);
		if ($r === false) {
			fclose($f);
			return false;
		}
		if (strlen($r)>0) {
			$tMsg = unserialize($r);
		}
		$found = false;
		foreach($tMsg as $k=>$msg) {
			if (($msg['idmsg'] == $idMsg && !$isIdDest) || ($msg['iddest'] == $idMsg && $isIdDest)) {
				unset($tMsg[$k]);
				$found = true;
				if (!$isIdDest)
					break;
			}
		}
		if (!$found) {
			fclose($f);		
			return true;
		}
		$tMsg = array_values($tMsg);
		ftruncate($f,0);
		rewind($f);		
		fputs($f,serialize($tMsg));
		fclose($f);		
		return true;
	}
	return false;
}

// Efface la liste des connectés
function clearConnectedList() {
	setConnectedList(array());
}

// Retourne la liste des connectés et le nombre de connectés.
// Si $clean=true, le tableau retourné ne comporte pas d'éléments vides.
function getConnectedList(&$num,$clean=false) {
	$tRes = array();
	$num=0;
	$f = @fopen(MINICHAT_PATH_CNXLIST,'r');
	if ($f) {
		flock($f,LOCK_EX);
		$r = fgets($f);
		if ($r === false) {
			fclose($f);
			return $tRes;
		}
		if (strlen($r)>0) {
			$tRes = unserialize($r);
		}
		foreach($tRes as $k=>$v) 
			if ($v['id']!='') $num++;
			else if ($clean) {
				unset($tRes[$k]);
			}
			
		fclose($f);
	}
	return $tRes;
	
}

// Ajoute un connecté à la liste
function addConnected($id,$name,$cv) {
	$t = getConnectedList($null);
	foreach($t as $key=>$vals) {
		if ($t[$key]['id']=='') {
			$t[$key]['id']=$id;
			$t[$key]['tlast']=time();
			$t[$key]['name']=trim($name);
			$t[$key]['cv']=trim($cv);
			setConnectedList($t);
			return;
		}
	}
	$i = count($t);
	$t[$i]['id']=$id;
	$t[$i]['tlast']=time();
	$t[$i]['name']=trim($name);
	$t[$i]['cv']=trim($cv);
	setConnectedList($t);
}

// Supprime un connecté de la liste
function removeConnected($id) {
	$t = getConnectedList($null);
	foreach($t as $key=>$vals) {
		if ($vals['id']==$id) {
			$t[$key]['id']='';
		}
	}
	setConnectedList($t);
	delMsg($id,true);
}

// Enregistre la liste des connectés
function setConnectedList($t) {
	$f = fopen(MINICHAT_PATH_CNXLIST,'w');
	if ($f) {
		flock($f,LOCK_EX);
		fputs($f,serialize($t));
		fclose($f);
	}
}



function updateLastAction($id) {
	$tRes = array();
	$found=false;
	if (!file_exists(MINICHAT_PATH_CNXLIST))
		$f = fopen(MINICHAT_PATH_CNXLIST,'w+');
	else
		$f = fopen(MINICHAT_PATH_CNXLIST,'r+');
	if ($f) {
		flock($f,LOCK_EX);
		$r = fgets($f);
		if ($r === false) {
			fclose($f);
			return $found;
		}
		if (strlen($r)>0) {
			$tRes = unserialize($r);
		}
		foreach($tRes as $k=>$v) {
			if ($v['id']==$id)  {
				$found=true;
				$tRes[$k]['tlast']=time();
				break;
			}
		}
		ftruncate($f,0);
		rewind($f);		
		fputs($f,serialize($tRes));
		fclose($f);
	}
	return $found;
}

// Nettoyage des connexions inactives depuis plus d'une heure // MARCHE PAS
function chatClean() {
	$tDel=array();
	
	$f = @fopen(MINICHAT_PATH_CNXLIST,'r+');
	if ($f) {
		flock($f,LOCK_EX);
		$r = fgets($f);
		if ($r === false) {
			fclose($f);
			return $tDel;
		}
		if (strlen($r)>0) {
			$tRes = unserialize($r);
		}
		foreach($tRes as $k=>$v) {
			if ($tRes[$k]['id']!='' && (int)($v['tlast'])+MINICHAT_TIMEOUT<time()) {
				trigger_error("[MiniChatFunc] Ghost ".$tRes[$k]['id']);
				$tDel[]=$tRes[$k]['id'];
				delMsg($tRes[$k]['id'],true);
				$tRes[$k]['id']='';
			}
		}
		ftruncate($f,0);
		rewind($f);		
		fputs($f,serialize($tRes));
		fclose($f);
	}
	return $tDel;
}


function setStep($newStep) {
	global $step;
	global $prevStep;
	
	$prevStep=$step;
	$step=$newStep;
}
?>