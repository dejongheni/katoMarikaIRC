<?php
//Le script de la mocheté pour un bot IRC sur un serveur utilisant ChanServ gérant différents aspects tels que l'autokick pour flood,
//l'autorisation de parler sur un channel modéré,...
//doit avoir les droits OP et les droits de set les flags (donc le flag +F, le mieux est qu'il ait tout les flags)
//par Kornakh

//set les variables de connection
$server = 'chat.freenode.net';
$port = 6667;
$password = $argv[1];
$nickname = 'KatoMarika';
$ident = 'v1.0';
$gecos = 'Bot Kato Marika v1.0';
$channel = "#bentenmaru";

//set les variables utilisées dans certaines commandes irc
$opList=array();
$autoKickOn=false;
$kickDelaiMax=10;
$kickNombreMessagesMax=6;
$prefixe='.';

//tableau de ce que dis le bot lorsqu'un user JOIN
$tabBonjour=array("Bonjour","Salutations","Coucou","Yo","Salut","Hey");
//set le tableau des phrases que va dire le bot lorsqu'il est mentionné
$tabPhrases[0]="<3";
$tabPhrases[1]="Hey !";

//set la connection au réseau
$socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
$error = socket_connect( $socket, $server, $port );

//gestion des erreurs
if ($socket==false){
  $errorCode = socket_last_error();
  $errorString = socket_strerror( $errorCode );
  die( "Error $errorCode: errorString\n");
}

//info de connection
socket_write($socket, "PASS $password\r\n");
socket_write($socket, "NICK $nickname\r\n");
socket_write($socket, "USER $ident * 8 :$gecos\r\n");

