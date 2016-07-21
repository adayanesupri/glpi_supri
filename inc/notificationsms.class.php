<?php
/*
 * @version $Id: notificationsms.class.php 2014-03-14 13:00:00Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is not part of GLPI.

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

 Esta classe foi desenvolvida por Fabio Rosalem Dazzi da equipe de desenvolvimento
 da Supriservice Informática
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 *  NotificationSms class envia SMS baseado no sistema de envio da Claro
 *  encontrado no serviço de mensagens contratado pela Supriservice
**/
class NotificationSms {
   // Variaveis do Socket
   //var $host      = "10.10.10.26";//189.43.117.94";  // IP Fixo Supriservice
   //var $port      = "5038";
   //var $user      = "supridesk";
   //var $pass      = "supri2011";
   //var $timeout   = 30;

   //var $destino   = "996256618";
   //var $mensagem  = "TESTE 004";

   /**
    * Constructor
   **/
   function __construct() {
   }

   public function enviaSMS($ticked_id=NULL,$user_id=NULL,$ticket_tipo=NULL) {
      global $DB;

      // Recupera informações que serão enviadas via SMS
      // Numero do Chamado
      // Nome da Entidade
      // Equipamento

      if ($ticked_id != NULL) {
         // Número do Chamado
         $num_chamado   = $ticked_id;

         // Informações do Tickect
         $qry_ticket = "
                        SELECT
                           T.id,
                           T.type,
                           T.entities_id,
                           T.entities_id,
                           T.itemtype,
                           T.items_id,
                           T.itilcategories_id as CATEGORIA
                        FROM
                           glpi_tickets  T
                        WHERE
                           T.id = $ticked_id
                        ";
         $res_ticket = $DB->query($qry_ticket);
         $dts_ticket = $DB->fetch_assoc($res_ticket);


         // Tipo do chamado
         $tipo_chamado = $dts_ticket['type'];
         if ($tipo_chamado==1)
            $tipo_chamado = "I";
         else {
            $tipo_chamado = "R";

            if (($dts_ticket['CATEGORIA'] == 416) || ($dts_ticket['CATEGORIA'] == 417))
               $tipo_chamado .= "T";
            elseif (($dts_ticket['CATEGORIA'] == 556) || ($dts_ticket['CATEGORIA'] == 571) || ($dts_ticket['CATEGORIA'] == 576))
               $tipo_chamado .= "F";
         }


         // Entidade, Local e Endereço
         if ($dts_ticket['entities_id'] != 0) {
            $qry_entidade = "
                              SELECT
                                 E.name,
                                 E.completename,
                                 ED.address,
                                 ED.town_abbrev
                              FROM
                                 glpi_entities E,
                                 glpi_entitydatas ED
                              WHERE
                                 E.id = {$dts_ticket['entities_id']}
                                 AND E.id = ED.entities_id
                              ";
            $res_entidade = $DB->query($qry_entidade);
            $dts_entidade = $DB->fetch_assoc($res_entidade);
            $entidade            = explode(">",$dts_entidade['completename']);
            //$entidade          = substr($entidade[0],0,10)."...>".$entidade[count($entidade)-1];
            $entidade            = $entidade[0];
            $entidade            = trim(substr($entidade,0,  strpos($entidade,"-")));
            if ($entidade == '')
               $entidade = "Entidade";
            $endereco            = $dts_entidade['address'];
            $local               = $dts_entidade['name'];
            $local2              = trim(substr($local,0,  strpos($local,"-")));
            if ($local2 != '')
               $local = $local2;
            $sigla_cidade        = $dts_entidade['town_abbrev'];
         } else {
            $entidade            = "Entidade";
            $endereco            = "Endereco";
            $local               = "Local";
            $sigla_cidade        = "Sigla";
         }

         // Equipamento/Modelo
         $itemtype         = $dts_ticket['itemtype'];
         $tbl_item         = "glpi_".strtolower(getPlural($itemtype));
         $tbl_item_models  = "glpi_".strtolower($itemtype)."models";
         $models_id        = strtolower($itemtype)."models_id";
         $itemtypeID       = $dts_ticket['items_id'];
         $query_eq         = "
                              SELECT
                                 IM.name
                              FROM
                                 $tbl_item I,
                                 $tbl_item_models IM
                              WHERE
                                 I.$models_id = IM.id
                                 AND I.id = $itemtypeID
                             ";
         $result_eq     = $DB->query($query_eq);
         if ($result_eq) {
            $data_eq       = $DB->fetch_assoc($result_eq);
            $equipamento   = $data_eq['name'];
         } else {
            $equipamento   = "Equipamento Inexistente.";
         }

         // Criando a mensagem
         $mensagemSMS   = "$num_chamado|";
         $mensagemSMS  .= "$tipo_chamado|";
         $mensagemSMS  .= "$equipamento|";
         $mensagemSMS  .= "$sigla_cidade|";
         $mensagemSMS  .= "$entidade|";
         $mensagemSMS  .= "$local|";
         $mensagemSMS  .= "$endereco";

         $sql_user_id = '';
         if ($user_id != NULL)
            $sql_user_id = "AND U.id = $user_id";

         // Usuários envolvidos no chamado
         // Tipo 1: Requerentes
         // Tipo 2: Técnicos
         // Tipo 3: Observador
         $query = "  SELECT
                        U.id AS ID,
                        U.mobile AS TARGET
                     FROM
                        glpi_users U,
                        glpi_tickets_users TU
                     WHERE
                        U.id = TU.users_id
                        AND U.usercategories_id IN (1,4,17)
                        AND TU.type = 2
                        AND TRIM(U.mobile) <> ''
                        AND TU.tickets_id = $ticked_id
                        $sql_user_id";

         if ($result=$DB->query($query)) {
            while ($data=$DB->fetch_assoc($result)) {
               // TODO: FALTA IMPLEMENTAR FUNÇÃO DE ENVIO DE E-MAIL

               // TESTE: Função temporária para testes
               //        ao invez de enviar um SMS, ela preenche a tabela supridesk_sms_temp
               if (trim($mensagemSMS) != '') {
                  $data_add = date("Y-m-d H:i:s");
                  $qry_insert = "INSERT INTO
                                    supridesk_sms
                                    (data_add, destinatario, mensagem, situacao)
                                 VALUES
                                    ('$data_add','{$data['TARGET']}','$mensagemSMS','p')";
                  
                  $DB->query($qry_insert);

                  /*if ($ticket_tipo=='add') {
                     // Envia para o Gilson na abertura
                     $qry_insert = "INSERT INTO
                                       supridesk_sms
                                       (data_add, destinatario, mensagem, situacao)
                                    VALUES
                                       ('$data_add','999730716','$mensagemSMS','p')";

                     $DB->query($qry_insert);
                  }*/
               }
            }
         }
      }
   }
    
