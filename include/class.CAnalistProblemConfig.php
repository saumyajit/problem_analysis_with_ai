<?php declare(strict_types = 1);

namespace Modules\AnalistProblem\Include;

class CAnalistProblemConfig {
    private static $config = null;
    
    public static function getConfig(): array {
        if (self::$config === null) {
            // Load from Zabbix DB or config file
            self::$config = self::loadConfig();
        }
        
        return self::$config;
    }
    
    private static function loadConfig(): array {
        // Try to load from Zabbix module config table
        $config = [];
        
        // Default configuration
        $defaults = [
            'ai_enabled' => false,
            'provider_preference' => ['ollama', 'openai', 'claude'],
            'openai_api_key' => '',
            'openai_model' => 'gpt-3.5-turbo',
            'ollama_enabled' => true,
            'ollama_base_url' => 'http://localhost:11434',
            'ollama_model' => 'llama2',
            'claude_api_key' => '',
            'claude_model' => 'claude-3-haiku-20240307',
            'cache_duration' => 3600, // Cache AI analysis for 1 hour
            'max_tokens' => 2000,
            'temperature' => 0.3
        ];
        
        // Load from DB (example)
        try {
            $dbConfig = DB::select('module_config', [
                'output' => ['config_key', 'config_value'],
                'filter' => ['module_name' => 'analistproblem']
            ]);
            
            foreach ($dbConfig as $item) {
                $config[$item['config_key']] = json_decode($item['config_value'], true) ?? $item['config_value'];
            }
        } catch (\Exception $e) {
            // Use defaults if DB not available
        }
        
        return array_merge($defaults, $config);
    }
    
    public static function saveConfig(array $config): bool {
        // Save to DB
        try {
            foreach ($config as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                
                // Upsert logic
                DB::insert('module_config', [
                    'module_name' => 'analistproblem',
                    'config_key' => $key,
                    'config_value' => $value,
                    'updated_at' => time()
                ], true); // true = update on duplicate key
            }
            
            self::$config = null; // Reset cache
            return true;
        } catch (\Exception $e) {
            error_log('Failed to save config: ' . $e->getMessage());
            return false;
        }
    }
}
