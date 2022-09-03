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
    public const SEPARATOR = ':';

    private const IN_ROOT_ROUTE = 'в корне';
    private const IN_ROUTE = 'для поля %s';

    private array $fields;
    /**
     * @var Issue[]
     */
    private array $issues;

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
    public function __construct(string $json, array $types)
    {
        $this->fields = [];
        $this->issues = [];
        $fields = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->validate($fields, $types);
        if (!$this->doErrorsExist()) {
            $this->fields = $this->getNormalized($fields, $types);
        }

        if (!empty($this->issues)) {
            throw new SanitizerException($this->issues);
        }
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Метод производит валидацию полей (в соответствии с типами) и типов
     *
     * @param array $fields
     * @param array $types
     * @param string[] $namePrefixes
     * @return void
     */
    private function validate(array $fields, array $types, array $namePrefixes = [])
    {
        $typesCount = count($types);
        $fieldsCount = count($fields);

        if ($typesCount != $fieldsCount) {
            $route = empty($namePrefixes) ? self::IN_ROOT_ROUTE :
                sprintf(self::IN_ROUTE, implode(self::SEPARATOR, $namePrefixes));
            $this->issues[] = Issue::createWarning('NOT_EQUAL_COUNT', $fieldsCount, $typesCount, $route);
        }

        foreach ($fields as $fieldName => $fieldValue) {
            $fieldNameWithPrefix = empty($namePrefixes) ? (string)$fieldName :
                implode(self::SEPARATOR, $namePrefixes) . self::SEPARATOR . $fieldName;

            if (key_exists($fieldName, $types)) {
                if (is_array($types[$fieldName])) {
                    $this->validateNestedField($fieldName, $fieldValue, $types[$fieldName], $namePrefixes);
                } else {
                    $this->validateScalarField($fieldNameWithPrefix, $fieldValue, $types[$fieldName]);
                }
            } else {
                $this->issues[] = Issue::createError('FIELD_TYPE_NOT_SET', $fieldNameWithPrefix);
            }
        }
    }

    private function validateArray($value, array $int)
    {

    }

    private function validateScalarField(string $nameWithPrefix, $value, $type)
    {
        if (Type::isValidScalar($type)) {
            if (!$this->isValidScalarValue($value, $type)) {
                $this->issues[] = Issue::createInvalidFieldValue($value, $nameWithPrefix, $type);
            }
        } else {
            $this->issues[] = Issue::createPrettyError('INVALID_SCALAR_FIELD_TYPE', $nameWithPrefix, $type);
        }
    }

    private function validateNestedField(string $name, $value, array $type, array $namePrefixes)
    {
        $nameWithPrefix = empty($namePrefixes) ? $name :
            implode(self::SEPARATOR, $namePrefixes) . self::SEPARATOR . $name;

        if (Type::isValidMap($type)) {
            if (Utils::isAssociativeArray($value)) {
                $namePrefixes[] = $name;
                $this->validate($value, $type[1], $namePrefixes);
            } else {
                $this->issues[] = Issue::createInvalidFieldValue($value, $nameWithPrefix, $type[0]);
            }
        } elseif (Type::isValidArray($type)) {
            if (Utils::isSequentialArray($value)) {
                if (is_array($type[1])) {
                    $this->validateArray($value, $type[1]);
                } else {
                    foreach ($value as $valueElement) {
                        if (!$this->isValidScalarValue($valueElement, $type[1])) {
                            $this->issues[] = Issue::createError('INVALID_ARRAY_ELEMENT_VALUE',
                                json_encode($valueElement), $nameWithPrefix, strtoupper(Type::getName($type[1])));
                        }
                    }
                }
            } else {
                $this->issues[] = Issue::createInvalidFieldValue($value, $nameWithPrefix, $type[0]);
            }
        }
        else {
            if (isset($type[0]) && Type::isNestedType($type[0])) {
                $typeName = Type::getName($type[0]);
                $this->issues[] = Issue::createPrettyError("INVALID_{$typeName}_FIELD_TYPE", $nameWithPrefix,
                    $type);
            } else {
                $this->issues[] = Issue::createPrettyError('INVALID_NESTED_FIELD_TYPE', $nameWithPrefix,
                    $type);
            }
        }
    }

    private function isValidScalarValue($value, $type): bool
    {
        switch ($type) {
            case Type::INT:
                return is_string($value) ? (string)(int)$value == $value :
                    (is_float($value) && (float)(int)$value == $value) || is_int($value);
            case Type::FLOAT:
                return is_string($value) ? (string)(float)$value == $value :
                    (is_int($value) && (int)(float)$value == $value) || is_float($value);
            case Type::STRING:
                return !is_array($value);
            case Type::PHONE:
                return Utils::isPhone($value);
            default:
                return false;
        }
    }

    private function doErrorsExist(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->getType() === IssueType::ERROR) {
                return true;
            }
        }
        return false;
    }

    /**
     * Метод возвращает нормализованные данные полей $fields по типам $types
     *
     * @param array $fields
     * @param array $types
     * @return array
     */
    private function getNormalized(array $fields, array $types): array
    {
        $normFields = [];
        foreach ($fields as $fieldName => $fieldValue) {
            switch ($types[$fieldName]) {
                case Type::INT:
                    $normFields[$fieldName] = (int)$fieldValue;
                    break;
                case Type::FLOAT:
                    $normFields[$fieldName] = (float)$fieldValue;
                    break;
                case Type::STRING:
                    $normFields[$fieldName] = (string)$fieldValue;
                    break;
                default:
                    if ($types[$fieldName][0] === Type::MAP) {
                        $normFields[$fieldName] = $this->getNormalized($fieldValue, $types[$fieldName][1]);
                    } else if ($types[$fieldName][0] === Type::ARRAY) {
                        $typeClones = [];
                        foreach ($fieldValue as $key => $value) {
                            $typeClones[$key] = $types[$fieldName][1];
                        }
                        $normFields[$fieldName] = $this->getNormalized($fieldValue, $typeClones);
                    }
            }
        }
        return $normFields;
    }
}