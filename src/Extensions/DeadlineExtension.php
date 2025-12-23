<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Extensions;

use Carbon\CarbonImmutable;
use Cline\Forrst\Data\ErrorData;
use Cline\Forrst\Data\ExtensionData;
use Cline\Forrst\Data\ResponseData;
use Cline\Forrst\Enums\ErrorCode;
use Cline\Forrst\Events\ExecutingFunction;
use Cline\Forrst\Events\FunctionExecuted;
use DateTimeInterface;
use Override;

use function abs;
use function assert;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function round;

/**
 * Deadline extension handler.
 *
 * Sets a deadline for request completion. If the deadline passes
 * before the request completes, a DEADLINE_EXCEEDED error is returned.
 *
 * Request options:
 * - deadline: ISO 8601 timestamp when the request should expire
 * - timeout: Duration object {value: int, unit: string} relative to now
 *
 * Response data (per Forrst protocol specification):
 * - specified: {value, unit} - Original timeout/deadline specified
 * - elapsed: {value, unit} - Time elapsed since request started
 * - remaining: {value, unit} - Time remaining before deadline
 * - utilization: float - Percentage of deadline used (0.0 to 1.0)
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/specs/forrst/extensions/deadline
 */
final class DeadlineExtension extends AbstractExtension
{
    /**
     * Request start time for elapsed calculation.
     */
    private ?CarbonImmutable $requestStartTime = null;

    /**
     * Original timeout specification from request.
     *
     * @var null|array{value: int, unit: string}
     */
    private ?array $specifiedTimeout = null;

    #[Override()]
    public function getUrn(): string
    {
        return ExtensionUrn::Deadline->value;
    }

    #[Override()]
    public function getSubscribedEvents(): array
    {
        return [
            ExecutingFunction::class => [
                'priority' => 10,
                'method' => 'onExecutingFunction',
            ],
            FunctionExecuted::class => [
                'priority' => 200,
                'method' => 'onFunctionExecuted',
            ],
        ];
    }

    /**
     * Check deadline before function execution.
     *
     * Records request start time and validates deadline hasn't already passed.
     * If deadline is in the past, stops propagation and returns DEADLINE_EXCEEDED
     * error response immediately without executing function.
     *
     * @param ExecutingFunction $event Function execution event with extension data
     */
    public function onExecutingFunction(ExecutingFunction $event): void
    {
        // Record start time for elapsed calculation
        $this->requestStartTime = CarbonImmutable::now();

        // Track specified timeout for response
        if (isset($event->extension->options['timeout']) && is_array($event->extension->options['timeout'])) {
            /** @var int $timeoutValue */
            $timeoutValue = $event->extension->options['timeout']['value'] ?? 0;

            /** @var string $timeoutUnit */
            $timeoutUnit = $event->extension->options['timeout']['unit'] ?? 'second';

            $this->specifiedTimeout = [
                'value' => $timeoutValue,
                'unit' => $timeoutUnit,
            ];
        }

        $deadline = $this->resolveDeadline($event->extension->options);

        if (!$deadline instanceof CarbonImmutable) {
            return;
        }

        // Check if deadline has already passed
        if (!$deadline->isPast()) {
            return;
        }

        $event->setResponse(ResponseData::error(
            new ErrorData(
                code: ErrorCode::DeadlineExceeded,
                message: 'Request deadline has already passed',
                details: [
                    'deadline' => $deadline->toIso8601String(),
                ],
            ),
            $event->request->id,
            extensions: [
                ExtensionData::response(ExtensionUrn::Deadline->value, [
                    'exceeded' => true,
                    'deadline' => $deadline->toIso8601String(),
                ]),
            ],
        ));
        $event->stopPropagation();
    }

