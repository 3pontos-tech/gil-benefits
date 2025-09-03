<?php

namespace App\Clients\Requests;

use JsonSerializable;

class UpsertOpportunityDTO implements JsonSerializable
{
    public function __construct(
        public string  $pipelineId,
        public string  $locationId,
        public string  $contactId,
        public ?string $name,
        public ?string $status,
        public ?string $pipelineStageId,
        public ?int    $monetaryValue,
        public ?string $assignedTo
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'pipelineId' => $this->pipelineId,
            'locationId' => $this->locationId,
            'contactId' => $this->contactId,
            'name' => $this->name,
            'status' => $this->status,
            'pipelineStageId' => $this->pipelineStageId,
            'monetaryValue' => $this->monetaryValue,
            'assignedTo' => $this->assignedTo,
        ];
    }
}