import datetime
import sys

from google.appengine.ext import db
from google.appengine.tools import bulkloader
from google.appengine.api.datastore_types import Key

sys.path.insert(0, '.')
sys.path.insert(0, '..')
from models import Candidacy, Seat, Candidate

def int_or_null(x):
    if (x == "null" or x == "None" or x == ""):
        return None
    return int(x)

class CandidacyLoader(bulkloader.Loader):
    """Loads a CSV of parties into GAE."""
    def __init__(self):
        bulkloader.Loader.__init__(self, 'Candidacy',
                                   [
                                    ('id', int),
                                    ('seat', lambda x: Key.from_path('Seat', x)),
                                    ('candidate', lambda x: Key.from_path('Candidate', x)),
                                    ('created', lambda x: datetime.datetime.strptime(x, "%Y-%m-%dT%H:%M:%S")),
                                    ('updated', lambda x: datetime.datetime.strptime(x, "%Y-%m-%dT%H:%M:%S")),
                                   ])
    def generate_key(self, i, values):
        id = values[0]
        return id

loaders = [CandidacyLoader]


