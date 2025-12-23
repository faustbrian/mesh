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
 * Contact information for the API service or team.
 *
 * Provides contact details for the individuals or teams responsible for the API,
 * enabling API consumers to reach out for support, questions, or collaboration.
 * Typically included in discovery documents to facilitate communication.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://docs.cline.sh/forrst/
 */
final class ContactData extends Data
{
    /**
     * Create a new contact information instance.
     *
     * @param null|string $name  The name of the contact person, team, or organization responsible
     *                           for the API. Used for identification in documentation and support
     *                           communications (e.g., "API Team", "John Smith").
     * @param null|string $url   The URL to a web page, documentation site, or contact form where
     *                           additional information can be found or support requests can be submitted.
     *                           Must be a valid HTTP/HTTPS URL.
     * @param null|string $email The email address for contacting the API team or responsible individual.
     *                           Used for support inquiries, bug reports, and general communication.
     *                           Should be a monitored email address.
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $url = null,
        public readonly ?string $email = null,
    ) {}
}
