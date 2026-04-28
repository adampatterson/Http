<?php

namespace Http\Traits;

use Illuminate\Support\Collection;

trait TransformResponse
{

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
     * @return mixed
     * @throws \JsonException
     */
    public function object(): mixed
    {
        return json_decode(
            $this->response->getBody(),
            true,
            flags: JSON_THROW_ON_ERROR
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
