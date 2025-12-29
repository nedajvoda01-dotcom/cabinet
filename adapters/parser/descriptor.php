<?php

/**
 * Parser Adapter Descriptor
 * 
 * Returns metadata about the parser adapter
 */

header('Content-Type: application/json');

echo json_encode([
    'adapter' => 'parser',
    'version' => '1.0.0',
    'description' => 'Content parsing and extraction adapter',
    'endpoints' => [
        '/invoke' => 'Execute parsing operation',
        '/descriptor' => 'Get adapter metadata',
        '/health' => 'Health check'
    ],
    'capabilities' => [
        'html_parsing',
        'data_extraction',
        'schema_validation'
    ],
    'schema' => [
        'input' => [
            'url' => 'string',
            'content' => 'string',
            'options' => 'object'
        ],
        'output' => [
            'parsed_data' => 'object',
            'metadata' => 'object'
        ]
    ],
    'mode' => 'fallback'
], JSON_PRETTY_PRINT);
