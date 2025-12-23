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
use Cline\Forrst\Exceptions\OperationNotFoundException;
use Cline\Forrst\Extensions\Async\Descriptors\OperationStatusDescriptor;
use Cline\Forrst\Functions\AbstractFunction;

use function assert;
use function is_string;

/**
 * Async operation status check function.
 *
 * Implements forrst.operation.status for retrieving operation status.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/async
 */
#[Descriptor(OperationStatusDescriptor::class)]
final class OperationStatusFunction extends AbstractFunction
{
    /**
     * Create a new operation status function instance.
     *
     * @param OperationRepositoryInterface $repository Operation repository
     */
    public function __construct(
        private readonly OperationRepositoryInterface $repository,
    ) {}

    /**
     * Execute the operation status function.
     *
     * @throws OperationNotFoundException If the operation ID does not exist
     *
     * @return array<string, mixed> Operation status details
     */
    public function __invoke(): array
    {
        $operationId = $this->requestObject->getArgument('operation_id');

        assert(is_string($operationId));

        $operation = $this->repository->find($operationId);

        if (!$operation instanceof OperationData) {
            throw OperationNotFoundException::create($operationId);
        }

        return $operation->toArray();
    }
}
