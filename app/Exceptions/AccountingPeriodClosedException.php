<?php

namespace App\Exceptions;

use RuntimeException;

class AccountingPeriodClosedException extends RuntimeException
{
    protected mixed $period;
    protected ?string $transactionDate;

    public function __construct(string $message = "", mixed $period = null, ?string $transactionDate = null, int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->period = $period;
        $this->transactionDate = $transactionDate;
    }

    public function getPeriod(): mixed
    {
        return $this->period;
    }

    public function getTransactionDate(): ?string
    {
        return $this->transactionDate;
    }
}
