import os
import re
import json
import time
from pathlib import Path

import yaml
import requests
import json_repair
import tiktoken
from openai import OpenAI, BadRequestError


from bitbucket_pipes_toolkit import Pipe, get_logger, fail

logger = get_logger()


schema = {
    'OPENAI_API_KEY': {'type': 'string', 'required': True},
    'BITBUCKET_ACCESS_TOKEN': {'type': 'string', 'required': True},
    'MODEL': {'type': 'string', 'required': True, 'allowed': ['gpt-4o', 'gpt-4-turbo-preview', 'gpt-3.5-turbo-0125']},
    'ORGANIZATION': {'type': 'string', 'required': False},
    'MESSAGE': {'type': 'string', 'required': False},
    'FILES_TO_REVIEW': {'type': 'string', 'required': False},
    'CHATGPT_COMPLETION_FILEPATH': {'type': 'string', 'required': False},
    'CHATGPT_CLIENT_FILEPATH': {'type': 'string', 'required': False},
    'CHATGPT_PROMPT_MAX_TOKENS': {'type': 'integer', 'required': False, 'default': 0},
    'DEBUG': {'type': 'boolean', 'required': False, 'default': False},
}


DEFAULT_SYSTEM_PROMPT_FOR_CODE_REVIEW = '''
"You are a Drupal 10 specialist and an expert in PHP coding standards. Please review the following code with the following goals in mind:"
"Drupal 10 Best Practices:"
"Assess the code's alignment with Drupal 10 architecture and conventions."
"Verify adherence to Drupal's APIs, hooks, and services."
"Check for proper usage of dependency injection, configuration management, and any other relevant Drupal-specific patterns."
"PHP Coding Standards:"
"Evaluate compliance with the official Drupal PHP Coding Standards: https://www.drupal.org/docs/develop/standards/php/php-coding-standards."
"Identify any deviations or improvements related to code readability, formatting, and structure."
"Code Quality:"
"Check for potential bugs, inefficiencies, or edge cases."
"Suggest optimizations or refactoring opportunities."
"Highlight any missing documentation, comments, or tests."
"Provide a detailed, structured review with clear suggestions for improvement. Focus on actionable feedback and best practices that enhance maintainability and performance of the code."
"You are designed to output JSON."
"The response must be a JSON object where the key for each piece of feedback is the filename and line number in the file where the feedback must be left, and the value is the feedback itself as a string. "
"JSON must follow the next structure {“{filename:line-number}“: “{feedback relating to the referenced line in the file.}“}"
'''


class BitbucketApiService:
    BITBUCKET_API_BASE_URL = "https://api.bitbucket.org/2.0"
    DIFF_DELIMITER = "diff --git a/"

    def __init__(self, auth, workspace, repo_slug):
        self.auth = auth
        self.workspace = workspace
        self.repo_slug = repo_slug

    def get_pull_request_diffs(self, pull_request_id):
        url_diff = f"{self.BITBUCKET_API_BASE_URL}/repositories/{self.workspace}/{self.repo_slug}/pullrequests/{pull_request_id}/diff"
        response = requests.request("GET", url_diff, auth=self.auth)
        response.raise_for_status()

        # git diff context is too complex and contains JSON-restricted symbols to return in JSON format
        return response.text

    def add_comment(self, pull_request_id, payload):
        url_comment = f"{self.BITBUCKET_API_BASE_URL}/repositories/{self.workspace}/{self.repo_slug}/pullrequests/{pull_request_id}/comments"
        response = requests.request("POST", url_comment, auth=self.auth, json=payload)
        response.raise_for_status()

        return response.json()

    @staticmethod
    def fetch_diffs(diffs, filenames=None, delimiter=None):
        if filenames:
            return [delimiter + diff for diff in diffs.split(delimiter) for filename in filenames if diff.startswith(filename)]
        else:
            return [delimiter + diff for diff in diffs.split(delimiter)]


