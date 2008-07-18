
FAMILY CONNECTIONS 1.5
........................

  Keep your family "Connected" with this content management system (CMS) designed 
  specifically with family's in mind. Key features are: a message board, a photo 
  gallery, a blog-like "Family News" section, and an address book.




I. Installation

  1. Upload the entire contents of FCMS_1.5.zip to your web host.

  2. Set permissions to the following folders to 777 (read, write, and execute for 
     user, group and other)

    inc/
    gallery/avatar/
    gallery/photos/
    gallery/upimages/

  3. Go to http://www.yourdomain.com/fcms/ where yourdomain.com is your domain and  
     fcms/ is the directory you used to install FCMS.

  4. It is recommended that you delete the install.php file after installation.




II. Upgrading from 1.4 to 1.5

  1. Delete all files except for the files in the following directories:

    gallery/avatar/
    gallery/photos/
    gallery/upimages/

  2. Upload the entire contents of FCMS_1.5.zip except for the files and 
     directories listed in step #1 above.

  3. Login as the administrator and visit the upgrade section and run the
     upgrade script.

  4. Delete the install.php files from your web host.



III. Change Log

 1.5
  - fixed "Sitename: special characters" bug id 1991922 
  - fixed "Multi-language - Character Encoding" bug id 1993128 
  - fixed "Magic Quotes causes default "A" as caption in Gallery" bug id 1992332 
  - fixed "(Profile) Last Visited date not working" bug id 1992291 
  - fixed "PHP Notices (Gallery and Calendar)" bug id 1993876 
  - added "Easy Installation" feature request id 1995356 
  - added "Move some configuration to DB" feature request id 1993883 
  - added "Optimize Photo Gallery Design" feature request id 1990146 
  - added "Help Integration" feature request id 1839811 
  - added "Reply link at bottom of post list" feature request id 1992902 


 1.4
  - fixed "Can't install Address Book (MySQL 5)" bug id 1974803 
  - fixed "Message Board: edited by date doesn't work in PHP5" bug id 1963793 
  - fixed "Pages Links cut off in Most Viewed Photos" bug id 1963282
  - fixed "Private event links show up on small calendar" bug id 1967207 
  - fixed "Most View/Top Rated pages links don't work" bug id 1975039 
  - added "Create more user-friendly and personal messages." request id 1969879 
  - added "Force and/or check Installation" request id 1970517 
  - added "Use LiveValidation" request id 1969905 
  - added "Fix misc. sorting problems" feature request id 1963263
  - added "Code Cleanup" feature request id 1986752 


 1.3.1
  - fixed "Profile.php: DST error in last visited" bug id 1963148 


 1.3
  - fixed "CSS problems in non IE browsers" bug id 1943132 
  - fixed "Misc. date problems" bug id 1960265 
  - fixed "Missing </div> tag on Family News page" bug id 1960243
  - fixed "Not all members showing up in Address Book / Profiles pages" bug id 1961544 
  - added "Inline Comments - Remove popup window" feature request id 1958744 
  - added "Change Family News Frontpage Links" feature request id 1956121 
  - added "5 Latest Family News entries - Remove My News Link" feature request id 1960234 
  - added "Names in Members Online are getting cut off" feature request id 1960236 


 1.2
  - fixed "Magic Quotes problems" bug id 1945794 
  - added "Guest account" feature request id 1858175 
  - added "Create different user access levels." feature request id 1822305 
  - added Part 1 of "Import / Export Address Book Contacts" feature request id 1909002 


 1.1.2
  - fixed "Install: sitename doesn't allow for special chars" bug id 1942054 
  - fixed "Calendar doesn't allow for special chars" bug id 1942052 
  - fixed "Family News Date not showing properly" bug id 1942050 


 1.1.1
  - fixed problem with non english letters displaying properly from db


 1.1
  - added "Internationalize II: Language File" feature request id 1812285 
  - fixed "Cannot Edit/Delete Prayers - Needs Multiple pages" bug id 1872751
  - created FCMS favicon
  - updated the way Awards are calculated


 1.0
  - added "Emailing multiple people in the address book" feature request id 1790477
  - added "Minor Usability Updates" feature request id 1868472
  - added "Calendar: Private Events" feature request id 1803588
  - added "Email user when activated " feature request id 1900324 
  - fixed "Cannot Edit/Delete Prayers - Needs Multiple pages" bug id 1872751
  - fixed "Times are off when frontpage view is set to All (by date)" bug id 1884659
  - renamed tables in db to be more uniform
  - updated themes
  - included form validation on the message board and family news sections



 0.9.9
  - fixed "Thumbnail of comment not showing on home.php" bug id 1844410 
  - fixed "Edit poll: adding new option with apostrophe causes error" bug id 1848866 
  - fixed "Wrong Category Names showing up in Photo Gallery" bug id 1850834 
  - added "Help Integration" feature request id 1839811 
  - updated themes
  - updated latest info on home.php
  - updated latest photo gallery info on home.php
  - updated profile.php



 0.9.8
  - fixed "Multiple bugs found in pre MySQL 4.1.1" bug id 1833299 
  - fixed "Last 5 Photos not showing up" bug id 1834562 
  - fixed "Changing password causes unexpected errors" bug id 1820760 
  - fixed "Photos not displaying in Most Viewed/Top Rated" bug id 1828386 
  - changed the layout of the addressbook and the profile
  - updated latest news addressbook info on home.php
  - created new theme (fcms2.0)


 0.9.5
  - fixed "MISC. photo gallery bugs" bug id 1808283
  - fixed "Stripslashes issues" bug id 1810924
  - fixed "MySQL 5.0 - Null values in non null fields" bug id 1811526 
  - added "Create member dir for phot gallery" feature request id 1782727 
  - added "Internationalize" feature request id 1804694 


 0.9.2
  - added "Most View/Top Rated -- more detailed" feature request id 1794931
  - fixed "People without last names don't show up in Address Book" bug id 1752346 
  - fixed "Upload Images - message board or family news" bug id 1804473 
  - fixed "Admin: Delete members not working" bug id 1806313 
  - fixed "Family News - stripslashes" bug id 1806372 
  - added "Family News - view individual entry" feature request id 1767259 


  0.9.1
  - fixed "Make an announcement a thread" bug id 1764691 
  - fixed "Awards not working" bug id 1790321 
  - updated the display of latest news on homepage
  - added "Edit / Delete Family News" feature request id 1782867
  - added "Create more user friendly error messages. " feature request id 1789696 


  0.9
  - fixed "Bypass Vulnerability" bug id 1778696 
  - fixed "Null Comments" bug id 1764686 
  - updated message board administration


  0.8
  - fixed "Field validation doesn't work" bug id 1775623.
  - updated installation
  - updated how awards work


  0.6
  - added smileys
  - fixed second part of "Edit photos not working" bug id 1764727.
  - fixed "Add new address not working" bug id 1766098.
  - fixed "Not able to activate members" bug id 1766100.


  0.5
  - fixed "Various Photo Gallery/Settings Bugs" bug id 1765673.
  - added register functionality
  - added password reset functionality
  - fixed "Edit photos not working" bug id 1764727.


  0.1.2

  - fixed "After installation, passwords won't work" bug id 1765251.
  - fixed "Fatal error: Cannot redeclare gettheme()" bug id 1765301.




IV. License

  This software uses the The GNU General Public License (GPL), please refer to the 
  full license description in the "license.txt" file.

