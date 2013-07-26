# Reusable Stats plugin for Vanilla Forums
Expose additional smarty tags to get forum stats. Moreover add a special url to provide these data in json (enabled via configuration).

Requires Vanilla >= 2.0.18

## Howto use in your theme tpl files
```
Threads: {$threads} 
Posts: {$posts} <-- total posts count
Members: {$members} <-- total members count
RoleMembers: {$role_members.ROLE_NAME} (case sensitive) <-- total count of users in a role
RoleMembersOnline: {$role_members_online.ROLE_NAME} (case sensitive) <-- total count of users in a role that are online (Requires WhoisOnline pluigin)
```

### Using in theme's php files
```php
<?php echo $this->Data['threads']; ?>
<?php echo $this->Data['posts']; ?>
<?php echo $this->Data['members']; ?>
<?php echo $this->Data['role_members']['ROLE_NAME']; ?>
<?php echo $this->Data['role_members_online']['ROLE_NAME']; ?>
```

## Stats via Json 
If enabled (via plugin's settings page), a special url http://example.com/forum/jsonstat is exposed to provide
these stats in json format (e.g. grab them with jquery to show forum stats on your blog header...).

## Sponsor
Thanks to [johnnyzen](http://vanillaforums.org/profile/43062/johnnyzen) for making this happen.


##Author and License
Alessandro Miliucci, GPL v3, Icon by [Webdesigner Depot](http://www.webdesignerdepot.com)
