<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Exceptions;

use Cline\Forrst\Enums\ErrorCode;

/**
 * Exception thrown when a cancellation token is unknown or has expired.
 *
 * Part of the Forrst cancellation extension exceptions. Thrown when attempting to
 * cancel a request using a token that does not exist in the server's active token
 * registry. This may occur if the token was never issued, has already been consumed
 * by a previous cancellation, or has expired due to the associated request completing.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/cancellation
 */
final class CancellationTokenNotFoundException extends NotFoundException
{
    /**
     * Create an exception for an unknown cancellation token.
     *
     * Factory method that constructs an exception for a token that was not found
     * in the server's active token registry. Includes the invalid token value in
     * the error details for debugging purposes.
     *
     * @param  string $token The cancellation token that was not found or has expired.
     *                       Included in error details to help identify which token
     *                       the client attempted to use.
     * @return self   The constructed exception instance
     */
    public static function forToken(string $token): self
    {
        return self::new(
            code: ErrorCode::CancellationTokenUnknown,
            message: 'Unknown cancellation token',
            details: ['token' => $token],
        );
    }
}
