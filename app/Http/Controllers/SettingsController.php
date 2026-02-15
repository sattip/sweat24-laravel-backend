<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        // In a real application, these would be fetched from database
        $settings = [
            'business_name' => 'SWEAT24 Fitness',
            'business_email' => 'info@sweat24.com',
            'business_phone' => '+30 210 1234567',
            'business_address' => '123 Fitness Street, Athens, Greece',
            'vat_number' => 'EL123456789',
            'registration_number' => 'GR-2024-12345',
        ];
        
        return view('admin.settings.index', compact('settings'));
    }
}