<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;

class HateoasService
{
    public function generateLinks(string $resourceType, int $id, array $relationships = []): array
    {
        $links = [
            'self' => [
                'href' => url("/api/{$resourceType}/{$id}"),
                'method' => 'GET'
            ],
            'update' => [
                'href' => url("/api/{$resourceType}/{$id}"),
                'method' => 'PUT'
            ],
            'delete' => [
                'href' => url("/api/{$resourceType}/{$id}"),
                'method' => 'DELETE'
            ]
        ];

        // Add collection link
        $links['collection'] = [
            'href' => url("/api/{$resourceType}"),
            'method' => 'GET'
        ];

        foreach ($relationships as $relation => $ids) {
            if (is_array($ids)) {
                $links[$relation] = array_map(function($relatedId) use ($relation) {
                    return [
                        'href' => url("/api/{$relation}/{$relatedId}"),
                        'method' => 'GET'
                    ];
                }, $ids);
            } else {
                $links[$relation] = [
                    'href' => url("/api/{$relation}/{$ids}"),
                    'method' => 'GET'
                ];
            }
        }

        return $links;
    }

    public function generateCollectionLinks(string $resourceType, ?LengthAwarePaginator $paginator = null): array
    {
        $links = [
            'self' => [
                'href' => url("/api/{$resourceType}"),
                'method' => 'GET'
            ],
            'create' => [
                'href' => url("/api/{$resourceType}"),
                'method' => 'POST'
            ]
        ];

        if ($paginator) {
            if ($paginator->hasMorePages()) {
                $links['next'] = [
                    'href' => $paginator->nextPageUrl(),
                    'method' => 'GET'
                ];
            }
            
            if ($paginator->currentPage() > 1) {
                $links['prev'] = [
                    'href' => $paginator->previousPageUrl(),
                    'method' => 'GET'
                ];
            }
            
            $links['first'] = [
                'href' => $paginator->url(1),
                'method' => 'GET'
            ];
            
            $links['last'] = [
                'href' => $paginator->url($paginator->lastPage()),
                'method' => 'GET'
            ];
        }

        return $links;
    }
}