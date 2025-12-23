<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Repositories;

use Cline\Forrst\Contracts\OperationRepositoryInterface;
use Cline\Forrst\Data\OperationData;
use Cline\Forrst\Models\Operation;
use Illuminate\Database\Eloquent\Collection;

use function array_map;
use function assert;
use function base64_decode;
use function base64_encode;
use function is_array;
use function json_decode;
use function json_encode;
use function now;

/**
 * Persistent storage for asynchronous operation tracking and status management.
 *
 * Stores operation metadata in a database table, enabling long-lived operation
 * tracking that survives application restarts. Supports automatic expiration of
 * completed operations after a configurable retention period. Designed for scenarios
 * where operations may be checked hours, days, or weeks after initiation.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/async
 * @psalm-immutable
 */
final readonly class DatabaseOperationRepository implements OperationRepositoryInterface
{
    /**
     * Default retention period for completed operations.
     *
     * Operations are automatically deleted after this many days to prevent
     * unbounded database growth.
     */
    private const int DEFAULT_RETENTION_DAYS = 30;

    /**
     * Creates a repository with configurable operation retention.
     *
     * @param int $retentionDays Number of days to retain completed operations before automatic
     *                           deletion. Applies to all operation statuses. Used to set the
     *                           expires_at timestamp when operations are first created.
     */
    public function __construct(
        private int $retentionDays = self::DEFAULT_RETENTION_DAYS,
    ) {}

    /**
     * Retrieves an operation by its unique identifier.
     *
     * @param string $id Operation identifier (typically a ULID)
     *
     * @return null|OperationData Operation data if found, null otherwise
     */
    public function find(string $id): ?OperationData
    {
        $operation = Operation::query()->find($id);

        return $operation?->toOperationData();
    }

    /**
     * Persists operation state to the database, creating or updating as needed.
     *
     * Creates a new database record for new operations with an automatic expiration
     * timestamp. Updates existing operations by overwriting status, progress, results,
     * timestamps, and error information.
     *
     * @param OperationData $operation Operation data to persist
     */
    public function save(OperationData $operation): void
    {
        $model = Operation::query()->find($operation->id);

        if ($model === null) {
            $model = Operation::fromOperationData($operation);
            $model->expires_at = now()->addDays($this->retentionDays)->toImmutable();
        } else {
            $model->status = $operation->status->value;
            $model->progress = $operation->progress;

            /** @var null|array<string, mixed> $result */
            $result = $operation->result;
            $model->result = $result;
            $model->started_at = $operation->startedAt;
            $model->completed_at = $operation->completedAt;
            $model->cancelled_at = $operation->cancelledAt;
            $model->metadata = $operation->metadata;

            if ($operation->errors !== null) {
                $model->errors = array_map(
                    fn ($err): array => $err->toArray(),
                    $operation->errors,
                );
            }
        }

        $model->save();
    }

    /**
     * Removes an operation from the database.
     *
     * @param string $id Operation identifier to delete
     */
    public function delete(string $id): void
    {
        Operation::destroy($id);
    }

    /**
     * Lists operations with optional filtering and cursor-based pagination.
     *
     * Returns operations sorted by creation time (newest first). Supports filtering
     * by status and function name. Uses cursor-based pagination for efficient
     * traversal of large result sets.
     *
     * @param null|string $status   Filter by operation status (pending, running, completed, failed, cancelled)
     * @param null|string $function Filter by function name (e.g., "orders.create")
     * @param int         $limit    Maximum number of operations to return
     * @param null|string $cursor   Base64-encoded pagination cursor from previous response
     *
     * @return array{operations: array<int, OperationData>, next_cursor: ?string} Operations and pagination cursor
     */
    public function list(
        ?string $status = null,
        ?string $function = null,
        int $limit = 50,
        ?string $cursor = null,
    ): array {
        $query = Operation::query()->latest();

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($function !== null) {
            $query->where('function', $function);
        }

        if ($cursor !== null) {
            $decodedBase64 = base64_decode($cursor, true);

            if ($decodedBase64 !== false) {
                /** @var null|array<string, mixed> $decoded */
                $decoded = json_decode($decodedBase64, true);

                if (is_array($decoded) && isset($decoded['id'])) {
                    $query->where('id', '<', $decoded['id']);
                }
            }
        }

        /** @var Collection<int, Operation> $operations */
        $operations = $query->limit($limit + 1)->get();

        $hasMore = $operations->count() > $limit;

        if ($hasMore) {
            $operations = $operations->take($limit);
        }

        $nextCursor = null;

        if ($hasMore && $operations->isNotEmpty()) {
            $lastOp = $operations->last();
            $encodedJson = json_encode(['id' => $lastOp->id]);
            assert($encodedJson !== false);
            $nextCursor = base64_encode($encodedJson);
        }

        return [
            'operations' => $operations->map(fn (Operation $op): OperationData => $op->toOperationData())->all(),
            'next_cursor' => $nextCursor,
        ];
    }

    /**
     * Lists operations initiated by a specific caller with filtering and pagination.
     *
     * Returns operations scoped to a single caller, sorted by creation time (newest
     * first). Useful for user-specific operation dashboards or tracking. Uses the
     * same cursor-based pagination strategy as list().
     *
     * @param string      $callerId Unique identifier for the caller (e.g., user ID, API key)
     * @param null|string $status   Filter by operation status
     * @param int         $limit    Maximum number of operations to return
     * @param null|string $cursor   Base64-encoded pagination cursor from previous response
     *
     * @return array{operations: array<int, OperationData>, next_cursor: ?string} Operations and pagination cursor
     */
    public function listForCaller(
        string $callerId,
        ?string $status = null,
        int $limit = 50,
        ?string $cursor = null,
    ): array {
        $query = Operation::query()->where('caller_id', $callerId)->latest();

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($cursor !== null) {
            $decodedBase64 = base64_decode($cursor, true);

            if ($decodedBase64 !== false) {
                /** @var null|array<string, mixed> $decoded */
                $decoded = json_decode($decodedBase64, true);

                if (is_array($decoded) && isset($decoded['id'])) {
                    $query->where('id', '<', $decoded['id']);
                }
            }
        }

        /** @var Collection<int, Operation> $operations */
        $operations = $query->limit($limit + 1)->get();

        $hasMore = $operations->count() > $limit;

        if ($hasMore) {
            $operations = $operations->take($limit);
        }

        $nextCursor = null;

        if ($hasMore && $operations->isNotEmpty()) {
            $lastOp = $operations->last();
            $encodedJson = json_encode(['id' => $lastOp->id]);
            assert($encodedJson !== false);
            $nextCursor = base64_encode($encodedJson);
        }

        /** @var array<int, OperationData> $operationsArray */
        $operationsArray = $operations->map(fn (Operation $op): OperationData => $op->toOperationData())->all();

        return [
            'operations' => $operationsArray,
            'next_cursor' => $nextCursor,
        ];
    }

    /**
     * Removes all operations that have passed their expiration timestamp.
     *
     * Intended for scheduled execution (e.g., daily cron job) to prevent unbounded
     * database growth. Only deletes operations where expires_at is in the past.
     *
     * @return int Number of operations deleted
     */
    public function cleanupExpired(): int
    {
        return Operation::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }
}
