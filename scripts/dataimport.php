<?php
/*//echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' /><br>";*/

date_default_timezone_set('America/Sao_Paulo');

print date('H:i:s') . "<br>";


/*//SUPRISERVICE*/
require_once("../inc/attachmentread.class.php");



//$host="{pod51028.outlook.com:993/imap/ssl}INBOX"; // pop3host
$host="{smtp.office365.com:993/imap/ssl/novalidate-cert}INBOX"; // pop3host

$login="dataimport@supridesk.com.br"; //pop3 login
$password="supri2011@"; //pop3 password

$jk = new readattachment();
$filename = $jk->getdata($host,$login,$password,"VND.MS-EXCEL,CSV"); // calling member function

if ( $filename == null )
{
	print "Não há novos emails para processar.";
	die();
}

$dbhost = '127.0.0.1';
$dbuser = 'root';
$dbpass = '';
$dbname = 'glpi_supri';

$conn = mysql_connect($dbhost, $dbuser, $dbpass) or die('Error ao conectar ao mysql');
mysql_select_db($dbname);

$handle = fopen ( $filename, "r" );

while ( ( $data = fgetcsv( $handle, 1024, "," ) ) !== FALSE )
{
	//se for report manual, pega a data digitada no campo custom, posição 6 do array
	$isManual = ( isset($data[5]) && strtoupper($data[5]) == 'MANUAL' );
	if ( $isManual )
	{
		$aDate_parts = explode( "/", $data[6]);
		if ( checkdate( $aDate_parts[1], $aDate_parts[0], $aDate_parts[2] ) )
			$data[4] = $aDate_parts[1] . "/" . $aDate_parts[0] . "/" . $aDate_parts[2];
	}
	//se for report automático e estiver com data padrão dd/mm/yyyy, transforma para padrão americano
	else if ( strrpos($data[4], "AM") === FALSE && strrpos($data[4], "PM") === FALSE )
	{
		$aDate_parts = explode( "/", $data[4]);
		if ( checkdate( $aDate_parts[1], $aDate_parts[0], substr($aDate_parts[2], 0, 4) ) )
			$data[4] = $aDate_parts[1] . "/" . $aDate_parts[0] . "/" . $aDate_parts[2];
	}

	//reformata a data para o padrão da tabela do fusion inventory
	$data[4] = date( 'Y-m-d H:i:s', strtotime( $data[4] ) );
	$data[1] = trim($data[1]);
	$pages_total = intval($data[2] + $data[3]);
	$pages_n_b = intval($data[2]);
	$pages_color = intval($data[3]);
	$pages_total_print = intval($data[2] + $data[3]);
	$pages_n_b_print = intval($data[2]);
	$pages_color_print = intval($data[3]);

	//verifica se a impressora existe no GLPI
	$strSQL = "SELECT glpi_printers.id, glpi_printers.serial, glpi_printermodels.name from glpi_printers, glpi_printermodels WHERE is_deleted = 0 AND printermodels_id = glpi_printermodels.id AND TRIM(UCASE(replace(serial, '\t', ''))) = '{$data[1]}'";
	$rs_printer = mysql_query( $strSQL );
	if( $rs_row_printer = mysql_fetch_array( $rs_printer ) )
	{
		//verifica se a impressora já foi detectada pelo netdiscovery do fusion inventory, senão insere o registro dela.
		$strSQL = "SELECT * from glpi_plugin_fusinvsnmp_printers where printers_id = {$rs_row_printer['id']}";
		$rs_fusinvsnmp_printers = mysql_query( $strSQL );
		$rs_row_fusinvsnmp_printers = mysql_fetch_array( $rs_fusinvsnmp_printers );
		if( !$rs_row_fusinvsnmp_printers )
		{
			//insere o registro da impressora (emulando o que o netdiscovery faria)
			$strSQL = "INSERT INTO glpi_plugin_fusinvsnmp_printers (
								printers_id,
								sysdescr,
								last_fusioninventory_update
							) VALUES (
								{$rs_row_printer['id']},
								'{$rs_row_printer['name']}',
								'{$data[4]}'
							)";
			mysql_query( $strSQL ) or die(mysql_error());
		}
		else
		{
			//atualiza a última leitura da impressora para a data que veio do CSV
			$strSQL = "UPDATE glpi_plugin_fusinvsnmp_printers SET last_fusioninventory_update = '{$data[4]}' WHERE printers_id = {$rs_row_printer['id']}";
			mysql_query( $strSQL ) or die(mysql_error());
		}

		//se a última leitura da impressora não for a data do CSV, precisa adicionar dados na tabela printerlogs
		if ( $rs_row_fusinvsnmp_printers['last_fusioninventory_update'] != $data[4] )
		{
			$strSQL = "INSERT INTO glpi_plugin_fusinvsnmp_printerlogs (
								printers_id,
								date,
								pages_total,
								pages_n_b,
								pages_color,
								pages_total_print,
								pages_n_b_print,
								pages_color_print,
								import_mode,
								ip
							) VALUES (
								{$rs_row_printer['id']},
								'{$data[4]}',
								{$pages_total},
								{$pages_n_b},
								{$pages_color},
								{$pages_total_print},
								{$pages_n_b_print},
								{$pages_color_print},
								'{$data[5]}',
								'{$data[7]}'
							)";
			mysql_query( $strSQL ) or die(mysql_error());
		}
		else if ( $rs_row_fusinvsnmp_printers['last_fusioninventory_update'] == $data[4] )
		{
			//verifica o contador que está no banco para esta data
			$strSQL2 = "select * from glpi_plugin_fusinvsnmp_printerlogs where printers_id = {$rs_row_printer['id']} and date like '{$data[4]}%'";
			$rs_contador = mysql_query( $strSQL2 );
			$rs_row_contador = mysql_fetch_array( $rs_contador );

			//se o contador recebido for maior que o atual do banco, faz update no banco
			if ( mysql_num_rows($rs_contador) > 0 && $pages_total > $rs_row_contador['pages_total'] )
			{
				$strSQL3 = "UPDATE glpi_plugin_fusinvsnmp_printerlogs
								SET pages_total = {$pages_total},
									pages_n_b = {$pages_n_b},
									pages_color = {$pages_color},
									pages_total_print = {$pages_total_print},
									pages_n_b_print = {$pages_n_b_print},
									pages_color_print = {$pages_color_print},
									ip = '{$data[7]}'
								WHERE id = {$rs_row_contador['id']}";
				mysql_query( $strSQL3 ) or die(mysql_error());
			}
		}
		print date('H:i:s') . " # Impressora processada: {$rs_row_printer['id']} . <br />\n";
		flush();
	}
	else
	{
		Logger( $filename, "Impressora '{$data[1]}' (serial) não está cadastrada no GLPI ou o modelo não está definido." );
	}
}

fclose ( $handle );
mysql_close( $conn );

print date('H:i:s') . "<br>";

function Logger( $filename, $data )
{
	//Nome do arquivo:
	$filename = explode( "/", $filename );
	$filename = $filename[count($filename)-1];
	$filename = "../files/_dataimport/_log/{$filename}.txt";

	//Texto a ser impresso no log:
	$texto = "{$data} \r\n";

	$arquivo = fopen( $filename, "a+b" );
	fwrite( $arquivo, $texto );
	fclose( $arquivo );
}
?>
