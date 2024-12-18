import io
import os
import sys
from copy import copy
from contextlib import contextmanager
from unittest import TestCase

import pytest

from pipe.pipe import BitbucketApiService, ChatGPTApiService, ChatGPTCodereviewPipe, schema


@contextmanager
def capture_output():
    standard_out = sys.stdout
    try:
        stdout = io.StringIO()
        sys.stdout = stdout
        yield stdout
    finally:
        sys.stdout = standard_out
        sys.stdout.flush()


class ChatGPTCodereviewSmokePipeTestCase(TestCase):
    @pytest.fixture(autouse=True)
    def inject_fixtures(self, caplog, mocker, requests_mock):
        self.caplog = caplog
        self.mocker = mocker
        self.request_mock = requests_mock

    def setUp(self):
        self.sys_path = copy(sys.path)
        sys.path.insert(0, os.getcwd())

    def tearDown(self):
        sys.path = self.sys_path

    def test_validation_errors(self):
        with capture_output() as out:
            with pytest.raises(SystemExit) as pytest_wrapped_e:
                ChatGPTCodereviewPipe(
                    schema=schema, check_for_newer_version=True)

        assert 'Validation errors:' in out.getvalue()
        assert pytest_wrapped_e.type is SystemExit


class TestBitbucketApiService:
    @pytest.fixture
    def service(self):
        return BitbucketApiService('auth', 'workspace', 'repo_slug')

    def test_get_pull_request_diffs(self, service, mocker):
        mock_request = mocker.patch('requests.request')
        mock_response = mocker.Mock()
        mock_response.raise_for_status.return_value = None
        mock_response.text = 'diff'
        mock_request.return_value = mock_response

        result = service.get_pull_request_diffs('pull_request_id')

        assert result == 'diff'
        mock_request.assert_called_once_with(
            "GET",
            "https://api.bitbucket.org/2.0/repositories/workspace/repo_slug/pullrequests/pull_request_id/diff",
            auth='auth'
        )

    def test_add_comment(self, service, mocker):
        mock_request = mocker.patch('requests.request')
        mock_response = mocker.Mock()
        mock_response.raise_for_status.return_value = None
        mock_response.json.return_value = {'comment': 'test'}
        mock_request.return_value = mock_response

        result = service.add_comment('pull_request_id', {'content': 'test'})

        assert result == {'comment': 'test'}
        mock_request.assert_called_once_with(
            "POST",
            "https://api.bitbucket.org/2.0/repositories/workspace/repo_slug/pullrequests/pull_request_id/comments",
            auth='auth',
            json={'content': 'test'}
        )

    def test_fetch_diffs(self, service):
        diffs = 'diff --git a/test1\ndiff --git a/test2'
        result = service.fetch_diffs(diffs, ['test1'], 'diff --git a/')

        assert result == ['diff --git a/test1\n']


class TestChatGPTApiService:
    @pytest.fixture
    def service(self):
        return ChatGPTApiService('api_key')

    def test_fetch_json(self, service):
        result = service.fetch_json('{"test": "value"}')

        assert result == {"test": "value"}
