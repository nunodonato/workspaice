<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'input_cost' => Setting::getSetting('input_cost'),
            'output_cost' => Setting::getSetting('output_cost'),
            'api_key' => Setting::getSetting('api_key'),
            'default_debug' => Setting::getSetting('default_debug'),
            'system_info' => Setting::getSetting('system_info'),
        ];
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'input_cost' => 'required|numeric',
            'output_cost' => 'required|numeric',
            'api_key' => 'sometimes',
            'default_debug' => 'nullable|sometimes',
            'system_info' => 'string',
        ]);

        if (!isset($validatedData['default_debug'])) {
            $validatedData['default_debug'] = false;
        }

        if (!isset($validatedData['api_key'])) {
            $validatedData['api_key'] = '';
        }

        foreach ($validatedData as $key => $value) {
            Setting::setSetting($key, $value);
        }

        return redirect()->route('settings.index')->with('success', 'Settings updated successfully.');
    }
}
