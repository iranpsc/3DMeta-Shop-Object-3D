<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuildPackageResource;
use App\Models\Product;
use Illuminate\Http\Request;

class BuildPackageController extends Controller
{
    public function getBuildPackage(Request $request)
    {
        $models = Product::with('attributes', 'images', 'files')
            ->whereHas('attributes', function ($query) use ($request) {
                $query->where('slug', 'area')
                    ->whereRaw('CAST(`value` AS DECIMAL(12,2)) <= ?', [$request->area]);
            })
            ->whereHas('attributes', function ($query) use ($request) {
                $query->where('slug', 'density')
                    ->whereRaw('CAST(`value` AS SIGNED) <= ?', [$request->density]);
            })
            ->whereHas('attributes', function ($query) use ($request) {
                $query->where('slug', 'karbari')
                    ->where('value', $request->karbari);
            })
            ->whereHas('attributes', function ($query) {
                $query->where('slug', 'owner')
                    ->where('value', 'METARGB');
            })
            ->select('id', 'name', 'sku')
            ->simplePaginate(10);

        return BuildPackageResource::collection($models);
    }
}
