<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer;

use JsonException;
use Lindrid\Sanitizer\Exceptions\SanitizerException;
use Lindrid\Sanitizer\Result\Issue;

class Sanitizer
{
    private const NESTED_INDEX_SEPARATOR = ':';
    private const PHONE_FIRST_DIGIT = '7';
    private const IN_ROOT = 'в корне';
    private const FOR_FIELD = 'для поля %s';

    private array $fields;
    /**
     * @var Issue[][]
     */
    private array $issues;

    /**
     * Типы полей JSON данных описываются следующим образом:
     *      1) скалярные:
     *          new Sanitizer('{"i": 10}', ['i' => Type::INT]);
     *      2) вложенные
     * Массив из однотипных элементов:
     *          new Sanitizer('{"arr": [1, 2]}',
     *              ['arr' => [Type::Array, Type::INT]]);
     * Структура (ассоциативный массив с заранее известными ключами):
     *          new Sanitizer('{"map": {"a": 1.5, "b": "str"}}',
     *              ['map' => [Type::MAP, ['a' => Type::FLOAT, 'b' => Type::STRING]]]);
     *
     * @throws JsonException
     * @throws SanitizerException
     */
    public function __construct(string $json, array $types)
    {
        $this->fields = [];
        $this->issues = ['warnings' => [], 'errors' => []];
        $fields = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->validate($fields, $types);
        if (!$this->doErrorsExist()) {
            $this->fields = $this->getNormalized($fields, $types);
        }

        if (!empty($this->issues['warnings']) || !empty($this->issues['errors'])) {
            throw new SanitizerException($this->issues);
        }
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Метод производит валидацию полей (в соответствии с типами)
     *
     * @param array $fields
     * @param array $types
     * @param string[] $namePrefixes служит для отображения имени поля вложенной структуры.
     * @param bool $isArray = true, если валидируется массив из однотипных элементов
     * @return void
     */
    private function validate(array $fields, array $types, array $namePrefixes = [], bool $isArray = false)
    {
        $typesCount = count($types);
        $fieldsCount = count($fields);

        if (($typesCount != $fieldsCount) && !$isArray) {
            $route = empty($namePrefixes) ? self::IN_ROOT :
                sprintf(self::FOR_FIELD, implode(self::NESTED_INDEX_SEPARATOR, $namePrefixes));
            $this->issues['warnings'][] = Issue::createWarning('NOT_EQUAL_COUNT', $fieldsCount, $typesCount,
                $route);
        }

        foreach ($fields as $fieldName => $fieldValue) {
            $fieldNameWithPrefix = empty($namePrefixes) ? (string)$fieldName :
                implode(self::NESTED_INDEX_SEPARATOR, $namePrefixes) . self::NESTED_INDEX_SEPARATOR . $fieldName;

            if (!$isArray && key_exists($fieldName, $types) || $isArray) {
                $type = $isArray ? $types[1] : $types[$fieldName];
                if (is_array($type)) {
                    $this->validateNestedField((string)$fieldName, $fieldValue, $type, $namePrefixes);
                } else {
                    $this->validateScalarField($fieldNameWithPrefix, $fieldValue, $type);
                }
            } else {
                $this->issues['errors'][] = Issue::createError('FIELD_TYPE_NOT_SET', $fieldNameWithPrefix);
            }
        }
    }

    private function validateScalarField(string $nameWithPrefix, $value, $type)
    {
        if (Type::isValidScalar($type)) {
            if (!$this->isValidScalarValue($value, $type)) {
                $this->issues['errors'][] = Issue::createInvalidFieldValue($value, $nameWithPrefix, $type);
            }
        } else {
            $this->issues['errors'][] = Issue::createPrettyError('INVALID_SCALAR_FIELD_TYPE',
                $nameWithPrefix, $type);
        }
    }

    private function validateNestedField(string $name, $value, array $type, array $namePrefixes)
    {
        $nameWithPrefix = empty($namePrefixes) ? $name :
            implode(self::NESTED_INDEX_SEPARATOR, $namePrefixes) . self::NESTED_INDEX_SEPARATOR . $name;

        if (Type::isValidMap($type)) {
            if (Utils::isAssociativeArray($value)) {
                $namePrefixes[] = $name;
                $this->validate($value, $type[1], $namePrefixes);
            } else {
                $this->issues['errors'][] = Issue::createInvalidFieldValue($value, $nameWithPrefix, $type[0]);
            }
        } elseif (Type::isValidArray($type)) {
            if (Utils::isSequentialArray($value)) {
                foreach ($value as $key => $element) {
                    if (is_array($element) && is_array($type[1])) {
                        if (empty($namePrefixes)) {
                            $namePrefixes[] = $name;
                        }
                        $namePrefixes[] = $key;
                        $this->validate($element, $type[1], $namePrefixes, true);
                    } else {
                        if (!$this->isValidScalarValue($element, $type[1])) {
                            $this->issues['errors'][] = Issue::createError('INVALID_ARRAY_ELEMENT_VALUE',
                                json_encode($element), $nameWithPrefix, strtoupper(Type::getName($type[1])));
                        }
                    }
                }
            } else {
                $this->issues['errors'][] = Issue::createInvalidFieldValue($value, $nameWithPrefix, $type[0]);
            }
        }
        else {
            $this->issues['errors'][] = Issue::createPrettyError("INVALID_NESTED_FIELD_TYPE",
                $nameWithPrefix, $type);
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
                return Type::isValidPhone($value);
            default:
                return false;
        }
    }

    private function doErrorsExist(): bool
    {
        return !empty($this->issues['errors']);
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
                case Type::PHONE:
                    $normFields[$fieldName] = self::PHONE_FIRST_DIGIT .
                        substr(preg_replace('/[^0-9]/', '', $fieldValue), 1);
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