class ChatGPTApiService:
    def __init__(self, api_key, organization=None, *args, **kwargs):
        self.client = OpenAI(api_key=api_key, organization=organization, *args, **kwargs)

    def create_completion(self, model, messages, **kwargs):
        completion = self.client.chat.completions.create(
            response_format={"type": "json_object"},
            model=model,
            messages=messages,
            **kwargs
        )

        return completion

    @staticmethod
    def fetch_json(data):
        return json_repair.loads(data)

    @staticmethod
    def num_tokens_from_messages(messages, model):
        """Returns the number of tokens used by a list of messages.
        Recommended way by OpenAI guides:
        https://platform.openai.com/docs/guides/text-generation/managing-tokens
        """

        try:
            encoding = tiktoken.encoding_for_model(model)
        except KeyError:
            encoding = tiktoken.get_encoding("cl100k_base")

        num_tokens = 0
        for message in messages:
            num_tokens += 4  # every message follows <im_start>{role/name}\n{content}<im_end>\n
            for key, value in message.items():
                num_tokens += len(encoding.encode(value))
                if key == "name":  # if there's a name, the role is omitted
                    num_tokens += -1  # role is always required and always 1 token
        num_tokens += 2  # every reply is primed with <im_start>assistant
        return num_tokens


class ChatGPTCodereviewPipe(Pipe):

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.auth_method_bitbucket = self.resolve_auth()

        # Bitbucket
        self.workspace = os.getenv('BITBUCKET_WORKSPACE')
        self.repo_slug = os.getenv('BITBUCKET_REPO_SLUG')
        self.bitbucket_client = BitbucketApiService(
            self.auth_method_bitbucket, self.workspace, self.repo_slug)

        # ChatGPT
        self.open_api_key = self.get_variable('OPENAI_API_KEY')
        self.organization = self.get_variable('ORGANIZATION')
        self.model = self.get_variable('MODEL')
        self.user_message_content = self.get_variable('MESSAGE')
        self.files_to_review = self.get_variable("FILES_TO_REVIEW")
        self.completion_parameters_payload_file = self.get_variable('CHATGPT_COMPLETION_FILEPATH')
        self.chatgpt_parameters_payload_file = self.get_variable('CHATGPT_CLIENT_FILEPATH')
        self.chat_gpt_client = None

    def get_diffs_to_review(self, pull_request_id):
        diffs_text = self.bitbucket_client.get_pull_request_diffs(pull_request_id)

        files_to_review = []
        if self.files_to_review and self.files_to_review.split(','):
            files_to_review = self.files_to_review.split(',')

        return self.bitbucket_client.fetch_diffs(diffs_text, files_to_review, self.bitbucket_client.DIFF_DELIMITER)

    @staticmethod
    def get_files_with_diffs(diffs_to_review):
        # string example "diff --git a/pipe/pipe.py b/pipe/pipe.py\n..."
        pattern_filename = re.compile(r"a/(.*?) b/")
        files_with_diffs = [re.search(pattern_filename, diff).group(1) for diff in diffs_to_review if
                            re.search(pattern_filename, diff)]

        return files_with_diffs

    @staticmethod
    def load_yaml(filepath):
        if not Path(filepath).exists():
            fail(f"File {filepath} doesn't exist.")

        try:
            with open(filepath, 'r') as stream:
                return yaml.safe_load(stream)
        except yaml.YAMLError as error:
            fail(f"File {filepath} couldn't be loaded. Error: {error}")

    def get_suggestions(self, diffs_to_review):
        messages = []
        default_messages_system = {
            "role": "system",
            "content": DEFAULT_SYSTEM_PROMPT_FOR_CODE_REVIEW
        }
        messages.append(default_messages_system)

        if self.user_message_content:
            messages.append({"role": "system", "content": self.user_message_content})

        default_messages_diffs = {
            "role": "user",
            "content": str(diffs_to_review)
        }
        messages.append(default_messages_diffs)

        # count tokens
        num_tokens = self.chat_gpt_client.num_tokens_from_messages(messages, self.model)

        chat_gpt_token_limit = int(self.get_variable('CHATGPT_PROMPT_MAX_TOKENS'))
        if chat_gpt_token_limit != 0 and num_tokens > chat_gpt_token_limit:
            self.log_warning(f"The number of tokens is ~{num_tokens} that more then allowed CHATGPT_PROMPT_MAX_TOKENS {chat_gpt_token_limit} tokens")
            self.success(message='Pipe is stopped.', do_exit=True)

        self.log_info(f"ChatGPT configuration: model: {self.model}")

        completion_params = {
            'model': self.model,
            'messages': messages,
        }

        # get payload with params for completion
        if self.completion_parameters_payload_file:
            users_completion_params = self.load_yaml(self.completion_parameters_payload_file)

            self.log_info(f"ChatGPT configuration: completion parameters: {users_completion_params}")

            completion_params.update(users_completion_params)

        self.log_info(f"ChatGPT configuration: messages: {messages}")
        self.log_info("Processing ChatGPT...")

        start_time = time.time()
        completion = None
        try:
            completion = self.chat_gpt_client.create_completion(**completion_params)
        except BadRequestError as error:
            self.fail(f"{str(error)}")

        end_time = time.time()

        self.log_debug(completion)
        self.log_info(f"Processing ChatGPT takes: {round(end_time - start_time)} seconds")
        self.log_info(f'ChatGPT completion tokens: {completion.usage}')

        raw_suggestions = completion.choices[0].message.content

        self.log_debug(raw_suggestions)

        suggestions = None
        try:
            suggestions = self.chat_gpt_client.fetch_json(raw_suggestions)
        except json.JSONDecodeError as error:
            self.fail(str(error))

        self.log_debug(suggestions)

        return suggestions

    def add_comments(self, pull_request_id, data):
        # add comment to PR
        # "app/pipe/pipe.py:92: Review comments"
        # "pipe/pipe.py:92-95: Review comments"
        pattern_filename_line = re.compile(r"(.+):(\d+)")

        added_suggestions_counter = 0
        files_with_comments = []

        for filename_line, content in data.items():
            filename_line_match = re.match(pattern_filename_line, filename_line)
            if filename_line_match and len(content):
                filename, line = filename_line_match.groups()
                payload = {
                    'inline': {
                        'to': int(line),
                        'path': filename
                    },
                    'content': {
                        'raw': content
                    }
                }
                self.bitbucket_client.add_comment(pull_request_id, payload)

                files_with_comments.append(filename)
                added_suggestions_counter += 1

        return set(files_with_comments), added_suggestions_counter

    def run(self):
        super().run()
        self.log_info('Executing the pipe...')

        # fetch the current triggered PR ID
        # Only available on a pull request triggered build
        # https://support.atlassian.com/bitbucket-cloud/docs/variables-and-secrets/
        # https://support.atlassian.com/bitbucket-cloud/docs/pipeline-start-conditions/#Pull-Requests
        pull_request_id = os.getenv("BITBUCKET_PR_ID")
        if pull_request_id is None:
            self.fail(
                'BITBUCKET_PR_ID variable is required! '
                'Pullrequest ID variable is not detected in the environment. '
                'Make sure the pipe is executed on a pull request triggered build: '
                'https://support.atlassian.com/bitbucket-cloud/docs/pipeline-start-conditions/#Pull-Requests'
            )

        diffs_to_review = self.get_diffs_to_review(pull_request_id)

        self.log_debug(diffs_to_review)

        if not diffs_to_review:
            self.log_warning(f"No files for codereview. Check configuration in FILES_TO_REVIEW: {self.files_to_review}")
            self.success(message='Pipe is stopped.', do_exit=True)

        files_with_diffs = self.get_files_with_diffs(diffs_to_review)

        self.log_info(f"Files with diffs count {len(files_with_diffs)}: {set(files_with_diffs)}")

        # chatGPT
        chatgpt_parameters = {
            "api_key": self.open_api_key,
            "organization": self.organization,
        }
        # get payload with params for chatgpt client
        if self.chatgpt_parameters_payload_file:
            users_chatgpt_parameters = self.load_yaml(self.chatgpt_parameters_payload_file)

            self.log_info(f"ChatGPT configuration: client parameters: {users_chatgpt_parameters}")

            chatgpt_parameters.update(users_chatgpt_parameters)

        self.chat_gpt_client = ChatGPTApiService(**chatgpt_parameters)

        suggestions = self.get_suggestions(diffs_to_review)
        files_with_comments, added_suggestions_counter = self.add_comments(pull_request_id, suggestions)

        self.log_info(f"ChatGPT suggestions count: {added_suggestions_counter}")
        self.log_info(f'Commented files count {len(files_with_comments)}: {files_with_comments}')

        ui_pull_request_url = f"https://bitbucket.org/{self.workspace}/{self.repo_slug}/pull-requests/{pull_request_id}"
        self.success(message=f"Successfully added the comments provided by ChatGPT to the pull request: {ui_pull_request_url}")


if __name__ == '__main__':
    with open('/pipe.yml') as f:
        metadata = yaml.safe_load(f.read())
    pipe = ChatGPTCodereviewPipe(schema=schema, pipe_metadata=metadata, check_for_newer_version=True)
    pipe.run()
