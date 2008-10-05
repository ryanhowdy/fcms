
FAMILY CONNECTIONS 1.7
........................

  Keep your family "Connected" with this content management system (CMS) designed 
  specifically with family's in mind. Key features are: a message board, a photo 
  gallery, a blog-like "Family News" section, and an address book.




I. Installation

  1. Upload the entire contents of FCMS_1.7.zip to your web host.

  2. Set permissions to the following folders to 777 (read, write, and execute for 
     user, group and other)

    inc/
    gallery/avatar/
    gallery/photos/
    gallery/upimages/

  3. Go to http://www.yourdomain.com/fcms/ where yourdomain.com is your domain and  
     fcms/ is the directory you used to install FCMS.

  4. It is recommended that you delete the install.php file after installation.




II. Upgrading from 1.6.4 to 1.7

  1. Delete all files except for the files in the following directories:

    gallery/avatar/
    gallery/photos/
    gallery/upimages/

  2. Upload the entire contents of FCMS_1.7.zip except for the files and 
     directories listed in step #1 above.

  3. Login as the administrator and visit the upgrade section and run the
     upgrade script.

  4. Delete the install.php file from your web host.




III. Change Log

 1.7
    - fixed "Undefined variable/index" bug id 2136558 
    - fixed "Rotating Photo doesn't rotate thumbnail" bug id 2082344
    - fixed "New Photos displaying wrong when displaying last 5 on home" bug id 2112468 
    - added "Tag photos on Photo Gallery" feature request id 1990107 
    - added "Auto Activation of Members" feature request id 2106223
    - added "Full Size Photos (3 sizes total)" feature request id 1943152 
    - added "Make polls optional" feature request id 2109460
    - added "Custom Error Handling" feature request id 2095601 
    - added "RSS Feed" feature request id 2006842 
    - added "Remove meta refresh on admin/member.php" feature request id 2112806 


  For the full change log please refer to the included "ChangeLog.txt" file.




IV. License

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.

