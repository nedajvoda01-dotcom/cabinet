<?php

declare(strict_types=1);

return [
    'TraceContext' => [
        'kind' => 'object',
        'description' => 'Trace identifiers propagated across calls.',
        'fields' => [
            'requestId' => [
                'type' => 'string',
                'required' => true,
                'constraints' => [
                    'non-empty',
                    'ascii',
                    'max_length:128',
                ],
                'description' => 'Stable request correlation identifier.',
                'example' => 'req-123456',
            ],
            'timestamp' => [
                'type' => 'string',
                'required' => false,
                'constraints' => [
                    'iso8601',
                    'utc',
                ],
                'description' => 'Optional ISO8601 timestamp of when the context was created.',
                'example' => '2024-01-01T00:00:00Z',
            ],
        ],
    ],
    'ActorId' => [
        'kind' => 'string',
        'description' => 'Opaque stable identifier for an actor.',
        'constraints' => [
            'non-empty',
            'ascii',
            'max_length:128',
        ],
        'examples' => ['user-42', 'integration:maps'],
    ],
    'ActorType' => [
        'kind' => 'enum',
        'description' => 'Classification of actor origin.',
        'values' => ['user', 'integration'],
        'examples' => ['user'],
    ],
    'Actor' => [
        'kind' => 'object',
        'description' => 'Represents the caller identity.',
        'fields' => [
            'actorId' => [
                'type' => 'ActorId',
                'required' => true,
                'description' => 'Actor identifier bound to the session or integration.',
            ],
            'actorType' => [
                'type' => 'ActorType',
                'required' => true,
                'description' => 'Origin of the actor.',
            ],
        ],
    ],
    'HierarchyRole' => [
        'kind' => 'enum',
        'description' => 'Role within the Cabinet hierarchy.',
        'values' => ['user', 'admin', 'super_admin'],
        'examples' => ['admin'],
    ],
    'Scope' => [
        'kind' => 'string',
        'description' => 'Opaque authorization scope string.',
        'constraints' => [
            'lowercase',
            'dot-delimited segments',
            'segments use [a-z0-9]',
            'no empty segments',
        ],
        'examples' => ['cabinet.read', 'cabinet.admin.audit'],
    ],
    'ErrorKind' => [
        'kind' => 'enum',
        'description' => 'Classification of API and pipeline errors.',
        'values' => [
            'validation_error',
            'security_denied',
            'not_found',
            'internal_error',
            'integration_unavailable',
            'rate_limited',
        ],
        'examples' => ['validation_error'],
    ],
    'PipelineStage' => [
        'kind' => 'enum',
        'description' => 'Stages of Cabinet pipeline execution.',
        'values' => ['parse', 'photos', 'publish', 'export', 'cleanup'],
    ],
    'JobStatus' => [
        'kind' => 'enum',
        'description' => 'State of a queued job.',
        'values' => ['queued', 'running', 'succeeded', 'failed', 'dead_letter'],
    ],
];
