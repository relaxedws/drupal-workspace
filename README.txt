********************************************************************
                     D R U P A L    M O D U L E
********************************************************************
Name: Workspace Module
Author: John VanDyk <jvandyk at iastate dot edu>
Drupal: cvs
********************************************************************
DESCRIPTION:

This is a module designed for users to have a central place to
view and manage their content.

Thanks to Matt Westgate for his hand-holding during the creation
of this, my first Drupal module.

********************************************************************
INSTALLATION:

1. Place the entire workspace directory into your Drupal modules/
   directory.

2. Enable the workspace module by navigating to:

     administer > modules

   When enabled, a "my workspace" menu should appear in the menu 
   system.
   
3. Under administer > access control you may wish to give
   users the ability to configure their own workspace.

********************************************************************
SYNERGISTIC MODULES:

The nodeperm_role module (http://drupal.org/project/nodeperm_role)
allows nodes to be edited by users in a given role. 
When the nodeperm_role module is enabled, the workspace module
shows not only nodes you own but also nodes that you may edit
because of the role that has been assigned to your user.

********************************************************************
NOTES:

The workspace module is not compatible with MySQL 3 because it uses
a UNION clause, which is supported by MySQL 4 and higher only. The
UNION clause is necessary to unify nodes and comments (which are
not nodes).

There is a workaround for MySQL 3's lack of a UNION clause:

http://www.google.com/search?&q=union+%22mysql+3%22

but I have no intentions of implementing this myself because I use
MySQL 4.

********************************************************************
KEEPING UP TO DATE:

If you wish to keep up to date with the latest developments of this
module using CVS, here is a recipe:

To check the latest version out of CVS for the first time, login 
by running the command:

cvs -d:pserver:anonymous@cvs.drupal.org:/cvs/drupal login

Enter anonymous as the password. Now you are logged in.

To check out the latest workspace module, run the command:

cvs -d:pserver:anonymous@cvs.drupal.org:/cvs/drupal checkout contributions/modules/workspace

This will create a directory called contributions containing 
a directory called modules that contains the workspace directory. 
Move the workspace directory to the modules directory of your 
Drupal installation.

Once you have a copy of the workspace module in your Drupal 
installation, use the following (much easier) command to keep 
your copy of workspace.module up to date:

cd /path/to/drupal/modules/workspace
cvs update -dP

