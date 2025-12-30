<?php
/**
 * Version Controller
 * Handles version and health check endpoint
 */

namespace Platform\Http\Controllers;

class VersionController {
    /**
     * Get platform version and health status
     * GET /api/version
     */
    public function handle(): array {
        return [
            'version' => '1.0.0',
            'status' => 'healthy',
            'timestamp' => time(),
            'platform' => 'Cabinet',
            'phase' => 'Phase 2 - Control Plane API'
        ];
    }
}
