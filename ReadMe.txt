FAMILY CONNECTIONS 1.8

  Keep your family "Connected" with this content management system (CMS) designed 
  specifically with family's in mind. Key features are: a message board, a photo 
  gallery, a blog-like "Family News" section, and an address book.




I. Installation
----------------

  1. Upload the entire contents of FCMS_1.8.zip to your web host.

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




II. Upgrading from 1.7.4 to 1.8
--------------------------------

  ** DO NOT DELETE THE FOLLOWING FILE **
    inc/config_inc.php

  ** DO NOT TOUCH ANY FILES IN THE FOLLOWING DIRECTORIES **
    gallery/avatar/
    gallery/photos/
    gallery/upimages/

  1. It is recommended that you backup your entire site, including your
     MySQL database before upgrading.

  2. Upload the entire contents of FCMS_1.8.zip to your web host
     (Overwriting any previous files).

  3. Login as the administrator and visit the upgrade section and run the
     upgrade script.

  4. Delete the install.php file from your web host (if it still exists).




III. Change Log
----------------

 1.8
    - fixed "Cannot edit Address Book entries" bug id 2540797
    - fixed "Cannot vote for photo when viewing Latest Comments" bug id 2531076
    - fixed "Pages on Top Rated and Most Viewed don't work" bug id 2531146
    - fixed "Captions disappear when editing" bug id 2540787
    - fixed "Recipe title disppears when editing recipe" bug id 2528700
    - fixed "Sending email from FCMS has escaped quotes" bug id 2562008
    - fixed "Pages links on Tagged Photos view don't work" bug id 2549701
    - fixed "Special characters cause error on registration" bug id 2549242
    - fixed "Member ID is wrong on Prayer Concerns" bug id 2512258
    - fixed "Popups missing sitename and version" bug id 2563999
    - added "Lock account after 5 failed login attempts" feature request id 2524376
    - added "Add additional notifications to the homepage" feature request id 2187394
    - added "Add documents to the site?" feature request id 1816831
    - added "Private Messages (PM)" feature request id 1850324

  For the full change log please refer to the included "ChangeLog.txt" file.




IV. License
------------

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.

