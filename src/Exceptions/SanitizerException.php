<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer\Exceptions;

use Exception;
use Lindrid\Sanitizer\Result\Issue;
use Lindrid\Sanitizer\Result\IssueType;
use Throwable;

class SanitizerException extends Exception
{
    /**
     * @var Issue[]
     */
    private array $issues = [];

    protected $message = '';
    protected $code = 0;

    /**
     * @param Issue[] $issues
     * @param Throwable|null $previous
     */
    public function __construct(array $issues, Throwable $previous = null)
    {
        $this->issues = $issues;
        $this->setMessageAndCode();
        parent::__construct($this->message, $this->code, $previous);
    }

    /**
     * @param int $index
     * @return Issue
     */
    public function getIssue(int $index): Issue
    {
        return $this->issues[$index];
    }

    /**
     * @return Issue[]
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    private function setMessageAndCode()
    {
        $hasWarnings = $hasErrors = false;
        foreach ($this->issues as $issue) {
            if (!$hasWarnings && $issue->getType() === IssueType::WARNING) {
                $this->message .= "Warnings: \n";
                $hasWarnings = true;
            }
            if (!$hasErrors && $issue->getType() === IssueType::ERROR) {
                $this->message .= "Errors: \n";
                $hasErrors = true;
            }
            $this->message .= $issue->getMessage() . "\n";
            $this->code += $issue->getCode();
        }
    }
}