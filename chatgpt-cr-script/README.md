# Bitbucket Pipelines Pipe: Bitbucket ChatGPT codereview

Unlock AI's power of [OpenAI ChatGPT][OpenAI ChatGPT] for code review pull requests


## YAML Definition

Add the following snippet to the script section of your `bitbucket-pipelines.yml` file:

```yaml
- pipe: atlassian/bitbucket-chatgpt-codereview:0.1.3
  variables:
    OPENAI_API_KEY: "<string>"
    BITBUCKET_ACCESS_TOKEN: "<string>"
    MODEL: "<string>"
    # ORGANIZATION: "<string>" # Optional
    # MESSAGE: "<string>" # Optional
    # FILES_TO_REVIEW: "<string>" # Optional
    # CHATGPT_COMPLETION_FILEPATH: "<string>" # Optional
    # CHATGPT_CLIENT_FILEPATH: "<string>" # Optional
    # CHATGPT_PROMPT_MAX_TOKENS: "<string>" # Optional
    # DEBUG: "<boolean>" # Optional
```


## Variables

| Variable                     | Usage                                                                                                                                                                                                         |
|------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| OPENAI_API_KEY (*)           | OPENAI api key to access ChatGPT.                                                                                                                                                                             |
| BITBUCKET_ACCESS_TOKEN (*)   | The [access token][Bitbucket Access Token] will be used for authentication. Repositories write and Repositories read and PullRequest read.                                                                    |
| MODEL (*)                    | ID of the model to use. See the model endpoint compatibility table for details on which [models work with the Chat API in JSON mode][JSON models]. Supported: `gpt-4-turbo-preview` and `gpt-3.5-turbo-0125`. |
| ORGANIZATION                 | Organization ID. Can be found on your [Organization settings][Organization settings] page in OpenAI UI.                                                                                                       |
| MESSAGE                      | Content of the message object to extend system [prompt][prompt]. If provided, users MESSAGE will extend default pipe’s messages.                                                                              |
| FILES_TO_REVIEW              | List of files for review comma-separated. Default: pipe will review all diffs files from the pull-request.                                                                                                    |
| CHATGPT_COMPLETION_FILEPATH  | Path to JSON file containing [completion parameters][Completion parameters].                                                                                                                                  |
| CHATGPT_CLIENT_FILEPATH      | Path to JSON file containing [ChatGPT client parameters][ChatGPT client parameters].                                                                                                                          |
| CHATGPT_PROMPT_MAX_TOKENS    | Max allowed prompt's tokens number. This parameter prevents pipe making request to ChatGPT to keep control on token costs. Default: `0` (not limited).                                                        |
| DEBUG                        | Turn on extra debug information. Default: `false`.                                                                                                                                                            |

_(*) = required variable._


## Prerequisites
**Note!** [BITBUCKET_PR_ID variable][BITBUCKET_PR_ID variable] is required and must be available in the environment for the pipe.
The pipe works only if executed on a pull request triggered build.
Bitbucket pipelines with [pull-request start conditions][bitbucket pull-request start conditions] (should be present in bitbucket-pipelines.yml file).


Limitations of the AI's models:

- ChatGPT, like any AI model has limitations. It doesn't understand code or context in the same way a human does. It generates responses based on patterns it learned during training and may not always provide accurate or optimal solutions.
- Data Privacy: When using OpenAI's API, the data you send for processing might be used to improve their models. Always refer to [OpenAI's data usage policy][OpenAI ChatGPT] for the most accurate information.
- Recommendations by ChatGPT: The recommendations or suggestions provided by ChatGPT should be reviewed carefully. It's crucial to verify the correctness and appropriateness of the code before using it in production.
- Configuration: Provided configuration of completion (max_token, etc.) might cut the response text of the answer.
- Remember, AI is a tool to assist you, not a replacement for human expertise. Always review the output from AI models carefully.


## Examples

Basic examples:

Review all changes relates to the pull request by ChatGPT.
```yaml
pipelines:
  pull-requests:
    '**':
      - step:
          name: Build for any pull request created
          script:
            - pipe: atlassian/bitbucket-chatgpt-codereview:0.1.3
              variables:
                OPENAI_API_KEY: $OPENAI_API_KEY
                BITBUCKET_ACCESS_TOKEN: $BITBUCKET_ACCESS_TOKEN
                MODEL: 'gpt-4-turbo-preview'
  default:
    - step:
        name: All other builds
        script:
          - echo "Default build"
```

