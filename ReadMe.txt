FAMILY CONNECTIONS 2.0

  Keep your family "Connected" with this content management system (CMS) designed 
  specifically with family's in mind. Key features are: a message board, a photo 
  gallery, a blog-like "Family News" section, and an address book.




I. Installation
----------------

  1. Upload the entire contents of FCMS_2.0.zip to your web host.

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




II. Upgrading from 1.9 to 2.0
--------------------------------
  ** DO NOT DELETE THE FOLLOWING FILE **
    inc/config_inc.php

  ** DO NOT TOUCH ANY FILES IN THE FOLLOWING DIRECTORIES **
    gallery/avatar/
    gallery/documents/
    gallery/photos/
    gallery/upimages/

 ** DURING STEP 2 DO NOT UPLOAD THE FOLLOWING FILE **
    inc/util_inc.php

  1. It is recommended that you backup your entire site, including your
     MySQL database before upgrading.

  2. Upload the entire contents of FCMS_2.0.zip to your web host
     (Overwriting any previous files other than the above mentioned files/directories).
     
     PLEASE NOTE: After you upload the new files you will see a lot of errors.
                  These errors are expected and will go away after step 4. 

  3. Login as the administrator and visit the upgrade section and run the
     upgrade script.

  4. Upload the inc/util_inc.php file now.

  5. Delete the install.php file from your web host (if it still exists).




III. Change Log
----------------

 2.0
    - fixed "Documents with spaces doesn't always work" Ticket #1
    - fixed "May - short/long version translation" Ticket #2
    - fixed "Pages on Documents messed up" Ticket #59
    - added "Add ability to remove tagged members" Ticket #3
    - added "Allow admin to reset user's password" Ticket #9
    - added "Chat Room" Ticket #13
    - added "Change default member settings" Ticket #14
    - added "Show more photo detail in Photo Gallery" Ticket #23
    - added "Easier entries into recipies section" Ticket #24
    - added "Add Select All to Mass Email" Ticket #25
    - added "Change Default Theme Choice in Admin" Ticket #26
    - added "Daily Calendar View" Ticket #31
    - added "Update Themes" Ticket #37
    - added "Show caption on mouseover" Ticket #44
    - added "Export/Import Calendar in *.ics format" Ticket #47
    - added "Fix .link_block" Ticket #54
    - added "Email members on update" Ticket #56
    - added "Allow youtube clips to be inserted in Message Board/Family News" Ticket #57
    - added "Easier Photo Gallery Options" Ticket #64

  For the full change log please refer to the included "ChangeLog.txt" file.




IV. License
------------

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.
