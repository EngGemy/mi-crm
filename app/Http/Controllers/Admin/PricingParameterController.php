<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingParameter;
use Illuminate\Http\Request;

class PricingParameterController extends Controller
{
    public function index()
    {
        $parameters = PricingParameter::orderBy('category')->orderBy('id')->get();

        return view('admin.pricing-parameters.index', compact('parameters'));
    }

    public function edit(PricingParameter $pricingParameter)
    {
        return view('admin.pricing-parameters.edit', compact('pricingParameter'));
    }

    public function update(Request $request, PricingParameter $pricingParameter)
    {
        $validated = $request->validate([
            'value' => 'required|numeric|min:0',
            'label_ar' => 'required|string|max:255',
            'label_en' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $pricingParameter->update($validated);

        return redirect()->route('admin.pricing-parameters.index')
            ->with('success', 'تم تحديث المعامل بنجاح');
    }
}
