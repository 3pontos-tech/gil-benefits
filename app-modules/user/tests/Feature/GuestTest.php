<?php

declare(strict_types=1);

it('should render the institutional pages', function (string $route): void {
    $this->get(route($route))->assertOk();
})->with([
    'site.home',
    'site.companies',
    'site.collaborator',
]);
