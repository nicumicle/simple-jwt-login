<?php

namespace SimpleJWTLogin\Repositories;

trait DateFilterTrait
{
    protected function isValidDate(string $date): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }
}
