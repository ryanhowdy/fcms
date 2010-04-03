+-----------------------------------------------------------------------------+
| Family Connections 2.1.2                                                    |
+-----------------------------------------------------------------------------+
| Keep your family "Connected" with this content management system (CMS)      |
| designed specifically with family's in mind. Key features are: a message    |
| board, a photo gallery, a blog-like "Family News" section, and an address   |
| book.                                                                       |
+-----------------------------------------------------------------------------+



I. Installation
----------------

  1. Upload the entire contents of FCMS_2.1.2.zip to your web host.

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




II. Upgrading from 2.1.1 to 2.1.2
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

  2. Upload the following list of files from the FCMS_2.1.2.zip file to your web host
     (Overwriting any previous files other than the above mentioned files/directories).

        admin/members.php
        admin/upgrade.php
        gallery/index.php
        inc/fcms.js
        install.php
  
  3. Login as the administrator and visit the upgrade section and run the
     upgrade script.

  4. Delete the install.php file from your web host (if it still exists).




III. Change Log
----------------
 2.1.2

    #123 - Edit Category deletes the category
    #124 - Admin Members - selected buttons cause errors when no member selected

  For the full change log please refer to the included "ChangeLog.txt" file.




IV. License
------------

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.

 	  	 
