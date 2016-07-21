<?php
   // BD - Porta, usuario e senha do banco de dados
   $db_database   = "glpi_supri";
   $db_table      = "glpi_computermodels";
   $campo         = "name";
   $tag_rep       = $_REQUEST['tag_rep'];

   // Localhost
   $db_host       = "localhost";
   $db_user       = "root";
   $db_pass       = "root";
   // Produção
   $db_host       = "50.30.47.40";
   $db_user       = "supridesk";
   $db_pass       = "sup_#@!desK";


   // ALTERA: se true já altera, se false ou null só exibe
   $ALTERA = $_REQUEST['ALTERA'];
   
   date_default_timezone_set("Brazil/East");



   // Conecta e seleciona o BD
   $conn    = mysql_connect($db_host,$db_user,$db_pass,false);
   $sel_tab = mysql_select_db($db_database);

   // SMS - Dados de envio do SMS
   $sql = "SELECT
              *
           FROM
              $db_table
           WHERE
              $campo like '$tag_rep%'";

   $result     = mysql_query($sql);
   $num        = mysql_num_rows($result);

   echo "Quantidade: $num<hr>";
   while ($values = mysql_fetch_assoc($result)) {
      echo $values[$campo]."<br>";
      $nome_novo  = $values[$campo];
      $nome_novo  = substr($nome_novo,strlen($tag_rep),strlen($nome_novo));
      echo $nome_novo."<hr>";

      $id         = $values['id'];
      
      $sql_updt = "UPDATE
                     $db_table
                   SET
                     $campo = '$nome_novo'
                   WHERE
                     id = $id";
      echo "$sql_updt<hr>";

      if ($ALTERA) {
         mysql_query($sql_updt);
      }
   }

   if ($ALTERA)
      echo "<script>location='{$_SERVER['PHP_SELF']}?tag_rep=$tag_rep';</script>";

   mysql_close($conn);
?>