//permet de maintenir la connection
while (is_resource($socket)){

  //prends les données du socket
  $data=trim(socket_read($socket, 1024, PHP_NORMAL_READ));
  echo $data."\n";

  //sépares les données dans un tableau
  $d=explode(' ', $data);

  //décale le tableau pour evider des erreurs de placement
  $d = array_pad($d, 10, '' );

  //gestion du ping, y répond par pong
  //PING : rajaniemi.freenode.net
  if ($d[0]==='PING'){
    socket_write($socket, 'PONG '.$d[1]."\r\n");
  }

  //rejoins le channel $channel

  if ($d[1]==='376'||$d[1]==='422'){
    socket_write($socket, 'JOIN '.$channel."\r\n");
    socket_write($socket, 'MODE '.$channel." +o \r\n");
  }

  //fait un message random quand le bot est mensionné
  if (!empty(preg_grep('*'.$nickname.'*i' ,$d))&&$d[1]=='PRIVMSG'&&$d[2]==$channel){
    if (!empty(preg_grep('*préfixe*i' ,$d))&&$d[1]=='PRIVMSG'){
      socket_write($socket, 'PRIVMSG '.$d[2]." :Le préfixe actuel est ".$prefixe."\r\n");
    }else{
      $phrase=$tabPhrases[array_rand($tabPhrases)];
      socket_write($socket, 'PRIVMSG '.$d[2]." :".$phrase."\r\n");
    }
  }

  if (!empty(preg_grep('*Bot_?Nazi*i' ,$d))&&$d[1]=='PRIVMSG'){
    $user=substr($d[0], 1);
    $tab=explode("!",$user);
    $user=$tab[0];
    if (!in_array($user, $opList)){
      socket_write($socket, 'KICK '.$d[2].' '.$user." :Je ne suis pas Nazi :'(\r\n");
    }
  }

  //stoque le fait qu'un user passe op
  // [0]                      [1]     [2]           [3]
  //d :nickname!ident@hostname PRIVMSG #botter-test :.test
  if ($d[3]=='+o'&&$d[1]=='MODE'){
    if (!in_array($d[4], $opList)){
      array_push($opList, $d[4]);
    }
  }

  //dit bonjour quand un user JOIN
  if ($d[1]=='JOIN'){
    $user=substr($d[0], 1);
    $tab=explode("!",$user);
    $user=$tab[0];
    $Bonjour=$tabBonjour[array_rand($tabBonjour)];
    if($user==$nickname){
      socket_write($socket, 'PRIVMSG '.$d[2]." :".$Bonjour." tout le monde !\r\n");
    }else{
      socket_write($socket, 'PRIVMSG '.$d[2]." :".$Bonjour.' '.$user." !\r\n");
    }

  }

  //enlève l'op comme op si il se déconnecte
  if($d[1]=='PART'||$d[1]=='QUIT'){
    $leaver=substr($d[0], 1);
    $tab=explode("!",$leaver);
    $leaver=$tab[0];
    if (in_array($leaver, $opList)){
      $pos=array_search($leaver, $opList);
      unset($opList[$pos]);
      $opList=array_values($opList);
    }
  }elseif ($d[1]=='KICK'&&(in_array($d[3], $opList))) {
    $pos=array_search($d[3], $opList);
    unset($opList[$pos]);
    $opList=array_values($opList);
  }

  //gère le changement de nick d'un op
  if($d[1]=='NICK'){
    $nouvPseudo=substr($d[2], 1);
    $leaver=substr($d[0], 1);
    $tab=explode("!",$leaver);
    $leaver=$tab[0];
    if (in_array($leaver, $opList)){
      $pos=array_search($leaver, $opList);
      unset($opList[$pos]);
      $opList=array_values($opList);
      array_push($opList, $nouvPseudo);
    }
  }
  echo $d[3];
  switch($d[3]){
    case ':'.$prefixe.'help':
      help($d);
      break;

    case ':'.$prefixe.'test':
      test($d);
      break;

    case ':'.$prefixe.'autokick':
      ableAutokick($d);
      break;

    case ':'.$prefixe.'op':
      list_addOP($d);
      break;

    case ':'.$prefixe.'deop':
      deop($d);
      break;

    case ':'.$prefixe.'unop':
      unop($d);
      break;

    case ':'.$prefixe.'prefixe':
      changePrefixe($d);
      break;

    case ':'.$prefixe.'voice':
      voice($d);
      break;

    case ':'.$prefixe.'unvoice':
      unvoice($d);
      break;

    case ':'.$prefixe.'trivia':
      ableTrivia($d);
      break;

    case ':'.$prefixe.'say':
      say($d);
      break;

    case ':'.$prefixe.'radio':
      radio($d);
      break;
  }


  //ajoute l'user à un tableau avec son pseudo en tant que key et le timestamp de son message en valeur
  //le kick si il a trop parlé pendant les 4 dernières secondes
  if ($d[1]=='PRIVMSG'&&$d[0]!=':ChanServ!ChanServ@services.'&&$autoKickOn){
	  $user=substr($d[0], 1);
    $tab=explode("!",$user);
    $user=$tab[0];
    $user=str_replace("[","@",$user);
    $user=str_replace("]","@",$user);
  	if (!in_array($user, $opList)){
  		$timeStamp=time();
  		if (!isset($logFlood[$user])||!array_key_exists($user,$logFlood)){
  			$logFlood=array($user=>array($timeStamp));
  		}else{
  			array_push($logFlood[$user], $timeStamp);
  			if(count($logFlood[$user])>$kickNombreMessagesMax){
  				$firstTimeStamp=array_shift($logFlood[$user]);
  				echo $firstTimeStamp;
  				if(($timeStamp-$firstTimeStamp)<=$kickDelaiMax){
  					socket_write($socket, 'KICK '.$d[2].' '.$user." :Tu me fais mal à la tête !\r\n");
  				}
  			}
  		}
  	}
  }
}

