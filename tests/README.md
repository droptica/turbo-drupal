# Smoke tests for Drupal 8/9/10

## Structure
```yml
paths:
    tests: tests
    output: tests/_output
    data: tests/_data
    support: tests/_support
    envs: tests/_envs
actor_suffix: Tester
extensions:
    enabled:
        - Codeception\Extension\RunFailed
```

## Configuration

Go to the `tests/acceptance.suite.yml` file and make sure that the `url` and `root` parameters are correct

```json
modules:
    enabled:
        - PhpBrowser:
            url: http://web/
        - DrupalBootstrap:
            root: '/var/www/html/web'
```

Configure content types, vocabularies and URLs in `tests/acceptance/BaseResponseCodeTestCest.php`

## Running tests

To execute all acceptance tests run:

```bash
ddev tests
```

You can select the test to run by:

```bash
ddev tests acceptance AnonymousResponseCodeTestCest
```

## Update the default tests package

At the begging of the project, get the latest tests templates from [droptica/codeception_smoke_test](https://bitbucket.org/droptica/codeception_smoke_test/src/master/).