    public function enviaSMSGilson($ticked_id=NULL,$user_id=NULL,$ticket_tipo=NULL) {
      global $DB;

      // Recupera informações que serão enviadas via SMS
      // Numero do Chamado
      // Nome da Entidade
      // Equipamento

      if ($ticked_id != NULL) {
         // Número do Chamado
         $num_chamado   = $ticked_id;

         // Informações do Tickect
         $qry_ticket = "
                        SELECT
                           T.id,
                           T.type,
                           T.entities_id,
                           T.entities_id,
                           T.itemtype,
                           T.items_id,
                           T.itilcategories_id as CATEGORIA
                        FROM
                           glpi_tickets  T
                        WHERE
                           T.id = $ticked_id
                        ";
         $res_ticket = $DB->query($qry_ticket);
         $dts_ticket = $DB->fetch_assoc($res_ticket);


         // Tipo do chamado
         $tipo_chamado = $dts_ticket['type'];
         if ($tipo_chamado==1)
            $tipo_chamado = "I";
         else {
            $tipo_chamado = "R";

            if (($dts_ticket['CATEGORIA'] == 416) || ($dts_ticket['CATEGORIA'] == 417))
               $tipo_chamado .= "T";
            elseif (($dts_ticket['CATEGORIA'] == 556) || ($dts_ticket['CATEGORIA'] == 571) || ($dts_ticket['CATEGORIA'] == 576))
               $tipo_chamado .= "F";
         }


         // Entidade, Local e Endereço
         if ($dts_ticket['entities_id'] != 0) {
            $qry_entidade = "
                              SELECT
                                 E.name,
                                 E.completename,
                                 ED.address,
                                 ED.town_abbrev
                              FROM
                                 glpi_entities E,
                                 glpi_entitydatas ED
                              WHERE
                                 E.id = {$dts_ticket['entities_id']}
                                 AND E.id = ED.entities_id
                              ";
            $res_entidade = $DB->query($qry_entidade);
            $dts_entidade = $DB->fetch_assoc($res_entidade);
            $entidade            = explode(">",$dts_entidade['completename']);
            //$entidade          = substr($entidade[0],0,10)."...>".$entidade[count($entidade)-1];
            $entidade            = $entidade[0];
            $entidade            = trim(substr($entidade,0,  strpos($entidade,"-")));
            if ($entidade == '')
               $entidade = "Entidade";
            $endereco            = $dts_entidade['address'];
            $local               = $dts_entidade['name'];
            $local2              = trim(substr($local,0,  strpos($local,"-")));
            if ($local2 != '')
               $local = $local2;
            $sigla_cidade        = $dts_entidade['town_abbrev'];
         } else {
            $entidade            = "Entidade";
            $endereco            = "Endereco";
            $local               = "Local";
            $sigla_cidade        = "Sigla";
         }

         // Equipamento/Modelo
         $itemtype         = $dts_ticket['itemtype'];
         $tbl_item         = "glpi_".strtolower(getPlural($itemtype));
         $tbl_item_models  = "glpi_".strtolower($itemtype)."models";
         $models_id        = strtolower($itemtype)."models_id";
         $itemtypeID       = $dts_ticket['items_id'];
         $query_eq         = "
                              SELECT
                                 IM.name
                              FROM
                                 $tbl_item I,
                                 $tbl_item_models IM
                              WHERE
                                 I.$models_id = IM.id
                                 AND I.id = $itemtypeID
                             ";
         $result_eq     = $DB->query($query_eq);
         if ($result_eq) {
            $data_eq       = $DB->fetch_assoc($result_eq);
            $equipamento   = $data_eq['name'];
         } else {
            $equipamento   = "Equipamento Inexistente.";
         }

         // Criando a mensagem
         $mensagemSMS   = "$num_chamado|";
         $mensagemSMS  .= "$tipo_chamado|";
         $mensagemSMS  .= "$equipamento|";
         $mensagemSMS  .= "$sigla_cidade|";
         $mensagemSMS  .= "$entidade|";
         $mensagemSMS  .= "$local|";
         $mensagemSMS  .= "$endereco";

         $sql_user_id = '';
         if ($user_id != NULL)
            $sql_user_id = "AND U.id = $user_id";

         // Usuários envolvidos no chamado
         // Tipo 1: Requerentes
         // Tipo 2: Técnicos
         // Tipo 3: Observador
         $query = "  SELECT
                        U.id AS ID,
                        U.mobile AS TARGET
                     FROM
                        glpi_users U,
                        glpi_tickets_users TU
                     WHERE
                        U.id = TU.users_id
                        AND U.usercategories_id IN (1,4,17)
                        AND TU.type = 2
                        AND TRIM(U.mobile) <> ''
                        AND TU.tickets_id = $ticked_id
                        $sql_user_id";

         if ($result=$DB->query($query)) {
            while ($data=$DB->fetch_assoc($result)) {
               // TODO: FALTA IMPLEMENTAR FUNÇÃO DE ENVIO DE E-MAIL

               // TESTE: Função temporária para testes
               //        ao invez de enviar um SMS, ela preenche a tabela supridesk_sms_temp
               if (trim($mensagemSMS) != '') {
                  $data_add = date("Y-m-d H:i:s");
                  $qry_insert = "INSERT INTO
                                    supridesk_sms
                                    (data_add, destinatario, mensagem, situacao)
                                 VALUES
                                    ('$data_add','999730716','$mensagemSMS','p')";
                  
                  $DB->query($qry_insert);

                  /*if ($ticket_tipo=='add') {
                     // Envia para o Gilson na abertura
                     $qry_insert = "INSERT INTO
                                       supridesk_sms
                                       (data_add, destinatario, mensagem, situacao)
                                    VALUES
                                       ('$data_add','999730716','$mensagemSMS','p')";

                     $DB->query($qry_insert);
                  }*/
               }
            }
         }
      }
   }

   
}
?>
