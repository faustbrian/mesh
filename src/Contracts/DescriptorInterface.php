<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Contracts;

use Cline\Forrst\Discovery\FunctionDescriptor;

/**
 * Contract for function descriptor classes.
 *
 * Descriptor classes define the discovery metadata for Forrst functions,
 * separating schema definitions from business logic. Each function class
 * references its descriptor via the #[Descriptor] attribute.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/discovery
 */
interface DescriptorInterface
{
    /**
     * Create the function descriptor with all discovery metadata.
     *
     * @return FunctionDescriptor Fluent builder containing function schema
     */
    public static function create(): FunctionDescriptor;
}