Review only changes in `pipe/pipe.py` and `requirements.txt` files related to the pull request.

```yaml
pipelines:
  pull-requests:
    '**':
      - step:
          name: Build for any pull request created
          script:
            - pipe: atlassian/bitbucket-chatgpt-codereview:0.1.3
              variables:
                OPENAI_API_KEY: $OPENAI_API_KEY
                BITBUCKET_ACCESS_TOKEN: $BITBUCKET_ACCESS_TOKEN
                MODEL: $MODEL
                FILES_TO_REVIEW: 'pipe/pipe.py,requirements.txt' 
  default:
    - step:
        name: All other builds
        script:
          - echo "Default build"
```


Advanced examples:

Review all changes relates to the pull request with customized ChatGPT client and completion parameters provided in yaml files.

```yaml
pipelines:
  pull-requests:
    '**':
      - step:
          name: Build for any pull request created
          script:
            - pipe: atlassian/bitbucket-chatgpt-codereview:0.1.3
              variables:
                OPENAI_API_KEY: $OPENAI_API_KEY
                BITBUCKET_ACCESS_TOKEN: $BITBUCKET_ACCESS_TOKEN
                MODEL: $MODEL
                CHATGPT_COMPLETION_FILEPATH: .chatgpt_config/chatgpt_completion_parameters.yaml
                CHATGPT_CLIENT_FILEPATH: .chatgpt_config/chatgpt_client_parameters.yaml
  default:
    - step:
        name: All other builds
        script:
          - echo "Default build"
```

Modify prompt for ChatGpt completions and review all changes relates to the pull request by ChatGPT.
Prevent unexpected spending might be caused by huge request to ChatGPP during big changes (i.e. codestyle re-formatting).
```yaml
pipelines:
  pull-requests:
    '**':
      - step:
          name: Build for any pull request created
          script:
            - pipe: atlassian/bitbucket-chatgpt-codereview:0.1.3
              variables:
                OPENAI_API_KEY: $OPENAI_API_KEY
                BITBUCKET_ACCESS_TOKEN: $BITBUCKET_ACCESS_TOKEN
                MODEL: $MODEL
                MESSAGE: "Provide only most important comments."
                CHATGPT_PROMPT_MAX_TOKENS: 2000   
  default:
    - step:
        name: All other builds
        script:
          - echo "Default build"
```


## Support
If you’d like help with this pipe, or you have an issue or feature request, [let us know on Community][community].

The pipe is maintained by Atlassian.

If you’re reporting an issue, please include:

- the version of the pipe
- relevant logs and error messages
- steps to reproduce


## License
Copyright (c) 2024 Atlassian and others.
Apache 2.0 licensed, see [LICENSE](LICENSE.txt) file.


## Third-party licenses
[OpenAI Python API library][OpenAI Python API library]
Copyright (c) 2024 OpenAI
Apache 2.0 licensed, see [LICENSE-openai](LICENSE-openai.txt) file.



[community]: https://community.atlassian.com/t5/forums/postpage/board-id/bitbucket-questions?add-tags=bitbucket-pipelines,pipes,ai,chatgpt,code-quality
[Bitbucket Access Token]: https://support.atlassian.com/bitbucket-cloud/docs/access-tokens/
[OpenAI ChatGPT]: https://openai.com/blog/chatgpt
[Organization settings]: https://platform.openai.com/account/organization
[JSON models]: https://platform.openai.com/docs/guides/text-generation/json-mode
[prompt]: https://platform.openai.com/docs/guides/prompt-engineering
[Completion parameters]: https://platform.openai.com/docs/api-reference/chat/create
[ChatGPT client parameters]: https://github.com/openai/openai-python/blob/e41abf7b7dbc1e744d167f748e55d4dedfc0dca7/src/openai/_client.py#L73
[bitbucket pull-request start conditions]: https://support.atlassian.com/bitbucket-cloud/docs/pipeline-start-conditions/#Pull-Requests
[BITBUCKET_PR_ID variable]: https://support.atlassian.com/bitbucket-cloud/docs/variables-and-secrets/
[OpenAI Python API library]: https://pypi.org/project/openai/
