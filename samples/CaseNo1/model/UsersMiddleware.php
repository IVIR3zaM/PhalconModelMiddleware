<?php

use IVIR3zaM\PhalconModelMiddleware as ModelMiddleware;

abstract class UsersMiddleware extends Users
{
    use ModelMiddleware;

    public static function getUniqueField()
    {
        return 'id';
    }
}