<?php

namespace App\Services;

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

    public function generateCollectionLinks(string $resourceType, ?array $pagination = null): array
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

        if ($pagination) {
            if (isset($pagination['next_page_url'])) {
                $links['next'] = [
                    'href' => $pagination['next_page_url'],
                    'method' => 'GET'
                ];
            }
            if (isset($pagination['prev_page_url'])) {
                $links['prev'] = [
                    'href' => $pagination['prev_page_url'],
                    'method' => 'GET'
                ];
            }
        }

        return $links;
    }
}