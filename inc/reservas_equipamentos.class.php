<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}
//***** SUPRISERVICE *****//

class Reservas_Equipamentos extends CommonDBTM {
    
    public $dohistory=true;
    
    //***** SUPRISERVICE *****//
   static function deleteReservaEquip($tabela,$id,$alocado,$tickets_id){
        global $DB;
        
        if($tabela == 'supridesk_pecas'){
            $sql = '';
        }else{
            $sql = ', `serial`';
        }
        
        $qryname = "SELECT `name` $sql FROM $tabela WHERE id = {$id}";
        $resultname = $DB->query($qryname);
        $rowname = $DB->fetch_assoc($resultname);
        
        
        switch ($tabela){
            case "glpi_printers":
                $equipamento = "impressora";                
                $classe = 'Printer';  
                break;
            case "glpi_monitors":
                $equipamento = "monitor";                
                $classe = 'Monitor';  
                break;
            case "glpi_computers":
                $equipamento = "computador";                
                $classe = 'Computer';  
                break;
            case "glpi_peripherals":
                $equipamento = "periférico";                
                $classe = 'Peripheral';  
                break;
            case "glpi_networkequipments":
                $equipamento = "periférico de rede";                
                $classe = 'NetworkEquipment';  
                break;
            case "supridesk_pecas":
                $equipamento = "peça";                
                $classe = 'Stock';  
                break;
        }
        
        
        //limpa os parametros que setam que um equipamento está em reserva.
            $query = "UPDATE {$tabela} 
                    SET 
                        alocado_para = NULL , 
                        alocado_tipo = NULL 
                    WHERE
                        id = {$id}
                        AND alocado_para = {$alocado} ";
                        
             
            
            if ($DB->query($query)) {
                
                $name = $rowname['name'];

                if($name == ''){
                    $name = $rowname['serial'];
                }

                $changes[0] = $id;
                $changes[1] = '';
                $changes[2] = "removido 1 $equipamento $name de reserva.";
                Log::history($alocado, 'User', $changes, $classe, Log::HISTORY_LOG_SIMPLE_MESSAGE);
                
                if($tickets_id != ''){
                    
                    //retira o  equipamento aplicado para um chamado.
                    $type = strtolower($classe);
                    $qrydel = "DELETE FROM `supridesk_tickets_equipamentos` 
                    WHERE `equipamentos_id` = {$id} AND `type` = '{$type}'";  
                    
                    // Alocar para o grupo Inventário
                    $qry = "INSERT INTO `glpi_groups_tickets` (`tickets_id`,`groups_id`,`type`) 
                            VALUES ({$tickets_id},23,2)";
                       
                    $DB->query($qry);
                }
                
                return $name;
                
            }else{
                return false;
            }
                
        
    }
    
        
    //***** SUPRISERVICE *****//
    function addReservaChamado($tabela,$id_item, $user_id,$tickets_id) {
        global $DB;
        
        if($tabela == 'supridesk_pecas'){
            $sql = '';
        }else{
            $sql = ', `serial`';
        }
        
        $query = "  SELECT 
                        `name`
                        $sql
                    FROM 
                        {$tabela}
                     WHERE 
                        `id` = $id_item                        
                        AND alocado_para IS NULL";
                        
                        
        $result = $DB->query($query);       
        
        
        if($DB->numrows($result) > 0){
            $data = $DB->fetch_assoc($result);
            $name = $data['name'];
            
            if($name == ''){
                $name = $data['serial'];
            }            
        }else{
            return false;
        }
        
        switch ($tabela){
            case "glpi_printers":
                $equipamento = "impressora";                
                $classe = 'Printer';  
                break;
            case "glpi_monitors":
                $equipamento = "monitor";                
                $classe = 'Monitor';  
                break;
            case "glpi_computers":
                $equipamento = "computador";                
                $classe = 'Computer';  
                break;
            case "glpi_peripherals":
                $equipamento = "periférico";                
                $classe = 'Peripheral';  
                break;
            case "glpi_networkequipments":
                $equipamento = "periférico de rede";                
                $classe = 'NetworkEquipment';  
                break;
            case "supridesk_pecas":
                $equipamento = "peça";                
                $classe = 'Stock';  
                break;
        }
        
                
        $changes[0] = $id_item;
        $changes[1] = '';
        $changes[2] = "adicionado 1 {$equipamento} {$name} de reserva (chamado).";
        Log::history($user_id, 'User', $changes, $classe, Log::HISTORY_LOG_SIMPLE_MESSAGE);        
        
        
        $query_update = "UPDATE {$tabela}
                        SET alocado_para = {$user_id}, alocado_tipo = 'c'
                        WHERE id = {$id_item}";        
                        
        $type = strtolower($classe);
        if($DB->query($query_update)){            
            
            $query_insert = "INSERT INTO supridesk_tickets_equipamentos
                        (tickets_id,equipamentos_id,type) VALUES ({$tickets_id},{$id_item},'".$type."')";
            $DB->query($query_insert);
            
            return $name;
            
        }else{
            return false;
        }
        
        
    }
    
    static function setStatus($tabela,$id,$status_id){
        global $DB;
        
        $query = "UPDATE {$tabela} SET `states_id` = {$status_id} WHERE `id` = {$id}";
        
        $DB->query($query);

        if (mysql_errno())
            return false;

        return true;
    }
    
    
    //***** SUPRISERVICE *****//
    function addReserva($tabela,$id_item, $user_id) {
        global $DB;
        
        switch ($tabela){
            case "glpi_printers":
                $equipamento = "impressora";                
                $classe = 'Printer';  
                $sql = ", `serial`";
                break;
            case "glpi_monitors":
                $equipamento = "monitor";                
                $classe = 'Monitor';  
                $sql = ", `serial`";
                break;
            case "glpi_computers":
                $equipamento = "computador";                
                $classe = 'Computer';  
                $sql = ", `serial`";
                break;
            case "glpi_peripherals":
                $equipamento = "periférico";                
                $classe = 'Peripheral';  
                $sql = ", `serial`";
                break;
            case "glpi_networkequipments":
                $equipamento = "periférico de rede";                
                $classe = 'NetworkEquipment';  
                $sql = ", `serial`";
                break;
            case "supridesk_pecas":
                $equipamento = "peça";                
                $classe = 'Stock';  
                break;
        }
        
        $query = "  SELECT 
                        `name`
                        $sql
                    FROM 
                        {$tabela}
                     WHERE 
                        `id` = $id_item                        
                        AND alocado_para IS NULL";
        
        $result = $DB->query($query);       
        
        
        if($DB->numrows($result) > 0){
            $data = $DB->fetch_assoc($result);
            $name = $data['name'];
            
            if($name == ''){
                $name = $data['serial'];
            }            
        }else{
            return false;
        }

                
        $changes[0] = $id_item;
        $changes[1] = '';
        $changes[2] = "adicionado 1 {$equipamento} {$name} de reserva.";
        Log::history($user_id, 'User', $changes, $classe, Log::HISTORY_LOG_SIMPLE_MESSAGE);
        
        $query_update = "UPDATE {$tabela}
                        SET alocado_para = {$user_id}, alocado_tipo = 'r'
                        WHERE id = {$id_item}";
                        
        if($DB->query($query_update)){
            return $name;
        }else{
            return false;
        }

        
    }
}

