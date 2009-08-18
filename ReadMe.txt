FAMILY CONNECTIONS 1.8.2

  Keep your family "Connected" with this content management system (CMS) designed 
  specifically with family's in mind. Key features are: a message board, a photo 
  gallery, a blog-like "Family News" section, and an address book.




I. Installation
----------------

  1. Upload the entire contents of FCMS_1.8.2.zip to your web host.

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




II. Upgrading from 1.8.1 to 1.8.2
--------------------------------

  1. Replace the following files on your web host.

    admin/upgrade.php
    activate.php
    addressbook.php
    home.php
    recipes.php
    register.php
    install.php

  2. Run the upgrade.php script.

  3. Delete the install.php file from your web host.




III. Change Log
----------------

 1.8.2
    - fixed "Multiple SQL Injections" bug id 2722736


  For the full change log please refer to the included "ChangeLog.txt" file.




IV. License
------------

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.

