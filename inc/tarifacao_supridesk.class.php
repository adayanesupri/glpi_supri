<?php
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Contract_Item_Supridesk class
class Tarifacao_Supridesk extends CommonDBChild {

   // From CommonDBChild
   public $itemtype = 'Contract';
   public $items_id = 'contracts_id';

	protected $table = "supridesk_tarifacao";
   public $dohistory = true;

   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['agrupamento'][2];
      }
      return $LANG['agrupamento'][1];
   }

   static function faturado( $post ) {
      global $DB, $LANG;

      $queryTarifacao = "SELECT *
                         FROM `supridesk_tarifacao`
								 WHERE is_billing IS NOT NULL
                         AND `contracts_id` = {$post['contracts_id']} 
								 AND bilhetagem_final > '{$post['bilhetagem_inicial']}' ";
      $result = $DB->query($queryTarifacao);
		return ($DB->numrows($result) > 0);
   }

   function periodoJaFaturado() {
      global $DB, $LANG;

      $queryTarifacao = "SELECT *
                         FROM `supridesk_tarifacao`
								 WHERE is_billing IS NOT NULL
                         AND `contracts_id` = {$this->fields["contracts_id"]} 
								 AND bilhetagem_inicial <= '{$this->fields["bilhetagem_final"]}'
								 AND bilhetagem_final >= '{$this->fields["bilhetagem_inicial"]}'";
      $result = $DB->query($queryTarifacao);
		return ($DB->numrows($result) > 0);
   }

   function canDelete() {
		if ( $this->fields["is_billing"] )
			return false;

      return Session::haveRight('contract', 'w');
   }

   function canFaturar() {
		if ( $this->fields["is_billing"] || $this->periodoJaFaturado() )
			return false;

      return Session::haveRight('contract', 'w');
   }

   static function getCredito( $contracts_items_id, $data_inicial, $tipo )
	{
      global $DB;

		//Busca dados dos créditos
		$strSQL = "SELECT	*
						FROM supridesk_creditos c
								LEFT JOIN supridesk_contracts_items ci ON c.contracts_items_id = ci.id
						WHERE ci.id = {$contracts_items_id}
						  AND c.date < '{$data_inicial}'
						ORDER BY c.date DESC LIMIT 1 ";
		$rs_creditos = $DB->query($strSQL);
		
		if ( $DB->numrows($rs_creditos) )
		{
			$row = $DB->fetch_assoc($rs_creditos);
			return $row["credito_" . $tipo];
		}

		return 0;
	}

   static function setCredito( $contracts_items_id, $data_final, $saldo_mono, $saldo_color )
	{
      global $DB;

      $query = "INSERT INTO
                supridesk_creditos ( contracts_items_id, date, credito_mono, credito_color )
                VALUES ( $contracts_items_id, '{$data_final}', $saldo_mono, $saldo_color )";
      $DB->query($query);
	}

   function faturar() {
      global $DB;

		$contracts_id = $this->fields["contracts_id"];
		$date = $this->fields["date"];
		$tarifacao_inicial = $this->fields["tarifacao_inicial"];
		$tarifacao_final = $this->fields["tarifacao_final"];
		$bilhetagem_inicial = $this->fields["bilhetagem_inicial"];
		$bilhetagem_final = $this->fields["bilhetagem_final"];

		$sql_contracts_items = " SELECT * FROM supridesk_contracts_items ci WHERE ci.contracts_id = {$contracts_id}";
		$rs_contracts_items = $DB->query($sql_contracts_items);

      while ( $row_ci = $DB->fetch_assoc($rs_contracts_items))
		{
			$contracts_items_id = $row_ci["id"];
			$franquia_mono = $row_ci["franquia_mono"];
			$franquia_color = $row_ci["franquia_color"];
			$franquia_a3_mono = $row_ci["franquia_a3_mono"];
			$franquia_a3_color = $row_ci["franquia_a3_color"];
			$franquia_digitalizacao = $row_ci["franquia_digitalizacao"];
			$franquia_plotagem = $row_ci["franquia_plotagem"];

			print "Contrato item: " . $contracts_items_id . "<br>";
			//Seleciona impressoras independente de agrupamentos
			$strSQLPrinter = "SELECT	p.id as printers_id,
									cip.contracts_items_id,
									cIN.date as dateIN,
									cIN.impressao_mono as impressao_monoIN,
									cOUT.impressao_mono as impressao_monoOUT,
									cIN.impressao_color as impressao_colorIN,
									cOUT.impressao_color as impressao_colorOUT,
									cOUT.date as dateOUT,
									cip.replaced_printers_id,
									cip.id as contracts_items_printers_id,
									ci.*
							FROM supridesk_contracts_items_printers cip
									LEFT JOIN glpi_printers p ON cip.printers_id = p.id
									LEFT JOIN glpi_entities e ON p.entities_id = e.id
									LEFT JOIN glpi_printermodels pm ON p.printermodels_id = pm.id
									LEFT JOIN supridesk_contracts_items ci ON cip.contracts_items_id = ci.id
									LEFT JOIN supridesk_contadores cIN ON cip.in_contadores_id = cIN.id
									LEFT JOIN supridesk_contadores cOUT ON cip.out_contadores_id = cOUT.id
							WHERE
								ci.id = {$contracts_items_id}
							AND 
								(
									( ( cOUT.date >= '{$tarifacao_inicial}' OR cOUT.date is null ) AND ( cIN.date <= '{$tarifacao_final}' OR cIN.date is null ) )
									OR
									( ( cOUT.date >= '{$bilhetagem_inicial}' OR cOUT.date is null ) AND ( cIN.date <= '{$bilhetagem_final}' OR cIN.date is null ) )
								)
							ORDER BY contracts_items_id ";
			$rs_printers = $DB->query($strSQLPrinter);

			$printerAmount = $DB->numrows($rs_printers);
			$impressoes_mono = 0;
			$impressoes_color = 0;
			$impressoes_a3_mono = 0;
			$impressoes_a3_color = 0;
			$impressoes_digitalizacao = 0;
			$impressoes_plotagem = 0;

			while ( $row_printer = $DB->fetch_assoc($rs_printers))
			{
				$pages_n_b = 0;
				$obj_printerIN = null;
				if ( $row_printer["dateIN"] < $bilhetagem_inicial )
				{
					$strSQL = "SELECT	*
									FROM glpi_plugin_fusinvsnmp_printerlogs
									WHERE
										printers_id = {$row_printer['printers_id']}
										AND date < '{$bilhetagem_inicial}'
									ORDER BY date DESC limit 1";
					$rs_printer_log = mysql_query( $strSQL );
					$obj_printerIN = mysql_fetch_array( $rs_printer_log );
					$pages_n_b = $obj_printerIN["pages_n_b_print"];
				}
				else
				{
					$pages_n_b = $row_printer["impressao_monoIN"];
				}

				$pages_n_b2 = 0;
				$obj_printerOUT = null;
				if ( $row_printer["dateOUT"] == null || $row_printer["dateOUT"] > $bilhetagem_final )
				{
					$strSQL = "SELECT	*
									FROM glpi_plugin_fusinvsnmp_printerlogs
									WHERE
										printers_id = {$row_printer['printers_id']}
										AND date < '{$bilhetagem_final}'
									ORDER BY date DESC limit 1";
					$rs_printer_log = mysql_query( $strSQL );

					if ( $obj_printerOUT = mysql_fetch_array( $rs_printer_log ) )
					{
						$pages_n_b2 = $obj_printerOUT["pages_n_b_print"];
						$lastScan = $obj_printerOUT["date"];
					}
				}
				else
				{
					$pages_n_b2 = $row_printer["impressao_monoOUT"];
				}
				$impressoes_mono += intval($pages_n_b2 - $pages_n_b);

				if ( $franquia_color > 0 )
				{
					$pages_colorIN = 0;
					if ( $row_printer["dateIN"] < $bilhetagem_inicial )
						$pages_colorIN = $obj_printerIN["pages_color_print"];
					else
						$pages_colorIN = $row_printer["impressao_colorIN"];

					$pages_colorOUT = 0;
					if ( $row_printer["dateOUT"] == null || $row_printer["dateOUT"] > $bilhetagem_final )
						$pages_colorOUT = $obj_printerOUT["pages_color_print"];
					else
						$pages_colorOUT = $row_printer["impressao_colorOUT"];
					$impressoes_color += intval($pages_colorOUT - $pages_colorIN);
				}
			}

			//calcula saldo de créditos, se todos créditos anteriores foram gastos, zera o crédito
			$credito_mono = Tarifacao_Supridesk::getCredito( $contracts_items_id, $bilhetagem_inicial, "mono" );
			$franquia_mono = $printerAmount * $franquia_mono;
			$saldo_mono = $franquia_mono + $credito_mono - $impressoes_mono;
			$saldo_mono = $saldo_mono < 0 ? 0 : $saldo_mono;

			$credito_color = Tarifacao_Supridesk::getCredito( $contracts_items_id, $bilhetagem_inicial, "color" );
			$franquia_color = $printerAmount * $franquia_color;
			$saldo_color = $franquia_color + $credito_color - $impressoes_color;
			$saldo_color = $saldo_color < 0 ? 0 : $saldo_color;

			$credito_a3_mono = Tarifacao_Supridesk::getCredito( $contracts_items_id, $bilhetagem_inicial, "mono" );
			$franquia_a3_mono = $printerAmount * $franquia_a3_mono;
			$saldo_a3_mono = $franquia_a3_mono + $credito_a3_mono - $impressoes_a3_mono;
			$saldo_a3_mono = $saldo_a3_mono < 0 ? 0 : $saldo_a3_mono;

			$credito_a3_color = Tarifacao_Supridesk::getCredito( $contracts_items_id, $bilhetagem_inicial, "color" );
			$franquia_a3_color = $printerAmount * $franquia_a3_color;
			$saldo_a3_color = $franquia_a3_color + $credito_a3_color - $impressoes_a3_color;
			$saldo_a3_color = $saldo_a3_color < 0 ? 0 : $saldo_a3_color;

			$credito_digitalizacao = Tarifacao_Supridesk::getCredito( $contracts_items_id, $bilhetagem_inicial, "mono" );
			$franquia_digitalizacao = $printerAmount * $franquia_digitalizacao;
			$saldo_digitalizacao = $franquia_digitalizacao + $credito_digitalizacao - $impressoes_digitalizacao;
			$saldo_digitalizacao = $saldo_digitalizacao < 0 ? 0 : $saldo_digitalizacao;

			$credito_plotagem = Tarifacao_Supridesk::getCredito( $contracts_items_id, $bilhetagem_inicial, "color" );
			$franquia_plotagem = $printerAmount * $franquia_plotagem;
			$saldo_plotagem = $franquia_plotagem + $credito_plotagem - $impressoes_plotagem;
			$saldo_plotagem = $saldo_plotagem < 0 ? 0 : $saldo_plotagem;

			//grava os saldos na tabela de créditos
			print("mono: $saldo_mono = $franquia_mono + $credito_mono - $impressoes_mono <br>");
			print("color: $saldo_color = $franquia_color + $credito_color - $impressoes_color <br><br>");
			Tarifacao_Supridesk::setCredito( $contracts_items_id, $bilhetagem_final, $saldo_mono, $saldo_color );
		}


		die("end");

      $query1 = "UPDATE supridesk_tarifacao
                SET is_billing = 1
                WHERE id = " . $this->fields["id"];
      //$DB->query($query1);
   }
	
   function post_deleteFromDB() {
		$fullPath = ".." . $this->fields['filepath'];
		if (file_exists($fullPath)) { unlink ($fullPath); }
   }
	
   function canCreate() {
      return Session::haveRight('contract', 'w');
   }

   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      //$this->addStandardTab('Agrupamento_Printer_Supridesk', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Show ports for an item
    *
    * @param $item CommonDBTM object
    * @param $withtemplate integer : withtemplate param
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB, $CFG_GLPI, $LANG;

      $rand = mt_rand();

      $contracts_id = $item->getField('id');

      if (!Session::haveRight('contract','r') || !$item->can($contracts_id, 'r')) {
         return false;
      }

      $canedit = $item->can($contracts_id, 'w');
      $showmassiveactions = false;
      if ($withtemplate!=2) {
         $showmassiveactions = count(Dropdown::getMassiveActions(__CLASS__));
      }

      Session::initNavigateListItems('Tarifacao_Supridesk', $item->getTypeName()." = ".$item->getName());

      $query = "SELECT `id`
                FROM `supridesk_tarifacao`
                WHERE `contracts_id` = $contracts_id ";

      if ($result = $DB->query($query)) {
         echo "<div class='spaced'>";

         if ($DB->numrows($result) != 0) {
            $colspan = 9;

            if ($showmassiveactions) {
               $colspan++;
               echo "\n<form id='tarifacao$rand' name='tarifacao$rand' method='post'
                     action='" . $CFG_GLPI["root_doc"] . "/front/tarifacao_supridesk.form.php'>\n";
            }

            echo "<table class='tab_cadre_fixe'>\n";

            echo "<tr><th colspan='$colspan'>Relatórios encontrados : ".$DB->numrows($result)."</th></tr>\n";

            echo "<tr>";
            if ($showmassiveactions) {
               echo "<th>&nbsp;</th>\n";
            }
            echo "<th>#</th>\n";
            echo "<th>Data</th>\n";
            echo "<th>Locação<br>Inicial</th>\n";
            echo "<th>Locação<br>Final</th>\n";
            echo "<th>Bilhetagem<br>Inicial</th>\n";
            echo "<th>Bilhetagem<br>Final</th>\n";
            echo "<th>Faturado</th>\n";
            echo "<th>Arquivo</th>\n";

            $i = 0;
            $tarifacao = new Tarifacao_Supridesk();

            while ($devid = $DB->fetch_row($result)) {
               $tarifacao->getFromDB(current($devid));

               Session::addToNavigateListItems('Tarifacao_Supridesk', $tarifacao->fields["id"]);

               echo "<tr class='tab_bg_1'>\n";
               if ($showmassiveactions) {
                  echo "<td class='center' width='20'>";
                  echo "<input type='checkbox' name='del_relatorio[".$tarifacao->fields["id"]."]' value='1'>";
                  echo "</td>\n";
               }
               echo "<td class='center' width='50'><span class='b'>";
               if ($canedit && $withtemplate != 2) {
                  echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/tarifacao_supridesk.form.php?id=" .
                        $tarifacao->fields["id"] . "\">";
               }
               echo $tarifacao->fields["id"];
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               echo "</td>\n";

               echo "<td align='center'>" . date("d/m/Y", strtotime($tarifacao->fields["date"])) . "</td>\n";
               echo "<td align='center'>" . date("d/m/Y", strtotime($tarifacao->fields["tarifacao_inicial"])) . "</td>\n";
               echo "<td align='center'>" . date("d/m/Y", strtotime($tarifacao->fields["tarifacao_final"])) . "</td>\n";
               echo "<td align='center'>" . date("d/m/Y", strtotime($tarifacao->fields["bilhetagem_inicial"])) . "</td>\n";
               echo "<td align='center'>" . date("d/m/Y", strtotime($tarifacao->fields["bilhetagem_final"])) . "</td>\n";

					if ( $tarifacao->fields["is_billing"] )
						$imgBilling = "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark_record.png' title='Já faturado' alt='Já faturado'>";
					else
						$imgBilling = "";
					echo "<td align='center'>" . $imgBilling . "</td>\n";

					$filelink = "<a href='" . $CFG_GLPI["root_doc"] . $tarifacao->fields["filepath"] . "'><img src='" . $CFG_GLPI["typedoc_icon_dir"] . "/xls-dist.png' border=0> " . $tarifacao->fields["filename"] . "</a>";
               echo "<td>" . $filelink ."</td>\n";

               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               echo "</tr>\n";
            }
            echo "</table>\n";

            if ($showmassiveactions) {
               Html::openArrowMassives("tarifacao$rand", true);
               Dropdown::showForMassiveAction('Tarifacao_Supridesk');
               $actions = array();
               Html::closeArrowMassives($actions);
               Html::closeForm();
            }

         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>Nenhum relatório de tarifação encontrado para este contrato.</th></tr>";
            echo "</table>";
         }
         echo "</div>";
      }
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG, $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight("contract","r")) {
         switch ($item->getType()) {
            case 'Contract' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry($LANG['tarifacao'][40], self::countForContract($item));
               }
               return $LANG['tarifacao'][40];
         }
      }
      return '';
   }

   /**
    * @param $item   Contract object
   **/
   static function countForContract(Contract $item) {

      $restrict = "`supridesk_tarifacao`.`contracts_id` = '".$item->getField('id')."'";

      return countElementsInTable(array('supridesk_tarifacao'), $restrict);
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      switch ($item->getType()) {
         case 'Contract' :
            self::showForItem($item);

         default :
            if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
               Contract::showAssociated($item, $withtemplate);
            }
      }
      return true;
   }

}

?>
