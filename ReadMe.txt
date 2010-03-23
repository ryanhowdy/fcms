+-----------------------------------------------------------------------------+
| Family Connections 2.0.2                                                    |
+-----------------------------------------------------------------------------+
| Keep your family "Connected" with this content management system (CMS)      |
| designed specifically with family's in mind. Key features are: a message    |
| board, a photo gallery, a blog-like "Family News" section, and an address   |
| book.                                                                       |
+-----------------------------------------------------------------------------+


I. Installation
----------------

  1. Upload the entire contents of FCMS_2.0.2.zip to your web host.

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




II. Upgrading from 2.0.1 to 2.0.2
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

        admin/members.php
        admin/upgrade.php
        gallery/index.php
        inc/admin_class.php
        inc/gallery_class.php
        inc/members_class.php
        inc/util_inc.php
        documents.php
        familynews.php
        install.php
        messageboard.php
        prayers.php
        recipes.php
   
  3. Login as the administrator and visit the upgrade section and run the
     upgrade script.

  4. Delete the install.php file from your web host (if it still exists).




III. Change Log
----------------
 2.0.2

    Fixed the following bugs:

    #91 - undefined function: stripos()
    #86 - Undefined var: edit_del_options
    #87 - Names are messed up with the "Email Members on Updates"
    #85 - Can't change default timezone to GMT
    #89 - Undefined variable: photo_arr on Photo Edit
    #90 - Dupe email address gives no error on Admin Create
    #92 - Large Tall photos not displaying properly

  For the full change log please refer to the included "ChangeLog.txt" file.




IV. License
------------

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.
