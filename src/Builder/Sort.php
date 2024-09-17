<?php

namespace Savks\ESearch\Builder;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

final class Sort implements Arrayable
{
    final public const ASC = 'asc';

    final public const DESC = 'desc';

    public array $options;

    public bool $visible;

    public string $order;

    public function __construct(
        public string $id,
        public string $name,
        public array|string $field
    ) {
        $this->visible = false;
        $this->options = [];

        $this->order = self::ASC;
    }

    public function asc(): self
    {
        $this->order = self::ASC;

        return $this;
    }

    public function desc(): self
    {
        $this->order = self::DESC;

        return $this;
    }

    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function toArray(array $options = []): array
    {
        if (\is_array($this->field)) {
            $result = [];

            foreach ($this->field as $name => $data) {
                if (is_int($name) && is_string($data)) {
                    $name = $data;
                    $data = [];
                }

                $result[$name] = array_merge(
                    $options,
                    [
                        'order' => $this->order,
                    ],
                    $this->options,
                    $data
                );
            }

            return [$result];
        }

        return [
            $this->field => array_merge(
                $options,
                [
                    'order' => $this->order,
                ],
                $this->options
            ),
        ];
    }

    /**
     * @throws BindingResolutionException
     */
    public static function fromArray(array $data): self
    {
        /** @var Validator $validator */
        $validator = app(ValidatorFactory::class)->make(
            $data,
            [
                'id' => [
                    'required',
                    'string',
                ],
                'name' => [
                    'required',
                    'string',
                ],
                'field' => [
                    'required',
                ],
                'order' => [
                    'required',
                    'string',
                    'in:asc,ASC,desc,DESC',
                ],
                'options' => [
                    'nullable',
                    'array',
                ],
            ]
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException(
                implode(
                    '. ',
                    Arr::collapse(
                        $validator->errors()->messages()
                    )
                )
            );
        }

        $sort = new self($data['id'], $data['name'], $data['field']);

        if (! empty($data['visible'])) {
            $sort->visible = true;
        }

        if (! empty($data['options'])) {
            $sort->options($data['options']);
        }

        if (isset($data['order'])) {
            if ($data['order'] === self::DESC) {
                $sort->desc();
            } else {
                $sort->asc();
            }
        }

        return $sort;
    }
}
