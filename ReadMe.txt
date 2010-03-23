+-----------------------------------------------------------------------------+
| Family Connections 2.1                                                      |
+-----------------------------------------------------------------------------+
| Keep your family "Connected" with this content management system (CMS)      |
| designed specifically with family's in mind. Key features are: a message    |
| board, a photo gallery, a blog-like "Family News" section, and an address   |
| book.                                                                       |
+-----------------------------------------------------------------------------+



I. Installation
----------------

  1. Upload the entire contents of FCMS_2.1.zip to your web host.

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




II. Upgrading from 2.0.3 to 2.1
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

  2. Upload the entire contents of FCMS_2.0.zip to your web host
     (Overwriting any previous files other than the above mentioned files/directories).
  
  3. Login as the administrator and visit the upgrade section and run the
     upgrade script.

  4. Delete the install.php file from your web host (if it still exists).




III. Change Log
----------------
 2.1

    #5 - Import/Export Address Book in CSV
    #18 - Add info note on Poll Admin page
    #19 - Add welcome note to frontpage after install
    #35 - Standardize side navigation in Family News
    #39 - New users should show only show up after activation
    #49 - Face Lift
    #50 - Fix blank value submit buttons
    #62 - Simple Registration / Install
    #67 - Unobtrusive delete confirmation
    #73 - Allow site to be Turned Off/Closed.
    #81 - Easier Private Messages
    #83 - Expand comments input field
    #88 - "RE:" on message board doesn't follow sort order
    #93 - Poll Addons
    #95 - Tag User box needs to display first and last name
    #96 - Create alert system
    #100 - Latest Version check not working
    #102 - Change the way Quoting works on MB
    #105 - Prevent empty comments on Photo Gallery
    #107 - Cross browser JavaScript issues
    #109 - Better Documents Error Messages
    #110 - Prevent blank title on Private Messages
    #111 - View Photos of User Broken

  For the full change log please refer to the included "ChangeLog.txt" file.




IV. License
------------

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.

 	  	 
