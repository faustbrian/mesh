<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Extensions\Async\Functions;

use Cline\Forrst\Attributes\Descriptor;
use Cline\Forrst\Contracts\OperationRepositoryInterface;
use Cline\Forrst\Data\OperationData;
use Cline\Forrst\Extensions\Async\Descriptors\OperationListDescriptor;
use Cline\Forrst\Functions\AbstractFunction;

use function array_map;
use function assert;
use function is_int;
use function is_string;

/**
 * Async operation listing function.
 *
 * Implements forrst.operation.list for retrieving paginated operation lists.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/async
 */
#[Descriptor(OperationListDescriptor::class)]
final class OperationListFunction extends AbstractFunction
{
    /**
     * Create a new operation list function instance.
     *
     * @param OperationRepositoryInterface $repository Operation repository
     */
    public function __construct(
        private readonly OperationRepositoryInterface $repository,
    ) {}

    /**
     * Execute the operation list function.
     *
     * @return array{operations: array<int, array<string, mixed>>, next_cursor?: string} Paginated operations
     */
    public function __invoke(): array
    {
        $status = $this->requestObject->getArgument('status');
        $function = $this->requestObject->getArgument('function');
        $limit = $this->requestObject->getArgument('limit', 50);
        $cursor = $this->requestObject->getArgument('cursor');

        assert(is_string($status) || $status === null);
        assert(is_string($function) || $function === null);
        assert(is_int($limit));
        assert(is_string($cursor) || $cursor === null);

        $result = $this->repository->list($status, $function, $limit, $cursor);

        $response = [
            'operations' => array_map(
                fn (OperationData $op): array => $op->toArray(),
                $result['operations'],
            ),
        ];

        if ($result['next_cursor'] !== null) {
            $response['next_cursor'] = $result['next_cursor'];
        }

        return $response;
    }
}
