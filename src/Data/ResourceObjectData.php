<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Data;

/**
 * JSON:API compliant resource object structure.
 *
 * Encapsulates the core components of a JSON:API resource object including type
 * identification, unique identifier, attributes, relationships, and metadata.
 * This structure provides a standardized format for representing domain entities
 * in Forrst protocol responses, ensuring consistency and interoperability.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://docs.cline.sh/forrst/resource-objects
 * @see https://jsonapi.org/format/#document-resource-objects
 */
final class ResourceObjectData extends AbstractData
{
    /**
     * Create a new resource object data instance.
     *
     * @param string                    $type          The resource type identifier, typically a singular, lowercase
     *                                                 snake_case representation of the resource model name (e.g., 'order',
     *                                                 'order_item'). Used for resource type identification and routing
     *                                                 in JSON:API compliant responses.
     * @param string                    $id            The unique identifier for this specific resource instance, typically
     *                                                 the primary key value cast as a string. Used for resource identification,
     *                                                 relationship references, and URL generation in JSON:API responses.
     * @param array<string, mixed>      $attributes    The resource's attribute data as a key-value array, containing
     *                                                 all non-relationship fields that describe the resource. Excludes the
     *                                                 id and type fields, which are represented separately in the JSON:API
     *                                                 specification.
     * @param null|array<string, mixed> $relationships Optional array of relationship data following JSON:API relationship
     *                                                 object structure. Each relationship includes linkage to related resources
     *                                                 via type and id references. Null when no relationships are included or
     *                                                 available for the resource.
     * @param null|array<string, mixed> $meta          Optional non-standard meta-information about the resource. Used for
     *                                                 permission indicators, cache hints, version numbers, or computed values
     *                                                 not represented in attributes.
     */
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly array $attributes,
        public readonly ?array $relationships = null,
        public readonly ?array $meta = null,
    ) {}
}
