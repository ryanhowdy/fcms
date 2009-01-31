
FAMILY CONNECTIONS 1.7.4

  Keep your family "Connected" with this content management system (CMS) designed 
  specifically with family's in mind. Key features are: a message board, a photo 
  gallery, a blog-like "Family News" section, and an address book.




I. Installation
----------------

  1. Upload the entire contents of FCMS_1.7.4.zip to your web host.

  2. Set permissions to the following folders to 777 (read, write, and execute for 
     user, group and other)

    inc/
    gallery/avatar/
    gallery/photos/
    gallery/upimages/

  3. Go to http://www.yourdomain.com/fcms/ where yourdomain.com is your domain and  
     fcms/ is the directory you used to install FCMS.

  4. It is recommended that you delete the install.php file after installation.




II. Upgrading from 1.7.3 to 1.7.4
--------------------------------

  1. Replace the following files on your web host.

    admin/upgrade.php
    themes/alpha.css
    themes/beta.css
    themes/default.css
    themes/login.css
    inc/gallery_class.php
    install.php
    lostpw.php

  2. Run the upgrade.php script.

  3. Delete the install.php files from your web host.




III. Change Log
----------------

 1.7.4
    - fixed "Photo Gallery display problems in IE7" bug id 2309109 
    - fixed "Footer display problems in FF 3" bug id 2309036
    - fixed "Undefined var on password reset" bug id 2371816 
    - fixed "Various Theme issues" bug id 2392772 
    - added "Add additional classes for theme creation" feature request id 2153279 


  For the full change log please refer to the included "ChangeLog.txt" file.




IV. License
------------

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.

