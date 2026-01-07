<?php declare(strict_types = 1);

namespace Modules\AnalistProblem\Lib;

class OllamaProvider implements AIProviderInterface {
    private $baseUrl;
    private $model;
    
    public function __construct(array $config = []) {
        $this->baseUrl = $config['base_url'] ?? 'http://localhost:11434';
        $this->model = $config['model'] ?? 'llama2';
    }
    
    public function analyzeProblem(array $problemContext): array {
        $prompt = $this->buildAnalysisPrompt($problemContext);
        
        $response = $this->callOllamaAPI($prompt);
        
        return $this->parseAIResponse($response);
    }
    
    private function callOllamaAPI(string $prompt): array {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/api/generate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
                'options' => [
                    'temperature' => 0.3,
                    'num_predict' => 2000
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true) ?? [];
    }
    
    // ... similar parsing methods as OpenAIProvider
    
    public function getName(): string {
        return 'Ollama (' . $this->model . ')';
    }
    
    public function isAvailable(): bool {
        // Test connection to Ollama
        $ch = curl_init($this->baseUrl . '/api/tags');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return !empty($response);
    }
    
    public function getCostEstimate(): float {
        return 0.0; // Free for local AI
    }
}
