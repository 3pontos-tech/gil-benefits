<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;

class AssetOptimizationService
{
    private const CACHE_TTL = 86400; // 24 hours
    private const CACHE_PREFIX = 'asset_optimization:';

    /**
     * Build and optimize assets for production.
     */
    public function buildAssets(): array
    {
        $results = [];
        
        Log::info('Starting asset build process');
        
        try {
            // Install dependencies if needed
            if (!File::exists(base_path('node_modules'))) {
                Log::info('Installing npm dependencies');
                $installResult = Process::run('npm install', base_path());
                
                if (!$installResult->successful()) {
                    throw new \Exception('npm install failed: ' . $installResult->errorOutput());
                }
                
                $results['npm_install'] = true;
            } else {
                $results['npm_install'] = 'skipped';
            }
            
            // Build assets
            Log::info('Building production assets');
            $buildResult = Process::run('npm run build', base_path());
            
            if (!$buildResult->successful()) {
                throw new \Exception('npm run build failed: ' . $buildResult->errorOutput());
            }
            
            $results['build'] = true;
            $results['build_output'] = $buildResult->output();
            
            // Get build statistics
            $results['stats'] = $this->getBuildStats();
            
            Log::info('Asset build completed successfully');
            
        } catch (\Exception $e) {
            Log::error('Asset build failed: ' . $e->getMessage());
            $results['build'] = false;
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Optimize images in the public directory.
     */
    public function optimizeImages(): array
    {
        $results = [];
        
        Log::info('Starting image optimization');
        
        try {
            $imageDirectories = [
                public_path('images'),
                public_path('img'),
                resource_path('images'),
            ];
            
            foreach ($imageDirectories as $directory) {
                if (File::isDirectory($directory)) {
                    $results[$directory] = $this->optimizeImagesInDirectory($directory);
                }
            }
            
            Log::info('Image optimization completed');
            
        } catch (\Exception $e) {
            Log::error('Image optimization failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Optimize images in a specific directory.
     */
    private function optimizeImagesInDirectory(string $directory): array
    {
        $results = [
            'processed' => 0,
            'saved_bytes' => 0,
            'errors' => [],
        ];
        
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        foreach ($imageExtensions as $extension) {
            $files = File::glob($directory . '/*.' . $extension);
            
            foreach ($files as $file) {
                try {
                    $originalSize = File::size($file);
                    
                    // Create optimized version
                    $optimizedFile = $this->optimizeImage($file);
                    
                    if ($optimizedFile && File::exists($optimizedFile)) {
                        $newSize = File::size($optimizedFile);
                        $savedBytes = $originalSize - $newSize;
                        
                        if ($savedBytes > 0) {
                            // Replace original with optimized version
                            File::move($optimizedFile, $file);
                            $results['saved_bytes'] += $savedBytes;
                        } else {
                            // Remove optimized version if no improvement
                            File::delete($optimizedFile);
                        }
                        
                        $results['processed']++;
                    }
                    
                } catch (\Exception $e) {
                    $results['errors'][] = "Failed to optimize {$file}: " . $e->getMessage();
                }
            }
        }
        
        return $results;
    }

    /**
     * Optimize a single image file.
     */
    private function optimizeImage(string $filePath): ?string
    {
        $info = pathinfo($filePath);
        $optimizedPath = $info['dirname'] . '/' . $info['filename'] . '_optimized.' . $info['extension'];
        
        // Use imagemagick or similar tool for optimization
        // This is a simplified example - you might want to use a more sophisticated approach
        
        try {
            $extension = strtolower($info['extension']);
            
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    // Optimize JPEG with quality 85
                    $result = Process::run("convert \"{$filePath}\" -quality 85 -strip \"{$optimizedPath}\"");
                    break;
                    
                case 'png':
                    // Optimize PNG
                    $result = Process::run("convert \"{$filePath}\" -strip \"{$optimizedPath}\"");
                    break;
                    
                case 'gif':
                    // Optimize GIF
                    $result = Process::run("convert \"{$filePath}\" -coalesce -strip \"{$optimizedPath}\"");
                    break;
                    
                default:
                    return null;
            }
            
            if ($result->successful() && File::exists($optimizedPath)) {
                return $optimizedPath;
            }
            
        } catch (\Exception $e) {
            Log::warning("Image optimization failed for {$filePath}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Generate WebP versions of images.
     */
    public function generateWebPImages(): array
    {
        $results = [];
        
        Log::info('Generating WebP images');
        
        try {
            $imageDirectories = [
                public_path('images'),
                public_path('img'),
            ];
            
            foreach ($imageDirectories as $directory) {
                if (File::isDirectory($directory)) {
                    $results[$directory] = $this->generateWebPInDirectory($directory);
                }
            }
            
            Log::info('WebP generation completed');
            
        } catch (\Exception $e) {
            Log::error('WebP generation failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Generate WebP images in a directory.
     */
    private function generateWebPInDirectory(string $directory): array
    {
        $results = [
            'generated' => 0,
            'errors' => [],
        ];
        
        $sourceExtensions = ['jpg', 'jpeg', 'png'];
        
        foreach ($sourceExtensions as $extension) {
            $files = File::glob($directory . '/*.' . $extension);
            
            foreach ($files as $file) {
                try {
                    $info = pathinfo($file);
                    $webpPath = $info['dirname'] . '/' . $info['filename'] . '.webp';
                    
                    // Skip if WebP version already exists
                    if (File::exists($webpPath)) {
                        continue;
                    }
                    
                    // Generate WebP version
                    $result = Process::run("cwebp -q 85 \"{$file}\" -o \"{$webpPath}\"");
                    
                    if ($result->successful() && File::exists($webpPath)) {
                        $results['generated']++;
                    } else {
                        $results['errors'][] = "Failed to generate WebP for {$file}";
                    }
                    
                } catch (\Exception $e) {
                    $results['errors'][] = "Error processing {$file}: " . $e->getMessage();
                }
            }
        }
        
        return $results;
    }

    /**
     * Compress CSS and JS files.
     */
    public function compressAssets(): array
    {
        $results = [];
        
        Log::info('Compressing CSS and JS assets');
        
        try {
            // Compress CSS files
            $cssFiles = File::glob(public_path('build/assets/*.css'));
            $results['css'] = $this->compressFiles($cssFiles, 'css');
            
            // Compress JS files
            $jsFiles = File::glob(public_path('build/assets/*.js'));
            $results['js'] = $this->compressFiles($jsFiles, 'js');
            
            Log::info('Asset compression completed');
            
        } catch (\Exception $e) {
            Log::error('Asset compression failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Compress files using gzip.
     */
    private function compressFiles(array $files, string $type): array
    {
        $results = [
            'processed' => 0,
            'total_saved' => 0,
            'errors' => [],
        ];
        
        foreach ($files as $file) {
            try {
                $originalSize = File::size($file);
                $gzipFile = $file . '.gz';
                
                // Create gzip version
                $content = File::get($file);
                $compressed = gzencode($content, 9);
                
                File::put($gzipFile, $compressed);
                
                $compressedSize = File::size($gzipFile);
                $saved = $originalSize - $compressedSize;
                
                $results['processed']++;
                $results['total_saved'] += $saved;
                
            } catch (\Exception $e) {
                $results['errors'][] = "Failed to compress {$file}: " . $e->getMessage();
            }
        }
        
        return $results;
    }

    /**
     * Get build statistics.
     */
    public function getBuildStats(): array
    {
        $buildDir = public_path('build');
        
        if (!File::isDirectory($buildDir)) {
            return ['error' => 'Build directory not found'];
        }
        
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'css_files' => 0,
            'css_size' => 0,
            'js_files' => 0,
            'js_size' => 0,
            'asset_files' => 0,
            'asset_size' => 0,
        ];
        
        $files = File::allFiles($buildDir);
        
        foreach ($files as $file) {
            $size = $file->getSize();
            $extension = $file->getExtension();
            
            $stats['total_files']++;
            $stats['total_size'] += $size;
            
            switch ($extension) {
                case 'css':
                    $stats['css_files']++;
                    $stats['css_size'] += $size;
                    break;
                    
                case 'js':
                    $stats['js_files']++;
                    $stats['js_size'] += $size;
                    break;
                    
                default:
                    $stats['asset_files']++;
                    $stats['asset_size'] += $size;
                    break;
            }
        }
        
        return $stats;
    }

    /**
     * Clean old build files.
     */
    public function cleanOldBuilds(): array
    {
        $results = [
            'deleted_files' => 0,
            'freed_space' => 0,
        ];
        
        Log::info('Cleaning old build files');
        
        try {
            $buildDir = public_path('build');
            
            if (File::isDirectory($buildDir)) {
                // Get manifest to identify current files
                $manifestPath = $buildDir . '/manifest.json';
                $currentFiles = [];
                
                if (File::exists($manifestPath)) {
                    $manifest = json_decode(File::get($manifestPath), true);
                    $currentFiles = array_values($manifest);
                }
                
                // Find and delete old files
                $allFiles = File::allFiles($buildDir);
                
                foreach ($allFiles as $file) {
                    $relativePath = str_replace($buildDir . '/', '', $file->getPathname());
                    
                    // Skip current files and manifest
                    if (in_array($relativePath, $currentFiles) || $relativePath === 'manifest.json') {
                        continue;
                    }
                    
                    // Delete old file
                    $size = $file->getSize();
                    File::delete($file->getPathname());
                    
                    $results['deleted_files']++;
                    $results['freed_space'] += $size;
                }
            }
            
            Log::info("Cleaned {$results['deleted_files']} old build files");
            
        } catch (\Exception $e) {
            Log::error('Build cleanup failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Cache asset optimization results.
     */
    public function cacheOptimizationResults(array $results): void
    {
        Cache::put(self::CACHE_PREFIX . 'last_optimization', [
            'timestamp' => now(),
            'results' => $results,
        ], self::CACHE_TTL);
    }

    /**
     * Get cached optimization results.
     */
    public function getCachedOptimizationResults(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . 'last_optimization');
    }

    /**
     * Check if assets need optimization.
     */
    public function needsOptimization(): bool
    {
        $lastOptimization = $this->getCachedOptimizationResults();
        
        if (!$lastOptimization) {
            return true;
        }
        
        // Check if more than 24 hours since last optimization
        $lastTime = $lastOptimization['timestamp'];
        return now()->diffInHours($lastTime) > 24;
    }

    /**
     * Get asset optimization status.
     */
    public function getOptimizationStatus(): array
    {
        return [
            'build_exists' => File::isDirectory(public_path('build')),
            'manifest_exists' => File::exists(public_path('build/manifest.json')),
            'last_optimization' => $this->getCachedOptimizationResults(),
            'needs_optimization' => $this->needsOptimization(),
            'build_stats' => $this->getBuildStats(),
        ];
    }
}