import os
import sys
import pytest

# Skip tests if Flask isn't available (e.g., in minimal environments)
pytest.importorskip("flask")

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
import main


def test_home_route():
    main.app.config['TESTING'] = True
    with main.app.test_client() as client:
        resp = client.get('/')
        assert resp.status_code == 200

