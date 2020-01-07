<?php

namespace IM\Fabric\Package\API\Error\Subscriber;

use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

abstract class AbstractExceptionSubscriber implements EventSubscriberInterface
{
    protected const TRACE_TEMPLATE_STRING = 'Line <line>: \<class>::<function>';

    /** @var string */
    protected $appEnv;

    /** @var array */
    protected $exceptionToStatus;

    public function __construct(string $appEnv, array $exceptionToStatus)
    {
        $this->appEnv = $appEnv;
        $this->exceptionToStatus = $exceptionToStatus;
    }

    protected function getStatusCode(Throwable $throwable): int
    {
        foreach ($this->exceptionToStatus as $exceptionClass => $statusCode) {
            if ($throwable instanceof $exceptionClass) {
                return $statusCode;
            }
        }

        return $throwable instanceof HttpExceptionInterface
            ? $throwable->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    protected function toArray(Throwable $throwable, int $statusCode, string $appEnv = null): array
    {
        $data = [
            'message' => $throwable->getMessage(),
            'status' => $statusCode,
            'title' => (new Response())->statusTexts[$statusCode] ?? 'Unknown status code',
        ];

        $appEnv = $appEnv ?? $this->appEnv;

        if ($appEnv !== 'prod') {
            $data['trace'] = $this->generateTrace($throwable->getTrace());
        }

        return array_filter($data);
    }

    private function generateTrace(array $stackTrace): array
    {
        $matches = $this->extractTemplateStringElements();

        $trace = [];
        foreach ($stackTrace as $traceElement) {
            $line = self::TRACE_TEMPLATE_STRING;

            foreach ($matches as $lookup) {
                $line = str_replace("<$lookup>", $traceElement[$lookup] ?? '<PHP>', $line);
            }

            $trace[] = $line;
        }

        return $trace;
    }

    private function extractTemplateStringElements(): array
    {
        $matches = [];

        preg_match_all('/(?<=<)([\w]+)(?=>)/', self::TRACE_TEMPLATE_STRING, $matches);

        return reset($matches);
    }
}
