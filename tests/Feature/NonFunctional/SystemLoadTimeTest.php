<?php

namespace Tests\Feature\NonFunctional;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Validates that critical public pages load within acceptable performance thresholds.
 */
class SystemLoadTimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function system_public_pages_load_within_two_seconds(): void
    {
        // Define public endpoints to test load time
        $endpoints = [
            '/',
            '/login',
            '/register',
            '/mentors',
        ];

        foreach ($endpoints as $endpoint) {
            $startTime = microtime(true);
            
            $response = $this->get($endpoint);
            
            $endTime = microtime(true);
            $loadTimeSeconds = $endTime - $startTime;
            $timeFormatted = number_format($loadTimeSeconds, 4);
            $status = $loadTimeSeconds < 2.0 ? 'PASS' : 'FAIL';

            // Print the result directly to the terminal
            fwrite(STDOUT, "\n[Performance Test] Endpoint '{$endpoint}' loaded in {$timeFormatted} seconds | Result: {$status}");

            // Optional: Ensure the endpoint actually loads properly
            $response->assertStatus(200);

            // Assert that the load time is less than 2.0 seconds
            $this->assertLessThan(
                2.0, 
                $loadTimeSeconds, 
                "Endpoint '{$endpoint}' failed non-functional requirement: took longer than 2 seconds to load (took " . round($loadTimeSeconds, 3) . "s)"
            );
        }
        
        fwrite(STDOUT, "\n");
    }
}
