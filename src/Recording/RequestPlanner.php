<?php
declare(strict_types=1);

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\Client\FmpApiClient;

final class RequestPlanner extends FmpApiClient
{
    use PlanningTransport;

    public function plan(string $id, array $case): string
    {
        $parameters = $case['capture'] ?? $case['parameters'];
        foreach ($case['types'] ?? [] as $name => $type) {
            if (str_contains($type, 'DateTimeInterface') && isset($parameters[$name])) {
                $parameters[$name] = new \DateTimeImmutable($parameters[$name]);
            }
        }
        if (str_starts_with($id, 'legacy:')) {
            $method = substr($id, 7);
            $legacy = new LegacyRequestPlanner();
            $legacy->$method(...$parameters);
            $this->endpoint = $legacy->endpoint;
            $this->parameters = $legacy->parameters;
            return $this->buildEndpointURL($this->endpoint, $this->parameters);
        } else {
            $this->fetch($id, ...$parameters);
        }
        return $this->getLastEndpointURL();
    }
}
