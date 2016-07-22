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

//print $_FILES['filename']['tmp_name'];
if ( isset($_FILES) && array_key_exists('filename', $_FILES) 
		  && $_FILES['filename']['error'] == UPLOAD_ERR_OK //checks for errors
		  && is_uploaded_file($_FILES['filename']['tmp_name'])) { //checks that file is uploaded

	$manual = 0;
	$strERRORS = "";
	
	$handle = fopen ( $_FILES['filename']['tmp_name'], "r" );
	while ( ( $data = fgetcsv( $handle, 1024, ";" ) ) !== FALSE )
	{
		//pula primeira linha ou se id da entidade não for numérica
		if ( !is_numeric($data[1]) )
		{
			continue;
		}

		$classSource = $data[0]; //Printer, Computer, Monitor, Peripheral
		$equip = new $classSource();

		$postData = Array();
		$postData['entities_id'] = $data[1];
		$postData['name'] = utf8_encode($data[2]);
		$postData['contact'] = $data[3];
		$postData['serial'] = $data[4];
		$postData['otherserial'] = $data[5];

		global $DB;

		$IDState = null;
		$state = utf8_encode(trim($data[6]));
		foreach ($DB->request("select * from glpi_states where name = '{$state}'") as $dados)
		{
			$IDState = $dados['id'];
		}
		if ( $IDState == null )
		{
			$strERRORS .= "Status não encontrado: {$data[6]}.<br>";
			continue;
		}
		$postData['states_id'] = $IDState;

		$table = strtolower("glpi_{$classSource}s");
		$tabletypes = strtolower("glpi_{$classSource}types");

		$IDType = null;
		$tipo = utf8_encode(trim($data[7]));
		foreach ($DB->request("select * from {$tabletypes} where name = '{$tipo}'") as $dados)
		{
			$IDType = $dados['id'];
		}
		if ( $IDType == null )
		{
			$strERRORS .= "Tipo não encontrado: {$data[7]}.<br>";
			continue;
		}
		$postData[strtolower("{$classSource}types_id")] = $IDType;
		
		$IDManufacturer = null;
		$manufacturer = utf8_encode(trim($data[8]));
		foreach ($DB->request("select * from glpi_manufacturers where name = '{$manufacturer}'") as $dados)
		{
			$IDManufacturer = $dados['id'];
		}
		if ( $IDManufacturer == null )
		{
			$strERRORS .= "Fabricante não encontrado: {$data[8]}.<br>";
			continue;
		}
		$postData['manufacturers_id'] = $IDManufacturer;

		$tablemodels = strtolower("glpi_{$classSource}models");
		$IDModel = null;
		$model = utf8_encode(trim($data[9]));
		foreach ($DB->request("select * from {$tablemodels} where name = '{$model}'") as $dados)
		{
			$IDModel = $dados['id'];
		}
		if ( $IDModel == null )
		{
			$strERRORS .= "Modelo não encontrado: {$data[9]}.<br>";
			continue;
		}
		$postData[strtolower("{$classSource}models_id")] = $IDModel;

		$postData['comment'] = utf8_encode($data[10]);
		$postData['notepad'] = utf8_encode($data[11]);

		$IDEquip = $equip->add( $postData );

		//Informações financeiras e administrativas
		//checar se foram preenchidas
		$infocom_fornecedor = utf8_encode(trim($data[12]));
		$infocom_invoice = utf8_encode(trim($data[13]));
		$infocom_valor = utf8_encode(trim($data[14]));
		$infocom_comment = utf8_encode(trim($data[15]));
		$infocom_inicio_garantia = utf8_encode(trim($data[16]));
		$infocom_validade_garantia = utf8_encode(trim($data[17]));
		$infocom_info_garantia = utf8_encode(trim($data[18]));
		$full_infocom = $infocom_fornecedor . $infocom_invoice . $infocom_valor . $infocom_comment . $infocom_inicio_garantia . $infocom_validade_garantia . $infocom_info_garantia;

		if (strlen($full_infocom) > 0)
		{
			//Checagens de campos relacionais
			$tablesuppliers = strtolower("glpi_suppliers");
			$IDSupplier = 0;

			foreach ($DB->request("select * from {$tablesuppliers} where name = '{$infocom_fornecedor}'") as $dados)
			{
				$IDSupplier = $dados['id'];
			}
			$icData["items_id"] = $IDEquip;
			$icData["itemtype"] = $classSource;
			$icData["entities_id"] = $postData['entities_id'];

			$icData["suppliers_id"] = $IDSupplier;
			$icData["bill"] = $infocom_invoice;
			$icData["value"] = $infocom_valor;
			$icData["comment"] = $infocom_comment;
			$icData["warranty_date"] = $infocom_inicio_garantia;
			$icData["warranty_duration"] = $infocom_validade_garantia;
			$icData["warranty_info"] = $infocom_info_garantia;

			$ic = new Infocom();
			if (!$ic->getFromDBforDevice($classSource, $IDEquip))
			{
				$ic->add( $icData );
			}
			else
			{
				$icData["id"] = $ic->fields['id'];
				$ic->update( $icData );
			}
		}

		$manual++;
		$_SESSION["MESSAGE_AFTER_REDIRECT"] = '';
	}

	echo "<table class='tab_cadre' width='950'>";
	echo "<tr class='tab_bg_2'>";
	echo "<td>Processados:</td>";
	echo "<td>{$manual}</td>";
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
	echo "<form enctype='multipart/form-data' method='post' name='form' action='import.equipamento.php'>";
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