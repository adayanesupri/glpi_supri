<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Entity Data class
 */
class EntityMap extends CommonDBChild {


   // From CommonDBChild
   public $itemtype               = 'Entity';
   public $items_id               = 'entities_id';

   // From CommonDBTM
   public $dohistory              = true;
   // link in message dont work (no showForm)
   public $auto_message_on_action = false;

   function getIndexName() {
      return 'entities_id';
   }


   function getLogTypeID() {
      return array('Entity', $this->fields['entities_id']);
   }


   function canCreate() {

      foreach (self::$field_right as $right => $fields) {
         if (Session::haveRight($right, 'w')) {
            return true;
         }
      }
      return false;
   }


   function canView() {
      return Session::haveRight('entity', 'r');
   }


   function prepareInputForAdd($input) {

      $input['max_closedate'] = $_SESSION["glpi_currenttime"];

      return $this->checkRightDatas($input);
   }

   /**
    *
   **/
   static function showMap(Entity $entity) {
      global $LANG, $CFG_GLPI;

      $ID = $entity->getField('id');

      // Get data
      $entdata = new EntityData();
      if (!$entdata->getFromDB($ID)) {
         $entdata->getEmpty();
      }

      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th>Mova o PIN até o local desta Entidade</th></tr>";

		$endereco = $entdata->fields["town"] . " " . $entdata->fields["state"] . " " . $entdata->fields["address"];

		$latitude = $entdata->fields["latitude"];
		$longitude = $entdata->fields["longitude"];

		if ( $latitude == "" )
			$latitude = 0;
		if ( $longitude == "" )
			$longitude = 0;

		echo "
			<tr class='tab_bg_1'>
				<td class='center'>
					<form onsubmit='getAddress();return false;' method='post'>
							Pesquisar endereço: <input id='address' type='text' size='100' name='address' value='{$endereco}'/>
							<input type='submit' value=' Procurar ' /> <span id='message' name='message' />
				  </form> ";
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td class='center'>" . "" . "</td></tr>";

      echo "<tr class='tab_bg_1'><td class='center'>";
		echo "
					<div align='center' id='map' style='width: 100%; height: 300px'>
						<br/>teste
					</div>";
		
         echo "</td></tr>";

      echo "<tr class='tab_bg_1'>
					<td class='center'>"
						. "<form method='post' name=form action='" . $CFG_GLPI["root_doc"] . "/front/entitydata.form.php'>
						Latitude: <input type='text' name='latitude' id='latitude' value='{$latitude}' />
						Longitude <input type='text' name='longitude' id='longitude' value='{$longitude}'/> &nbsp;";
		echo "<input type='hidden' name='entities_id' value='".$ID."'>";
		echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";

		Html::closeForm();
		echo "</td></tr>";
         echo "</table>";

$JS = <<<JAVASCRIPT
	<script type='text/javascript' charset='utf-8'>
		var geocoder;
		var map;
		var markersArray = [];

		function addMarker(location)
		{
			marker = new google.maps.Marker({
				position: location,
				map: map,
				title: 'Arraste e solte na posição exata da Entidade',
				draggable: true
			});

			google.maps.event.addListener(marker, 'dragend', function(e)
			{
				geocodePosition(marker.getPosition());
			});

			markersArray.push(marker);
			
			geocodePosition(location);
		}

		// Deletes all markers in the array by removing references to them
		function deleteOverlays()
		{
			while(markersArray[0])
			{
				markersArray.pop().setMap(null);
			}
		}

		function initialize()
		{
			geocoder = new google.maps.Geocoder();

			var loadLat = $latitude;
			var loadLng = $longitude;

			//se não tiver latlng setada, usa da supriservice
			if ( loadLat == 0 )
				loadLat = -20.3017314;
			if ( loadLng == 0 )
				loadLng = -40.298259099999996;

			var latlng = new google.maps.LatLng(loadLat, loadLng);
			var myOptions =
			{
				zoom: 15,
				center: latlng,
				mapTypeId: google.maps.MapTypeId.HYBRID
			}
			map = new google.maps.Map(document.getElementById('map'), myOptions);

			addMarker(latlng);
		}

		function geocodePosition(pos) 
		{
			geocoder.geocode
			(
				{ latLng: pos }, 
				function(results, status) 
				{
					if (status == google.maps.GeocoderStatus.OK) 
					{
						document.getElementById('message').innerHTML = '';
						document.getElementById('latitude').value= results[0].geometry.location.lat();
						document.getElementById('longitude').value= results[0].geometry.location.lng();
					} 
					else 
					{
						document.getElementById('message').innerHTML = '<br>Cannot determine address at this location.'+status;
					}
				}
			);
		}

		function getAddress()
		{
			var address = document.getElementById('address').value;

			var latlng;
			geocoder.geocode({'address': address},function(results, status)
			{
				if (status == google.maps.GeocoderStatus.OK)
				{
					latlng = results[0].geometry.location;
					document.getElementById('latitude').value= latlng.lat();
					document.getElementById('longitude').value= latlng.lng();
					map.setCenter(latlng);
					deleteOverlays();
					addMarker(latlng);
				}
				else
				{
					alert('Geocode was not successful for the following reason: ' + status);
				}
			});
		}
	</script>
JAVASCRIPT;
      echo $JS;

$JS = <<<JAVASCRIPT
	<script type='text/javascript' charset='utf-8'>
		initialize();
	</script>
JAVASCRIPT;
      echo $JS;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'Entity' :
               $ong = array();
               $ong[1] = "Mapa";      // Mapa
               return $ong;
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='Entity') {
         switch ($tabnum) {
            case 1 :
               self::showMap($item);
               break;
         }
      }
      return true;
   }
}

?>
