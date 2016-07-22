<?php
/*
 * @version $Id: document.php 17152 2012-01-24 11:22:16Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("contract", "r");

Html::header($LANG['Menu'][100],$_SERVER['PHP_SELF'],"financial","entitymap");

$intColumnWidth = 50;
if (Session::haveRight("contract", "w")) {
	$intColumnWidth = 33;
}
echo "<table class='tab_cadre_fixe entitymap'>";
echo "<tr>";
echo "<th width='{$intColumnWidth}%'><a href='entitymap.php'>Todas entidades</a></th>";
echo "<th width='{$intColumnWidth}%'><a href='entitymap.php?delayed=0'>Entidades com chamados abertos</a></th>";
if (Session::haveRight("contract", "w")) {
	echo "<th width='{$intColumnWidth}%'><a href='entitymap.php?delayed=1'>Entidades com chamados com SLA atrasado</a></th>";
}
echo "</tr></table>";
echo "<div align='center' id='map' style='width: 100%; height: 900px'></div>";

global $LANG, $CFG_GLPI;

$JS = <<<JAVASCRIPT
	<script type='text/javascript' charset='utf-8'>
		var map;

		function addMarker(location, title, icon)
		{
			marker = new google.maps.Marker({
				position: location,
				map: map,
				title: title,
				icon: icon
			});
		}

		function initialize()
		{
			//se não tiver latlng setada, usa da supriservice
			var loadLat = -20.3017314;
			var loadLng = -40.298259099999996;

			var latlng = new google.maps.LatLng(loadLat, loadLng);
			var myOptions =
			{
				zoom: 8,
				center: latlng,
				mapTypeId: google.maps.MapTypeId.HYBRID
			}
			map = new google.maps.Map(document.getElementById('map'), myOptions);
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

$cidGV = Array('vitória', 'vila velha', 'serra', 'cariacica', 'viana');

$cidN1 = Array('sooretama', 'governador lindenberg', 'rio bananal', 'linhares',
					'marilândia', 'baixo guandú', 'colatina', 'aracruz',
					'jacupemba', 'guaraná',
					'joão neiva', 'ibiraçu', 'são roque do canaã', 'santa teresa',
					'fundão', 'itaguaçu', 'laranja da terra', 'itarana',
					'santa maria de jetibá'
			 );

$cidN2 = Array('pancas', 'são domingos do norte', 'alto rio novo', 'mantenópolis',
					'vila valério', 'jaguaré', 'são mateus', 'Águia branca',
	 				'são gabriel da palha', 'barra de são francisco', 'vila pavão', 'nova venécia',
					'Água doce do norte', 'ecoporanga', 'boa esperança', 'conceição da barra',
					'pinheiros', 'ponto belo', 'pedro canário', 'mucurici', 'montanha'
			);

$cidS1 = Array('santa leopoldina', 'domingos martins', 'marechal floriano', 'alfredo chaves', 'guarapari',
					'anchieta', 'iconha', 'piúma', 'itapemirim', 'marataízes');

$cidS2 = Array('afonso cláudio', 'brejetuba', 'ibatiba', 'conceição do castelo',
					'venda nova do imigrante', 'castelo', 'vargem alta', 'rio novo do sul',
					'presidente kennedy', 'atílio vivacqua', 'cachoeiro de itapemirim', 'mimoso do sul',
					'muqui', 'apiacá', 'bom jesus do norte', 'são josé do calçado',
					'jerônimo monteiro', 'alegre', 'guaçuí', 'dores do rio preto',
					'divino de são lourenço', 'ibitirama', 'iúna', 'muniz freire',
					'irupi'
			);

$entities_ids = array_reverse(getSonsOf("glpi_entities", $_SESSION["glpiactive_entity"]));
$entities = Entity::getEntitiesMaps( $entities_ids, $_GET['delayed'] );
for ($i = 0; $i < count($entities); $i++)
{
	$pathImage = "http://maps.google.com/mapfiles/ms/icons/red-dot.png";

	if ( in_array( strtolower( trim($entities[$i]['town']) ), $cidGV))
		$pathImage = $CFG_GLPI["root_doc"] . "/pics/pin_gv.png";
	elseif ( in_array(strtolower( trim($entities[$i]['town']) ), $cidN1))
		$pathImage = $CFG_GLPI["root_doc"] . "/pics/pin_n1.png";
	elseif ( in_array(strtolower( trim($entities[$i]['town']) ), $cidN2))
		$pathImage = $CFG_GLPI["root_doc"] . "/pics/pin_n2.png";
	elseif ( in_array(strtolower( trim($entities[$i]['town']) ), $cidS1))
		$pathImage = $CFG_GLPI["root_doc"] . "/pics/pin_s1.png";
	elseif ( in_array(strtolower( trim($entities[$i]['town']) ), $cidS2))
		$pathImage = $CFG_GLPI["root_doc"] . "/pics/pin_s2.png";

	$namePin = $entities[$i]['id'] . "-" . $entities[$i]['completename'];
$JS = <<<JAVASCRIPT
	<script type='text/javascript' charset='utf-8'>
		var latlng = new google.maps.LatLng('{$entities[$i]['latitude']}', '{$entities[$i]['longitude']}');
		addMarker(latlng, '{$namePin}', '{$pathImage}');
	</script>
JAVASCRIPT;
	echo $JS;
}
//Search::show('Document');

Html::footer();
?>