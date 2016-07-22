<?php
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("importacao_dados", "1");

Html::header($LANG['Menu'][101],$_SERVER['PHP_SELF'],"utils","import");

Import::title();

if ( isset($_FILES) && array_key_exists('filename', $_FILES) 
		  && $_FILES['filename']['error'] == UPLOAD_ERR_OK //checks for errors
		  && is_uploaded_file($_FILES['filename']['tmp_name'])) { //checks that file is uploaded

	$manual = 0;
	$pulosNotFound = 0;
	$strERRORS = "";

	$handle = fopen ( $_FILES['filename']['tmp_name'], "r" );
	while ( ( $data = fgetcsv( $handle, 1024, ";" ) ) !== FALSE )
	{
		if ( !isset($data[0]) || $data[0] == 'Equipamento' )
		{
			continue;
		}

		if ( !isset($data[1]) || strlen($data[1]) == 0 )
		{
			continue;
		}

		$ticket = new Ticket();

		$postData = Array();
		$postData['date'] = date("Y-m-d H:i:s");
		$postData['slas_id'] = 0;
		$postData['type'] = 2;
		

		$IDCategoria = null;
		$categoria = utf8_encode($data[2]);
		foreach ($DB->request("select * from glpi_itilcategories where id = '{$categoria}'") as $dados)
		{
			$IDCategoria = $dados['id'];
		}
		if ( $IDCategoria == null )
		{
			$strERRORS .= "Categoria não encontrada: {$data[2]}.<br>";
			continue;
		}
		$postData['itilcategories_id'] = $IDCategoria;

		$postData['_users_id_requester'] = Session::getLoginUserID(); //ID do usuário logado
		$postData['_users_id_requester_notif'] = Array( 'use_notification' => 1 );

		$classSource = $data[0]; //Printer, Computer, Monitor
		$table = strtolower("glpi_{$classSource}s");
		$rs_source = $ticket->getEntityIDFromEquipamento($table, $data[1]);
		if ( $rs_source === false )
		{
			$pulosNotFound++;
			$strERRORS .= "Dados não encontrados para o Equipamento/Serial: {$classSource}/{$data[1]}.<br>";
			continue;			
		}

		$entity_id = $rs_source['entities_id'];
		$items_id = $rs_source['id'];

		$postData['entities_id'] = $entity_id; //entidade que o equipamento está relacionada
		$postData['_users_id_observer'] = 0;

		$postData['_users_id_assign'] = $data[3];
		$postData['_users_id_assign_notif'] = Array( 'use_notification' => 1 );
		$postData['status'] = 'new';
		$postData['requesttypes_id'] = 7;
		$postData['urgency'] = 3;
		$postData['impact'] = 3;
		$postData['_my_items'] = '';
		$postData['itemtype'] = $classSource; //tipo do equipamento
		$postData['items_id'] = $items_id; //ID do equipamento
		$postData['actiontime'] = 0;
		$postData['name'] = utf8_encode($data[4]);
		$postData['content'] = utf8_encode($data[5]);
		$postData['_tickettemplates_id'] = 1;
		$postData['id'] = 0;
		$postData['_glpi_csrf_token'] = Session::getNewCSRFToken();
		$postData['_users_id_requester'] = 4086; //Foi definido para ser criado pelo usuário SUPRISERVICE
		$postData['_users_id_requester_notif'] = Array( 'use_notification' => 1 );
		

		$ticket->add( $postData );
		$manual++;
		$_SESSION["MESSAGE_AFTER_REDIRECT"] = '';
	}
	echo "<table class='tab_cadre' width='950'>";
	echo "<tr class='tab_bg_2'>";
	echo "<td>Processados:</td>";
	echo "<td>{$manual}</td>";
	echo "</tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td>Dados não encontrados:</td>";
	echo "<td>{$pulosNotFound}</td>";
	echo "</tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td>Erros:</td>";
	echo "<td>{$strERRORS}</td>";
	echo "</tr>";
	echo "</table>";

	fclose ( $handle );
}
else
{
	echo "<form enctype='multipart/form-data' method='post' name='form' action='import.ticket.php'>";
	echo "<table class='tab_cadre'>";
	echo "<tr class='tab_bg_2'>";
	echo "<td>".$LANG['document'][2]." (".Document::getMaxUploadSize().")&nbsp;:</td>";
	echo "<td><input type='file' name='filename' value='' size='39'></td>";
	echo "<td class='center'><input type='submit' class='button' name='submit' value='".$LANG['buttons'][37] ."'></td>";
	echo "</tr>";
	echo "</table>";
}

Html::closeForm();

Html::footer();
?>