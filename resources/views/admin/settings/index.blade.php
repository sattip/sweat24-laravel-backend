@extends('layouts.admin')

@section('title', 'Gym Settings')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Gym Settings</h1>
        <p class="text-gray-600 mt-2">Manage your gym's business information and operational parameters.</p>
    </div>
    
    <!-- Business Information -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Business Information</h2>
            <button onclick="editBusinessInfo()" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-edit"></i> Edit
            </button>
        </div>
        
        <div class="grid grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600">Business Name</p>
                <p class="font-medium" id="business_name">SWEAT24 Fitness</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Email</p>
                <p class="font-medium" id="business_email">info@sweat24.com</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Phone</p>
                <p class="font-medium" id="business_phone">+30 210 1234567</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Address</p>
                <p class="font-medium" id="business_address">123 Fitness Street, Athens, Greece</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">VAT Number</p>
                <p class="font-medium" id="vat_number">EL123456789</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Registration Number</p>
                <p class="font-medium" id="registration_number">GR-2024-12345</p>
            </div>
        </div>
    </div>
    
    <!-- Operating Hours -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Operating Hours</h2>
            <button onclick="editOperatingHours()" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-edit"></i> Edit
            </button>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <div class="border rounded p-3">
                <p class="font-medium">Monday - Friday</p>
                <p class="text-gray-600" id="weekday_hours">06:00 - 22:00</p>
            </div>
            <div class="border rounded p-3">
                <p class="font-medium">Saturday - Sunday</p>
                <p class="text-gray-600" id="weekend_hours">08:00 - 20:00</p>
            </div>
        </div>
    </div>
    
    <!-- Class Settings -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Class Settings</h2>
            <button onclick="editClassSettings()" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-edit"></i> Edit
            </button>
        </div>
        
        <div class="grid grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-600">Default Class Duration</p>
                <p class="font-medium" id="default_duration">60 minutes</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Max Participants per Class</p>
                <p class="font-medium" id="max_participants">20</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Booking Window</p>
                <p class="font-medium" id="booking_window">7 days in advance</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Cancellation Deadline</p>
                <p class="font-medium" id="cancellation_deadline">4 hours before class</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Late Cancellation Penalty</p>
                <p class="font-medium" id="late_penalty">25%</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">No-Show Penalty</p>
                <p class="font-medium" id="noshow_penalty">100%</p>
            </div>
        </div>
    </div>
    
    <!-- Package Settings -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Package Settings</h2>
            <button onclick="editPackageSettings()" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-edit"></i> Edit
            </button>
        </div>
        
        <div class="grid grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-600">Expiry Warning Days</p>
                <p class="font-medium" id="expiry_warning">7 days</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Auto-Renewal Default</p>
                <p class="font-medium" id="auto_renewal">Disabled</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Max Freeze Duration</p>
                <p class="font-medium" id="max_freeze">90 days</p>
            </div>
        </div>
    </div>
    
    <!-- Notification Settings -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Notification Settings</h2>
            <button onclick="editNotificationSettings()" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-edit"></i> Edit
            </button>
        </div>
        
        <div class="space-y-3">
            <label class="flex items-center">
                <input type="checkbox" id="email_notifications" checked disabled class="mr-3">
                <span>Send email notifications for bookings</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" id="sms_notifications" checked disabled class="mr-3">
                <span>Send SMS reminders before classes</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" id="expiry_notifications" checked disabled class="mr-3">
                <span>Send package expiry notifications</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" id="marketing_notifications" disabled class="mr-3">
                <span>Send marketing communications</span>
            </label>
        </div>
    </div>
</div>

<!-- Edit Business Info Modal -->
<div id="businessInfoModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Business Information</h3>
            <form id="businessInfoForm">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Business Name</label>
                        <input type="text" name="business_name" value="SWEAT24 Fitness" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="business_email" value="info@sweat24.com" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input type="tel" name="business_phone" value="+30 210 1234567" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">VAT Number</label>
                        <input type="text" name="vat_number" value="EL123456789" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4 col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <input type="text" name="business_address" value="123 Fitness Street, Athens, Greece" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Registration Number</label>
                        <input type="text" name="registration_number" value="GR-2024-12345" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex justify-end gap-4 mt-6">
                    <button type="button" onclick="closeBusinessInfoModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Business Info
    function editBusinessInfo() {
        document.getElementById('businessInfoModal').classList.remove('hidden');
    }
    
    function closeBusinessInfoModal() {
        document.getElementById('businessInfoModal').classList.add('hidden');
    }
    
    document.getElementById('businessInfoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Update display values
        document.getElementById('business_name').textContent = formData.get('business_name');
        document.getElementById('business_email').textContent = formData.get('business_email');
        document.getElementById('business_phone').textContent = formData.get('business_phone');
        document.getElementById('business_address').textContent = formData.get('business_address');
        document.getElementById('vat_number').textContent = formData.get('vat_number');
        document.getElementById('registration_number').textContent = formData.get('registration_number');
        
        closeBusinessInfoModal();
        alert('Business information updated successfully!');
    });
    
    // Other settings functions (placeholders)
    function editOperatingHours() {
        alert('Operating hours editor will be implemented');
    }
    
    function editClassSettings() {
        alert('Class settings editor will be implemented');
    }
    
    function editPackageSettings() {
        alert('Package settings editor will be implemented');
    }
    
    function editNotificationSettings() {
        alert('Notification settings editor will be implemented');
    }
</script>
@endsection