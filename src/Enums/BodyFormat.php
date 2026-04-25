<?php

namespace Http\Enums;

enum BodyFormat: string
{
    case JSON = 'json';
    case FORM_DATA = 'form_params';
    case MULTIPART = 'multipart';
}
