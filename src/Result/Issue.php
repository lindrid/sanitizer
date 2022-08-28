<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer\Result;

use Lindrid\Sanitizer\Result\Codes\Warning as WarningCode;
use Lindrid\Sanitizer\Result\Messages\Warning;
use Lindrid\Sanitizer\Result\Messages\Error;
use Lindrid\Sanitizer\Result\Codes\Error as ErrorCode;
use ReflectionClass;

class Issue
{
    private string $message;
    private int $code;
    private int $type;

    public function __construct(string $message, int $code, int $type)
    {
        $this->message = $message;
        $this->code = $code;
        $this->type = $type;
    }

    /**
     * @param mixed $type
     * @param string $name
     * @param int $depth
     * @return Issue
     */
    public static function createInvalidFieldTypeError($type, string $name, int $depth): Issue
    {
        $typeDescription = json_encode($type, JSON_PRETTY_PRINT, $depth);
        return self::createError('INVALID_FIELD_TYPE', $typeDescription, $name);
    }

    /**
     * @param string $constName
     * @param ...$values
     * @return Issue
     */
    public static function createError(string $constName, ...$values): Issue
    {
        $message = sprintf(constant("Lindrid\Sanitizer\Result\Messages\Error::$constName"), ...$values);
        return new Issue($message, constant("Lindrid\Sanitizer\Result\Codes\Error::$constName"),
            IssueType::ERROR);
    }

    /**
     * @param string $constName
     * @param ...$values
     * @return Issue
     */
    public static function createWarning(string $constName, ...$values): Issue
    {
        $message = sprintf(constant("Lindrid\Sanitizer\Result\Messages\Warning::$constName"), ...$values);
        return new Issue($message, constant("Lindrid\Sanitizer\Result\Codes\Warning::$constName"),
            IssueType::WARNING);
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }
}