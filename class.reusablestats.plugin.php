<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2013 Alessandro Miliucci <lifeisfoo@gmail.com>
This file is part of ReusableStats <https://github.com/lifeisfoo/ReusableStats>

ReusableStats is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

ReusableStats is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with ReusableStats. If not, see <http://www.gnu.org/licenses/>.
*/

// Define the plugin:
$PluginInfo['Reusable Stats'] = array(
   'Description' => 'Expose additiona smarty tag to get forum stats. Moreover add a special url to provide this data in json (enabled via configuration).',
   'Version' => '0.1',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'MobileFriendly' => TRUE,
   'SettingsUrl' => '/plugin/reusablestats',
   'SettingsPermission' => 'Garden.AdminUser.Only',
   'Author' => "Alessandro Miliucci",
   'AuthorEmail' => 'lifeisfoo@gmail.com',
   'AuthorUrl' => 'http://forkwait.net'
);

class ReusableStatsPlugin extends Gdn_Plugin {

  public function __construct() {}
  
  public function Base_Render_Before($Sender) {
    $Sender->SetData('threads', $this->DiscussionsCount());
    $Sender->SetData('posts', $this->CommentsCount());
    $Sender->SetData('members', $this->MembersCount());
    $Sender->SetData('role_members', $this->MembersPerRole());
    /*HOWTO use these vars in your theme tpl file? Just add this code to your file!

    Threads: {$threads} |
    Posts: {$posts} |
    Members: {$members} |
    RoleMembers: {$role_members.PDI}

    */
  }

  public function PluginController_ReusableStats_Create($Sender) {
    //Settings page
    $Sender->Permission('Garden.Plugins.Manage');
    $Sender->AddSideMenu();
    $Sender->Title('Reusable Stats Settings');
    $ConfigurationModule = new ConfigurationModule($Sender);
    $ConfigurationModule->RenderAll = True;
    $Schema = array(
        'Plugins.ReusableStats.JsonStat' => 
        array('LabelCode' => T('Expose stats via json at http://example.com/forum/jsonstat'), 
              'Control' => 'CheckBox', 
              'Default' => C('Plugins.ReusableStats.JsonStat', '0')
        )
    );
    $ConfigurationModule->Schema($Schema);
    $ConfigurationModule->Initialize();
    $Sender->View = dirname(__FILE__) . DS . 'views' . DS . 'settings.php';
    $Sender->ConfigurationModule = $ConfigurationModule;
    $Sender->Render();
  }

  public function PluginController_ReusableStatsJSON_Create($Sender) {
    $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
    $Sender->DeliveryType(DELIVERY_TYPE_DATA);
    $Sender->Render();
  }

  private function MembersCount(){
    $UserModel = new UserModel();
    return $UserModel->GetCountWhere();
  }
    
  private function DiscussionsCount(){
    $DiscussionModel = new DiscussionModel();
    return $DiscussionModel->GetCount();
  }
   
  private function CommentsCount(){
    $CommentModel = new CommentModel();
    return $CommentModel->GetCountWhere();
  }

  private function MembersPerRole() {
    $RoleModel = new RoleModel();
    $RolesCount = array();
    foreach ($RoleModel->GetArray() as $RoleID => $RoleName) {
      $RolesCount[$RoleName] = $this->RoleMembers($RoleID);
    }
    //var_dump($RolesCount);
    return $RolesCount;
  }

  private function RoleMembers($RoleID){
    $Data = Gdn::SQL()->Select('u.UserID', 'count', 'UserCount')
                    ->From('User u')
                    ->Join('UserRole ur', 'u.UserID = ur.UserID ')
                    ->Where('u.Deleted', 0)
                    ->Where('ur.RoleID', $RoleID)
                    ->Get()
                    ->FirstRow();
    return $Data === FALSE ? 0 : $Data->UserCount;
  }
  
  public function Setup() {
    Gdn::Router()->SetRoute('jsonstat', '/plugin/ReusableStatsJSON', 'Internal');
  }
  
  public function OnDisable() {
    Gdn::Router()->DeleteRoute('jsonstat');
  }
   
}