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
 * License information for the service.
 *
 * Specifies the legal terms under which the API can be used and integrated.
 * Displayed in documentation and API explorers to inform developers about
 * usage rights, restrictions, and attribution requirements.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/system-functions
 * @see https://docs.cline.sh/specs/forrst/discovery#license-object
 */
final class LicenseData extends Data
{
    /**
     * Create a new license information object.
     *
     * @param string      $name License name or identifier (e.g., "MIT", "Apache 2.0", "Proprietary").
     *                          Can be an SPDX license identifier for standard open-source licenses
     *                          or a custom name for proprietary licensing terms. Displayed in API
     *                          documentation to quickly convey licensing model.
     * @param null|string $url  Optional URL to the full license text or licensing agreement.
     *                          Should point to a stable, versioned document containing complete
     *                          legal terms and conditions. Enables developers to review detailed
     *                          licensing requirements before integration.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $url = null,
    ) {}
}
