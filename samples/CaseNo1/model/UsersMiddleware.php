<?php

use IVIR3zaM\PhalconModelMiddleware\ModelsConnector as ModelMiddleware;

abstract class UsersMiddleware extends Users
{
    use ModelMiddleware;

    public static function getUniqueField()
    {
        return 'id';
    }
}