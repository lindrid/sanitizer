<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer\Result;

use Lindrid\Sanitizer\Result\Codes\Warning as WarningCode;
use Lindrid\Sanitizer\Result\Messages\Warning;
use Lindrid\Sanitizer\Result\Messages\Error;
use Lindrid\Sanitizer\Result\Codes\Error as ErrorCode;
use Lindrid\Sanitizer\Type;
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
     * Возвращает Issue типа Error с текстом ошибки, взятым из Error::$errorConstName.
     * Форматирует текст ошибки для удобочитаемости.
     * Заменяет значения констант типов на их имена.
     *
     * @param string $errorConstName
     * @param string $name
     * @param mixed $type
     * @return Issue
     */
    public static function createPrettyError(string $errorConstName, string $name, $type): Issue
    {
        $prettyType = str_replace(
            [':', '{', '}'],
            ['=>', '[', ']'],
            json_encode($type)
        );

        $patterns = $replacements = [];
        foreach (Type::getAll() as $typeConst) {
            $patterns[] = "/(\[|>|,)$typeConst(,|]|})/i";
            $replacements[] = '$1' . 'Type::' . Type::getName($typeConst) . '$2';
        }

        return self::createError($errorConstName, $name, preg_replace($patterns, $replacements, $prettyType));
    }

    /**
     * @param $value
     * @param string $name
     * @param int $type
     * @return Issue
     */
    public static function createInvalidFieldValue($value, string $name, int $type): Issue
    {
        $valueJson = json_encode($value);
        return self::createError('INVALID_FIELD_VALUE',$valueJson, $name, Type::getName($type));
    }

    /**
     * @param string $errorConstName
     * @param ...$values
     * @return Issue
     */
    public static function createError(string $errorConstName, ...$values): Issue
    {
        $message = sprintf(constant("Lindrid\Sanitizer\Result\Messages\Error::$errorConstName"), ...$values);
        return new Issue($message, constant("Lindrid\Sanitizer\Result\Codes\Error::$errorConstName"),
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