function help($d){
  $demandeur=substr($d[0], 1);
  $tab=explode("!",$demandeur);
  $demandeur=$tab[0];
  socket_write($socket, 'PRIVMSG '.$demandeur.' :Je suis '.$nickname.', un bot pour gérer '.$channel.", j'espère que tu seras gentil avec moi <3\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur." :Je connais les commandes suivantes :\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."test : Je renvois l'identifiant de l'user ainsi que son message.\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."autokick : J'active ou désactive l'autokick, si la commande est utilisée avec les paramètres nombreSecondes et nombreMessage, je modifie le nombre de messages autorisé dans l'intervale de secondes précisé\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."op : J'affiche les utilisateurs que je considère comme OP (si vous êtes OP et que je ne vous pas considère comme tel, faites /op votrePseudo).\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."op pseudo : J'ajoute pseudo en tant que op\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."deop pseudo : J'enlève pseudo en tant que op\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."unop : Je m'enlève le statut d'OP, mais pourquoi me feriez vous faire ça ? :(\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."voice user : J'autorise un user à parler sur un channel modéré\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."unvoice user : J'enlève les droits d'un user de parler sur un channel modéré (ça c'est si vous n'êtes pas gentils)\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."prefixe nouveauPrefixe : Je change le préfixe d'appel de mes commandes, le préfixe actuel est ".$prefixe."\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."trivia : J'active ou désactive le trivia et l'autokick\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."say truc à dire : Je dis ce qu'on me dis de dire\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."autokick : J'active ou désactive l'autokick\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."autokick delai messsage: J'active l'autokick et change le nombre de messages autorisés en delai secondes\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."autokick help : Je dis si l'autokick est activé ou non et affiche le nombre de messages maximum pendant quelle durée\r\n");
  socket_write($socket, 'PRIVMSG '.$demandeur.' :  '.$prefixe."radio : J'annonce les musiques en cours de diffusion sur http://j-pop.moe\r\n");
}

function test($d){
  socket_write($socket, 'PRIVMSG '.$d[2]." :$d[0] : $d[4]\r\n");
}

function ableAutokick($d){
  $demandeur=substr($d[0], 1);
  $tab=explode("!",$demandeur);
  $demandeur=$tab[0];
  if (!is_null($d[4])&&($d[4]=='help')) {
    if($autoKickOn){
      socket_write($socket, 'PRIVMSG '.$d[2]." :L'autokick est activé.\r\n");
      socket_write($socket, 'PRIVMSG '.$d[2].' :Le nombre de messages maximum en '.$kickDelaiMax.'s est de '.$kickNombreMessagesMax.".\r\n");
    }else{
      socket_write($socket, 'PRIVMSG '.$d[2]." :L'autokick est désactivé.\r\n");
    }
  }else{
    if(in_array($demandeur, $opList)){
      if(!is_null($d[4])&&!is_null($d[5])&&($d[4]!='')&&($d[5]!='')){
        if(!$autoKickOn){
          $autoKickOn=true;
          socket_write($socket, 'PRIVMSG '.$d[2]." :J'active l'auto kick, si vous floodez trop, je vous vire <3\r\n");
        }
        $kickDelaiMax=intval($d[4]);
        $kickNombreMessagesMax=intval($d[5]);
        socket_write($socket, 'PRIVMSG '.$d[2].' :Le nouveau nombre de messages maximum en '.$kickDelaiMax.'s est de '.$kickNombreMessagesMax.".\r\n");
      }else{
        if($autoKickOn){
          socket_write($socket, 'PRIVMSG '.$d[2]." :Je désactive l'auto kick, mais évitez quand même de flood ;)\r\n");
          $autoKickOn=false;
        }else{
          socket_write($socket, 'PRIVMSG '.$d[2]." :J'active l'auto kick, si vous floodez trop, je vous vire <3\r\n");
          if(!is_null($d[4])&&!is_null($d[5])&&($d[4]!='')&&($d[5]!='')){
            $kickDelaiMax=intval($d[4]);
            $kickNombreMessagesMax=intval($d[5]);
          }
          socket_write($socket, 'PRIVMSG '.$d[2].' :Le nombre de messages maximum en '.$kickDelaiMax.'s est de '.$kickNombreMessagesMax.".\r\n");
          $autoKickOn=true;
        }
      }
    }else{
      socket_write($socket, 'PRIVMSG '.$d[2]." :Tu n'as aucune autorité sur moi !\r\n");
    }
  }
}

