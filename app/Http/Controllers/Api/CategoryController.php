<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory; // Jangan lupa import model ini

class CategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::all();
        
        return response()->json([
            'success' => true,
            'data'    => $categories
        ]);
    }

    public function show($id)
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Kategori tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $category
        ]);
    }
}