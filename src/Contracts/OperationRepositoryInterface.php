<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Contracts;

use Cline\Forrst\Data\OperationData;

/**
 * Forrst async operation repository contract interface.
 *
 * Defines the contract for implementing persistent storage for async operations
 * created by the async extension. Repositories manage the complete lifecycle of
 * async operation state from creation through completion or cancellation.
 *
 * Provides CRUD operations for managing async operation state including status
 * tracking, result storage, and progress monitoring. Implementations must ensure
 * thread-safe operations and support concurrent access patterns.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/async Async extension specification
 */
interface OperationRepositoryInterface
{
    /**
     * Find an operation by ID.
     *
     * Retrieves a single operation by its unique identifier. Returns null if the
     * operation does not exist or has been deleted.
     *
     * @param string $id Operation ID
     *
     * @return null|OperationData The operation or null if not found
     */
    public function find(string $id): ?OperationData;

    /**
     * Save an operation.
     *
     * Persists an operation to storage. If the operation already exists (based on ID),
     * it should be updated. If it does not exist, it should be created. Implementations
     * must handle concurrent updates safely.
     *
     * @param OperationData $operation The operation to save
     */
    public function save(OperationData $operation): void;

    /**
     * Delete an operation.
     *
     * Removes an operation from storage by its ID. Idempotent - silently succeeds
     * if the operation does not exist.
     *
     * @param string $id Operation ID
     */
    public function delete(string $id): void;

    /**
     * List operations with optional filters.
     *
     * Retrieves a paginated list of operations matching the specified criteria.
     * Supports filtering by status and function name, with cursor-based pagination
     * for efficient traversal of large result sets.
     *
     * @param null|string $status   Filter by status (pending, running, completed, failed, cancelled)
     * @param null|string $function Filter by function name
     * @param int         $limit    Maximum number of results to return (default: 50)
     * @param null|string $cursor   Pagination cursor for fetching subsequent pages
     *
     * @return array{operations: array<int, OperationData>, next_cursor: ?string} Operations and next page cursor
     */
    public function list(
        ?string $status = null,
        ?string $function = null,
        int $limit = 50,
        ?string $cursor = null,
    ): array;
}
