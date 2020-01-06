# pimple-package-utility

Utility classes for pimple container based libraries and applications with configuration and tag support.

## Installation

```bash
composer require gameplayjdk/pimple-package-utility
```

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Pimple\Container;
use Pimple\Package\Exception\PackageException;
use Pimple\Package\PackageAbstract;

/**
 * Interface SomeServiceInterface
 */
interface SomeServiceInterface
{
    const TAG_SOME_SERVICE = 'tag.some_service';
}

/**
 * Class SomeService
 */
class SomeService implements SomeServiceInterface
{
    /**
     * @var int
     */
    private $someValue;

    /**
     * SomeService constructor.
     * @param int $someValue
     */
    public function __construct(int $someValue)
    {
        $this->someValue = $someValue;
    }

    /**
     * Print this instance.
     */
    public function doSomething(): void
    {
        echo print_r($this, true);
        echo PHP_EOL;
    }
}

/**
 * Class MyPackage
 */
class MyPackage extends PackageAbstract
{
    /**
     * @param Container $pimple
     * @throws PackageException
     */
    public function register(Container $pimple): void
    {
        parent::register($pimple);

        $this->registerSomeLibrary($pimple);
    }

    /**
     * @param Container $container
     * @throws PackageException
     */
    private function registerSomeLibrary(Container $container): void
    {
        // Define a configuration for some service...

        $this->registerConfiguration(SomeService::class, [
            'someValue' => 0,
        ]);

        // But make sure to get the new configuration array afterwards...

        /** @var array $configuration */
        $configuration = $container[static::SERVICE_NAME_CONFIGURATION];

        // And also notice, that the configuration should be present before adding the default configuration like above.

        // Either define a service...

        $this->registerService(SomeService::class, function (Container $container) use ($configuration): SomeServiceInterface {
            /** @var array $settings */
            $settings = $configuration[SomeService::class];

            return new SomeService($settings['someValue']);
        });

        // And tag it separately...

        $this->registerTag(SomeServiceInterface::TAG_SOME_SERVICE, SomeService::class);

        // Or do both at the same time...

        $this->registerTagAndService(SomeServiceInterface::TAG_SOME_SERVICE, SomeService::class, function (Container $container) use ($configuration): SomeServiceInterface {
            /** @var array $settings */
            $settings = $configuration[SomeService::class];

            return new SomeService($settings['someValue']);
        });
    }
}

// Initialize configuration in container.
$valueArray = [
    PackageAbstract::SERVICE_NAME_CONFIGURATION => [
        SomeService::class => [
            'someValue' => 10,
        ],
    ],
];
// Create container with that value array.
$container = new Container();

// Register the package to pimple without a container instance, in which case the internal container is set when calling
// parent::register($container)...
$container->register(new MyPackage());
// Or give it to the constructor which sets the internal container variable on instantiation...
$container->register(new MyPackage($container));

```

## License

It's MIT.
