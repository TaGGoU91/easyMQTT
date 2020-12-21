<?php
if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
// sendVarToJS('eqType', 'easyMQTT');
// $eqLogics = eqLogic::byType('easyMQTT');
$plugin = plugin::byId('easyMQTT');
sendVarToJS('eqType', $plugin->getId()); // Permet de rendre cliquable les éléments de la page Mes équipements 
$eqLogics = eqLogic::byType($plugin->getId()); // Permet de récupérer la liste des équipements de type easyMQTT dans la table eqLogic

// pour le débug -> permet d'afficher sur la console du navigateur en appelant la fonction console_log
function console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . 
');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}

?>


<div class="row row-overflow">
  <div class="col-lg-2 col-sm-3 col-sm-4" id="hidCol" style="display: none;">
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
        <?php
        foreach ($eqLogics as $eqLogic) {
          echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
        }
        ?>
      </ul>
    </div>
  </div>

  <div class="col-lg-12 eqLogicThumbnailDisplay" id="listCol">
    <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction logoPrimary" data-action="add">
          <i class="fas fa-plus-circle"></i>
          <br/>
        <span>{{Ajouter}}</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i>
        <br/>
        <span>{{Configuration}}</span>
      </div>
	  <div class="cursor eqLogicAction logoSecondary" id="bt_healtheasyMQTT"> <!-- l'action est traitée dans le vmware.js -->
			<i class="fas fa-medkit"></i>
			<br>
			<span>{{Santé}}</span>
	  </div>
    </div>

   <!--<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />-->
    <legend><i class="fas fa-home" id="butCol"></i> {{Mes Equipements MQTT}}</legend>
		<input class="form-control" placeholder="{{Rechercher parmis vos équipements}}" id="in_searchEqlogic" />
    <!--<div class="eqLogicThumbnailContainer">-->
      <?php
	  
	  ///////// ZIGBEE
	  echo '<div class="eqLogicThumbnailContainer">'; 
		// echo '<div class="eqLogicAction cursor"  data-id="' . $eqLogic->getId() . '">'; // l'action est traitée dans le vmware.js le data-id permet de récupérer l'info dans le JS pour transmettre l'appel à la fonction refresh pour l'ESXi en question uniquement // on se base sur le terme synchronisation pour le retrouver dans le JS
		echo '<div>'; // l'action est traitée dans le vmware.js le data-id permet de récupérer l'info dans le JS pour transmettre l'appel à la fonction refresh pour l'ESXi en question uniquement // on se base sur le terme synchronisation pour le retrouver dans le JS
		echo '<br>';
		echo '<img src="plugins/easyMQTT/resources/zigbee2mqtt.png">'; 
		echo '<br>';
		echo '<span class="name">Zigbee2mqtt</span>'; 
		echo '<br>';
		echo '</div>';
	  // echo '</div>';
	  foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('type') == 'zigbee') {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo "<center>";
					
				if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('modelShort') . '.jpg')) {
						echo '<img src="plugins/easyMQTT/core/config/devices/' . $eqLogic->getConfiguration('modelShort') . '.jpg' . '"/>';
				 } else {
				  echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				}
				///////// echo '<img src="plugins/easyMQTT/plugin_info/easyMQTT_icon.png" height="105" width="95" />';
				echo "</center>";
				echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';		
				echo '</div>';
			}
		}
	echo '</div>';
	
	
		///////// Yeelight
	  echo '<div class="eqLogicThumbnailContainer">'; 
		// echo '<div class="eqLogicAction cursor"  data-id="' . $eqLogic->getId() . '">'; // l'action est traitée dans le vmware.js le data-id permet de récupérer l'info dans le JS pour transmettre l'appel à la fonction refresh pour l'ESXi en question uniquement // on se base sur le terme synchronisation pour le retrouver dans le JS
		echo '<div>'; // l'action est traitée dans le vmware.js le data-id permet de récupérer l'info dans le JS pour transmettre l'appel à la fonction refresh pour l'ESXi en question uniquement // on se base sur le terme synchronisation pour le retrouver dans le JS
		echo '<br>';
		echo '<img src="plugins/easyMQTT/resources/yeelight.png">'; 
		echo '<br>';
		echo '<span class="name">Yeelight</span>'; 
		echo '<br>';
		echo '</div>';
	 // echo '</div>';
	  foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('type') == 'yeelight') {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo "<center>";
					
				if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('modelShort') . '.jpg')) {
						echo '<img src="plugins/easyMQTT/core/config/devices/' . $eqLogic->getConfiguration('modelShort') . '.jpg' . '"/>';
				 } else {
				  echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				}
				///////// echo '<img src="plugins/easyMQTT/plugin_info/easyMQTT_icon.png" height="105" width="95" />';
				echo "</center>";
				echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';		
				echo '</div>';
			}
		}
	  
      // foreach ($eqLogics as $eqLogic) { // Utiliser avant le rangement par type de docker zigbee/yeelight/etc
        // $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
		// echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
        // echo "<center>";
			
		// if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('modelShort') . '.jpg')) {
				// echo '<img src="plugins/easyMQTT/core/config/devices/' . $eqLogic->getConfiguration('modelShort') . '.jpg' . '"/>';
		 // } else {
          // echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
        // }
        /////////// echo '<img src="plugins/easyMQTT/plugin_info/easyMQTT_icon.png" height="105" width="95" />';
        // echo "</center>";
        // echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';		
		// echo '</div>';
      // }
      ?>
    </div>
  </div>
  
 <div class="eqLogic col-lg-12" style="display: none;" id="listCol2">
  <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
    <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
    <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer"></i> {{Equipement}}</a></li>
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
	  <div class="row">
	   <div class="col-sm-6">
        <form class="form-horizontal">
          <fieldset>
		  <br>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
              <div class="col-sm-6">
                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement easyMQTT}}"/>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label" >{{Objet parent}}</label>
              <div class="col-sm-6">
                <select class="form-control eqLogicAttr" data-l1key="object_id">
                  <option value="">{{Aucun}}</option>
                  <?php
                  foreach (jeeObject::all() as $object) {
                    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                  }
                  ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Catégorie}}</label>
              <div class="col-sm-8">
                <?php
                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                  echo '<label class="checkbox-inline">';
                  echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                  echo '</label>';
                }
                ?>

              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label" ></label>
              <div class="col-sm-8">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
              </div>
            </div>
					
			<div class="form-group" id="pileType">
			  <label class="col-sm-3 control-label">{{Type de piles}}</label>
			  <div class="col-sm-6">
				<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="battery_type" placeholder="{{Doit être indiqué sous la forme : 3xAA}}"/>
			  </div>
			</div>
			
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Topic MQTT}}</label>
              <div class="col-sm-6">
                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="topic" id="topicMQTT"></span>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Commandes par topic ou json}}</label>
              <div class="col-sm-6">
                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="type"></span>
              </div>
            </div>
			
		</fieldset>
	  </form>
	</div>
	
	<div class="col-sm-6">
		<form class="form-horizontal">
		<fieldset>
		<br>
		<div class="form-group" id="modelShort">
		  <label class="col-sm-3 control-label">{{Modèle court}}</label>
		  <div class="col-sm-6">
			<span class="eqLogicAttr" data-l1key="configuration" data-l2key="modelShort"></span>
		  </div>
		</div>
		<div class="form-group" id="modelLong">
		  <label class="col-sm-3 control-label">{{Modèle Long}}</label>
		  <div class="col-sm-8">
			<span class="eqLogicAttr" data-l1key="configuration" data-l2key="modelLong"></span>
		  </div>
		</div>
		

		<div class="form-group">
			<label class="col-sm-4 control-label">{{Icone de l'équipement}}</label>
			<div class="col-sm-5">
			  <select id="sel_icon" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="icone">
				<option value="">{{Aucun}}</option>				
				<option value="bslamp1">{{Bed Side Lamp 1}}</option>
				<option value="bslamp2">{{Bed Side Lamp 2}}</option>
				<option value="bslamp3">{{Bed Side Lamp 3}}</option>				
				<option value="ceiling1">{{Ceiling 1}}</option>
				<option value="ceiling2">{{Ceiling 2}}</option>
				<option value="ceiling3">{{Ceiling 3}}</option>
				<option value="ceiling4">{{Ceiling 4}}</option>
				<option value="ceiling5">{{Ceiling 5}}</option>
				<option value="ceiling6">{{Ceiling 6}}</option>
				<option value="ceiling7">{{Ceiling 7}}</option>				
				<option value="color1">{{Ampoule RGB}}</option>
				<option value="ct1">{{Ampoule Blanche 1}}</option>
				<option value="ct2">{{Ampoule Blanche 2}}</option>
				<option value="ct3">{{Ampoule Blanche 3}}</option>
				<option value="desklamp1">{{Lampe de bureau 1}}</option>
				<option value="desklamp2">{{Lampe de Bureau 2}}</option>				
				<option value="strip1">{{Strip 1}}</option>
				<option value="strip2">{{Strip 2}}</option>
			  </select>
			</div>
		</div>
	  <!--<div class="form-group">
		<div style="text-align: center">
		  <img name="icon_visu" src="" width="160" height="200"/>
		</div>
	  </div>-->
  
		<div class="form-group">
		<br>
			<center>
			<!--<img src="core/img/no_image.gif" data-original=".jpg" id="img_device" class="img-responsive" style="max-height : 250px;"  onerror="this.src='plugins/easyMQTT/plugin_info/easyMQTT_icon.png'"/>-->
			<img src="" data-original=".jpg" id="img_device" class="img-responsive" style="max-height : 250px;"  onerror="this.src='plugins/easyMQTT/plugin_info/easyMQTT_icon.png'"/>
			<!--<img name="icon_visu" src="" width="160" height="200"/>-->
			</center>
			<!--<img src="core/img/no_image.gif" data-original=".jpg" id="img_device" class="img-responsive" style="max-height : 250px;"  onerror="this.src='plugins/xiaomihome/plugin_info/xiaomihome_icon.png'"/>-->
		</div>
		  
        </fieldset>
      </form>
    </div>
   </div>
   </div>
    <div role="tabpanel" class="tab-pane" id="commandtab">

      <form class="form-horizontal">
        <fieldset>
          <div class="form-actions">
            <a class="btn btn-success btn-sm cmdAction" id="bt_addeasyMQTTAction"><i class="fas fa-plus-circle"></i> {{Ajouter une commande action}}</a>
          </div>
        </fieldset>
      </form>
      <br />
      <table id="table_cmd" class="table table-bordered table-condensed">
        <thead>
          <tr>
            <th style="width: 50px;">#</th>
            <th style="width: 150px;">{{Nom}}</th>
            <th style="width: 120px;">{{Sous-Type}}</th>
            <th style="width: 400px;">{{Topic}}</th>
            <th style="width: 300px;">{{Payload}}</th>
            <th style="width: 150px;">{{Paramètres}}</th>
            <th style="width: 80px;"></th>
          </tr>
        </thead>
        <tbody>

        </tbody>
      </table>

    </div>
  </div>
</div>
</div>
</div>

<?php include_file('desktop', 'easyMQTT', 'js', 'easyMQTT'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

<!--<script>
$( "#sel_icon" ).change(function(){
  var text = 'plugins/easyMQTT/plugin_info/node_' + $("#sel_icon").val() + '.png';
  ///////////////////$("#icon_visu").attr('src',text);
  document.icon_visu.src=text;
});
</script>-->
