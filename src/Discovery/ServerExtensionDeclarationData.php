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
 * Extension declaration for server endpoints in discovery documents.
 *
 * Declares which extensions a server supports and their versions. Used in the
 * servers[].extensions array to indicate available optional protocol features.
 * Clients use this information to discover which extensions can be used when
 * communicating with a particular server endpoint.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/discovery#extension-declarations
 */
final class ServerExtensionDeclarationData extends Data
{
    /**
     * Create a new server extension declaration.
     *
     * @param string $urn     Extension URN identifier using the format "urn:forrst:ext:name"
     *                        (e.g., "urn:forrst:ext:async", "urn:forrst:ext:caching"). The URN
     *                        uniquely identifies the extension and follows URN naming conventions.
     * @param string $version Semantic version of the extension supported by this server
     *                        (e.g., "1.0.0", "2.1.0"). Clients can use this to determine
     *                        compatibility and feature availability for specific extension versions.
     */
    public function __construct(
        public readonly string $urn,
        public readonly string $version,
    ) {}
}
