<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\ApiErrorHandlerBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

abstract class AbstractExceptionSubscriber implements EventSubscriberInterface
{
    protected const TRACE_TEMPLATE_STRING = 'Line <line>: \<class>::<function>';

    public function __construct(
        private readonly string $appEnv,
        private readonly array $exceptionToStatus
    ) {
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

    /** @SuppressWarnings(PHPMD.UndefinedVariable) */
    protected function toArray(Throwable $throwable, int $statusCode, ?string $appEnv = null): array
    {
        $response = new Response();

        $data = [
            'message' => $throwable->getMessage(),
            'status' => $statusCode,
            'title' => $response::$statusTexts[$statusCode] ?? 'Unknown status code',
        ];

        $appEnv ??= $this->appEnv;

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
                $element = $traceElement[$lookup] ?? '<PHP>';
                $line = str_replace("<$lookup>", (string)$element, $line);
            }

            $trace[] = $line;
        }

        return $trace;
    }

    private function extractTemplateStringElements(): array
    {
        $matches = [];

        preg_match_all('/(?<=<)(\w+)(?=>)/', self::TRACE_TEMPLATE_STRING, $matches);

        return reset($matches);
    }
}
