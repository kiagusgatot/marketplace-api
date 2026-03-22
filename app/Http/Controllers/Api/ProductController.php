<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'seller']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->whereBetween('price', [(float)$request->min_price, (float)$request->max_price]);
        }

        if ($request->filled('sort_by')) {
            $allowedSorts = ['price', 'created_at', 'title', 'rating'];
            if (in_array($request->sort_by, $allowedSorts)) {
                $order = strtolower($request->order) === 'desc' ? 'desc' : 'asc';
                $query->orderBy($request->sort_by, $order);
            }
        }

        $products = $query->paginate(5);

        $products->getCollection()->transform(function ($product) {
            $ratingClass = 'Regular';
            if ($product->rating >= 8.5) {
                $ratingClass = 'Top Rated';
            } elseif ($product->rating >= 7) {
                $ratingClass = 'Popular';
            }

            return [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
                'rating' => $product->rating,
                'rating_class' => $ratingClass,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name
                ] : null,
                'seller' => $product->seller ? [
                    'id' => $product->seller->id,
                    'name' => $product->seller->name
                ] : null
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data produk berhasil diambil',
            'data' => $products
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => 'required|numeric|min:0',
            'thumbnail'   => 'nullable|string', 
            'file_path'   => 'nullable|string',
        ]);

        $product = $request->user()->products()->create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data'    => $product 
        ], 201);
    }

    public function update(Request $request, Product $product)
    {
        if ($request->user()->id !== $product->seller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Anda tidak memiliki akses untuk mengubah produk ini.'
            ], 403);
        }

        $validatedData = $request->validate([
            'category_id' => 'sometimes|required|exists:product_categories,id',
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price'       => 'sometimes|required|numeric|min:0',
            'thumbnail'   => 'nullable|string', 
            'file_path'   => 'nullable|string',
        ]);

        $product->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data'    => $product
        ]);
    }

    public function destroy(Request $request, Product $product)
    {
        if ($request->user()->id !== $product->seller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Anda tidak memiliki hak untuk menghapus produk ini.'
            ], 403);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus'
        ]);
    }
}