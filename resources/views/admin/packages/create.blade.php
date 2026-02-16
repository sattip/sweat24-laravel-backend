@extends('layouts.admin')

@section('title', 'Assign Package')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Assign Package to User</h1>
        <a href="{{ route('admin.packages.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.packages.store') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="user_id">
                    Select User <span class="text-red-500">*</span>
                </label>
                <select name="user_id" id="user_id" required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('user_id') border-red-500 @enderror">
                    <option value="">-- Select User --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="package_id">
                    Select Package <span class="text-red-500">*</span>
                </label>
                <select name="package_id" id="package_id" required onchange="updatePackageDetails()"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('package_id') border-red-500 @enderror">
                    <option value="">-- Select Package --</option>
                    @foreach($packages as $package)
                        <option value="{{ $package->id }}" 
                            data-duration="{{ $package->duration }}"
                            data-sessions="{{ $package->sessions }}"
                            data-price="{{ $package->price }}"
                            {{ old('package_id') == $package->id ? 'selected' : '' }}>
                            {{ $package->name }} - €{{ number_format($package->price, 2) }}
                        </option>
                    @endforeach
                </select>
                @error('package_id')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div id="packageDetails" class="mb-4 p-4 bg-gray-100 rounded hidden">
                <h3 class="font-bold mb-2">Package Details:</h3>
                <p><span class="font-semibold">Duration:</span> <span id="packageDuration"></span> days</p>
                <p><span class="font-semibold">Sessions:</span> <span id="packageSessions"></span></p>
                <p><span class="font-semibold">Price:</span> €<span id="packagePrice"></span></p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="expiry_date">
                    Custom Expiry Date (Optional)
                </label>
                <input type="date" name="expiry_date" id="expiry_date" 
                    value="{{ old('expiry_date') }}"
                    min="{{ now()->addDay()->format('Y-m-d') }}"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('expiry_date') border-red-500 @enderror">
                <p class="text-xs text-gray-500 mt-1">Leave empty to use package default duration</p>
                @error('expiry_date')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="auto_renew" value="1" {{ old('auto_renew') ? 'checked' : '' }}
                        class="mr-2 leading-tight">
                    <span class="text-sm">
                        Enable Auto-Renewal
                    </span>
                </label>
                <p class="text-xs text-gray-500 mt-1">Package will automatically renew 1 day before expiry</p>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-save mr-2"></i>Assign Package
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updatePackageDetails() {
    const select = document.getElementById('package_id');
    const selectedOption = select.options[select.selectedIndex];
    const detailsDiv = document.getElementById('packageDetails');
    
    if (select.value) {
        document.getElementById('packageDuration').textContent = selectedOption.dataset.duration;
        document.getElementById('packageSessions').textContent = selectedOption.dataset.sessions;
        document.getElementById('packagePrice').textContent = parseFloat(selectedOption.dataset.price).toFixed(2);
        detailsDiv.classList.remove('hidden');
        
        // Update suggested expiry date
        const duration = parseInt(selectedOption.dataset.duration);
        const expiryDate = new Date();
        expiryDate.setDate(expiryDate.getDate() + duration);
        document.getElementById('expiry_date').placeholder = `Default: ${expiryDate.toISOString().split('T')[0]}`;
    } else {
        detailsDiv.classList.add('hidden');
    }
}

// Initialize on page load if package is already selected
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('package_id').value) {
        updatePackageDetails();
    }
});
</script>
@endsection