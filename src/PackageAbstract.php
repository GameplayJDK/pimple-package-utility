<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2020 GameplayJDK
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Pimple\Package;

use ArrayObject;
use Closure;
use Pimple\Container;
use Pimple\Package\Exception\PackageException;

/**
 * Class PackageAbstract
 *
 * @package Pimple\Package
 */
abstract class PackageAbstract implements PackageInterface
{
    /**
     * Service name for the tag.
     */
    const SERVICE_NAME_TAG = 'tag';

    /**
     * Service name for the configuration.
     */
    const SERVICE_NAME_CONFIGURATION = 'configuration';

    /**
     * @var Container|null
     */
    protected $container;

    /**
     * PackageAbstract constructor.
     * @param Container|null $container
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * Make sure the parent is called when overriding this function.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple): void
    {
        if (null === $this->container) {
            $this->container = $pimple;
        }

//        // Automatically add tag support if not present.
//        if (!$this->hasTagSupport(false)) {
//            $this->addTagSupport();
//        }
//
//        // Automatically add configuration support if not present.
//        if (!$this->hasConfigurationSupport(false)) {
//            $this->addConfigurationSupport();
//        }
    }

    /**
     * @param string $serviceName
     * @param Closure $closure
     */
    protected function registerService(string $serviceName, Closure $closure): void
    {
        $this->container[$serviceName] = $closure;
    }

    /**
     * Register a service name alias for a service name.
     *
     * @param string $serviceNameAlias
     * @param string $serviceName
     */
    protected function registerServiceAlias(string $serviceNameAlias, string $serviceName): void
    {
        $this->registerService($serviceNameAlias, function (Container $container) use ($serviceName) {
            return $container[$serviceName];
        });
    }

    /**
     * Add tag support.
     *
     * See {@link https://github.com/silexphp/Pimple/issues/205#issuecomment-230919514 this issue} for more information.
     *
     * __WARNING__: This should only be called after checking for existing tag support. That can be done using
     * {@link hasTagSupport()}.
     */
    protected function addTagSupport(): void
    {
        $this->container[static::SERVICE_NAME_TAG] = new ArrayObject();
    }

    /**
     * Remove tag support.
     *
     * @deprecated
     */
    protected function removeTagSupport(): void
    {
        unset($this->container[static::SERVICE_NAME_TAG]);
    }

    /**
     * Check for existing tag support.
     *
     * @param bool $shouldThrow
     * @return bool
     * @throws PackageException
     */
    protected function hasTagSupport(bool $shouldThrow = false): bool
    {
        $tagSupport = (isset($this->container[static::SERVICE_NAME_TAG]) && ($this->container[static::SERVICE_NAME_TAG] instanceof ArrayObject));

        if (!$tagSupport && $shouldThrow) {
            throw new PackageException('Container does not have tag support!');
        }

        return $tagSupport;
    }

    /**
     * Register a service name for a tag name. Practically tag the service.
     *
     * @param string $tagName
     * @param string $serviceName
     */
    protected function registerTag(string $tagName, string $serviceName): void
    {
        $this->container[static::SERVICE_NAME_TAG][$tagName][] = $serviceName;
    }

    /**
     * @param string $tagName
     * @param string $serviceName
     * @param Closure $closure
     * @throws PackageException
     */
    protected function registerTagAndService(string $tagName, string $serviceName, Closure $closure): void
    {
        if ($this->hasTagSupport(true)) {
            $this->registerTag($tagName, $serviceName);
        }

        $this->registerService($serviceName, $closure);
    }

    /**
     * Add configuration support.
     *
     * __WARNING__: This should only be called after checking for existing configuration support. That can be done using
     * {@link hasConfigurationSupport()}.
     */
    protected function addConfigurationSupport(): void
    {
        $this->container[static::SERVICE_NAME_CONFIGURATION] = [];
    }

    /**
     * Remove configuration support.
     *
     * @deprecated
     */
    protected function removeConfigurationSupport(): void
    {
        unset($this->container[static::SERVICE_NAME_CONFIGURATION]);
    }

    /**
     * Check for existing configuration support.
     *
     * @param bool $shouldThrow
     * @return bool
     * @throws PackageException
     */
    protected function hasConfigurationSupport(bool $shouldThrow = false): bool
    {
        $configurationSupport = (isset($this->container[static::SERVICE_NAME_CONFIGURATION])) && (is_array($this->container[static::SERVICE_NAME_CONFIGURATION]));

        if (!$configurationSupport && $shouldThrow) {
            throw new PackageException('Container does not have configuration support!');
        }

        return $configurationSupport;
    }

    /**
     * Register a default configuration.
     *
     * __WARNING__: Make sure the custom configuration is present inside the container _before_ the package is loaded!
     *
     * @param string $serviceName
     * @param array $configuration
     * @throws PackageException
     */
    protected function registerConfiguration(string $serviceName, array $configuration): void
    {
        if (!$this->hasConfigurationSupport(true)) {
            return;
        }

        $settings = [];

        if (isset($this->container[static::SERVICE_NAME_CONFIGURATION][$serviceName]) && is_array($this->container[static::SERVICE_NAME_CONFIGURATION][$serviceName])) {
            $settings = $this->container[static::SERVICE_NAME_CONFIGURATION][$serviceName];
        }

        $settings = array_replace_recursive($configuration, $settings);

        // Overload the configuration.

        /** @var array $configuration */
        $configuration = $this->container[static::SERVICE_NAME_CONFIGURATION];
        $configuration[$serviceName] = $settings;
        $this->container[static::SERVICE_NAME_CONFIGURATION] = $configuration;
    }
}
