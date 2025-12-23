<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Extensions\AtomicLock\Descriptors;

use Cline\Forrst\Contracts\DescriptorInterface;
use Cline\Forrst\Discovery\FunctionDescriptor;
use Cline\Forrst\Enums\ErrorCode;
use Cline\Forrst\Functions\FunctionUrn;

/**
 * Descriptor for the lock force release function.
 *
 * Defines discovery metadata for the forrst.locks.forceRelease system function.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class LockForceReleaseDescriptor implements DescriptorInterface
{
    public static function create(): FunctionDescriptor
    {
        return FunctionDescriptor::make()
            ->urn(FunctionUrn::LocksForceRelease)
            ->summary('Force release a lock without ownership check (admin)')
            ->argument(
                name: 'key',
                schema: ['type' => 'string'],
                required: true,
                description: 'Lock key (with scope prefix if applicable)',
            )
            ->result(
                schema: [
                    'type' => 'object',
                    'properties' => [
                        'released' => [
                            'type' => 'boolean',
                            'description' => 'Whether release was successful',
                        ],
                        'key' => [
                            'type' => 'string',
                            'description' => 'The lock key',
                        ],
                        'forced' => [
                            'type' => 'boolean',
                            'description' => 'Always true for force release',
                        ],
                    ],
                    'required' => ['released', 'key', 'forced'],
                ],
                description: 'Lock force release result',
            )
            ->error(
                code: ErrorCode::LockNotFound,
                message: 'Lock does not exist',
                description: 'The specified lock does not exist',
            );
    }
}
