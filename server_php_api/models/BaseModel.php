<?php

abstract class BaseModel
{
    protected function pdo(): PDO
    {
        return Database::pdo();
    }
}
