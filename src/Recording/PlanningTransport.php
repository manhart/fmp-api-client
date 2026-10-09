<?php
declare(strict_types=1);

namespace Manhart\Fmp\Recording;

trait PlanningTransport
{
    public string $endpoint = '';
    public array $parameters = [];

    public function __construct()
    {
        parent::__construct('planner-placeholder');
    }

    public function executeCurl(string $endpoint, array $params = []): string
    {
        $this->endpoint = $endpoint;
        $this->parameters = $params;
        $this->buildEndpointURL($endpoint, $params);
        if (str_ends_with($endpoint, 'xlsx')) {
            return "PK\x03\x04PLAN";
        }
        return str_contains($endpoint, 'historical-price-full') ? '{"symbol":"PLAN","historical":[]}' : '[]';
    }
}
