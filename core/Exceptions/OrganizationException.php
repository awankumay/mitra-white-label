<?php

namespace Core\Exceptions;

class OrganizationException extends CoreException
{
    public static function invalidHierarchy(string $message): self
    {
        return new self($message);
    }

    public static function invalidAssignment(string $message): self
    {
        return new self($message);
    }
}
