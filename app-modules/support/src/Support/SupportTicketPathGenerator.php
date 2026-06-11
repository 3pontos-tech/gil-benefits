<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use TresPontosTech\Support\Models\SupportTicket;

final class SupportTicketPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        /** @var SupportTicket $ticket */
        $ticket = $media->model;

        return sprintf('tickets/%s/%s/', $ticket->protocol, $media->id);
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive/';
    }
}
