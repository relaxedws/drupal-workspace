********************************************************************
                     D R U P A L    M O D U L E
********************************************************************
Name: Workspace Module
Author: John VanDyk <jvandyk at iastate dot edu>
Drupal: 4.5
********************************************************************
DESCRIPTION:

This is a module designed for users to have a central place to
view and manage their content.

Thanks to Matt Westgate for his hand-holding during the creation
of this module.

********************************************************************
INSTALLATION:

1. Place the entire workspace directory into your Drupal modules/
   directory.

2. Enable the workspace module by navigating to:

     administer > modules

3. If you want your users to have a workspace, they must be given
   the "administer workspace" access permission:
   
     administer > users > configure > permissions

   When the module is enabled and the user has the "administer
   workspace" permission, a "my workspace" menu should appear in the 
   menu system.

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
