# Upgrading from existing installation of im-api-error-handler package in an API

## Setup

1. Remove existing package before installing this bundle.

```yaml
    composer remove immediate/im-api-error-handler
```

2. Check the API's 'config/services.yaml' for the two configurations below and, if present, remove them.

```yaml
       # IM API Error Handler
       IM\Fabric\Package\API\Error\Subscriber\ErrorDisplayHandler:
          arguments: [ '%kernel.environment%', '%api_platform.exception_to_status%' ]
```

```yaml
       IM\Fabric\B\API\Error\Subscriber\LoggingHandler:
          arguments: [ '@logger', '%kernel.environment%', '%api_platform.exception_to_status%' ]
          tags:
             - { name: monolog.logger, channel: app }
```

You can edit what channels logs go to by redeclaring the DI for the logging handler in your services.yaml

3. Install im-api-handler bundle

```yaml
    composer config repositories.repo-name vcs https://github.immediate.co.uk/WCP-Packages/im-api-error-handler.git
    composer require immediate/im-api-error-handler
```
4. Check the API's 'config/bundles.php' for the content below

```yaml

    IM\Fabric\Bundle\API\Error\Subscriber\ApiErrorHandlerBundle::class => ['all' => true],

```
5. If it is not present then add it
