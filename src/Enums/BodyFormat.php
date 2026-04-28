<?php

namespace Http\Enums;

enum BodyFormat: string
{
    // https://docs.guzzlephp.org/en/stable/request-options.html#json
    case JSON = \GuzzleHttp\RequestOptions::JSON;
    // https://docs.guzzlephp.org/en/stable/request-options.html#form-params
    case FORM_DATA = \GuzzleHttp\RequestOptions::FORM_PARAMS;
    // https://docs.guzzlephp.org/en/stable/request-options.html#multipart
    case MULTIPART = \GuzzleHttp\RequestOptions::MULTIPART;
    // https://docs.guzzlephp.org/en/stable/request-options.html#auth
    case AUTH = \GuzzleHttp\RequestOptions::AUTH;
}
