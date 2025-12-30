<?php
/**
 * Adapter Client
 * HTTP client for invoking adapters with standardized protocol
 * Phase 4: Unified adapter protocol
 */

namespace Platform\Adapter;

class AdapterClient {
    /**
     * Invoke adapter with standardized protocol
     * 
     * @param array $adapter Adapter configuration
     * @param string $capability Capability to invoke
     * @param array $payload Request payload
     * @param string $traceId Trace ID for request tracking
     * @param array $actor Actor information (user_id, role, ui)
     * @param int $timeout Request timeout in seconds
     * @return array Response with {ok: bool, data/error}
     */
    public function invoke(
        array $adapter,
        string $capability,
        array $payload,
        string $traceId,
        array $actor,
        int $timeout = 30
    ): array {
        $url = $adapter['url'] . '/invoke';
        
        // Standardized adapter protocol (Phase 4)
        $requestData = [
            'capability' => $capability,
            'payload' => $payload,
            'trace_id' => $traceId,
            'actor' => $actor,
            'timestamp' => time()
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Capability: ' . $capability,
            'X-Trace-Id: ' . $traceId
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            // Return standardized error response
            return [
                'ok' => false,
                'error' => [
                    'code' => 'ADAPTER_CONNECTION_ERROR',
                    'message' => "Adapter request failed: $error"
                ]
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'ok' => false,
                'error' => [
                    'code' => 'ADAPTER_HTTP_ERROR',
                    'message' => "Adapter returned error: HTTP $httpCode"
                ]
            ];
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'ok' => false,
                'error' => [
                    'code' => 'ADAPTER_INVALID_RESPONSE',
                    'message' => 'Invalid JSON response from adapter'
                ]
            ];
        }
        
        // Check if adapter returned standardized format
        // If it has 'ok' field, it's using new protocol
        if (isset($result['ok'])) {
            return $result;
        }
        
        // Legacy adapter response - wrap it in new format
        return [
            'ok' => true,
            'data' => $result
        ];
    }
    
    /**
     * Check adapter health
     */
    public function checkHealth(array $adapter, int $timeout = 5): bool {
        $url = $adapter['url'] . ($adapter['health_check'] ?? '/health');
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
}
