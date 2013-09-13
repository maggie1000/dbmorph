class DeltaManager:
    def __init__(self):
	self.lastDelta = None
	self.targetDelta = None
	self.direction = None
	self.database = None
	self.deltaStorageEngine = None
	self.deltaStorageLocation = None
	self.branch = None

	self.parseOptions()

    def parseOptions(self):
	from optparse import OptionParser
	parser = OptionParser()
	parser.add_option("-l", "--lastDelta", dest="lastDelta",
			  help="last delta installed in the database")
	parser.add_option("-t", "--targetDelta", dest="targetDelta",
			  help="target delta; all deltas from lastDelta (non-inclusive) to targetDelta (inclusive) will be processed")
	parser.add_option("-d", "--database", dest="database",
			  help="database engine, e.g. oracle, mysql")
	parser.add_option("-e", "--deltaStorageEngine", dest="deltaStorageEngine",
		          help="how deltas are stored, e.g. xml, text")
	parser.add_option("-s", "--deltaStorageLocation", dest="deltaStorageLocation",
			  help="where deltas are stored; accepts string 'local' or URI")
	parser.add_option("-b", "--branch", dest="branch", default="trunk",
			  help="svn branch; defaults to trunk")

	(options, args) = parser.parse_args()

	self.lastDelta = options.lastDelta
	self.targetDelta = options.targetDelta
	self.database = options.database
	self.deltaStorageEngine = options.deltaStorageEngine
	self.deltaStorageLocation = options.deltaStorageLocation
	self.branch = options.branch

	if self.lastDelta < self.targetDelta:
	    self.direction = 'up'
	else:
	    self.direction = 'down'

class OracleDeltaManager(DeltaManager):
    def __init__(self):
	DeltaManager.__init__(self)
	self.database = 'oracle'

class MySqlDeltaManager(DeltaManager):
    def __init__(self):
	DeltaManager.__init__(self)
	self.database = 'mysql'

deltaManager = OracleDeltaManager()

# todo: decorators for storage uri, engine, etc.
