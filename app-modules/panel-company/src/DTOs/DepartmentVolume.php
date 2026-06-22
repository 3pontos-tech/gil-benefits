<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class DepartmentVolume
{
    /**
     * @param  array<int, DepartmentVolumeRow>  $rows
     */
    public function __construct(public array $rows) {}
}
