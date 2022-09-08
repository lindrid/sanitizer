<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer\Result\Messages;

class Error
{
    const FIELD_TYPE_NOT_SET = 'Тип поля "%s" не задан!';
    const INVALID_SCALAR_FIELD_TYPE = 'Поле "%s" задано несуществующим скалярным типом %s. Используйте константы Type::';
    const INVALID_NESTED_FIELD_TYPE = 'Составной тип поля "%s" имеет некорректное описание %s!';
    const INVALID_FIELD_VALUE = 'Значение %s поля "%s" невозможно преобразовать к типу Type::%s!';
    const INVALID_ARRAY_ELEMENT_VALUE = 'Значение %s массива "%s" невозможно преобразовать к Type::%s!';
}