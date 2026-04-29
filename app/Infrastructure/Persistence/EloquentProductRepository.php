<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Product\Product;
use App\Domain\Product\Ports\ProductRepositoryInterface;
use App\Domain\Product\ProductSearchResult;
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

    public function search(string $query, int $perPage = 10, int $page = 1): ProductSearchResult
    {
        $safe = str_replace(['%', '_'], ['\\%', '\\_'], $query);

        $paginator = ProductModel::when(
                $safe !== '',
                fn ($q) => $q->where('name', 'like', "%{$safe}%")
            )
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return new ProductSearchResult(
            items:       $paginator->map(fn (ProductModel $m) => $this->toDomain($m))->all(),
            total:       $paginator->total(),
            perPage:     $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage:    $paginator->lastPage(),
        );
    }

    private function toDomain(ProductModel $model): Product
    {
        return new Product(
            id:    $model->id,
            name:  $model->name,
            price: $model->price,
        );
    }
}
