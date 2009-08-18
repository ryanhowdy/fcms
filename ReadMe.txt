FAMILY CONNECTIONS 2.0.1

  Keep your family "Connected" with this content management system (CMS) designed 
  specifically with family's in mind. Key features are: a message board, a photo 
  gallery, a blog-like "Family News" section, and an address book.




I. Installation
----------------

  1. Upload the entire contents of FCMS_2.0.1.zip to your web host.

  2. Set permissions to the following folders to 777 (read, write, and execute for 
     user, group and other)

    inc/
    gallery/avatar/
    gallery/documents/
    gallery/photos/
    gallery/upimages/

  3. Go to http://www.yourdomain.com/fcms/ where yourdomain.com is your domain and  
     fcms/ is the directory you used to install FCMS.

  4. It is recommended that you delete the install.php file after installation.




II. Upgrading from 2.0 to 2.0.1
--------------------------------
  ** DO NOT DELETE THE FOLLOWING FILE **
    inc/config_inc.php

  ** DO NOT TOUCH ANY FILES IN THE FOLLOWING DIRECTORIES **
    gallery/avatar/
    gallery/documents/
    gallery/photos/
    gallery/upimages/

  1. It is recommended that you backup your entire site, including your
     MySQL database before upgrading.

  2. Upload the following list of files to your web host
     (overwriting any previous files).

        admin/upgrade.php
        inc/fcms.js
        inc/gallery_class.php
        inc/getChat.php
        inc/members_class.php
        inc/util_inc.php
        chat.php
        install.php
        settings.php
   
  3. Login as the administrator and visit the upgrade section and run the
     upgrade script.

  4. Delete the install.php file from your web host (if it still exists).




III. Change Log
----------------
 2.0.1

    - fixed "Upgrading to 2.0 can cause some members themes not to work" Ticket #76
    - fixed "Upgrading to 2.0 can prevent users from editing settings" Ticket #78
    - fixed "Admin members doesn't show all members" Ticket #74
    - fixed "Chat Room doesn't ouput username" Ticket #75
    - fixed "Upgrading to 2.0 doesn't include Chat" Ticket #77
    - fixed "Uploading photos tall photos causes errors" Ticket #79
    - fixed "Alpha and Beta themes don't include Chat" Ticket #84
    - fixed Download link on upgrade screen

    - added "Show users online in Chat" Ticket #80


  For the full change log please refer to the included "ChangeLog.txt" file.




IV. License
------------

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.
