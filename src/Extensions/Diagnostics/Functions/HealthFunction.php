<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Extensions\Diagnostics\Functions;

use Carbon\CarbonImmutable;
use Cline\Forrst\Attributes\Descriptor;
use Cline\Forrst\Contracts\HealthCheckerInterface;
use Cline\Forrst\Extensions\Diagnostics\Descriptors\HealthDescriptor;
use Cline\Forrst\Functions\AbstractFunction;

/**
 * Comprehensive health check system function.
 *
 * Implements forrst.health for component-level health checks by aggregating
 * health status from all registered health checker instances.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/system-functions
 */
#[Descriptor(HealthDescriptor::class)]
final class HealthFunction extends AbstractFunction
{
    /**
     * Create a new health function instance.
     *
     * @param array<int, HealthCheckerInterface> $checkers Array of registered health checker instances
     */
    public function __construct(
        private readonly array $checkers = [],
    ) {}

    /**
     * Execute the health check function.
     *
     * @return array<string, mixed> Health check response
     */
    public function __invoke(): array
    {
        $component = $this->requestObject->getArgument('component');
        $includeDetails = $this->requestObject->getArgument('include_details', true);

        $components = [];
        $worstStatus = 'healthy';

        foreach ($this->checkers as $checker) {
            if ($component !== null && $checker->getName() !== $component) {
                continue;
            }

            $result = $checker->check();
            $components[$checker->getName()] = $includeDetails
                ? $result
                : ['status' => $result['status']];

            $worstStatus = $this->worstStatus($worstStatus, $result['status']);
        }

        if ($component === 'self') {
            return [
                'status' => 'healthy',
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ];
        }

        $response = [
            'status' => $worstStatus,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];

        if ($components !== []) {
            $response['components'] = $components;
        }

        return $response;
    }

    /**
     * Compare and return the worst status between current and new.
     */
    private function worstStatus(string $current, string $new): string
    {
        $order = ['healthy' => 0, 'degraded' => 1, 'unhealthy' => 2];

        return ($order[$new] ?? 0) > ($order[$current] ?? 0) ? $new : $current;
    }
}
