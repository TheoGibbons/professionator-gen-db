<?php

namespace Professionator;

class Env
{

    public static function get($v): mixed
    {
        return (require(__DIR__ . '/../.env.php'))[$v] ?? null;
    }

}