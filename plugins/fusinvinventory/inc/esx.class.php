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
   @author    Walid Nouh
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

class PluginFusinvinventoryESX extends PluginFusioninventoryOCSCommunication {

   /**
    * Get all devices and put in taskjobstate each task for
    * each device for each agent
    * 
    * @param type $taskjobs_id id of taskjob esx
    * 
    * @return uniqid value
    */
   function prepareRun($taskjobs_id) {

      $task       = new PluginFusioninventoryTask();
      $job        = new PluginFusioninventoryTaskjob();
      $joblog     = new PluginFusioninventoryTaskjoblog();
      $jobstate  = new PluginFusioninventoryTaskjobstate();
   
      $uniqid= uniqid();
   
      $job->getFromDB($taskjobs_id);
      $task->getFromDB($job->fields['plugin_fusioninventory_tasks_id']);
   
      $communication= $task->fields['communication'];
   
      //list all agents
      $agent_actions     = importArrayFromDB($job->fields['action']);
      $task_definitions  = importArrayFromDB($job->fields['definition']);
      $agent_actionslist = array();
      foreach($agent_actions as $targets) {
         foreach ($targets as $itemtype => $items_id) {
            $item = new $itemtype();
            // Detect if agent exists
            if($item->getFromDB($items_id)) {
               if($task->fields['communication'] == 'push') {
                  $agentStatus = $job->getStateAgent('1',$items_id);
                  if ($agentStatus) {
                     $agent_actionslist[$items_id] = 1;
                  }
               } elseif($task->fields['communication'] == 'pull') {
                  $agent_actionslist[$items_id] = 1;
               }
            }
         }
      }

      // *** Add jobstate
      if(empty($agent_actionslist)) {
         $a_input= array();
         $a_input['plugin_fusioninventory_taskjobs_id'] = $taskjobs_id;
         $a_input['state']                              = 0;
         $a_input['plugin_fusioninventory_agents_id']   = 0;
         $a_input['uniqid']                             = $uniqid;

         foreach ($task_definitions as $task_definition) {
            foreach ($task_definition as $task_itemtype => $task_items_id) {
               $a_input['itemtype'] = $task_itemtype;
               $a_input['items_id'] = $task_items_id;
               $jobstates_id= $jobstate->add($a_input);
               //Add log of taskjob
               $a_input['plugin_fusioninventory_taskjobstates_id']= $jobstates_id;
               $a_input['state'] = PluginFusioninventoryTaskjoblog::TASK_PREPARED;
               $a_input['date']  = date("Y-m-d H:i:s");
               $joblog->add($a_input);

               $jobstate->changeStatusFinish($jobstates_id, 
                                              0, 
                                              'PluginFusinvinventoryESX', 
                                              1, 
                                              "Unable to find agent to run this job");
            }
         }
         $job->update($job->fields);
      } else {
         foreach($agent_actions as $targets) {
            foreach ($targets as $items_id) {

               if ($communication == "push") {
                  $_SESSION['glpi_plugin_fusioninventory']['agents'][$items_id] = 1;
               }
               
               foreach ($task_definitions as $task_definition) {
                  foreach ($task_definition as $task_itemtype => $task_items_id) {
                     $a_input = array();
                     $a_input['plugin_fusioninventory_taskjobs_id'] = $taskjobs_id;
                     $a_input['state']                              = 0;
                     $a_input['plugin_fusioninventory_agents_id']   = $items_id;
                     $a_input['itemtype']                           = $task_itemtype;
                     $a_input['items_id']                           = $task_items_id;
                     $a_input['uniqid']                             = $uniqid;
                     $a_input['date']                               = date("Y-m-d H:i:s");
                     $jobstates_id = $jobstate->add($a_input);
                     //Add log of taskjob
                     $a_input['plugin_fusioninventory_taskjobstates_id'] = $jobstates_id;
                     $a_input['state']= PluginFusioninventoryTaskjoblog::TASK_PREPARED;
      
                     $joblog->add($a_input);
                     unset($a_input['state']);
                  }
               }
            }
         }
   
         $job->fields['status']= 1;
         $job->update($job->fields);
      }
      return $uniqid;
   }
   


   /**
    * Get ESX jobs for this agent
    * 
    * @param type $device_id deviceid of the agent runing and want job info to run
    * 
    * @return $response array
    */
   function run($a_Taskjobstates, $response) {
      $response      = array();
      $pfTaskjobstate = new PluginFusioninventoryTaskjobstate();
      $pfTaskjoblog = new PluginFusioninventoryTaskjoblog();
      $credential    = new PluginFusioninventoryCredential();
      $credentialip  = new PluginFusioninventoryCredentialIp();
      
      foreach ($a_Taskjobstates as $taskjobstatedatas) {
         $credentialip->getFromDB($taskjobstatedatas['items_id']);
         $credential->getFromDB($credentialip->fields['plugin_fusioninventory_credentials_id']);
         $responsetmp = array();
         $responsetmp['uuid']        = $taskjobstatedatas['id'];
         $responsetmp['host']        = $credentialip->fields['ip'];
         $responsetmp['user']        = $credential->fields['username'];
         $responsetmp['password']    = $credential->fields['password'];
         $response['jobs'][] = $responsetmp;
         
         $pfTaskjobstate->changeStatus($taskjobstatedatas['id'], 1);
         $pfTaskjoblog->addTaskjoblog($taskjobstatedatas['id'],
                                 '0',
                                 'PluginFusioninventoryAgent',
                                 '1',
                                 '');
      }
      return $response;
   }
}

?>