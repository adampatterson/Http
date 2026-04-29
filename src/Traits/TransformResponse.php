<?php

namespace Http\Traits;

use Illuminate\Support\Collection;

trait TransformResponse
{
    public static int $defaultJsonDecodingFlags = JSON_THROW_ON_ERROR;

    /**
     * @return Collection
     */
    public function collect(): Collection
    {
        return new Collection($this->object() ?? []);
    }

    /**
     * @return array
     */
    public function array(): array
    {
        return $this->collect()->toArray();
    }


    /**
     * @param  null  $flags
     * @return mixed
     *
     * @throws \JsonException
     */
    public function object($flags = null): mixed
    {
        return json_decode(
            $this->response->getBody(),
            false,
            flags: $flags ?? self::$defaultJsonDecodingFlags
        );
    }

    /**
     * @return string|false
     */
    public function toJson(): string|false
    {
        return json_encode(
            $this->array(),
        );
    }
}
