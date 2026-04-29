<?php

namespace Http\Enums;

enum PostMethod: string
{
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case GET = 'GET';
    case OPTIONS = 'OPTIONS';
}
