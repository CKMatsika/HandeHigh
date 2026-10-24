<?php

namespace App\Exceptions;

use RuntimeException;

class MissingAccountMappingException extends RuntimeException
{
    protected string $mappingType;
    protected string $sourceKey;
    protected int $schoolId;

    public function __construct(
        string $message,
        string $mappingType = '',
        string $sourceKey = '',
        int $schoolId = 0,
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->mappingType = $mappingType;
        $this->sourceKey = $sourceKey;
        $this->schoolId = $schoolId;
    }

    public function getMappingType(): string
    {
        return $this->mappingType;
    }

    public function getSourceKey(): string
    {
        return $this->sourceKey;
    }

    public function getSchoolId(): int
    {
        return $this->schoolId;
    }
}
