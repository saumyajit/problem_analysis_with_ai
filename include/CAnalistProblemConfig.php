<?php declare(strict_types = 1);

namespace Modules\AnalistProblem\Include;

class CAnalistProblemConfig {
    private static $config = null;
    
    public static function getConfig(): array {
        if (self::$config === null) {
            self::$config = self::loadConfig();
        }
        
        return self::$config;
    }
    
    private static function loadConfig(): array {
        // Default configuration - AI disabled by default
        return [
            'ai_enabled' => false,
            'cache_duration' => 3600
        ];
    }
}
