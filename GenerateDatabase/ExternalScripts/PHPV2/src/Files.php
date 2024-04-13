<?php

namespace Professionator;

class Files
{

    public static function root(): string
    {
        $path = realpath(__DIR__ . '/../../..');

        if (!$path) {
            throw new \Exception('Could not find root path');
        }

        return $path;
    }

    public static function cache(string $suffix = ''): string
    {
        $root = self::root() . '/Cache';

        if (!file_exists($root)) {
            throw new \Exception('Cache directory does not exist');
        }

        if ($suffix) {
            $root .= $suffix;
        }

        if (!file_exists(dirname($root))) {
            mkdir(dirname($root), 0777, true);
        }

        return $root;
    }

    public static function database(string $dbName): string
    {
        $root = self::root() . '/..';

        if (!file_exists($root)) {
            throw new \Exception('Database directory does not exist');
        }

        return $root . '/' .$dbName;
    }

}