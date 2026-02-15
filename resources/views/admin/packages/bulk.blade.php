@extends('layouts.admin')

@section('title', 'Bulk Package Operations')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Bulk Package Operations</h1>
        <div class="flex gap-4">
            <a href="{{ route('admin.packages.bulk.history') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-history mr-2"></i>Operation History
            </a>
            <a href="{{ route('admin.packages.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Packages
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Packages</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['total_packages'] }}</p>
                </div>
                <i class="fas fa-box text-3xl text-blue-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active Packages</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['active_packages'] }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Expiring Soon</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['expiring_soon'] }}</p>
                </div>
                <i class="fas fa-exclamation-triangle text-3xl text-yellow-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Expired</p>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['expired_packages'] }}</p>
                </div>
                <i class="fas fa-times-circle text-3xl text-red-500"></i>
            </div>
        </div>
    </div>

    <!-- Operation Tabs -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button class="tab-button py-2 px-1 border-b-2 font-medium text-sm text-blue-600 border-blue-500" data-tab="extension">
                    Package Extension
                </button>
                <button class="tab-button py-2 px-1 border-b-2 font-medium text-sm text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300" data-tab="pricing">
                    Pricing Adjustments
                </button>
            </nav>
        </div>

        <!-- Package Extension Tab -->
        <div id="extension-tab" class="tab-content p-6">
            <form id="bulk-extension-form">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Filters Column -->
                    <div class="lg:col-span-1">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Package Filters</h3>
                        
                        <!-- Status Filter -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="filters[status]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="expiring_soon">Expiring Soon</option>
                                <option value="expired">Expired</option>
                                <option value="frozen">Frozen</option>
                            </select>
                        </div>

                        <!-- Package Type Filter -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Package Type</label>
                            <select name="filters[package_id]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Packages</option>
                                @foreach($packages as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- User Search -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">User Search</label>
                            <input type="text" name="filters[user_search]" placeholder="Search by name or email..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Expiry Date Range -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date Range</label>
                            <div class="grid grid-cols-1 gap-2">
                                <input type="date" name="filters[expiry_from]" placeholder="From" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="date" name="filters[expiry_to]" placeholder="To" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Sessions Range -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sessions Range</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="filters[min_sessions]" placeholder="Min" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="number" name="filters[max_sessions]" placeholder="Max" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Auto-Renew Filter -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="filters[auto_renew]" value="1" class="mr-2">
                                <span class="text-sm text-gray-700">Auto-renew enabled only</span>
                            </label>
                        </div>

                        <!-- Filter Actions -->
                        <div class="flex gap-2">
                            <button type="button" id="preview-filters" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                                <i class="fas fa-search mr-2"></i>Preview
                            </button>
                            <button type="button" id="clear-filters" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                                <i class="fas fa-times mr-2"></i>Clear
                            </button>
                        </div>
                    </div>

                    <!-- Extension Options Column -->
                    <div class="lg:col-span-1">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Extension Options</h3>
                        
                        <!-- Date Extensions -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Extend by Duration</label>
                            <div class="grid grid-cols-3 gap-2">
                                <input type="number" name="extension[extend_days]" placeholder="Days" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="number" name="extension[extend_weeks]" placeholder="Weeks" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="number" name="extension[extend_months]" placeholder="Months" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Or Set Specific Date -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Or Set Specific Expiry Date</label>
                            <input type="date" name="extension[set_expiry_date]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Sessions -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sessions</label>
                            <div class="grid grid-cols-1 gap-2">
                                <input type="number" name="extension[add_sessions]" placeholder="Add sessions" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="number" name="extension[set_sessions]" placeholder="Set total sessions" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Discounts -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discounts</label>
                            <div class="grid grid-cols-1 gap-2">
                                <input type="number" name="extension[discount_amount]" placeholder="Discount amount ($)" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="number" name="extension[discount_percentage]" placeholder="Discount percentage (%)" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Notification Option -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="send_notifications" value="1" checked class="mr-2">
                                <span class="text-sm text-gray-700">Send notifications to users</span>
                            </label>
                        </div>
                    </div>

                    <!-- Preview Column -->
                    <div class="lg:col-span-1">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Preview</h3>
                        
                        <div id="preview-container" class="bg-gray-50 rounded-lg p-4 min-h-48">
                            <p class="text-gray-500 text-center">Select filters and extension options to see preview</p>
                        </div>
                        
                        <div class="mt-4 space-y-2">
                            <button type="button" id="preview-extension" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                                <i class="fas fa-eye mr-2"></i>Preview Extension
                            </button>
                            <button type="button" id="execute-extension" class="w-full bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition" disabled>
                                <i class="fas fa-play mr-2"></i>Execute Extension
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Pricing Adjustments Tab -->
        <div id="pricing-tab" class="tab-content p-6 hidden">
            <form id="bulk-pricing-form">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Filters Column (Same as extension) -->
                    <div class="lg:col-span-1">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Package Filters</h3>
                        
                        <!-- Status Filter -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="filters[status]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="expiring_soon">Expiring Soon</option>
                                <option value="expired">Expired</option>
                                <option value="frozen">Frozen</option>
                            </select>
                        </div>

                        <!-- Package Type Filter -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Package Type</label>
                            <select name="filters[package_id]" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Packages</option>
                                @foreach($packages as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- User Search -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">User Search</label>
                            <input type="text" name="filters[user_search]" placeholder="Search by name or email..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Expiry Date Range -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date Range</label>
                            <div class="grid grid-cols-1 gap-2">
                                <input type="date" name="filters[expiry_from]" placeholder="From" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="date" name="filters[expiry_to]" placeholder="To" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Sessions Range -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sessions Range</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="filters[min_sessions]" placeholder="Min" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="number" name="filters[max_sessions]" placeholder="Max" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Auto-Renew Filter -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="filters[auto_renew]" value="1" class="mr-2">
                                <span class="text-sm text-gray-700">Auto-renew enabled only</span>
                            </label>
                        </div>

                        <!-- Filter Actions -->
                        <div class="flex gap-2">
                            <button type="button" id="preview-pricing-filters" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                                <i class="fas fa-search mr-2"></i>Preview
                            </button>
                            <button type="button" id="clear-pricing-filters" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                                <i class="fas fa-times mr-2"></i>Clear
                            </button>
                        </div>
                    </div>

                    <!-- Pricing Options Column -->
                    <div class="lg:col-span-1">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Pricing Options</h3>
                        
                        <!-- Discount Amount -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discount Amount ($)</label>
                            <input type="number" name="pricing[discount_amount]" placeholder="Enter discount amount" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Discount Percentage -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discount Percentage (%)</label>
                            <input type="number" name="pricing[discount_percentage]" placeholder="Enter discount percentage" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Adjustment Reason -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Reason *</label>
                            <textarea name="pricing[adjustment_reason]" rows="3" placeholder="Enter reason for pricing adjustment..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <!-- Apply to Renewals -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="pricing[apply_to_renewals]" value="1" class="mr-2">
                                <span class="text-sm text-gray-700">Apply to future renewals</span>
                            </label>
                        </div>

                        <!-- Notification Option -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="send_notifications" value="1" checked class="mr-2">
                                <span class="text-sm text-gray-700">Send notifications to users</span>
                            </label>
                        </div>
                    </div>

                    <!-- Preview Column -->
                    <div class="lg:col-span-1">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Preview</h3>
                        
                        <div id="pricing-preview-container" class="bg-gray-50 rounded-lg p-4 min-h-48">
                            <p class="text-gray-500 text-center">Select filters and pricing options to see preview</p>
                        </div>
                        
                        <div class="mt-4 space-y-2">
                            <button type="button" id="preview-pricing" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                                <i class="fas fa-eye mr-2"></i>Preview Pricing
                            </button>
                            <button type="button" id="execute-pricing" class="w-full bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition" disabled>
                                <i class="fas fa-play mr-2"></i>Execute Pricing
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div id="progress-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Operation Progress</h3>
            <button type="button" id="close-progress" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Progress</span>
                <span id="progress-percentage" class="text-sm font-medium text-gray-700">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>
        
        <div class="mb-4">
            <p id="progress-status" class="text-sm text-gray-600">Preparing operation...</p>
        </div>
        
        <div class="flex justify-end space-x-2">
            <button type="button" id="cancel-operation" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Confirm Operation</h3>
            <button type="button" id="close-confirmation" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <p class="text-sm text-gray-600 mb-4">Are you sure you want to proceed with this bulk operation?</p>
            <div id="confirmation-summary" class="bg-gray-50 rounded-lg p-4">
                <!-- Summary will be populated by JavaScript -->
            </div>
        </div>
        
        <div class="flex justify-end space-x-2">
            <button type="button" id="cancel-confirmation" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                Cancel
            </button>
            <button type="button" id="confirm-operation" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                Confirm
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/bulk-operations.js') }}"></script>
@endsection