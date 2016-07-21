<?php

/*
 * @version $Id: networkport_vlan.class.php 18771 2012-06-29 08:49:19Z moyo $
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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class Atendimento extends CommonITILActor {

    // From CommonDBRelation
    public $itemtype_1 = 'Ticket_User';
    public $items_id_1 = 'tickets_users_id';
    protected $table = "supridesk_atendimentos";

    function addTempoAtendimento($ticket_id, $analista) {
        global $DB;

        $existe_atendimento = $this->verificaAtendimento($ticket_id);

        if ($existe_atendimento) {
            return null;
        } else {
            // $analista = $this->buscaAnalista($ticket_id);

            $query = "INSERT INTO `supridesk_atendimentos` ( `tickets_id`, `users_id`, `start_date`)
                         VALUES ( $ticket_id, $analista, NOW())";

            $DB->query($query);
        }

        //return null;
    }

    function verificaAtendimento($ticket_id) {
        global $DB;

        $query = "SELECT * FROM `supridesk_atendimentos` 
                WHERE `tickets_id` = '" . $ticket_id . "'
                AND `action` = 0 ";

        $result = $DB->query($query);
        $data = $DB->fetch_array($result);

        return $data;
    }

    function atualizaAtendimento($ticket_id, $analista) {
        global $DB;

        $existe_atendimento = $this->verificaAtendimento($ticket_id);

        if ($existe_atendimento) {

            date_default_timezone_set("Brazil/East");

            $data2 = date("Y-m-d H:i:s");
            $data1 = $existe_atendimento['start_date'];


            $hrs = $this->calculaHoras($data2, $data1);
            $cost = $this->custoAnalistaPorHora($ticket_id, $analista, $hrs);

            $query = "UPDATE `supridesk_atendimentos` 
                   SET `end_date` = NOW(), 
                       `action` = 1,
                       `hours_worked` = '" . $hrs . "',
                       `cost` = '" . $cost . "'
                   WHERE `tickets_id` = '" . $ticket_id . "'
                   AND `action` = 0 ";

            $DB->query($query);
        }
    }

    function buscaAnalista($ticket_id) {

        global $DB;

        $query = "SELECT users_id 
                FROM `glpi_tickets_users` 
                WHERE `tickets_id` = '" . $ticket_id . "'
                AND `type` = 2 ";

        $result = $DB->query($query);
        $data = $DB->fetch_assoc($result);

        return $data['users_id'];
    }

    //obs: a data2 deve ser maior que a data1
    function calculaHoras($data2, $data1) {

        $unix_data1 = strtotime($data1);
        $unix_data2 = strtotime($data2);

        $nHoras = ($unix_data2 - $unix_data1) / 3600;
        $nMinutos = (($unix_data2 - $unix_data1) % 3600) / 60;

        $total = sprintf('%02d:%02d', $nHoras, $nMinutos);

        return $total;
    }

    function somaHoras($times) {

         /*$times = array(
          '01:30:22',
          '34:17:03',
          ); */

        $seconds = 0;

        foreach ($times as $time) {
            list( $g, $i, $s ) = explode(':', $time);
            $seconds += $g * 3600;
            $seconds += $i * 60;
            $seconds += $s;
        }

        $hours = floor($seconds / 3600);
        $seconds -= $hours * 3600;
        $minutes = floor($seconds / 60);
        $seconds -= $minutes * 60;
        $seconds = $seconds;

        if ($hours <= 9) {
            $hours = "0" . $hours;
        }
        if ($minutes <= 9) {
            $minutes = "0" . $minutes;
        }
        if ($seconds <= 9) {
            $seconds = "0" . $seconds;
        }

        $total = sprintf("{$hours}:{$minutes}:{$seconds}");
        return $total;
    }

    function horasTotal($ticket_id) {

        global $DB;

        $query = "SELECT `hours_worked` 
                FROM `supridesk_atendimentos` 
                WHERE `tickets_id` = '" . $ticket_id . "'
                AND `action` = 1";

        $result = $DB->query($query);
        while ($data = $DB->fetch_assoc($result)) {
            $horas[] = $data['hours_worked'];
        }

        return $this->somaHoras($horas);
    }

    function custoAnalistaPorHora($ticket_id, $analista, $horas_trabalhadas) {
        global $DB;

        //$horas_trabalhadas = $this->horasTotal($ticket_id);     

        $query = "SELECT `value_time` 
                FROM `glpi_users` 
                WHERE `id` = '" . $analista . "'";

        $result = $DB->query($query);
        $data = $DB->fetch_assoc($result);

        $explode = explode(":", $horas_trabalhadas);
        $horas = $explode[0];
        $minutos = $explode[1];

        if ($horas > 0) {
            $horas = $horas * $data['value_time'];
        } else {
            $horas = 0;
        }

        if ($minutos > 0) {
            $min = $minutos * $data['value_time'];
            $min = $min / 60;
        } else {
            $min = 0;
        }

        $valor = $horas + $min;

        return $valor;
    }

    function custoHoras($ticket_id) {

        global $DB;

        $query = "SELECT SUM(`cost`) AS cost
                FROM `supridesk_atendimentos` 
                WHERE `tickets_id` = '" . $ticket_id . "'
                AND `action` = 1";

        $result = $DB->query($query);
        $data = $DB->fetch_assoc($result);

        $total = $data['cost'];

        return $total;
    }

    function horasUteis($ticket_id) {
        global $DB;
        //`glpi_tickets`.`solvedate`, `glpi_tickets`.`date`
        $query = "SELECT NOW() AS solvedate, `glpi_tickets`.`date`
                  FROM glpi_tickets
                  WHERE id = {$ticket_id}";

        $result = $DB->query($query);

        if ($DB->numrows($result) > 0)
            mysql_data_seek($result, 0);
        while ($row = $DB->fetch_array($result)) {
            $datas = array("inicial" => $row['date'], "final" => $row['solvedate'], "dt_inicio" => $row['date'], "dt_final" => $row['solvedate']);
        }

        $i = 1;
        if ($DB->numrows($result) > 0)
            mysql_data_seek($result, 0);
        while ($row = $DB->fetch_array($result)) {

            while ($datas['final'] > $datas['inicial']) {

                $sql = "SELECT DATE_ADD('{$datas['inicial']}', INTERVAL {$i} DAY) AS data;";
                $result_sql = $DB->query($sql);
                $row_sql = $DB->fetch_array($result_sql);
                
                //trabalhar data
                $dia_final = substr($datas['final'], 8, 2);
                $mes_final = substr($datas['final'], 5, 2) . "-";
                $ano_final = substr($datas['final'], 0, 4) . "-";
                $dt_final = "$ano_final$mes_final$dia_final";

                $dia_sql = substr($row_sql['data'], 8, 2);
                $mes_sql = substr($row_sql['data'], 5, 2) . "-";
                $ano_sql = substr($row_sql['data'], 0, 4) . "-";
                $dt_sql = "$ano_sql$mes_sql$dia_sql";

                if ($dt_final > $dt_sql) {

                    //pegar dia da semana, para nao somar finais de semana
                    $diaa = substr($datas['inicial'], 8, 2) . "-";
                    $mes = substr($datas['inicial'], 5, 2) . "-";
                    $ano = substr($datas['inicial'], 0, 4);
                    $dia_semana = date("w", mktime(0, 0, 0, $mes, $diaa, $ano));

                    if ($dia_semana != 5 && $dia_semana != 6) {
                        $intervalo[] = $row_sql['data'];
                    }
                }

                $datas['inicial'] = $row_sql['data'];
            }
        }
        //var_export($intervalo);
        if ($DB->numrows($result) > 0)
            mysql_data_seek($result, 0);
        while ($row = $DB->fetch_array($result)) {
            if ($intervalo) {
                $contador = count($intervalo);
                $horas_intervalo = $contador * 8;
                $horas_intervalo = "{$horas_intervalo}:00:00";
                $intervalo['total'] = $horas_intervalo;
                $total['intervalo'] = $intervalo['total'];
            }

            $hora_inicio = substr($datas['dt_inicio'], 11, 2) . ":";
            $min_inicio = substr($datas['dt_inicio'], 14, 2);

            $diaa = substr($datas['dt_inicio'], 8, 2);
            $mes = substr($datas['dt_inicio'], 5, 2) . "-";
            $ano = substr($datas['dt_inicio'], 0, 4);

            $total_inicio = "$hora_inicio$min_inicio:00";

            $unix_data1 = strtotime($datas['dt_inicio']);
            $unix_data2 = strtotime("$ano-$mes$diaa 18:00:00");

            $nHoras = ($unix_data2 - $unix_data1) / 3600;
            $nMinutos = (($unix_data2 - $unix_data1) % 3600) / 60;

            $total['hsinicio'] = sprintf('%02d:%02d', $nHoras, $nMinutos);

            if ($total_inicio < '12:00:00') {
                $var = substr($total['hsinicio'], 0, 2);
                $var = $var - 1;
                $var2 = substr($total['hsinicio'], 3, 2);
                $total['hsinicio'] = "$var:$var2";
            }

            if ($total_inicio > '12:00:00' && $total_inicio < '13:00:00') {

                $dt = "$ano-$mes$diaa 13:00:00";
                $dt1 = $datas['dt_inicio'];
                $sql_dif = "SELECT TIMEDIFF('$dt','$dt1') AS dif";
                $result_dif = mysql_query($sql_dif);
                $row_dif = mysql_fetch_assoc($result_dif);

                $min_dif = substr($total['hsinicio'], 3, 2);
                $min_sql = substr($row_dif['dif'], 3, 2);
                $total_dif = $min_sql - $min_dif;

                if ($total_dif == 0) {
                    $total_dif = "00";
                }/* elseif($total_dif < 10){
                  $total_dif = "0".$total_dif;
                  } */

                $var = substr($total['hsinicio'], 0, 2);
                $total['hsinicio'] = "$var:$total_dif";
            }
            
            //var_export($datas);

            $hora_final = substr($datas['dt_final'], 11, 2) . ":";
            $min_final = substr($datas['dt_final'], 14, 2);

            $diaa_f = substr($datas['dt_final'], 8, 2);
            $mes_f = substr($datas['dt_final'], 5, 2) . "-";
            $ano_f = substr($datas['dt_final'], 0, 4);

            $total_final = "$hora_final$min_final:00";

            $unix_data3 = strtotime("$ano_f-$mes_f$diaa_f 8:00:00");
            $unix_data4 = strtotime($datas['dt_final']);

            $nHoras_f = ($unix_data4 - $unix_data3) / 3600;
            $nMinutos_f = (($unix_data4 - $unix_data3) % 3600) / 60;

            $total['hsfinal'] = sprintf('%02d:%02d', $nHoras_f, $nMinutos_f);

            if ($total_final > '13:00:00') {

                $var = substr($total['hsfinal'], 0, 2);
                $var = $var - 1;
                $var2 = substr($total['hsfinal'], 3, 2);
                $total['hsfinal'] = "$var:$var2";
            }

            if ($total_final < '13:00:00' && $total_final > '12:00:00') {

                $dt_f = "$ano_f-$mes_f$diaa_f 13:00:00";
                $dt1_f = $datas['dt_final'];
                $sql_dif_f = "SELECT TIMEDIFF('$dt_f','$dt1_f') AS dif";
                $result_dif_f = mysql_query($sql_dif_f);
                $row_dif_f = mysql_fetch_assoc($result_dif_f);

                $min_dif_f = substr($total['hsfinal'], 3, 2);
                $min_sql_f = substr($row_dif_f['dif'], 3, 2);
                $total_dif_f = $min_sql_f - $min_dif_f;

                if ($total_dif_f == 0) {
                    $total_dif_f = "00";
                }/* elseif($total_dif_f < 10){
                  $total_dif_f = "0".$total_dif_f;
                  } */
                $var = substr($total['hsfinal'], 0, 2);        
                $total['hsfinal'] = "$var:$total_dif";                 
                
            }
            
            $ti = "$ano-$mes$diaa 18:00:00";
            $tf = "$ano_f-$mes_f$diaa_f 18:00:00";

            if($ti == $tf){

                if($datas['dt_inicio'] > $ti && $datas['dt_final'] > $tf){

                    $hor_dif = substr($total['hsinicio'], 0, 2);
                    $min_dif = substr($total['hsinicio'], 3, 2);                

                    $hor_dif_f = substr($total['hsfinal'], 0, 2);
                    $min_sql_f = substr($total['hsfinal'], 3, 2);

                    $hhfim = "$hora_final$min_final:00";
                    $hhinicio = "$hora_inicio$min_inicio:00";

                    $totalfim = "$ano-$mes$diaa $hhfim";
                    $totalinicio = "$ano-$mes$diaa $hhinicio";

                    $sql_total = "SELECT TIMEDIFF('$totalfim','$totalinicio') AS hora_total";
                    $result_total = mysql_query($sql_total);
                    $row_total = mysql_fetch_assoc($result_total);
                    //var_export($row_total['hora_total']);
                    //echo "$total_hora:$total_minuto";

                    $total['hsinicial'] = "00:00:00";
                    $total['hsfinal'] = $row_total['hora_total'];
                    //var_export($row_total['hora_total']);
                   // var_export($datas[$row['id']]);
                }

            }
        }
        
        //var_export($total);
        //die();

        $array_soma = $this->somaHoras($total);

        return $array_soma;
    }
    
    function minEmhoras($mins) {
        // Se os minutos estiverem negativos
        if ($mins < 0)
            $min = abs($mins);
        else
            $min = $mins;
 
        // Arredonda a hora
        $h = floor($min / 60);
        $m = ($min - ($h * 60)) / 100;
        $horas = $h + $m;
 
        // Matemática da quinta série
        // Detalhe: Aqui também pode se usar o abs()
        if ($mins < 0)
            $horas *= -1;
 
        // Separa a hora dos minutos
        $sep = explode('.', $horas);
        $h = $sep[0];
        if (empty($sep[1]))
            $sep[1] = 00;
 
        $m = $sep[1];
 
        // Aqui um pequeno artifício pra colocar um zero no final
        if (strlen($m) < 2)
            $m = $m . 0;
 
        return sprintf('%02d:%02d', $h, $m);
    }
    
    
    function updateEmAtendimento($ticket_id){
        
        global $DB;
        
        $query = "UPDATE `glpi_tickets` 
                   SET `status` = 'atendimento'
                   WHERE `id` = '" . $ticket_id . "'
                 ";

        $DB->query($query);
        
    }

}

?>
