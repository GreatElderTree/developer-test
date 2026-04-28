<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Product\Product;
use App\Domain\Product\Ports\ProductRepositoryInterface;
use App\Infrastructure\Persistence\Models\ProductModel;

/** Fixes: original ran one SELECT per item in a loop (N+1) — findByIds() loads all products in a single whereIn() query. */
class EloquentProductRepository implements ProductRepositoryInterface
{
    /** @return Product[] keyed by id */
    public function findByIds(array $ids): array
    {
        return ProductModel::whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (ProductModel $m) => [$m->id => $this->toDomain($m)])
            ->all();
    }

    /** @return Product[] */
    public function findAll(): array
    {
        return ProductModel::orderBy('name')
            ->get()
            ->map(fn (ProductModel $m) => $this->toDomain($m))
            ->all();
    }

    private function toDomain(ProductModel $model): Product
    {
        return new Product(
            id:    $model->id,
            name:  $model->name,
            price: (float) $model->price,
        );
    }
}
