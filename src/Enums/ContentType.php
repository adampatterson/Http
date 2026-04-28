<?php

namespace Http\Enums;

enum ContentType: string
{
    case JSON = 'application/json';
    case FORM_DATA = 'application/x-www-form-urlencoded';
}
