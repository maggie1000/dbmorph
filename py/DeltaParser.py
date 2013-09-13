class DeltaParser:

    def __init__(self):
	pass


class XmlDeltaParser(DeltaParser):

    def __init__(self, provider):
	self.provider = provider

    def getDelta(self, deltaId, direction):
	from xml.dom import minidom
	xmldoc = minidom.parse(provider.getDeltaUri)
	delta = Delta(deltaId)
	# now get the applicable elements for the direction
	# an populate delta
	# delta.blah = blah
	return delta