function list_addOP($d){
  $demandeur=substr($d[0], 1);
  $tab=explode("!",$demandeur);
  $demandeur=$tab[0];
  if(in_array($demandeur, $opList)&&(!is_null($d[4]))&&($d[4]!='')){
    array_push($opList, $d[4]);
    $newOPDeb=$d[4];
    $i=5;
    if((!is_null($d[$i]))&&($d[$i]!='')){
      $newOP=', ';
      while((!is_null($d[$i]))&&($d[$i]!='')){
        if(!in_array($d[$i], $opList)){
            array_push($opList, $d[$i]);
            $newOP=$newOP.$d[$i].', ';

        }
        $i++;
      }
      $newOP=substr($newOP, 0, -2);
      $newOP=$newOPDeb.$newOP;
      $lastNewOP = substr(strrchr($newOP, ","), 1);
      $newOP = substr($newOP, 0, strrpos($newOP, ','));
      $newOP = $newOP.' et'.$lastNewOP;
    }else{
      $newOP=$newOPDeb;
    }
    socket_write($socket, 'PRIVMSG '.$d[2].' :Je considère maintenant '.$newOP." comme op.\r\n");
  }else{
    $opCo='Je considère les utilisateurs suivant comme op : ';
    foreach($opList as $key => $valeur){
      if ($key!=0){
        $opCo=$opCo.', '.$valeur;
      }else{
        $opCo=$opCo.$valeur;
      }
      if ($valeur==$nickname){
        $opCo=$opCo." (c'est moi hihi <3)";
      }
    }
    $lastOP = substr(strrchr($opCo, ","), 1);
    $opCo = substr($opCo, 0, strrpos($opCo, ','));
    $opCo = $opCo.' et'.$lastOP;
    socket_write($socket, 'PRIVMSG '.$demandeur.' :'.$opCo."\r\n");
  }
}

function deop($d){
  $demandeur=substr($d[0], 1);
  $tab=explode("!",$demandeur);
  $demandeur=$tab[0];
  if(in_array($demandeur, $opList)){
    if((!is_null($d[4]))&&($d[4]!='')&&(in_array($d[4], $opList))){
      $pos=array_search($d[4], $opList);
      unset($opList[$pos]);
      $opList=array_values($opList);
      socket_write($socket, 'PRIVMSG '.$d[2].' :Je ne considère plus '.$d[4]." comme op\r\n");
    }else{
      socket_write($socket, 'PRIVMSG '.$d[2]." :Précise moi quelqu'un à enlever de ma liste des op.\r\n");
    }
  }else{
    socket_write($socket, 'PRIVMSG '.$d[2]." :Ne me donnes pas d'ordres !\r\n");
  }
}

function unop($d){
  $demandeur=substr($d[0], 1);
  $tab=explode("!",$demandeur);
  $demandeur=$tab[0];
  if(in_array($demandeur, $opList)){
    socket_write($socket, 'MODE '.$d[2].' -o '.$nickname."\r\n");
  }else{
    socket_write($socket, 'PRIVMSG '.$d[2]." :Ne touche pas à mes permissions, mécréant !\r\n");
  }
}

function changePrefixe($d){
  $demandeur=substr($d[0], 1);
  $tab=explode("!",$demandeur);
  $demandeur=$tab[0];
  if(in_array($demandeur, $opList)){
    if((!is_null($d[4]))&&($d[4]!='')){
      $prefixe=$d[4];
      socket_write($socket, 'PRIVMSG '.$d[2]." :Le nouveau préfixe de mes commandes est ".$prefixe."\r\n");
    }else{
      socket_write($socket, 'PRIVMSG '.$d[2]." :Le préfixe de mes commandes est ".$prefixe."\r\n");
    }
  }else{
    socket_write($socket, 'PRIVMSG '.$d[2]." :Ne touche pas à mes commandes, mécréant !\r\n");
  }
}

