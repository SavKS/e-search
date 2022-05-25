<?php

namespace Savks\ESearch\Builder;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class Sort implements Arrayable
{
    public const ASC = 'asc';
    public const DESC = 'desc';

    /**
     * @var string
     */
    public string $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string|array
     */
    public string|array $field;

    /**
     * @var array
     */
    public array $options;

    /**
     * @var bool
     */
    public bool $visible;

    /**
     * @var string
     */
    public string $order;

    /**
     * @param string $name
     * @param string $id
     * @param array|string $field
     */
    public function __construct(string $id, string $name, array|string $field)
    {
        $this->id = $id;
        $this->name = $name;
        $this->field = $field;

        $this->visible = false;
        $this->options = [];

        $this->order = static::ASC;
    }

    /**
     * @return $this
     */
    public function asc(): Sort
    {
        $this->order = static::ASC;

        return $this;
    }

    /**
     * @return $this
     */
    public function desc(): Sort
    {
        $this->order = static::DESC;

        return $this;
    }

    /**
     * @param array $options
     * @return self
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param array $options
     * @return array
     */
    public function toArray(array $options = []): array
    {
        if (\is_array($this->field)) {
            $result = [];

            foreach ($this->field as $name => $data) {
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
     * @param array $data
     * @return Sort
     * @throws BindingResolutionException
     */
    public static function fromArray(array $data): Sort
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

        $sort = new static($data['id'], $data['name'], $data['field']);

        if (! empty($data['visible'])) {
            $sort->visible = true;
        }

        if (! empty($data['options'])) {
            $sort->options($data['options']);
        }

        if (isset($data['order'])) {
            if ($data['order'] === static::DESC) {
                $sort->desc();
            } else {
                $sort->asc();
            }
        }

        return $sort;
    }
}
