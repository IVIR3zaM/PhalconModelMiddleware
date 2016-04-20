<?php

class Customers extends UsersMiddleware
{
    public $id;
    public $points;

    public static function getCustomFields()
    {
        return ['type' => 'Customer'];
    }
}