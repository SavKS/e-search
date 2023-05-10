# Description

ESearch

# Installation

### Step 1

install package

    composer require savks/e-search

### Step 2

Publish e-search config file

    php artisan vendor:publish

# Usage

### Step 1

Create resource class for mapping data into specific elastic index from example below

```php
<?php

namespace App\ESearch\Resources;

use Closure;
use App\Models\Product;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use Savks\ESearch\Support\Resource;
use Savks\ESearch\Support\Config;

class ProductResource extends Resource
{
    public static function id(): string
    {
        return 'product';
    }

    public static function configure(Config $config): void
    {
        //
    }


    public function seed(?array $ids, int $limit, Closure $callback, Closure $resolveCount, array $criteria = []): void
    {
        $query = Product::query()
            ->with([
                'categories',
            ]);

        if ($ids) {
            $query->whereIn('id', $ids);
        }

        $resolveCount(
            $query->count()
        );

        $query->chunk($limit, $callback);
    }


    public function clean(int $limit, Closure $callback, Closure $resolveCount, array $criteria = []): void
    {
    }


    public function mapTo(array $result): Collection
    {
        $ids = Arr::pluck($result['hits']['hits'], '_source.id');

        return Product::whereIn($ids)
            ->sortBy(function (Product $vehicle) use ($ids) {
                return \array_search($vehicle->id, $ids, true);
            })
            ->values();
    }


    public function buildDocument($vehicle): array
    {
        $isActive = $product->category_id
            && $product->is_active
            && $product->category->is_active;

        $data = [
            'id' => $product->id,
            'name_uk' => $product->getTranslation('name', 'uk'),
            'name_ru' => $product->getTranslation('name', 'ru'),
            'manufacturer_code' => mb_strtolower($product->manufacturer_code),
            'price' => $product->price,
            'category_id' => $product->categories->pluck('id'),
            'views' => $product->views ?: 0,
            'available' => $product->available,
            'is_active' => $isActive ? 1 : 0,
        ];

        return $data;
    }

    public function mapping(): array
    {
        return [
            'properties' => [
                'name_uk' => [
                    'type' => 'text',
                    // 'analyzer' => 'ukrainian',
                ],
                'name_ru' => [
                    'type' => 'text',
                    // 'analyzer' => 'russian',
                ],
                'manufacturer_code' => [
                    'type' => 'keyword'
                ],
                'price' => [
                    'type' => 'scaled_float',
                    'scaling_factor' => 100,
                ],
                'category_id' => [
                    'type' => 'long'
                ],
                'views' => [
                    'type' => 'integer'
                ],
                'available' => [
                    'type' => 'boolean'
                ],
                'is_active' => [
                    'type' => 'boolean'
                ],
            ]
        ];
    }

    private function calcWeight(Vehicle $vehicle): int
    {
    }
}
```

### Step 2

Add your index resource into **e-search** config within "resources" section

```php
return [
    'resources' => [
        'product' => App\ESearch\Resources\ProductResource::class,

        // or

        App\ESearch\Resources\ProductResource::class,
    ],
]
```
