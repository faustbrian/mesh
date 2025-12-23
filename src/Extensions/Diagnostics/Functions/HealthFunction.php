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
use Cline\Forrst\Exceptions\UnauthorizedException;
use Cline\Forrst\Extensions\Diagnostics\Descriptors\HealthDescriptor;
use Cline\Forrst\Functions\AbstractFunction;

/**
 * Comprehensive health check system function.
 *
 * Implements forrst.health for component-level health checks by aggregating
 * health status from all registered health checker instances.
 *
 * Special component values:
 * - "self": Returns immediate healthy response without checking components (lightweight ping)
 * - null: Checks all registered components
 * - specific component name: Checks only that component
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
     * @param array<int, HealthCheckerInterface> $checkers               Array of registered health checker instances
     * @param bool                               $requireAuthForDetails Whether to require authentication for detailed health info
     */
    public function __construct(
        private readonly array $checkers = [],
        private readonly bool $requireAuthForDetails = true,
    ) {}

    /**
     * Execute the health check function.
     *
     * @throws UnauthorizedException If detailed information is requested without authentication
     *
     * @return array<string, mixed> Health check response
     */
    public function __invoke(): array
    {
        $component = $this->requestObject->getArgument('component');
        $includeDetails = $this->requestObject->getArgument('include_details', true);

        // Require authentication for detailed health info
        if ($includeDetails && $this->requireAuthForDetails && !$this->isAuthenticated()) {
            throw UnauthorizedException::create('Authentication required for detailed health information');
        }

        // Limit component details for unauthenticated requests
        if (!$this->isAuthenticated()) {
            $includeDetails = false;
        }

        // Handle 'self' component check early (lightweight ping)
        if ($component === 'self') {
            return [
                'status' => 'healthy',
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ];
        }

        $components = [];
        $worstStatus = 'healthy';

        foreach ($this->checkers as $checker) {
            if ($component !== null && $checker->getName() !== $component) {
                continue;
            }

            $result = $checker->check();

            // Sanitize output based on authentication
            $components[$checker->getName()] = $this->sanitizeHealthResult(
                $result,
                $includeDetails,
                $this->isAuthenticated(),
            );

            $worstStatus = $this->worstStatus($worstStatus, $result['status']);
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
     * Check if request is authenticated.
     */
    private function isAuthenticated(): bool
    {
        // Check for user_id in context which indicates authentication
        return $this->requestObject->getContext('user_id') !== null;
    }

    /**
     * Sanitize health result based on authentication level.
     *
     * @param array<string, mixed> $result          Raw health check result
     * @param bool                 $includeDetails  Whether to include details
     * @param bool                 $isAuthenticated Whether request is authenticated
     *
     * @return array<string, mixed> Sanitized health result
     */
    private function sanitizeHealthResult(array $result, bool $includeDetails, bool $isAuthenticated): array
    {
        if (!$includeDetails) {
            return ['status' => $result['status']];
        }

        if (!$isAuthenticated) {
            // Only return status for unauthenticated users
            return ['status' => $result['status']];
        }

        // Remove sensitive fields even for authenticated users
        $sanitized = $result;
        unset(
            $sanitized['connection_string'],
            $sanitized['password'],
            $sanitized['secret'],
            $sanitized['api_key'],
            $sanitized['token'],
            $sanitized['credentials'],
        );

        return $sanitized;
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
