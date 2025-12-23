<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Contracts;

/**
 * Forrst health checker contract interface.
 *
 * Defines the contract for implementing health check monitors for system
 * components and dependencies. Health checkers provide standardized status
 * reporting used by the system.health function.
 *
 * Health checkers are responsible for checking the health of a specific
 * component (database, cache, queue, external API, etc.) and returning
 * a standardized health status. Each checker monitors a single component
 * and reports its availability and performance metrics.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/system-functions Health check specification
 */
interface HealthCheckerInterface
{
    /**
     * Get the component name.
     *
     * Standard component names:
     * - "self" - The service process itself
     * - "database" - Primary database
     * - "cache" - Caching layer (Redis, Memcached)
     * - "queue" - Message queue
     * - "storage" - Object/file storage
     * - "search" - Search engine (Elasticsearch)
     * - "<service>_api" - External service dependency
     *
     * @return string The component identifier
     */
    public function getName(): string;

    /**
     * Check the component health.
     *
     * Performs a health check on the component and returns standardized status
     * information including availability, latency metrics, and diagnostic messages.
     * The status field should be one of: "healthy", "degraded", "unhealthy".
     *
     * @return array{status: string, latency?: array{value: int, unit: string}, message?: string, last_check?: string} Health status data
     */
    public function check(): array;
}
