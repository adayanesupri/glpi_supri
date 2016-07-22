<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2012 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Behaviors. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    David Durieux
   @co-author 
   @copyright Copyright (c) 2010-2012 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2010
 
   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

require_once(GLPI_ROOT."/plugins/fusioninventory/inc/ocscommunication.class.php");

class PluginFusinvsnmpNetdiscovery extends PluginFusioninventoryOCSCommunication {

   // Get all devices and put in taskjobstat each task for each device for each agent
   function prepareRun($taskjobs_id) {

      $pfTask = new PluginFusioninventoryTask();
      $pfTaskjob = new PluginFusioninventoryTaskjob();
      $pfTaskjoblog = new PluginFusioninventoryTaskjoblog();
      $pfTaskjobstate = new PluginFusioninventoryTaskjobstate();
      $pfAgentmodule = new PluginFusioninventoryAgentmodule();
      $pfIPRange = new PluginFusioninventoryIPRange();
      $pfAgent = new PluginFusioninventoryAgent();

      $uniqid = uniqid();

      $pfTaskjob->getFromDB($taskjobs_id);
      $pfTask->getFromDB($pfTaskjob->fields['plugin_fusioninventory_tasks_id']);

      $communication = $pfTask->fields['communication'];
      
      //list all iprange
      $a_iprange = importArrayFromDB($pfTaskjob->fields['definition']);
      $count_ip = 0;
      $a_iprangelist = array();
      $a_subnet_nbip = array();
      foreach ($a_iprange as $iprange) {
         $iprange_id = current($iprange);
         $a_iprangelist[] = $iprange_id;
         $pfIPRange->getFromDB($iprange_id);
         $s = $pfIPRange->getIp2long($pfIPRange->fields['ip_start']);
         $e = $pfIPRange->getIp2long($pfIPRange->fields['ip_end']);
         $a_subnet_nbip[$iprange_id] = $e-$s;
         $count_ip += $e-$s;
      }

      //list all agents
      $a_agent = importArrayFromDB($pfTaskjob->fields['action']);
      $dynagent = 0;
      $a_agentlist = array();
      foreach ($a_agent as $agent) {
         $agent_id = current($agent);
         if ($agent_id == '.1') {
            $dynagent = 1;
         } else if ($agent_id == '.2') {
            $dynagent = 2;
         } else {
            // Detect if agent exists
            if ($pfAgent->getFromDB($agent_id)) {
               if ($pfTask->fields['communication'] == 'pull') {
                  $a_agentlist[$agent_id] = 1;
               } else {
                  $pfAgent->getFromDB($agent_id);
                  $agentStatus = $pfTaskjob->getStateAgent('1',$agent_id);
                  if ($agentStatus) {
                     $a_agentlist[$agent_id] = 1;
                  }
               }
            }
         }
      }
      if ($dynagent == '1') {
         $a_agents = $pfAgentmodule->getAgentsCanDo('NETDISCOVERY');
         foreach($a_agents as $data) {
            if (($count_ip / 10) >= count($a_agentlist)) {
               $pfAgent->getFromDB($data['id']);
               $a_ip = $pfAgent->getIPs($data['id']);
               foreach($a_ip as $ip) {
                  if ($pfTask->fields['communication'] == 'push') {
                     $agentStatus = $pfTaskjob->getStateAgent('1',$data['id']);
                     if ($agentStatus) {
                        $a_agentlist[$data['id']] = 1;
                     }
                  } else if ($pfTask->fields['communication'] == 'pull') {
                     $a_agentlist[$data['id']] = 1;
                  }
               }
            }
         }         
      }


      if ($dynagent == '2') {
         // Dynamic with subnet
         $pfSnmpinventory = new PluginFusinvsnmpSnmpinventory();
         $taskvalid = 0;
         foreach($a_subnet_nbip as $iprange_id=>$nbips) {
            //$maxagentpossible = $nbips/10;
            $pfIPRange->getFromDB($iprange_id);
            $a_agentListComplete = array();
            $a_agentList = $pfSnmpinventory->getAgentsSubnet($nbips, "push", "",
                                                      $pfIPRange->fields['ip_start'],
                                                      $pfIPRange->fields['ip_end']);
            if (isset($a_agentList)) {
               $a_agentListComplete = array_merge($a_agentListComplete, $a_agentList);
            }

            if (!isset($a_agentListComplete) or empty($a_agentListComplete)) {
               $a_input = array();
               $a_input['plugin_fusioninventory_taskjobs_id'] = $taskjobs_id;
               $a_input['state'] = 1;
               $a_input['plugin_fusioninventory_agents_id'] = 0;
               $a_input['itemtype'] = 'PluginFusioninventoryIPRange';
               $a_input['items_id'] = $iprange_id;
               $a_input['uniqid'] = $uniqid;
               $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                  //Add log of taskjob
                  $a_input['plugin_fusioninventory_taskjobstates_id'] = $Taskjobstates_id;
                  $a_input['state'] = 7;
                  $a_input['date'] = date("Y-m-d H:i:s");
                  $pfTaskjoblog->add($a_input);

               $pfTaskjobstate->changeStatusFinish($Taskjobstates_id,
                                                                       0,
                                                                       'PluginFusioninventoryIPRange',
                                                                       1,
                                                                       "Unable to find agent to run this job");
               $input_taskjob = array();
               $input_taskjob['id'] = $pfTaskjob->fields['id'];
               //$input_taskjob['status'] = 1;
               $pfTaskjob->update($input_taskjob);
            } else {
               $s = $pfIPRange->getIp2long($pfIPRange->fields['ip_start']);
               $e = $pfIPRange->getIp2long($pfIPRange->fields['ip_end']);
               $nbIpAgent = ceil(($e-$s) / count($a_agentListComplete));
               $iptimes = 0;

               foreach ($a_agentListComplete as $agent_id) {

                  $_SESSION['glpi_plugin_fusioninventory']['agents'][$agent_id] = 1;
                  //Add jobstate and put status (waiting on server = 0)
                  $a_input = array();
                  $a_input['plugin_fusioninventory_taskjobs_id'] = $taskjobs_id;
                  $a_input['state'] = 0;
                  $a_input['plugin_fusioninventory_agents_id'] = $agent_id;
                  $a_input['itemtype'] = 'PluginFusioninventoryIPRange';
                  $a_input['uniqid'] = $uniqid;

                  $a_input['items_id'] = $iprange_id;
                  if (($iptimes + $nbIpAgent) > ($e-$s)) {
                     $a_input['specificity'] = $iptimes."-".($e-$s);
                  } else {
                     $a_input['specificity'] = $iptimes."-".($iptimes + $nbIpAgent);
                  }
                  $taskvalid++;
                  $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                     //Add log of taskjob
                     $a_input['plugin_fusioninventory_taskjobstates_id'] = $Taskjobstates_id;
                     $a_input['state'] = 7;
                     $a_input['date'] = date("Y-m-d H:i:s");
                     $pfTaskjoblog->add($a_input);
                     unset($a_input['state']);
                  $iptimes += $nbIpAgent + 1;
                  if (($iptimes) >= ($e-$s+1)) {
                     break;
                  }
                  $input_taskjob = array();
                  $input_taskjob['id'] = $pfTaskjob->fields['id'];
                  $input_taskjob['status'] = 1;
                  $pfTaskjob->update($input_taskjob);
               }               
            }
         }
         if ($taskvalid == "0") {
            $pfTaskjob->reinitializeTaskjobs($pfTaskjob->fields['plugin_fusioninventory_tasks_id']);
         }
      // *** Add jobstate
      } else if (count($a_agentlist) == 0) {
         $a_input = array();
         $a_input['plugin_fusioninventory_taskjobs_id'] = $taskjobs_id;
         $a_input['state'] = 1;
         $a_input['plugin_fusioninventory_agents_id'] = 0;
         $a_input['itemtype'] = 'PluginFusioninventoryIPRange';
         $a_input['items_id'] = 0;
         $a_input['uniqid'] = $uniqid;
         $Taskjobstates_id = $pfTaskjobstate->add($a_input);
            //Add log of taskjob
            $a_input['plugin_fusioninventory_taskjobstates_id'] = $Taskjobstates_id;
            $a_input['state'] = 7;
            $a_input['date'] = date("Y-m-d H:i:s");
            $pfTaskjoblog->add($a_input);

         $pfTaskjobstate->changeStatusFinish($Taskjobstates_id,
                                                                 0,
                                                                 'PluginFusioninventoryIPRange',
                                                                 1,
                                                                 "Unable to find agent to run this job");
         $input_taskjob = array();
         $input_taskjob['id'] = $pfTaskjob->fields['id'];
         //$input_taskjob['status'] = 1;
         $pfTaskjob->update($input_taskjob);
      } else {
         $iptimes = 0;
         $nbIpadded = 0;
         $iptimes = 0;
         $break = 0;
         $numberIpByAgent = ceil($count_ip / (count($a_agentlist)));
         $a_iprangelistTmp = $a_iprangelist;
         $ip_id = array_shift($a_iprangelistTmp);
         foreach ($a_agentlist as $agent_id => $ip) {

            //Add jobstate and put status (waiting on server = 0)
            $a_input = array();
            $a_input['plugin_fusioninventory_taskjobs_id'] = $taskjobs_id;
            $a_input['state'] = 0;
            $a_input['plugin_fusioninventory_agents_id'] = $agent_id;
            $a_input['itemtype'] = 'PluginFusioninventoryIPRange';
            $a_input['uniqid'] = $uniqid;

//            $nbIpAgent = $numberIpByAgent;
            $nbIpadded = 0;
            foreach($a_iprangelist as $iprange_id) {
               if ($ip_id == $iprange_id) {
                  $pfIPRange->getFromDB($iprange_id);
                  $s = $pfIPRange->getIp2long($pfIPRange->fields['ip_start']);
                  $e = $pfIPRange->getIp2long($pfIPRange->fields['ip_end']);
                  if ($communication == "push") {
                     $_SESSION['glpi_plugin_fusioninventory']['agents'][$agent_id] = 1;
                  }

                  $a_input['items_id'] = $iprange_id;
                  $nbIpAgent = $numberIpByAgent - $nbIpadded;
                  if (($iptimes + $nbIpAgent) > ($e-$s)) {
                     $a_input['specificity'] = $iptimes."-".($e-$s);
                     $nbIpadded = ($e-$s) - $iptimes;
                     $ip_id = array_shift($a_iprangelistTmp);
                     $iptimes = 0;
                  } else {
                     $a_input['specificity'] = $iptimes."-".($iptimes + $nbIpAgent);
                     $iptimes += $nbIpAgent+1;
                     $nbIpadded = 0;
                     $break = 1;
                  }
                  $Taskjobstates_id = $pfTaskjobstate->add($a_input);
                     //Add log of taskjob
                     $a_input['plugin_fusioninventory_taskjobstates_id'] = $Taskjobstates_id;
                     $a_input['state'] = 7;
                     $a_input['date'] = date("Y-m-d H:i:s");
                     $pfTaskjoblog->add($a_input);
                     unset($a_input['state']);
               }
            }
            $input_taskjob = array();
            $input_taskjob['id'] = $pfTaskjob->fields['id'];
            $input_taskjob['status'] = 1;
            $pfTaskjob->update($input_taskjob);
         }
      }
      return $uniqid;
   }



