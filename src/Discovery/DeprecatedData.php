<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Discovery;

use Spatie\LaravelData\Data;

/**
 * Deprecation metadata for API elements being phased out.
 *
 * Indicates that a function, parameter, or other API element is deprecated and
 * should not be used in new code. Provides context about why the element is
 * deprecated and when it will be removed. The presence of this object alone
 * signifies deprecation status.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://docs.cline.sh/forrst/
 * @see https://docs.cline.sh/forrst/extensions/deprecation
 */
final class DeprecatedData extends Data
{
    /**
     * Create a new deprecation information instance.
     *
     * @param null|string $reason A human-readable explanation of why this element is deprecated.
     *                            Typically includes guidance on what to use instead, such as
     *                            "Use createUser() instead" or "Replaced by v2 authentication".
     *                            Helps developers migrate to supported alternatives.
     * @param null|string $sunset The date when this deprecated element will be removed from the API,
     *                            in ISO 8601 format (e.g., "2025-12-31"). Provides a timeline for
     *                            migration planning. Null indicates no specific removal date has been
     *                            set, though the element should still not be used in new code.
     */
    public function __construct(
        public readonly ?string $reason = null,
        public readonly ?string $sunset = null,
    ) {}
}
