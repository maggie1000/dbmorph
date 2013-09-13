# Elements within the delta will change depending on the database.

class Delta:
    self.id = id
    self.summary
    self.order
    self.direction
    # includes both DDL and DML for the up or down statement, depending on direction
    self.ddl

    def __init__(self, id):

    def parse(self, deltaParser):

    def grab(self, deltaProvider):


class OracleDelta(Delta):
    # assumes that the package head is in pkg.sql and package body in pkg_body.sql, for each package
    self.packages
    # assumes use of DBMS_SCHEDULER package to manage jobs within the database
    self.jobs

    def __init__(self)
	Delta.__init__()


class MySqlDelta(Delta):
    # MySQL does not yet support packages, just single stored procs
    self.functions

    def __init__(self)
	Delta.__init__()

