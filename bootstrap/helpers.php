<?php

if (! function_exists('modules_path')) {
    function modules_path(string $module, string $path = ''): string
    {
        return base_path(sprintf('app-modules/%s/%s', str($module)->lower()->kebab(), $path));
    }
}
