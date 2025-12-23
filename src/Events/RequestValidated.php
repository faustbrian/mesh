<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Events;

use Cline\Forrst\Data\RequestObjectData;

/**
 * Event dispatched after request parsing and protocol validation.
 *
 * Fired immediately after the raw request body has been parsed and validated
 * against the Forrst protocol schema, but before function resolution or routing.
 * This represents the earliest point in the request lifecycle where extensions
 * can inspect structured request data and make validation or routing decisions.
 *
 * Extensions can use this event for early-stage validation, request rejection,
 * authorization checks, rate limiting, or request transformation before the
 * expensive operation of function resolution and execution begins. Extensions
 * can call stopPropagation() and setResponse() to reject the request early.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/protocol
 */
final class RequestValidated extends ExtensionEvent
{
    /**
     * Create a new request validated event instance.
     *
     * @param RequestObjectData $request The validated request object that passed protocol
     *                                   schema validation. Contains parsed function name,
     *                                   arguments, protocol version, extension options, and
     *                                   request metadata. Extensions can inspect this data
     *                                   to perform early authorization, rate limiting, or
     *                                   custom validation before function resolution.
     */
    public function __construct(
        RequestObjectData $request,
    ) {
        parent::__construct($request);
    }
}
