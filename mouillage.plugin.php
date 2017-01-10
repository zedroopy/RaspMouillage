<?php
/*
@name Mouillage
@author Romain THIRION <ze_droopy@yahoo.fr>
@link http://www.zedroopy.net
@licence CC by nc sa
@version 1.0.0
@description Surveillance d'un bateau au mouillage
@client 2
*/

Plugin::addJs('/js/main.js');
Plugin::addJs('/js/ws.js');
Plugin::addCss('/css/style.css');
Plugin::addCss('/css/bouton.css');
//Plugin::addCss('/css/jquery-ui.min.css');
//Plugin::addJs('/js/jquery-3.1.1.min.js');
//Plugin::addJs('/js/jquery-ui.min.js');

function mouillage_plugin_menu(&$menuItems){
	global $_;
	$menuItems[] = array('sort'=>3,'content'=>'<a href="index.php?module=mouillage"><i class="fa fa-anchor"></i> Mouillage</a>');
}

Plugin::addHook('menubar_pre_home','mouillage_plugin_menu');

function mouillage_plugin_page($_){
	if(isset($_['module']) && $_['module']=='mouillage'){?>
		<div class="row">
			<div class="span12">
					<div id="debug"></div>
                    <?php
					include('bdd.php');
					$bddError="";
					try{
						$pdo = new PDO('mysql:host='.$host.';dbname='.$bdd.';charset=utf8',$user,$passwd);
						$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
						$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // ERRMODE_WARNING | ERRMODE_EXCEPTION | ERRMODE_SILENT
					} catch(Exception $e) {
						$bddError= "Impossible d'accéder à la base de données : ".$e->getMessage();
						echo $bddError;
						die();
					}
					$max_points=50;
					$max_weight=4;

					$stmt = $pdo->prepare("SELECT * FROM position ORDER BY utc DESC LIMIT ".$max_points);
					$stmt->execute();
					$positions=$stmt->fetchAll();

					$stmt = $pdo->prepare("SELECT * FROM config");
					$stmt->execute();
					$config=$stmt->fetch();
				?>
				<script>
					function myMap() {
					  var mapCanvas = document.getElementById("map");
					  var myCenter = new google.maps.LatLng(<?php echo $positions[0]['latitude'];?>, <?php echo $positions[0]['longitude'];?>);
					  var anchorPoint = new google.maps.LatLng(<?php echo $config['POS_MOUILLAGE_LAT'];?>, <?php echo $config['POS_MOUILLAGE_LON'];?>)
					  var mapOptions = {center: myCenter, zoom: 20, mapTypeId: google.maps.MapTypeId.SATELLITE};
					  var map = new google.maps.Map(mapCanvas,mapOptions);
						<?php
						$cercle = $config['CERCLE_EVITAGE'];
						$i=1;
						foreach ($positions as $segment) {
							if ($i>1) {
								$pt_actif="new google.maps.LatLng(".$segment['latitude'].",".$segment['longitude'].")";
								$opacity=0.8-($i-2)*(1/count($positions));
								$weight=$max_weight-($i-2)*($max_weight/count($positions));
								$rouge=$segment['distance']>$cercle ? 255 : 0;
								$vert=$segment['distance']>$cercle ? 0 : 255;
								echo "
									var p1 = ".$pt_prec."\n
									var p2 = ".$pt_actif."\n
									var seg".$i." = new google.maps.Polyline({\n
										path:[p1,p2],\n
										strokeColor:'rgb(".$rouge.",".$vert.",0)', //".$segment['distance']."\n
										strokeOpacity:".(string)$opacity.",\n
										strokeWeight:".(string)$weight."\n
									});\n
									seg".$i.".setMap(map);\n";
								echo "
									var GPSfix = new google.maps.Circle({
									strokeColor: 'rgb(".$rouge.",".$vert.",0)',
									strokeOpacity: ".$opacity.",
									strokeWeight: ".$weight.",
									fillColor: 'rgb(".$rouge.",".$vert.",0)',
									fillOpacity: ".($opacity-0.1).",
									map: map,
									center: p2,
									radius: 0.5
									});\n";

							}
							$pt_prec="new google.maps.LatLng(".$segment['latitude'].",".$segment['longitude'].")";
							$i++;
						}
						?>
					  var ancre = {
						url: './plugins/Mouillage/img/anchor_32.png',
						//size: new google.maps.Size(20, 32),
						//origin: new google.maps.Point(0, 0),
						anchor: new google.maps.Point(16, 16)
					  };

					  var wheel = {
						url: './plugins/Mouillage/img/ship_wheel_32.png',
						//size: new google.maps.Size(20, 32),
						//origin: new google.maps.Point(0, 0),
						anchor: new google.maps.Point(16, 16)
					  };

					  var marker_ancre=new google.maps.Marker({
						position:anchorPoint,
						icon:ancre
					  });

					  marker_ancre.setMap(map);

					  var marker_pos=new google.maps.Marker({
						position:myCenter,
						icon:wheel
					  });

					  marker_pos.setMap(map);

					  var evitage = new google.maps.Circle({
						strokeColor: '#0000FF',
						strokeOpacity: 0.9,
						strokeWeight: 1,
						fillColor: '#0000CC',
						fillOpacity: 0.3,
						map: map,
						center: anchorPoint,
						radius: <?php echo $cercle; ?>
					  });

					  var infoWindow=new google.maps.InfoWindow({ content: '<?php echo $positions[0]['utc']." Z";  ?>' });
					  marker_pos.addListener('mousedown', function(){
						  infoWindow.open(map, marker_pos);
					  });
					}
					// RESPONSIVE MARKER & CENTER
					//google.maps.event.addDomListener(window, 'resize', myMap);
					//google.maps.event.addDomListener(window, 'load', myMap);
				</script>
				<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCMG1L27hLHhIt8f0SNV_ADPzpu7WNXwps&callback=myMap"></script>
				<script>var posted = false;</script>
				</head>

				<?php	$running=exec('ps ax | grep [w]s_pid_test.py');	?>

				<body>
					<div id="accordion">
						<h3>&#9973; Suivi GPS</h3>
						<div>

							<div class="onoffswitch">
								<input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="switchPY" <?php echo $running!=""? "checked":"" ?> >
								<label class="onoffswitch-label" for="switchPY" onclick="switch_py(<?php echo $_SERVER['SERVER_ADDR']; ?>)">
									<span class="onoffswitch-inner"></span>
									<span class="onoffswitch-switch"></span>
								</label>
							</div>

							<div id="map">
							</div>
							<div id="rapport">
								<i>Dernière position:</i> <?php echo $positions[0]['latitude'];?> - <?php echo $positions[0]['longitude'];?>
								<br/><i>Heure: </i><?php echo $positions[1]['utc']." Z";  ?>
							</div>
						</div>

						<?php
				// ================================================ OPTIONS =======================================


				function test_input($data) {
				  $data = trim($data);
				  $data = stripslashes($data);
				  $data = htmlspecialchars($data);
				  return $data;
				}

				// Init variables
				$Enreg="";
				$Err1 = $Err2 = $Err3 = $Err4 = $Err5 = $Err6 = $Err7 = $Err8 = $Err9 = $Err10 = $Err11 = $Err12 ="";
				$email_from = $config['EMAIL_FROM'];
				$email_to = $config['EMAIL_TO'];
				$login_mail = $config['EMAIL_LOGIN'];
				$pwd_mail = $config['EMAIL_PASSWD'];
				$filtrage = $config['FILTRAGE'];
				$interval = $config['INTERVALLE'];
				$nb_positions = $config['MAX_RECORD'];
				$time_mail = $config['ATTENTE_MAIL'];
				$time_desync = $config['MAX_GPS_UNSYNC'];
				$latitude_mouillage = $config['POS_MOUILLAGE_LAT'];
				$longitude_mouillage = $config['POS_MOUILLAGE_LON'];
				$rayon_evitage = $config['CERCLE_EVITAGE'];


				if ($_SERVER["REQUEST_METHOD"] == "POST") {
				  if (empty($_POST["email_from"])) {
					$Err1 = "Champ obligatoire";
				  } else {
					$email_from = test_input($_POST["email_from"]);
					// check if e-mail address is well-formed
					if (!filter_var($email_from, FILTER_VALIDATE_EMAIL)) {
					  $Err1 = "email non valide";
					}
				  }

				  if (empty($_POST["email_to"])) {
					$Err2 = "Champ obligatoire";
				  } else {
					$email_to = test_input($_POST["email_to"]);
					$email_to_tab = explode(";",$email_to);
					// check if e-mail address is well-formed
					foreach ($email_to_tab as $email){
						if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
						  $Err2 = "email non valide";
						  if(count($email_to_tab)>1){ $Err2+=". Séparez les adresses par des ; (point-virgule)";}
						}
					}
				  }

				  if (empty($_POST["login_mail"])) {
					$Err3 = "Champ obligatoire";
				  } else {
					$login_mail = test_input($_POST["login_mail"]);
				  }
				  if (empty($_POST["pwd_mail"])) {
					$Err4 = "Champ obligatoire";
				  } else {
					$pwd_mail = test_input($_POST["pwd_mail"]);
				  }

				  if ($_POST["filtrage"]=="") {
					$Err5 = "Champ obligatoire";
				  } else {
					$filtrage = test_input($_POST["filtrage"]);
					if (!preg_match("/^[0-9]*$/",$filtrage) || intval($filtrage)>50 || intval($filtrage)<0) {
					  $Err5 = "Chiffres uniquement, 0 à 50";
					}
				  }

				  if (empty($_POST["interval"])) {
					$Err6 = "Champ obligatoire";
				  } else {
					$interval = test_input($_POST["interval"]);
					if (!preg_match("/^[1-9][0-9]*$/",$interval) || intval($interval)>120 || intval($interval)<1) {
					  $Err6 = "Chiffres uniquement, 1 à 120";
					}
				  }

				   if (empty($_POST["nb_positions"])) {
					$Err7 = "Champ obligatoire";
				  } else {
					$nb_positions = test_input($_POST["nb_positions"]);
					if (!preg_match("/^[1-9][0-9]*$/",$nb_positions) || intval($nb_positions)>1000 || intval($nb_positions)<10) {
					  $Err7 = "Chiffres uniquement, 10 à 1000";
					}
				  }

				   if (empty($_POST["time_mail"])) {
					$Err8 = "Champ obligatoire";
				  } else {
					$time_mail = test_input($_POST["time_mail"]);
					if (!preg_match("/^[1-9][0-9]*$/",$time_mail) || intval($time_mail)>480 || intval($time_mail)<10) {
					  $Err8 = "Chiffres uniquement, 10 à 480";
					}
				  }

				   if (empty($_POST["time_desync"])) {
					$Err9 = "Champ obligatoire";
				  } else {
					$time_desync = test_input($_POST["time_desync"]);
					if (!preg_match("/^[1-9][0-9]*$/",$time_desync) || intval($time_desync)>120 || intval($time_desync)<5 || intval($time_desync)<=intval($interval)) {
					  $Err9 = "Chiffres uniquement, 5 à 120, supérieur à l'intervalle";
					}
				  }

				   if (empty($_POST["latitude_mouillage"])) {
					$Err10 = "Champ obligatoire";
				  } else {
					$latitude_mouillage = test_input($_POST["latitude_mouillage"]);
					if (!is_numeric($latitude_mouillage)) {
					  $Err10 = "Chiffres uniquement";
					}
				  }

				   if (empty($_POST["longitude_mouillage"])) {
					$Err11 = "Champ obligatoire";
				  } else {
					$longitude_mouillage = test_input($_POST["longitude_mouillage"]);
					if (!is_numeric($latitude_mouillage)) {
					  $Err11 = "Chiffres uniquement";
					}
				  }

				   if (empty($_POST["rayon_evitage"])) {
					$Err12 = "Champ obligatoire";
				  } else {
					$rayon_evitage = test_input($_POST["rayon_evitage"]);
					if (!preg_match("/^[1-9][0-9]*$/",$rayon_evitage) || intval($rayon_evitage)>300 || intval($rayon_evitage)<1) {
					  $Err12 = "Chiffres uniquement, 1 à 300";
					}
				  }

				  if ($Err1.$Err2.$Err3.$Err4.$Err5.$Err6.$Err7.$Err8.$Err9.$Err10.$Err11.$Err12==""){
					  $query="UPDATE config SET EMAIL_TO=?,EMAIL_FROM=?,EMAIL_LOGIN=?,EMAIL_PASSWD=?,FILTRAGE=?,INTERVALLE=?,MAX_RECORD=?,ATTENTE_MAIL=?,MAX_GPS_UNSYNC=?,POS_MOUILLAGE_LAT=?,POS_MOUILLAGE_LON=?,CERCLE_EVITAGE=? WHERE id=0";
					  $stmt = $pdo->prepare($query);
					  $params=array($email_to,$email_from,$login_mail,$pwd_mail,$filtrage,$interval,$nb_positions,$time_mail,$time_desync,$latitude_mouillage,$longitude_mouillage,$rayon_evitage);

					  try{
						  $stmt->execute($params);
						  $Enreg= "Options enregisrées.";
					  } catch(PDOException $e){
						$Enreg= "Erreur d'enregistrement: ". $e->getMessage();
					  }
				  }
				}
				?>

						<h3><i class="fa fa-cog" aria-hidden="true"></i> Options</h3>
						<div>
							<form id="form_config_mouillage" method="post" action="">
								<ul >
                                	<li><a href="#tabs-1"><i class="fa fa-envelope" aria-hidden="true"></i> Mails</a></li>
                                    <li><a href="#tabs-2"><i class="fa fa-tachometer" aria-hidden="true"></i> Réglages</a></li>
                                    <li><a href="#tabs-3"><i class="fa fa-anchor" aria-hidden="true"></i> Mouillage</a></li>
                                </ul>
                                <div id="tabs-1">
                                    <ul>
                                        <li>
                                            <label for="email_from">e-mail Expéditeur </label>
                                            <input id="email_from" name="email_from" type="text" maxlength="255" value="<?php echo $email_from;?>"/><span class="error"><?php echo $Err1;?></span>
                                        </li>
                                        <li>
                                            <label for="email_to">e-mail Récipiendaire </label>
                                            <input id="email_to" name="email_to" type="text" maxlength="255" value="<?php echo $email_to;?>"/><span class="error"><?php echo $Err2;?></span>
                                        </li>
                                        <li>
                                            <label for="login_mail">LOGIN du serveur Gmail </label>
                                            <input id="login_mail" name="login_mail" type="text" maxlength="255" value="<?php echo $login_mail;?>"/><span class="error"><?php echo $Err3;?></span>
                                        </li>
                                        <li>
                                            <label for="pwd_mail">Mot de passe associé </label>
                                            <input id="pwd_mail" name="pwd_mail" type="password" maxlength="255" value="<?php echo $pwd_mail;?>"/><span class="error"><?php echo $Err4;?></span>
                                        </li>
                                    </ul>
								</div>
                                <div id="tabs-2">
                                	<ul>
                                        <li>
                                            <label for="filtrage">Filtrage sur x positions (0 = aucun) </label>
                                            <input id="filtrage" name="filtrage" type="text" maxlength="255" value="<?php echo $filtrage;?>"/><span class="error"><?php echo $Err5;?></span>
                                        </li>
                                        <li>
                                            <label for="interval">Intervalles entre les fix GPS</label>
                                            <input id="interval" name="interval"  type="text" maxlength="255" value="<?php echo $interval;?>"/><label id='unite'>minutes</label><span class="error"><?php echo $Err6;?></span>
                                        </li>
                                        <li>
                                            <label for="nb_positions">Nombre de positions en historique </label>
                                            <input id="nb_positions" name="nb_positions" type="text" maxlength="255" value="<?php echo $nb_positions;?>"/><span class="error"><?php echo $Err7;?></span>
                                        </li>
                                        <li>
                                            <label for="time_mail">Temps d'attente après email d'alerte</label>
                                            <input id="time_mail" name="time_mail" type="text" maxlength="255" value="<?php echo $time_mail;?>"/><label id='unite'>minutes</label><span class="error"><?php echo $Err8;?></span>
                                        </li>
                                        <li>
                                            <label for="time_desync">Temps maxi de desynchro GPS avant alerte email</label>
                                            <input id="time_desync" name="time_desync" type="text" maxlength="255" value="<?php echo $time_desync;?>"/><label id='unite'>minutes</label><span class="error"><?php echo $Err9;?></span>
                                        </li>
                                    </ul>
                                </div>
								<div id="tabs-3">
                                	<ul>
										<span><input id="getPOS" class="button_text" type="button" value="Position GPS" onclick="getPyPos(<?php echo $_SERVER['SERVER_ADDR']; ?>)"></span>
                                        <li>
                                            <label for="latitude_mouillage">Latitude du point de mouillage </label>
                                            <input id="latitude_mouillage" name="latitude_mouillage" type="text" maxlength="255" value="<?php echo $latitude_mouillage;?>"/><span class="error"><?php echo $Err10;?></span>
                                        </li>
                                        <li>
                                            <label for="longitude_mouillage">Longitude du point de mouillage </label>
                                            <input id="longitude_mouillage" name="longitude_mouillage" type="text" maxlength="255" value="<?php echo $longitude_mouillage;?>"/><span class="error"><?php echo $Err11;?></span>
                                        </li>
                                        <li>
                                            <label for="rayon_evitage">Rayon du cercle d''évitage</label>
                                            <input id="rayon_evitage" name="rayon_evitage" type="text" maxlength="255" value="<?php echo $rayon_evitage;?>"/><label id='unite'>m&egrave;tres</label><span class="error"><?php echo $Err12;?></span>
                                        </li>
                                    </ul>
                               </div>
                               <input id="saveForm" class="button_text" type="submit" name="submit" value="Enregistrer" /><span class="CR"><?php echo $Enreg;?></span>
							</form>
						</div>
					</div>

			</div>
		</div>
<?php
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			echo "<script>posted = true;</script>"; // Affichage du 2eme accordeon
		}
	}
}


Plugin::addHook("home", "mouillage_plugin_page");

/*function camera_action_camera(){
	global $_,$conf;
	switch($_['action']){
		case 'camera_refresh': 	system('raspistill -hf -w 400 -h 400 -o /var/www/yana-server/plugins/camera/view.jpg -t 0'); header('location:index.php?module=camera'); 		break;
	}
}

Plugin::addHook("action_post_case", "camera_action_camera");*/



?>