   // When agent contact server, this function send datas to agent
   /*
    * $a_Taskjobstates array with all taskjobstatus
    *
    */
   function run($a_Taskjobstates) {

      $pfAgent = new PluginFusioninventoryAgent();
      $pfAgentconfig = new  PluginFusinvsnmpAgentconfig();
      $pfTaskjobstate = new PluginFusioninventoryTaskjobstate();
      $pfTaskjob = new PluginFusioninventoryTaskjob();
      $pfTaskjoblog = new PluginFusioninventoryTaskjoblog();
      $pfIPRange = new PluginFusioninventoryIPRange();
      $pfConfigSecurity = new PluginFusinvsnmpConfigSecurity();
      $pfCommunicationSNMP = new PluginFusinvsnmpCommunicationSNMP();


      $current = current($a_Taskjobstates);
      $pfAgent->getFromDB($current['plugin_fusioninventory_agents_id']);

      $pfAgentconfig->loadAgentconfig($pfAgent->fields['id']);
      $sxml_option = $this->message->addChild('OPTION');
      $sxml_option->addChild('NAME', 'NETDISCOVERY');

      $a_versions = importArrayFromDB($pfAgent->fields["version"]);
      if (((isset($a_versions["NETDISCOVERY"])) AND ($a_versions["NETDISCOVERY"] >= 1.3))
              OR !isset($a_versions["NETDISCOVERY"])) {
         $sxml_option->addChild('DICOHASH', md5_file(GLPI_PLUGIN_DOC_DIR."/fusinvsnmp/discovery.xml"));
      }
      if (($pfAgentconfig->fields["senddico"] == "1")) {
         
         if (((isset($a_versions["NETDISCOVERY"]))
                 AND ($a_versions["NETDISCOVERY"] >= 1.3))) {

            $sxml_option->addChild('DICO', file_get_contents(GLPI_PLUGIN_DOC_DIR."/fusinvsnmp/discovery.xml"));
         }
         $input = array();
         $input['id'] = $pfAgentconfig->fields['id'];
         $input["senddico"] = "0";
         $pfAgentconfig->update($input);
      }

      $sxml_param = $sxml_option->addChild('PARAM');
         $sxml_param->addAttribute('CORE_DISCOVERY', "1");
         $sxml_param->addAttribute('THREADS_DISCOVERY', $pfAgentconfig->fields["threads_netdiscovery"]);
         $sxml_param->addAttribute('PID', $current['id']);

      $changestate = 0;
      foreach ($a_Taskjobstates as $taskjobstatedatas) {
         $sxml_rangeip = $sxml_option->addChild('RANGEIP');
            $pfTaskjob->getFromDB($taskjobstatedatas['plugin_fusioninventory_taskjobs_id']);
            $pfTaskjobstate->getFromDB($taskjobstatedatas['id']);
            $pfIPRange->getFromDB($taskjobstatedatas['items_id']);

            $sxml_rangeip->addAttribute('ID', $pfIPRange->fields['id']);

            if (!is_null($pfTaskjobstate->fields['specificity'])) {
               $a_split = explode("-", $pfTaskjobstate->fields['specificity']);

               $first_ip = $pfIPRange->getIp2long($pfIPRange->fields["ip_start"]);

               $last_ip = long2ip($first_ip + $a_split[1]);
               $first_ip = long2ip($first_ip + $a_split[0]);
               $sxml_rangeip->addAttribute('IPSTART', $first_ip);
               $sxml_rangeip->addAttribute('IPEND', $last_ip);
            } else {
               $sxml_rangeip->addAttribute('IPSTART', $pfIPRange->fields["ip_start"]);
               $sxml_rangeip->addAttribute('IPEND', $pfIPRange->fields["ip_end"]);
            }
            $sxml_rangeip->addAttribute('ENTITY', $pfIPRange->fields["entities_id"]);

            if ($changestate == '0') {
               $pfTaskjobstate->changeStatus($pfTaskjobstate->fields['id'], 1);
               $pfTaskjoblog->addTaskjoblog($pfTaskjobstate->fields['id'],
                                       '0',
                                       'PluginFusioninventoryAgent',
                                       '1',
                                       '');
               $changestate = $pfTaskjobstate->fields['id'];
            } else {
               $pfTaskjobstate->changeStatusFinish($pfTaskjobstate->fields['id'],
                                                                 $taskjobstatedatas['items_id'],
                                                                 $taskjobstatedatas['itemtype'],
                                                                 0,
                                                                 "Merged with ".$changestate);
            }
      }
      $snmpauthlist=$pfConfigSecurity->find("`is_deleted`='0'");
      if (count($snmpauthlist)){
         foreach ($snmpauthlist as $snmpauth){
            $pfCommunicationSNMP->addAuth($sxml_option, $snmpauth['id']);
         }
      }
      return $this->message;
   }
}

?>