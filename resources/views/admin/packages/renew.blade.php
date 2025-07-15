@extends('layouts.admin')

@section('title', 'Renew Package')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Renew Package</h1>
        <a href="{{ route('admin.packages.show', $userPackage) }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Package
        </a>
    </div>

    <!-- Current Package Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Current Package Information</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600">User</p>
                <p class="font-semibold">{{ $userPackage->user->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Current Package</p>
                <p class="font-semibold">{{ $userPackage->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Expiry Date</p>
                <p class="font-semibold">{{ $userPackage->expiry_date->format('M d, Y') }}</p>
                @if($userPackage->getDaysUntilExpiry() !== null)
                    @if($userPackage->getDaysUntilExpiry() > 0)
                        <p class="text-sm text-yellow-600">{{ $userPackage->getDaysUntilExpiry() }} days remaining</p>
                    @else
                        <p class="text-sm text-red-600">Expired {{ abs($userPackage->getDaysUntilExpiry()) }} days ago</p>
                    @endif
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-600">Remaining Sessions</p>
                <p class="font-semibold">{{ $userPackage->remaining_sessions }}</p>
            </div>
        </div>
    </div>

    <!-- Renewal Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Renewal Options</h2>
        
        <form action="{{ route('admin.packages.renew', $userPackage) }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Renewal Type
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="renewal_type" value="same" checked onchange="toggleRenewalOptions()" class="mr-2">
                        <span>Renew with same package ({{ $userPackage->package->name }})</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="renewal_type" value="different" onchange="toggleRenewalOptions()" class="mr-2">
                        <span>Upgrade/Change to different package</span>
                    </label>
                </div>
            </div>

            <div id="differentPackageOptions" class="mb-6 hidden">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="package_id">
                    Select New Package
                </label>
                <select name="package_id" id="package_id" onchange="updateNewPackageDetails()"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">-- Select Package --</option>
                    @foreach($packages as $package)
                        <option value="{{ $package->id }}" 
                            data-duration="{{ $package->duration }}"
                            data-sessions="{{ $package->sessions }}"
                            data-price="{{ $package->price }}">
                            {{ $package->name }} - €{{ number_format($package->price, 2) }}
                            @if($package->id == $userPackage->package_id)
                                (Current)
                            @endif
                        </option>
                    @endforeach
                </select>
                
                <div id="newPackageDetails" class="mt-4 p-4 bg-gray-100 rounded hidden">
                    <h3 class="font-bold mb-2">New Package Details:</h3>
                    <p><span class="font-semibold">Duration:</span> <span id="newPackageDuration"></span> days</p>
                    <p><span class="font-semibold">Sessions:</span> <span id="newPackageSessions"></span></p>
                    <p><span class="font-semibold">Price:</span> €<span id="newPackagePrice"></span></p>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="additional_sessions">
                    Additional Sessions (Optional)
                </label>
                <input type="number" name="additional_sessions" id="additional_sessions" min="0"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <p class="text-xs text-gray-500 mt-1">Add extra sessions on top of the package sessions</p>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            The new package will start from today with a new expiry date based on the package duration.
                            The current package will be marked as expired.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-sync mr-2"></i>Renew Package
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleRenewalOptions() {
    const renewalType = document.querySelector('input[name="renewal_type"]:checked').value;
    const differentPackageOptions = document.getElementById('differentPackageOptions');
    const packageSelect = document.getElementById('package_id');
    
    if (renewalType === 'different') {
        differentPackageOptions.classList.remove('hidden');
    } else {
        differentPackageOptions.classList.add('hidden');
        packageSelect.value = '';
        document.getElementById('newPackageDetails').classList.add('hidden');
    }
}

function updateNewPackageDetails() {
    const select = document.getElementById('package_id');
    const selectedOption = select.options[select.selectedIndex];
    const detailsDiv = document.getElementById('newPackageDetails');
    
    if (select.value) {
        document.getElementById('newPackageDuration').textContent = selectedOption.dataset.duration;
        document.getElementById('newPackageSessions').textContent = selectedOption.dataset.sessions;
        document.getElementById('newPackagePrice').textContent = parseFloat(selectedOption.dataset.price).toFixed(2);
        detailsDiv.classList.remove('hidden');
    } else {
        detailsDiv.classList.add('hidden');
    }
}
</script>
@endsection