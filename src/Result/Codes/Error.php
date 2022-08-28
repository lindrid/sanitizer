<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer\Result\Codes;

class Error
{
    const FIELD_TYPE_NOT_SET = 1;
    const INVALID_FIELD_TYPE = 2;
    const INVALID_FIELD_VALUE = 3;
    const ARRAY_ELEMENTS_TYPE_NOT_SET = 4;
    const INVALID_ARRAY_ELEMENT_VALUE = 5;
}