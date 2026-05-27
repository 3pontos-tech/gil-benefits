<?php

namespace App\Support;

use App\Models\Users\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

final class UserAvatarPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        /** @var User $user */
        $user = $media->model;

        return sprintf('usuarios/%s/avatar/', $user->id);
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
