# Smoke tests for Drupal 8/9/10

1. Run `composer require --dev droptica/codeception-package`.
2. Add `codeception.yml` to your project directory.

```yml
actor: Tester
namespace: Tests
support_namespace: Support
paths:
  tests: tests
  output: tests/_output
  data: tests/Support/Data
  support: tests/Support
  envs: tests/_envs
actor_suffix: Tester
settings:
  colors: true
  memory_limit: 1024M
  strict_xml: true
extensions:
  enabled:
    - Codeception\Extension\RunFailed
```

3. Copy the contents of this repository to the `tests` directory in your project root.
4. Configure `tests/Acceptance.suite.yml` if your Docker host is not `web`
5. Configure content types, vocabularies and URLs in `tests/Acceptance/BaseResponseCodeTestCest.php`\
6. Add autoload definition to `composer.json` after require-dev section:

```json
"autoload-dev": {
  "psr-4": {
    "Tests\\": "tests/"
  }
},
```

# Configure with DDEV

### Add "tests" file to .ddev/commands/web

```bash
#!/usr/bin/env bash

if [ $# -eq 0 ]; then
  codecept run Acceptance --html --xml
else
  codecept run $@ --html --xml
fi
```

### Running tests

To execute all acceptance tests:

```bash
ddev tests
ddev tests Acceptance
```

Or you can select the test to run by:

```bash
ddev tests Acceptance AnonymousResponseCodeTestCest
```
