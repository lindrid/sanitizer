<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer\Result\Messages;

class Error
{
    const FIELD_TYPE_NOT_SET = 'Тип поля %s не задан!';
    const INCORRECT_FIELD_TYPE = 'Тип %s поля %s задан некорректно!';
    const INVALID_FIELD_TYPE = 'Такого типа как %s не существует';
    const INVALID_FIELD_VALUE = 'Значение поля %s типа %s имеет некорректный тип %s!';
    const ARRAY_ELEMENTS_TYPE_NOT_SET = 'Тип %s элементов массива %s не задан!';
    const INVALID_ARRAY_ELEMENTS_TYPE = 'Тип элементов массива %s является некорректным!';
    const INVALID_ARRAY_ELEMENT_VALUE = 'Значение %d-го элемента массива %s типа %s имеет некорректный тип %s!';
}