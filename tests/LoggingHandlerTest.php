<?php

namespace IM\Fabric\Package\API\Error\Subscriber\Tests;

use ApiPlatform\Core\Exception\RuntimeException as ApiPlatformRuntimeException;
use IM\Fabric\Package\API\Error\Subscriber\LoggingHandler;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

class LoggingHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var LoggerInterface */
    private $logger;

    /** @var LoggingHandler */
    private $loggingHandler;

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
        $event = $this->buildMockEvent(Mockery::mock(ApiPlatformRuntimeException::class));

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

    /**
     * @param Throwable $throwable
     * @return ExceptionEvent|MockInterface
     */
    private function buildMockEvent(Throwable $throwable): ExceptionEvent
    {
        $event = Mockery::mock(ExceptionEvent::class);
        $event->shouldReceive('getThrowable')->andReturn($throwable);

        return $event;
    }
}
