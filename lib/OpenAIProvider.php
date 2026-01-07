<?php declare(strict_types = 1);

namespace Modules\AnalistProblem\Lib;

class OpenAIProvider implements AIProviderInterface {
    private $apiKey;
    private $model;
    private $baseUrl;
    
    public function __construct(array $config = []) {
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'gpt-3.5-turbo';
        $this->baseUrl = $config['base_url'] ?? 'https://api.openai.com/v1';
    }
    
    public function analyzeProblem(array $problemContext): array {
        if (!$this->isAvailable()) {
            throw new \Exception('OpenAI provider not configured');
        }
        
        // Build prompt from problem context
        $prompt = $this->buildAnalysisPrompt($problemContext);
        
        // Call OpenAI API
        $response = $this->callOpenAIAPI($prompt);
        
        // Parse and structure response
        return $this->parseAIResponse($response);
    }
    
    private function buildAnalysisPrompt(array $context): string {
        return sprintf(
            "You are a Zabbix monitoring expert analyzing a system problem. Analyze this incident and provide structured insights:\n\n" .
            "PROBLEM CONTEXT:\n" .
            "Event Name: %s\n" .
            "Host: %s\n" .
            "Trigger Description: %s\n" .
            "Severity: %s\n" .
            "Time: %s\n" .
            "Duration: %s\n" .
            "Host Groups: %s\n" .
            "Monitoring Type: %s\n" .
            "Historical Recurrence: %s\n" .
            "Related Events Count: %s\n" .
            "\n" .
            "ANALYSIS REQUEST:\n" .
            "1. Identify 3-5 most probable root causes (include likelihood percentages)\n" .
            "2. Provide a step-by-step diagnostic checklist\n" .
            "3. Suggest resolution actions with priority levels\n" .
            "4. Recommend preventive measures\n" .
            "5. Estimate time to resolve\n" .
            "\n" .
            "RESPONSE FORMAT (JSON):\n" .
            "{\n" .
            "  \"confidence_score\": 85,\n" .
            "  \"probable_causes\": [\n" .
            "    {\"cause\": \"...\", \"likelihood\": 85, \"description\": \"...\", \"evidence\": \"...\", \"check_points\": [...]}\n" .
            "  ],\n" .
            "  \"diagnostic_checklist\": [\n" .
            "    {\"step\": 1, \"action\": \"...\", \"description\": \"...\", \"command\": \"...\", \"expected_result\": \"...\"}\n" .
            "  ],\n" .
            "  \"resolution_suggestions\": [\n" .
            "    {\"suggestion\": \"...\", \"priority\": \"high|medium|low\", \"steps\": [...], \"risk\": \"...\", \"estimated_time\": \"...\"}\n" .
            "  ],\n" .
            "  \"preventive_measures\": [\n" .
            "    {\"measure\": \"...\", \"description\": \"...\", \"implementation\": \"...\", \"benefit\": \"...\"}\n" .
            "  ],\n" .
            "  \"similar_patterns\": \"...\",\n" .
            "  \"ai_model_used\": \"...\"\n" .
            "}\n",
            $context['event_name'] ?? 'Unknown',
            $context['host_name'] ?? 'Unknown',
            $context['trigger_description'] ?? 'Unknown',
            $context['severity'] ?? 'Unknown',
            $context['event_time'] ?? 'Unknown',
            $context['duration'] ?? 'Unknown',
            implode(', ', $context['host_groups'] ?? []),
            $context['monitoring_type'] ?? 'Unknown',
            $context['recurrence_rate'] ?? 'Unknown',
            $context['related_events_count'] ?? 0
        );
    }
    
    private function callOpenAIAPI(string $prompt): array {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a Zabbix monitoring expert. Always respond with valid JSON.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.3,
                'max_tokens' => 2000,
                'response_format' => ['type' => 'json_object']
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ]
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('OpenAI API call failed: ' . $error);
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new \Exception('OpenAI API error: ' . $data['error']['message']);
        }
        
        return $data;
    }
    
    private function parseAIResponse(array $apiResponse): array {
        $content = $apiResponse['choices'][0]['message']['content'] ?? '{}';
        $analysis = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback: try to extract JSON from text
            preg_match('/\{.*\}/s', $content, $matches);
            if ($matches) {
                $analysis = json_decode($matches[0], true);
            }
        }
        
        // Ensure proper structure
        $analysis = array_merge([
            'confidence_score' => 0,
            'probable_causes' => [],
            'diagnostic_checklist' => [],
            'resolution_suggestions' => [],
            'preventive_measures' => [],
            'similar_patterns' => '',
            'ai_model_used' => $this->model
        ], $analysis ?? []);
        
        return $analysis;
    }
    
    public function getName(): string {
        return 'OpenAI (' . $this->model . ')';
    }
    
    public function isAvailable(): bool {
        return !empty($this->apiKey);
    }
    
    public function getCostEstimate(): float {
        // Rough estimate: $0.002 per 1K tokens
        return 0.002; // $0.002 per analysis
    }
}
