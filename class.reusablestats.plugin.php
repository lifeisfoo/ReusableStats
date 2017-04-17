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
$PluginInfo['ReusableStats'] = array(
   'Description' => 'Expose additiona smarty tag to get forum stats. Moreover add a special url to provide this data in json (enabled via configuration). Provide additional stats if you are using WhoisOnline plugin.',
   'Version' => '0.2.3',
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
    $Sender->SetData('total_views', $this->totalViews());
    if(C('EnabledPlugins.WhosOnline')){
      $Sender->SetData('role_members_online', $this->MembersOnlinePerRole());
    }

    /*

    HOWTO use these vars in your theme tpl file? Just add this code to your file!

    Threads: {$threads} |
    Posts: {$posts} |
    Members: {$members} |
    RoleMembers: {$role_members.PDI}
    RoleMembersOnline: {$role_members_online.PDI}
    TotalViews: {$total_views}

    HOWTO use these vars in your theme php file?

    $this->Data['threads'];
    $this->Data['posts'];
    $this->Data['members'];
    $this->Data['role_members']['PDI'];//vanilla role name, case sensitive
    $this->Data['role_members_online']['PDI'];//vanilla role name, case sensitive
    $this->Data['total_views'];

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
    if(C('Plugins.ReusableStats.JsonStat', '0') == true) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      $Sender->Render();
    }
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
    return $RolesCount;
  }

  private function MembersOnlinePerRole() {
    $Frequency = C('WhosOnline.Frequency', 4);
    $History = time() - $Frequency;
    $OnlineRolesCount = array();
    $Online = Gdn::SQL()->Select('r.Name')
              ->Select('u.userID', 'count', 'CountUsers')
              ->From('Whosonline w')
              ->Join('User u', 'w.UserID = u.UserID')
              ->Join('UserRole ur', 'u.UserID = ur.UserID')
              ->Join('Role r', 'ur.RoleID = r.RoleID')
              ->Where('w.Timestamp >=', date('Y-m-d H:i:s', $History))
              ->Where('w.Invisible', 0)//only non-invisible users
              ->GroupBy('r.Name')
              ->Get()->Result();
    foreach ($Online as $OnlinePerRole) {
      $OnlineRolesCount[$OnlinePerRole->Name] = $OnlinePerRole->CountUsers;
    }
    //fill eventually empty roles
    $RoleModel = new RoleModel();
    foreach ($RoleModel->GetArray() as $RoleID => $RoleName) {
      if(!$OnlineRolesCount[$RoleName]) {
        $OnlineRolesCount[$RoleName] = "0";
      }
    }
    return $OnlineRolesCount;
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
  
  private function totalViews() {
    // Get from cache to be nice to db.
    $totalViewCount = Gdn::cache()->get('ReusableStatsTotalViews');
    if ($totalViewCount !== Gdn_Cache::CACHEOP_FAILURE) {
      return $totalViewCount;
    }

    // If not in cache, get from db.
    $totalViewCount = Gdn::sql()
      ->select('CountViews', 'sum', 'TotalViewCount')
      ->from('Discussion')
      ->get()
      ->firstRow()
      ->TotalViewCount;

    // Store for later retrievable.
    Gdn::cache()->store(
      'ReusableStatsTotalViews',
      $totalViewCount,
      [Gdn_Cache::FEATURE_EXPIRY => 30] // 30 seconds
    );

    return $totalViewCount;
  }

  public function Setup() {
    if(!Gdn::Router()->MatchRoute('jsonstat'))  {
      Gdn::Router()->SetRoute('jsonstat', '/plugin/ReusableStatsJSON', 'Internal');
    }
  }
  
  public function OnDisable() {
    if(Gdn::Router()->MatchRoute('jsonstat')) {
      Gdn::Router()->DeleteRoute('jsonstat');
    }
  }
   
}