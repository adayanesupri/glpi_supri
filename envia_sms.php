<?php
/*
 SCRIPT DE ENVIO DE SMS
 Este script, lê uma tabela com informações de número de aparelhos móveis (celulares)
 envia um SMS para os mesmos com informações contidas também nesta tabela.
 -------------------------------------------------------------------------
 Autor:   Fabio Dazzi
 Empresa: Supriservice Informática
 Data:    09/05/2014
 -------------------------------------------------------------------------

 Resumo de Funcionamento do Script

 1. Conecta no BD
 2. Pesquisa todos os SMSs que ainda não foram enviados
 3. Varre cada um dos registros
 3.1. Para cada registro, realiza 10 tentativas de conexão no Socket de envio de SMS
 3.1.1. Para cada tentativa de conexao, varre todos os IPs dos Hosts de servidores de serviços de SMS
 3.2. Se para o primeiro registro não houver conexão, finaliza este script

 OBS: se não houve conexão, torna-se desnecessário demais tentativas
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/* Este script, lê uma tabela com informações de número de aparelhos móveis (celulares)
    * e envia um SMS para os mesmos com informações contidas também nesta tabela.
    *
    * Autor:   Fabio Dazzi
    * Empresa: Supriservice Informática
    * Data:    09/05/2014
    *
    * Resumo de Funcionamento do Script
    * 1. Conecta no BD
    * 2. Pesquisa todos os SMSs que ainda não foram enviados
    * 3. Varre cada um dos registros
    * 3.1. Para cada registro, realiza 10 tentativas de conexão no Socket de envio de SMS
    * 3.1.1. Para cada tentativa de conexao, varre todos os IPs dos Hosts de servidores de serviços de SMS
    * 3.2. Se para o primeiro registro não houver conexão, finaliza este script
    * OBS: se não houve conexão, torna-se desnecessário demais tentativas
   */

   // VARIÁVEIS
   // SOCKET - Hosts possiveis de conexão
   $skt_host      = array();
   $skt_host[]    = "189.43.117.94";
   $skt_host[]    = "10.10.10.76"; // IP Fixo Supriservice
   //$skt_host[]    = "10.10.10.76"; // IP Fixo Supriservice

   // SOCKET - Porta, usuário e senha do socket
   $skt_port      = "5038";
   $skt_user      = "supridesk";
   $skt_pass      = "supri2011";
   $skt_timeout   = 30;

   // BD - Porta, usuario e senha do banco de dados
   $db_database   = "glpi_supri";
   $db_table      = "supridesk_sms";
   // Localhost
   $db_host       = "localhost";
   $db_user       = "root";
   $db_pass       = "root";
   // Produção
   $db_host       = "50.30.47.40";
   $db_user       = "supridesk";
   $db_pass       = "sup_#@!desK";


   date_default_timezone_set("Brazil/East");
   
   // Testa conexão com o Socket
   foreach ($skt_host as $ip_host) {
      if ($socket = fsockopen($ip_host, $skt_port, $errno, $errstr, $skt_timeout)) {
         break;
      }
   }

   // Conecta e seleciona o BD
   $conn    = mysql_connect($db_host,$db_user,$db_pass,false);
   $sel_tab = mysql_select_db($db_database);

   // SMS - Dados de envio do SMS
   $sql = "SELECT
              id,
              destinatario,
              mensagem
           FROM
              $db_table
           WHERE
              situacao = 'p'
           ORDER BY
              data_add";
   $result     = mysql_query($sql);
   $values     = mysql_fetch_assoc($result);
   $id         = $values['id'];
   $destino    = $values['destinatario'];
   $mensagem   = $values['mensagem'];
   $mensagem   = preg_replace( '/[`^~\'"]/', null, iconv( 'UTF-8', 'ASCII//TRANSLIT', $mensagem ) );

   //echo $destino."<br>";
   //echo $mensagem."<br>";

   //$socket = fsockopen($skt_ip_host, $skt_port, $errno, $errstr, $skt_timeout);

   if ($socket) {
      if ($destino != '') {
         //echo "Envia SMS para $destino<br>";
         fputs($socket, "Action: Login\r\n");
         fputs($socket, "UserName: $skt_user\r\n");   // Usuário do AMI
         fputs($socket, "Secret: $skt_pass\r\n\r\n");     // Senha do AMI

         // Enviando comandos via AMI para envio do SMS
         fputs($socket, "Action: Command\r\n");
         fputs($socket, "Command: dgv send sms g1 $destino \"$mensagem\"\r\n\r\n");
         fputs($socket, "Action: Logoff\r\n\r\n");

         $wrets = fgets($socket,128);
         fclose($socket);

         // Verificação de Erro
         if ($errno > 0) {
            echo "Falha no envio: $errno - $errstr";
         } else {
            $data_atual = date('Y-m-d H:i:s');
            $sql_update = "UPDATE
                              $db_table
                           SET
                              data_envio = '$data_atual',
                              situacao = 'e'
                           WHERE
                              id = $id";
            mysql_query($sql_update);
            //echo "Mensagem enviada para $destino as $data_atual";
         }
      } else {
        // die("Sem SMSs para enviar!");
      }
   } else {
      //die("Nao conectou ao socket!");
   }

   mysql_close($conn);
?>