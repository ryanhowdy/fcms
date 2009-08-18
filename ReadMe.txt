FAMILY CONNECTIONS 1.9

  Keep your family "Connected" with this content management system (CMS) designed 
  specifically with family's in mind. Key features are: a message board, a photo 
  gallery, a blog-like "Family News" section, and an address book.




I. Installation
----------------

  1. Upload the entire contents of FCMS_1.9.zip to your web host.

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




II. Upgrading from 1.8.2 to 1.9
--------------------------------

  ** DO NOT DELETE THE FOLLOWING FILE **
    inc/config_inc.php

  ** DO NOT TOUCH ANY FILES IN THE FOLLOWING DIRECTORIES **
    gallery/avatar/
    gallery/photos/
    gallery/upimages/

  1. It is recommended that you backup your entire site, including your
     MySQL database before upgrading.

  2. Upload the entire contents of FCMS_1.9.zip to your web host
     (Overwriting any previous files).

  3. Login as the administrator and visit the upgrade section and run the
     upgrade script.

  4. Delete the install.php file from your web host (if it still exists).





III. Change Log
----------------

 1.9
    - fixed "Can't turn off multiple sections once they've been turned on" bug id 2644006
    - fixed "Error upgrading auto activation (1.7.1)" bug id 2723720
    - fixed "Missing wording from language file" bug id 2635448
    - fixed "Latest Calendar entries not displaying properly" bug id 2627814
    - fixed "Calendar add button has extra padding-right" bug id 2647260
    - fixed "<br/> code showing up in Quotes" Ticket #32
    - added "Better Themes" feature request id 2646780 / Ticket #10
    - updated to DateChooser 2.9

  For the full change log please refer to the included "ChangeLog.txt" file.




IV. License
------------

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.

