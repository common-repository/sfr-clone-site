=== Plugin Name ===
Contributors: stakabo
Donate link: http://plugin.sfroy.net
Tags: Network, Multi site, Admin, Copy, Duplicate
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: 1.1.1


== Description ==

This plugin is for admins running a Wordpress network.

This plugin will clone a site to another site.
It will take Site A and duplicate to Site B.



This plugin is for Super Admin only.

Make sure you backup all your files and database.

I take no blame if you break your wp install or wipe files if not used correctly.
         

== Installation ==

0. BACK UP ALL YOUR DATABASES AND FILES BEFORE YOU START
1. Upload `sfr_clone_site` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Look in you 'Super Admin' menu for 'Clone site'
4. Set the directory to save the database .sql files
5. Set the file and theme backup directory
6. Set your WP install directory
7. Enjoy







== FAQ ==


= Plugin will take Site A and duplicate to Site B. =

What the plugin will do:
    
    1. Backup Site B database tables and files into a .tar archive somewhere on the server. 
         (the location of the backup can be define in the plugin's options).
    2. Duplicate Site A files and database to the location of Site B.
         (This action will also copy all theme files but will not change the theme folder name or screenshot.png)
    3. Update all of the newly duplicated database tables to reflect the value of Site B original value (URL and such).
         (This include all tables, event plugin table.)
         
         
This plugin was written to automate the task of syncing a devolvement site with a production site. 
You can freely work a theme/site for a client on one url and then move it to the production url in seconds.

When I start a new project, i will create 2 new site on my WP network. ( dev-client_name.example.com and client_name.example.com ).
I will create to new theme in my themes folder (Devlopement and production)
I map the client's domaine name to client_name.example.com, and work on dev-client_name.example.com. 
Once the client validate the site on dev-client_name.example.com i will use this plugin to clone the development site to client_name.example.com.
         
         
If you find that some stuff don't duplicate has it should, let me know.
        
 


== Screenshots ==

1. Admin Page


== Changelog ==

= 1.1.1 =
* Minor bug fix on one value.

= 1.1 = 
* Small change to the readme.txt file

= 1.0 = 
* Fixed some path bugs

= 0.9 =
* First realeases

== TO DO ==

Add some user feedback. the plug-in will not (almost not) warn you it fails.
Any help on improving the plugin would be appreciated.