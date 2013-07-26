# Reusable Stats plugin for Vanilla Forums
Expose additional smarty tags to get forum stats. Moreover add a special url to provide these data in json (enabled via configuration).

Requires Vanilla >= 2.0.18

## Howto use in your theme
```
Threads: {$threads} 
Posts: {$posts}
Members: {$members}
RoleMembers: {$role_members.ROLE_NAME} (case sensitive)
```

## Stats via Json 
If enabled (via plugin's settings page), a special url http://example.com/forum/jsonstat is exposed to provide
these stats in json format (e.g. grab them with jquery to show forum stats on your blog header...).

## Sponsor
Thanks to [johnnyzen](http://vanillaforums.org/profile/43062/johnnyzen) for making this happen.


##Author and License
Alessandro Miliucci, GPL v3, Icon by [Webdesigner Depot](http://www.webdesignerdepot.com)
