<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk dengan filter dan paginasi.
     */
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

    /**
     * Menyimpan data produk baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input (Layer Keamanan Pertama)
        $validatedData = $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => 'required|numeric|min:0',
            'thumbnail'   => 'nullable|string', 
            'file_path'   => 'nullable|string',
        ]);

        // 2. Eksekusi Penyimpanan Aman (Mencegah Mass Assignment & IDOR)
        $product = $request->user()->products()->create($validatedData);

        // 3. Kembalikan Response Sukses
        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data'    => $product 
        ], 201);
    }

    /**
     * Memperbarui data produk spesifik.
     */
    public function update(Request $request, Product $product)
    {
        // 1. Lapisan Otorisasi (Mutlak: Mencegah IDOR)
        if ($request->user()->id !== $product->seller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Anda tidak memiliki akses untuk mengubah produk ini.'
            ], 403);
        }

        // 2. Validasi Input
        // Menggunakan 'sometimes' agar client tidak wajib mengirim seluruh field jika hanya ingin mengubah satu data (misal: harga saja).
        $validatedData = $request->validate([
            'category_id' => 'sometimes|required|exists:product_categories,id',
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price'       => 'sometimes|required|numeric|min:0',
            'thumbnail'   => 'nullable|string', 
            'file_path'   => 'nullable|string',
        ]);

        // 3. Eksekusi Pembaruan Data
        $product->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data'    => $product
        ]);
    }

    /**
     * Menghapus data produk spesifik.
     */
    public function destroy(Request $request, Product $product)
    {
        // 1. Lapisan Otorisasi (Mutlak: Mencegah IDOR)
        if ($request->user()->id !== $product->seller_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Anda tidak memiliki hak untuk menghapus produk ini.'
            ], 403);
        }

        // 2. Eksekusi Penghapusan
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus'
        ]);
    }
} // <--- PENUTUP KELAS BERADA DI SINI, PALING BAWAH