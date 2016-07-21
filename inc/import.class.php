<?php
// ----------------------------------------------------------------------
// Original Author of file: Rafael Pedrini
// Purpose of file: Importação de dados de arquivos em lote para o sistema
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 *  Import class
**/
class Import {
   
   var $notable = true;
   
   static function title() {
      global $LANG, $PLUGIN_HOOKS, $CFG_GLPI;
      
      set_time_limit(0);
      
      
      // Report generation
      // Default Report included
      $report_list["tarifacao"]["name"] = $LANG['import'][1];
      $report_list["tarifacao"]["file"] = "import.tarifacao.php";

      if (Session::haveRight("printer","w")) {
         // Rapport ajoute par GLPI V0.2
         $report_list["equipamento"]["name"] = $LANG['import'][2];
         $report_list["equipamento"]["file"] = "import.equipamento.php";
      }
      if (Session::haveRight("create_ticket","1")) {
         $report_list["ticket"]["name"] = $LANG['import'][3];
         $report_list["ticket"]["file"] = "import.ticket.php";
      }
      
      //Affichage du tableau de presentation des stats
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>".$LANG['import'][0]."&nbsp;:</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo "<select name='statmenu' onchange='window.location.href=this.options
    [this.selectedIndex].value'>";
      echo "<option value='-1' selected>".Dropdown::EMPTY_VALUE."</option>";
      
      $i = 0;
      $count = count($report_list);
      while ($data = each($report_list)) {
         $val = $data[0];
         $name = $report_list["$val"]["name"];
         $file = $report_list["$val"]["file"];
         echo "<option value='".$CFG_GLPI["root_doc"]."/front/".$file."'>".$name."</option>";
         $i++;
      }
      
		/*
      foreach ($optgroup as $opt => $title) {

         echo "<optgroup label=\"". $title ."\">";
         
         foreach ($names as $key => $val) {
             if ($opt==$val["plug"]) {
               echo "<option value='".$CFG_GLPI["root_doc"]."/plugins/".$key."'>".
                                                                     $val["name"]."</option>";
             }
         }
         
          echo "</optgroup>";
      }
		 * 
		 */

      echo "</select>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
   }
}

?>