    /**
     * Add deadline metadata to response after execution.
     *
     * Enriches response with timing information including elapsed time, remaining
     * time until deadline, and utilization percentage. Helps clients understand
     * request performance and adjust timeout values for future requests.
     *
     * @param FunctionExecuted $event Function execution event with response
     */
    public function onFunctionExecuted(FunctionExecuted $event): void
    {
        $deadline = $this->resolveDeadline($event->extension->options);

        if (!$deadline instanceof CarbonImmutable) {
            return;
        }

        $now = CarbonImmutable::now();

        // Calculate remaining time - clamp to 0 if deadline is past
        $remainingMs = $deadline->isPast() ? 0 : (int) abs($deadline->diffInMilliseconds($now));

        // Calculate elapsed time since request started
        $startTime = $this->requestStartTime ?? $now;
        $elapsedMs = (int) abs($startTime->diffInMilliseconds($now));

        // Track specified timeout from options if not already set
        $specifiedTimeout = $this->specifiedTimeout;

        if ($specifiedTimeout === null && isset($event->extension->options['timeout']) && is_array($event->extension->options['timeout'])) {
            $specifiedTimeout = [
                'value' => $event->extension->options['timeout']['value'] ?? 0,
                'unit' => $event->extension->options['timeout']['unit'] ?? 'second',
            ];
        }

        // Build spec-compliant response data
        $responseData = [
            'elapsed' => [
                'value' => $elapsedMs,
                'unit' => 'millisecond',
            ],
            'remaining' => [
                'value' => $remainingMs,
                'unit' => 'millisecond',
            ],
        ];

        // Include specified timeout if available
        if ($specifiedTimeout !== null) {
            $responseData['specified'] = $specifiedTimeout;

            // Calculate utilization as percentage of deadline used
            assert(is_int($specifiedTimeout['value']));
            assert(is_string($specifiedTimeout['unit']));
            $specifiedMs = $this->convertToMilliseconds(
                $specifiedTimeout['value'],
                $specifiedTimeout['unit'],
            );

            if ($specifiedMs > 0) {
                $responseData['utilization'] = round($elapsedMs / $specifiedMs, 4);
            }
        }

        // Add deadline info to response extensions
        $extensions = $event->getResponse()->extensions ?? [];
        $extensions[] = ExtensionData::response(ExtensionUrn::Deadline->value, $responseData);

        $event->setResponse(
            new ResponseData(
                protocol: $event->getResponse()->protocol,
                id: $event->getResponse()->id,
                result: $event->getResponse()->result,
                errors: $event->getResponse()->errors,
                extensions: $extensions,
                meta: $event->getResponse()->meta,
            ),
        );
    }

    /**
     * Convert duration value to milliseconds.
     *
     * Normalizes various time units to milliseconds for consistent deadline
     * calculation and comparison. Defaults to seconds for unknown units.
     *
     * @param int    $value Duration value
     * @param string $unit  Duration unit (millisecond, second, minute, hour)
     *
     * @return int Duration in milliseconds
     */
    private function convertToMilliseconds(int $value, string $unit): int
    {
        return match ($unit) {
            'millisecond' => $value,
            'second' => $value * 1_000,
            'minute' => $value * 60 * 1_000,
            'hour' => $value * 60 * 60 * 1_000,
            default => $value * 1_000,
        };
    }

    /**
     * Resolve deadline from extension options.
     *
     * Supports both absolute deadline (ISO 8601 timestamp) and relative timeout
     * (duration object). Returns null if neither option is provided.
     *
     * @param null|array<string, mixed> $options Extension options from request
     *
     * @return null|CarbonImmutable Deadline timestamp or null if not specified
     */
    private function resolveDeadline(?array $options): ?CarbonImmutable
    {
        if ($options === null) {
            return null;
        }

        // Absolute deadline
        if (isset($options['deadline'])) {
            $deadline = $options['deadline'];
            assert(is_string($deadline) || $deadline instanceof DateTimeInterface || is_int($deadline) || is_float($deadline));

            return CarbonImmutable::parse($deadline);
        }

        // Relative timeout
        if (isset($options['timeout']) && is_array($options['timeout'])) {
            /** @var int $value */
            $value = $options['timeout']['value'] ?? 0;

            /** @var string $unit */
            $unit = $options['timeout']['unit'] ?? 'second';

            return CarbonImmutable::now()->add($value, $unit);
        }

        return null;
    }
}
