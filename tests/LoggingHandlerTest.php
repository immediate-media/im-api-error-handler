<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\API\Error\Subscriber\Tests;

use ApiPlatform\Exception\RuntimeException as ApiPlatformRuntimeException;
use Exception;
use IM\Fabric\Bundle\API\Error\Subscriber\LoggingHandler;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class LoggingHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use MocksExceptionEvents;

    private LoggerInterface $logger;

    private LoggingHandler $loggingHandler;

    public function setUp(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->loggingHandler = new LoggingHandler($this->logger, 'test', []);
    }

    public function testGetSubscribedEventsReturnsCallbackNameForExceptionEvent(): void
    {
        $expected = [KernelEvents::EXCEPTION => ['logException', 0]];

        $this->assertSame($expected, LoggingHandler::getSubscribedEvents());
    }

    public function testLogExceptionIgnoresApiPlatformExceptions(): void
    {
        $event = Mockery::namedMock(ExceptionEvent::class, RequestEvent::class);
        $event->expects('getThrowable')->andReturns(Mockery::mock(ApiPlatformRuntimeException::class));

        $this->logger->shouldNotReceive('log');

        $this->loggingHandler->logException($event);
    }

    public function testLogExceptionLogsInternalServerErrorAsCritical(): void
    {
        $exception = new Exception('Mock unexpected error');

        $event = $this->buildMockEvent($exception);

        $this->logger->shouldReceive('log')
            ->with(LogLevel::CRITICAL, 'Error 500: Mock unexpected error', Mockery::hasKey('trace'));

        $this->loggingHandler->logException($event);
    }

    public function testLogExceptionLogsBadRequestHttpExceptionAsError(): void
    {
        $exception = new BadRequestHttpException('Mock bad request error', null, 400);

        $event = $this->buildMockEvent($exception);

        $this->logger->shouldReceive('log')
            ->with(LogLevel::ERROR, 'Error 400: Mock bad request error', Mockery::hasKey('trace'));

        $this->loggingHandler->logException($event);
    }
}
