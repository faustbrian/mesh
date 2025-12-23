<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Functions;

use Cline\Forrst\Contracts\ResourceInterface;
use Cline\Forrst\Data\DocumentData;
use Cline\Forrst\Discovery\ArgumentData;
use Override;

/**
 * Base class for Forrst list functions with standardized cursor pagination.
 *
 * Extends AbstractFunction to provide complete list endpoint functionality with cursor
 * pagination, sparse fieldsets, filtering, relationship inclusion, and sorting. Designed
 * for resource collection endpoints that need efficient pagination without offset limitations.
 *
 * The handle() method is pre-implemented to query the resource class, apply request
 * parameters, and return cursor-paginated results. Subclasses only need to specify
 * the resource class via getResourceClass().
 *
 * Automatically generates Forrst Discovery argument descriptors for standard list endpoint
 * parameters including cursor, limit, fields, filter, include, and sort. These descriptors
 * enable automatic API documentation and client code generation.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/system-functions
 * @see https://docs.cline.sh/forrst/extensions/query
 */
abstract class AbstractListFunction extends AbstractFunction
{
    /**
     * Handle the list request and return cursor-paginated results.
     *
     * Builds a query using the resource class returned by getResourceClass(), applies
     * request filters and parameters through the query() helper, and returns cursor-paginated
     * results wrapped in a DocumentData structure with pagination metadata.
     *
     * @return DocumentData The paginated resource collection with cursor metadata and links
     */
    public function handle(): DocumentData
    {
        return $this->cursorPaginate(
            $this->query(
                $this->getResourceClass(),
            ),
        );
    }

    /**
     * Get Forrst Discovery argument descriptors for the list function.
     *
     * Generates standard list endpoint argument definitions including cursor pagination
     * (cursor, limit), sparse fieldsets (fields), filtering (filter), relationship
     * inclusion (include), and sorting (sort). These descriptors enable automatic
     * API documentation and client code generation.
     *
     * @return array<int, ArgumentData|array<string, mixed>> Standard list endpoint argument descriptors
     */
    #[Override()]
    public function getArguments(): array
    {
        $this->getResourceClass();

        return [
            // Cursor pagination arguments
            ArgumentData::from([
                'name' => 'cursor',
                'schema' => ['type' => 'string'],
                'required' => false,
                'description' => 'Pagination cursor for the next page',
            ]),
            ArgumentData::from([
                'name' => 'limit',
                'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                'required' => false,
                'default' => 25,
                'description' => 'Number of items per page',
            ]),
            // Sparse fieldsets
            ArgumentData::from([
                'name' => 'fields',
                'schema' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ],
                'required' => false,
                'description' => 'Sparse fieldset selection by resource type',
                'examples' => [['self' => ['id', 'name', 'created_at']]],
            ]),
            // Filters
            ArgumentData::from([
                'name' => 'filter',
                'schema' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                ],
                'required' => false,
                'description' => 'Filter criteria',
            ]),
            // Relationships to include
            ArgumentData::from([
                'name' => 'include',
                'schema' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'required' => false,
                'description' => 'Relationships to include',
                'examples' => [['customer', 'items']],
            ]),
            // Sorting
            ArgumentData::from([
                'name' => 'sort',
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'attribute' => ['type' => 'string'],
                            'direction' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                        ],
                        'required' => ['attribute'],
                    ],
                ],
                'required' => false,
                'description' => 'Sort order specification',
                'examples' => [[['attribute' => 'created_at', 'direction' => 'desc']]],
            ]),
        ];
    }

    /**
     * Get the resource class defining fields, filters, and relationships.
     *
     * Implement this method to specify the ResourceInterface implementation that
     * defines available fields, filterable attributes, loadable relationships, and
     * transformation logic for this list endpoint.
     *
     * @return class-string<ResourceInterface> The fully-qualified resource class name
     */
    abstract protected function getResourceClass(): string;
}
