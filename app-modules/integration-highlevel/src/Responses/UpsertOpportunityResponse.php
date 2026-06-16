<?php

namespace TresPontosTech\IntegrationHighlevel\Responses;

class UpsertOpportunityResponse
{
    public function __construct(
        public bool $new,
        public OpportunityResponse $opportunity,
    ) {}

    /**
     * @param  array{new: bool, opportunity: array<string, mixed>}  $payload
     */
    public static function make(array $payload): self
    {
        return new self(
            $payload['new'],
            OpportunityResponse::make($payload['opportunity']),
        );
    }
}
