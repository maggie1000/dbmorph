class DeltaProvider:

    def __init__(self, parser):
	self.isConnected = False
	self.connectionUri = None
	self.availableDeltas = None
	self.parser = parser

    def connect(self, uri):
	# connect
	pass

    def getAvailableDeltas(self, uri):
	# get deltas that are at uri that are available for this branch
	pass

    def disconnect(self):
	self.isConnected = False

    def isDeltaAvailable(self, id):
	return id in self.availableDeltas

    def getDelta(self, id, direction):
	return parser.getDelta(id, direction)

class LocalDeltaProvider(DeltaProvider):

    def __init__(self, parser):
	self.parser = parser

    def connect(self, uri):
	self.connectionUri = uri
	import os
	if os.stat(uri):
	    self.isConnected = True

    def disconnect(self):
	self.connectionUri = None
	self.isConnected = False

    def getAvailableDeltas(self):
	pass

    def isDeltaAvailable(self, id):
	# todo: return id in self.availableDeltas
	return True

    def getDelta(self, id, direction):
	delta = Delta(id, self)
	return parser.getDelta(id, direction)

    def getDeltaUri(self, deltaId):
	return provider.connectionUri + '/' + id + '.xml'

# todo: consider RemoteDeltaProvider - maybe conceptually they're all "remote"?
