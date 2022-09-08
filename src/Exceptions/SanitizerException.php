<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer\Exceptions;

use Exception;
use Lindrid\Sanitizer\Result\Issue;
use Throwable;

class SanitizerException extends Exception
{
    /**
     * @var Issue[]
     */
    private array $warnings = [];
    /**
     * @var Issue[]
     */
    private array $errors = [];

    protected $message = '';

    /**
     * @param Issue[][] $issues
     * @param Throwable|null $previous
     */
    public function __construct(array $issues, Throwable $previous = null)
    {
        $this->warnings = $issues['warnings'];
        $this->errors = $issues['errors'];
        $this->makeMessage();
        parent::__construct($this->message, 0, $previous);
    }

    /**
     * @param int $index
     * @return Issue
     */
    public function getWarning(int $index): Issue
    {
        return $this->warnings[$index];
    }

    /**
     * @return Issue[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * @param int $index
     * @return Issue
     */
    public function getError(int $index): Issue
    {
        return $this->errors[$index];
    }

    /**
     * @return Issue[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    private function makeMessage()
    {
        $this->makeMessageOutOf('warnings', 'Warnings:');
        $this->makeMessageOutOf('errors', 'Errors:');
    }

    private function makeMessageOutOf(string $arrayName, string $header)
    {
        $hasHeader = false;
        foreach ($this->$arrayName as $issue) {
            if (!$hasHeader) {
                $this->message .= "$header \n";
                $hasHeader = true;
            }
            $this->message .= $issue->getMessage() . "\n";
        }
    }
}