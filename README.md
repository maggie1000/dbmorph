dbmorph
=======

dbMorph: a database change manager

This project was based on the idea similar to Rails migrations - a great way to manage database schema changes and keep them consistent with code.

Except that in addition to very basic CREATE and ALTER statements, we wanted database changes to be managed in any RDBMS, specifically in Oracle, which, with its PL/SQL, put an additional burden on managing code as well as migrations.  On top of that, we made it so that it could live in a subversion repo and migrations on each branch could be managed via this one manager (using the magic of svn-externals). This worked great in 2008.

The code was originally available since early 2008 on SourceForge: https://sourceforge.net/projects/dbmorph/

This code is no longer maintained.

Enjoy.

Authors: Maggie Nelson (@maggie1000) and David Mora (@dmora)
