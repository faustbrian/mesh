<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Validation;

use Cline\Forrst\Exceptions\EmptyFieldException;
use Cline\Forrst\Exceptions\FieldExceedsMaxLengthException;
use Cline\Forrst\Exceptions\InvalidFieldValueException;
use JsonException;

use const JSON_THROW_ON_ERROR;

use function is_array;
use function json_encode;
use function mb_strlen;
use function preg_match;

/**
 * Validates URN formats and array structures for Forrst extensions.
 * @author Brian Faust <brian@cline.sh>
 */
final class UrnValidator
{
    /**
     * Validate Forrst extension URN format.
     *
     * @param string $urn       URN to validate
     * @param string $fieldName Field name for error messages
     *
     * @throws EmptyFieldException            If URN is empty
     * @throws FieldExceedsMaxLengthException If URN exceeds max length
     * @throws InvalidFieldValueException     If URN format is invalid
     */
    public static function validateExtensionUrn(string $urn, string $fieldName = 'urn'): void
    {
        if ($urn === '') {
            throw EmptyFieldException::forField('Extension '.$fieldName);
        }

        // Forrst extension URNs must follow: urn:vendor:forrst:ext:name
        if (!preg_match('/^urn:[a-z0-9][a-z0-9_-]*:forrst:ext:[a-z0-9][a-z0-9_-]*$/i', $urn)) {
            throw InvalidFieldValueException::forField(
                'Extension '.$fieldName,
                'must follow format \'urn:vendor:forrst:ext:name\', got: '.$urn,
            );
        }

        // Validate URN length (reasonable limit)
        if (mb_strlen($urn) > 255) {
            throw FieldExceedsMaxLengthException::forField('Extension '.$fieldName, 255);
        }
    }

    /**
     * Validate array structure, depth, and size.
     *
     * @param null|array<string, mixed> $array     Array to validate
     * @param string                    $fieldName Field name for error messages
     * @param int                       $maxDepth  Maximum nesting depth allowed
     *
     * @throws FieldExceedsMaxLengthException If array exceeds size limit
     * @throws InvalidFieldValueException     If array is invalid
     */
    public static function validateArray(?array $array, string $fieldName, int $maxDepth = 5): void
    {
        if ($array === null) {
            return;
        }

        // Validate array is not empty when provided
        if ($array === []) {
            throw InvalidFieldValueException::forField(
                'Extension '.$fieldName,
                'cannot be an empty array. Use null instead.',
            );
        }

        // Validate depth to prevent DoS
        $checkDepth = function (array $arr, int $currentDepth) use (&$checkDepth, $maxDepth, $fieldName): void {
            if ($currentDepth > $maxDepth) {
                throw InvalidFieldValueException::forField(
                    'Extension '.$fieldName,
                    'exceeds maximum nesting depth of '.$maxDepth,
                );
            }

            foreach ($arr as $value) {
                if (!is_array($value)) {
                    continue;
                }

                $checkDepth($value, $currentDepth + 1);
            }
        };

        $checkDepth($array, 1);

        // Validate total size
        try {
            $serialized = json_encode($array, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw InvalidFieldValueException::forField(
                'Extension '.$fieldName,
                'contains invalid data that cannot be JSON serialized: '.$jsonException->getMessage(),
            );
        }

        if (mb_strlen($serialized) > 65_536) { // 64KB limit
            throw FieldExceedsMaxLengthException::forField('Extension '.$fieldName, 65_536);
        }
    }
}