function voice($d){
  $demandeur=substr($d[0], 1);
  $tab=explode("!",$demandeur);
  $demandeur=$tab[0];
  if(in_array($demandeur, $opList)){
    socket_write($socket, "PRIVMSG ChanServ :flags $d[2] $d[4] +vV\r\n");
    socket_write($socket, 'MODE '.$d[2].' v '.$d[4]."\r\n");
    socket_write($socket, 'PRIVMSG '.$d[2].' :Tu peux maintenant parler '.$d[4]." :)\r\n");
    socket_write($socket, 'PRIVMSG '.$d[4]." :Si tu n'as pas enregistré ton compte, fait le avec la commande '/msg NickServ REGISTER password nom@domain.com' puis vérifie l'email, sinon ChanServ va venir te retirer tes droits.\r\n");
  }else{
    socket_write($socket, 'PRIVMSG '.$d[2].' :Désolé '.$demandeur.", mais tu n'es pas OP, je n'ai aucune raison de t'obéir.\r\n");
  }
}

function unvoice($d){
  $demandeur=substr($d[0], 1);
  $tab=explode("!",$demandeur);
  $demandeur=$tab[0];
  if(in_array($demandeur, $opList)){
    socket_write($socket, 'PRIVMSG '.$d[2].' :Alors, comme ça on embête tout le monde '.$d[4]." ?\r\n");
    socket_write($socket, "PRIVMSG ChanServ :flags $d[2] $d[4] -vV\r\n");
    socket_write($socket, 'MODE '.$d[2].' -v '.$d[4]."\r\n");
    socket_write($socket, 'PRIVMSG '.$d[2]." :Estime toi heureux que je ne te coupe que la langue ! Si ça ne tenait qu'à moi, tu serais déjà mort <3\r\n");
  }else{
    socket_write($socket, 'PRIVMSG '.$d[2].' :Désolé '.$demandeur.", mais tu n'es pas OP, je n'obéis qu'à eux.\r\n");
  }
}

function ableTrivia($d){
  $demandeur=substr($d[0], 1);
  $tab=explode("!",$demandeur);
  $demandeur=$tab[0];
  if(in_array($demandeur, $opList)){
    if(!$trivia){
      $autoKickOn = false;
      socket_write($socket, 'PRIVMSG '.$d[2]." :?start\r\n");
      socket_write($socket, 'PRIVMSG '.$d[2]." :Je désactive l'auto kick pour toi Pathey_triviabot <3 \r\n");
      $trivia=true;
    }else{
      socket_write($socket, 'PRIVMSG '.$d[2]." :?stop\r\n");
      $autoKickOn = true;
      socket_write($socket, 'PRIVMSG '.$d[2]." :Le trivia est finit, je réactive l'autokick. \r\n");
      $trivia=false;
    }
  }else{
    socket_write($socket, 'PRIVMSG '.$d[2]." :Ce n'est pas à toi de décider quand lancer le trivia !\r\n");
  }
}

function say($d){
  $demandeur=substr($d[0], 1);
  $tab=explode("!",$demandeur);
  $demandeur=$tab[0];
  if(in_array($demandeur, $opList)){
    $taille = count($d);
    $dire='';
    for ($i=4; $i < $taille; $i++) {
      $dire=$dire.$d[$i].' ';
    }
    socket_write($socket, 'PRIVMSG '.$channel.' :'.$dire."\r\n");
  }else{
    socket_write($socket, 'PRIVMSG '.$channel." :Vaurien ! Mécréant !! Ne me fait pas dire des trucs étranges $demandeur !\r\n");
  }
}

function radio($d){
  $radiotag=file_get_contents("/var/www/j-pop/music-names.txt");
  $radiotag=explode("||",$radiotag);
  $radio=$radiotag[0];
  $miku=$radiotag[1];
  socket_write($socket, 'PRIVMSG '.$d[2].' : Actuellement sur https://j-pop.moe : '."\r\n");
  socket_write($socket, 'PRIVMSG '.$d[2].' : J-pop : '.$radio."\r\n");
  socket_write($socket, 'PRIVMSG '.$d[2].' : Miku : '.$miku."\r\n");
}
?>
