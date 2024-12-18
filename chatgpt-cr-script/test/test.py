from bitbucket_pipes_toolkit.test import PipeTestCase


class ChatGPTCodereviewTestCase(PipeTestCase):

    def test_missing_variables(self):
        result = self.run_container(environment={})
        assert 'BITBUCKET_ACCESS_TOKEN:\n- required field' in result

    def test_missing_variable_pull_request_id(self):
        result = self.run_container(environment={
            "OPENAI_API_KEY": "openai-api-key",
            "BITBUCKET_ACCESS_TOKEN": "bitbucket-access-token",
            "MODEL": 'gpt-4-turbo-preview',
        })

        assert 'BITBUCKET_PR_ID variable is required' in result
