<?php

namespace App\Http\Controllers;

use App\Domain\Product\Product;
use App\Domain\Product\Ports\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Search endpoint for the order form's live product picker — replaces loading all products upfront. */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $result = $this->productRepository->search(
            query:   trim((string) $request->input('q', '')),
            perPage: 10,
            page:    max(1, (int) $request->input('page', 1)),
        );

        return response()->json([
            'data' => array_map(fn (Product $p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => round($p->price, 2),
            ], $result->items),
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page'    => $result->lastPage,
                'total'        => $result->total,
            ],
        ]);
    }
}
