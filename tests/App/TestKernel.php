<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\APIErrorHandlerBundle\Tests\App;

use IM\Fabric\Bundle\ApiErrorHandlerBundle\ApiErrorHandlerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function __construct()
    {
        parent::__construct('test', true);
    }

    /**
     * @inheritDoc
     */
    public function registerBundles(): iterable
    {
        return [
            new ApiErrorHandlerBundle(),
            new FrameworkBundle(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir() . '/Tests/App/config/config.yaml');
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/../cache/' . spl_object_hash($this);
    }
}
