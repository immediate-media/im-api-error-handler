<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\ApiErrorHandlerBundle\Tests;

use IM\Fabric\Bundle\ApiErrorHandlerBundle\ApiErrorHandlerBundle;
use IM\Fabric\Bundle\ApiErrorHandlerBundle\DependencyInjection\ApiErrorHandlerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ApiErrorHandlerBundleTest extends TestCase
{
    private readonly ApiErrorHandlerBundle $unit;

    protected function setUp(): void
    {
        $this->unit = new ApiErrorHandlerBundle();
    }

    public function testItShouldBeAnInstanceOfASymfonyBundle(): void
    {
        $this->assertInstanceOf(Bundle::class, $this->unit);
    }

    public function testShouldReturnNewContainerExtension(): void
    {
        $this->assertInstanceOf(ApiErrorHandlerExtension::class, $this->unit->getContainerExtension());
    }
}
