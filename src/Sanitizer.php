<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer;

use JsonException;
use Lindrid\Sanitizer\Exceptions\SanitizerException;
use Lindrid\Sanitizer\Result\Messages\Error;
use Lindrid\Sanitizer\Result\Messages\Warning;
use Lindrid\Sanitizer\Result\Codes\Warning as WarningCode;
use Lindrid\Sanitizer\Result\Codes\Error as ErrorCode;
use Lindrid\Sanitizer\Result\Issue;
use Lindrid\Sanitizer\Result\IssueType;

class Sanitizer
{
    private array $fields;
    private array $issues;
    /**
     * @var int
     */
    private int $depth;

    /**
     * Типы полей JSON данных описываются следующим образом:
     *      1) скалярные:
     *          new Sanitizer('{"i": 10}', ['i' => Type::INT]);
     *      2) вложенные
     *          new Sanitizer('{"arr": [1, 2]}',
     *              ['arr' => [Type::Array, Type::INT]]);
     *          new Sanitizer('{"map": {"a": 1.5, "b": "str"}}',
     *              ['map' => [Type::MAP, ['a' => Type::FLOAT, 'b' => Type::STRING]]]);
     *
     * @throws JsonException
     * @throws SanitizerException
     */
    public function __construct(string $json, array $types, int $depth = 512)
    {
        $this->fields = json_decode($json, true, $depth, JSON_THROW_ON_ERROR);
        $this->issues = [];
        $this->depth = $depth;

        $this->validate($this->fields, $types);

        if (!empty($this->issues)) {
            throw new SanitizerException($this->issues);
        }
    }

    public function getFields()
    {
        return $this->fields;
    }

    private function validate(array $fields, array $types)
    {
        $typesCount = count($types);
        $fieldsCount = count($fields);

        if (count($types) != count($fields)) {
            $this->issues[] = Issue::createWarning('NOT_EQUAL_COUNT', $fieldsCount, $typesCount);
        }

        foreach ($fields as $fieldName => $fieldValue) {
            if (key_exists($fieldName, $types)) {
                if (!is_array($types[$fieldName])) {
                    $this->validateScalarField($fieldName, $fieldValue, $types[$fieldName]);
                } else {
                    $this->validateNestedField($fieldName, $fieldValue, $types[$fieldName]);
                }
            } else {
                $this->issues[] = Issue::createError('FIELD_TYPE_NOT_SET', $fieldName);
            }
        }
    }

    private function validateScalarField(string $name, $value, $type)
    {
        if (Type::isValidScalar($type)) {
            $this->validateFieldValue('scalarFieldHasValidValue', $name, $value, $type);
        } else {
            $this->issues[] = Issue::createInvalidFieldTypeError($type, $name, $this->depth);
        }
    }

    private function validateNestedField(string $name, $value, array $type)
    {
        if (Type::isValidNested($type)) {
            $this->validateFieldValue('nestedFieldHasValidValue', $name, $value, $type);
            $this->validate($value, $type[1]);
        } else {
            if (isset($type[0]) && is_int($type[0]) && Type::isNestedType($type[0])) {
                $this->issues[] = Issue::createError('INCORRECT_FIELD_TYPE',
                    strtoupper(Type::getName($type[0])), $name
                );
            } else {
                $this->issues[] = Issue::createInvalidFieldTypeError($type, $name, $this->depth);
            }
        }
    }

    private function validateFieldValue(string $funcName, string $name, $value, $type)
    {
        if (!$this->$funcName($value, $type)) {
            $this->issues[] = Issue::createError('INVALID_FIELD_VALUE',
                strtoupper(gettype($value)), $name, Type::getName($type)
            );
        }
    }

    private function scalarFieldHasValidValue($value, $type): bool
    {
        switch ($type) {
            case Type::INT:
                return is_int($value);
            case Type::FLOAT:
                return is_float($value);
            case Type::STRING:
                return is_string($value);
            case Type::PHONE:
                return Utils::isPhone($value);
            default:
                return false;
        }
    }

    /**
     * @param mixed $value
     * @param mixed $type
     * @return bool
     */
    private function nestedFieldHasValidValue($value, $type): bool
    {
        switch ($type) {
            case Type::MAP:
                return Utils::isAssociativeArray($value);
            case Type::ARRAY:
                return Utils::isSequentialArray($value);
            default:
                return isset($type[0])
                    && $this->nestedFieldHasValidValue($value, $type[0]);
        }
    }

    private function getInvalidScalarDescription($type): string
    {
        if (is_array($type)) {

        }
    }
}