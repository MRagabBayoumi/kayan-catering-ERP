<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class BladeArchitectureSeparationTest extends TestCase
{
    /**
     * Test that all Blade views strictly decouple HTML from JavaScript and CSS.
     * Rules: Zero <script>, Zero <style>, Zero inline style="...", Zero inline on* handlers.
     */
    public function test_all_blade_views_adhere_to_zero_inline_code(): void
    {
        $viewsPath = resource_path('views');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));

        $violations = [];

        foreach ($iterator as $file) {
            if ($file->isDir() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            $content = file_get_contents($file->getPathname());

            // 1. Assert No <script> tags
            if (preg_match('/<script\b[^>]*>/i', $content)) {
                $violations[] = "{$relativePath}: contains inline <script> tag.";
            }

            // 2. Assert No <style> tags
            if (preg_match('/<style\b[^>]*>/i', $content)) {
                $violations[] = "{$relativePath}: contains inline <style> tag.";
            }

            // 3. Assert No inline style="..." attributes
            if (preg_match('/\bstyle\s*=\s*["\'][^"\']*["\']/i', $content)) {
                $violations[] = "{$relativePath}: contains inline style=\"...\" attribute.";
            }

            // 4. Assert No inline event handlers (onclick, onchange, onsubmit, etc.)
            if (preg_match('/\bon(click|change|submit|load|keydown|keyup|mouseover|mouseout)\s*=\s*["\'][^"\']*["\']/i', $content)) {
                $violations[] = "{$relativePath}: contains inline event handler (on*=\"...\").";
            }
        }

        $this->assertEmpty(
            $violations,
            "Blade Architecture Separation Violations Found:\n".implode("\n", $violations)
        );
    }
}
