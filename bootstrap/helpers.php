<?php

if (!function_exists('module_path')) {
    function module_path(string $module, string $path = ''): string
    {
        return base_path(sprintf("app-modules/%s/%s", str($module)->lower()->kebab(), $path));
    }
}
