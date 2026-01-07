<?php declare(strict_types = 1);

namespace Modules\AnalistProblem\Lib;

interface AIProviderInterface {
    /**
     * Analyze problem and provide AI insights
     */
    public function analyzeProblem(array $problemContext): array;
    
    /**
     * Get provider name
     */
    public function getName(): string;
    
    /**
     * Check if provider is available/configured
     */
    public function isAvailable(): bool;
    
    /**
     * Get cost estimate for analysis
     */
    public function getCostEstimate(): float;
}
