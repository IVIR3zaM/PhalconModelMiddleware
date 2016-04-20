<?php

class Operators extends UsersMiddleware
{
    public $id;
    public $level;

    public static function getCustomFields()
    {
        return ['type' => 'Operator'];
    }
}