document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let currentConfirmationToken = null;
    let currentOperationId = null;
    let progressInterval = null;

    // DOM elements
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const progressModal = document.getElementById('progress-modal');
    const confirmationModal = document.getElementById('confirmation-modal');
    const previewContainer = document.getElementById('preview-container');
    const pricingPreviewContainer = document.getElementById('pricing-preview-container');

    // Initialize tabs
    initializeTabs();

    // Extension tab event listeners
    initializeExtensionTab();

    // Pricing tab event listeners
    initializePricingTab();

    // Modal event listeners
    initializeModals();

    function initializeTabs() {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                switchTab(tabName);
            });
        });
    }

    function switchTab(tabName) {
        // Update tab buttons
        tabButtons.forEach(btn => {
            btn.classList.remove('text-blue-600', 'border-blue-500');
            btn.classList.add('text-gray-500', 'border-transparent');
        });

        // Update tab contents
        tabContents.forEach(content => {
            content.classList.add('hidden');
        });

        // Show selected tab
        const selectedButton = document.querySelector(`[data-tab="${tabName}"]`);
        const selectedContent = document.getElementById(`${tabName}-tab`);

        if (selectedButton) {
            selectedButton.classList.remove('text-gray-500', 'border-transparent');
            selectedButton.classList.add('text-blue-600', 'border-blue-500');
        }

        if (selectedContent) {
            selectedContent.classList.remove('hidden');
        }
    }

    function initializeExtensionTab() {
        // Preview filters
        document.getElementById('preview-filters').addEventListener('click', function() {
            previewFilteredPackages('extension');
        });

        // Clear filters
        document.getElementById('clear-filters').addEventListener('click', function() {
            clearForm('bulk-extension-form');
            previewContainer.innerHTML = '<p class="text-gray-500 text-center">Select filters and extension options to see preview</p>';
        });

        // Preview extension
        document.getElementById('preview-extension').addEventListener('click', function() {
            previewExtension();
        });

        // Execute extension
        document.getElementById('execute-extension').addEventListener('click', function() {
            showConfirmationModal('extension');
        });
    }

    function initializePricingTab() {
        // Preview pricing filters
        document.getElementById('preview-pricing-filters').addEventListener('click', function() {
            previewFilteredPackages('pricing');
        });

        // Clear pricing filters
        document.getElementById('clear-pricing-filters').addEventListener('click', function() {
            clearForm('bulk-pricing-form');
            pricingPreviewContainer.innerHTML = '<p class="text-gray-500 text-center">Select filters and pricing options to see preview</p>';
        });

        // Preview pricing
        document.getElementById('preview-pricing').addEventListener('click', function() {
            previewPricing();
        });

        // Execute pricing
        document.getElementById('execute-pricing').addEventListener('click', function() {
            showConfirmationModal('pricing');
        });
    }

    function initializeModals() {
        // Close progress modal
        document.getElementById('close-progress').addEventListener('click', function() {
            hideProgressModal();
        });

        // Cancel operation
        document.getElementById('cancel-operation').addEventListener('click', function() {
            if (currentOperationId) {
                cancelOperation(currentOperationId);
            }
        });

        // Close confirmation modal
        document.getElementById('close-confirmation').addEventListener('click', function() {
            hideConfirmationModal();
        });

        // Cancel confirmation
        document.getElementById('cancel-confirmation').addEventListener('click', function() {
            hideConfirmationModal();
        });

        // Confirm operation
        document.getElementById('confirm-operation').addEventListener('click', function() {
            executeOperation();
        });
    }

    function previewFilteredPackages(type) {
        const formId = type === 'extension' ? 'bulk-extension-form' : 'bulk-pricing-form';
        const form = document.getElementById(formId);
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        fetch('/admin/packages/bulk/filtered-packages?' + new URLSearchParams(data))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = type === 'extension' ? previewContainer : pricingPreviewContainer;
                    displayFilteredPackages(data.count, data.packages, container);
                } else {
                    showError(data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('An error occurred while fetching filtered packages.');
            });
    }

    function displayFilteredPackages(count, packages, container) {
        let html = `
            <div class="mb-4">
                <h4 class="text-lg font-semibold text-gray-900">Filtered Results</h4>
                <p class="text-sm text-gray-600">${count} packages match your criteria</p>
            </div>
        `;

        if (packages && packages.length > 0) {
            html += '<div class="space-y-2 max-h-64 overflow-y-auto">';
            packages.forEach(pkg => {
                html += `
                    <div class="bg-white border rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900">${pkg.user_name}</p>
                                <p class="text-xs text-gray-500">${pkg.user_email}</p>
                                <p class="text-xs text-gray-600">${pkg.package_name}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Expires: ${pkg.current_expiry}</p>
                                <p class="text-xs text-gray-500">Sessions: ${pkg.current_sessions}</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        container.innerHTML = html;
    }

    function previewExtension() {
        const form = document.getElementById('bulk-extension-form');
        const formData = new FormData(form);

        // Add preview flag
        formData.append('preview_only', '1');

        fetch('/admin/packages/bulk/preview-extension', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayExtensionPreview(data.preview, data.confirmation_token);
            } else {
                showError(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while previewing extension.');
        });
    }

    function displayExtensionPreview(preview, confirmationToken) {
        currentConfirmationToken = confirmationToken;
        
        let html = `
            <div class="mb-4">
                <h4 class="text-lg font-semibold text-gray-900">Extension Preview</h4>
                <p class="text-sm text-gray-600">${preview.affected_count} packages will be affected</p>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <h5 class="font-medium text-blue-900">Summary</h5>
                <ul class="text-sm text-blue-800 mt-1">
                    <li>Total packages: ${preview.summary.total_packages}</li>
                    <li>Avg extension: ${preview.summary.avg_extension_days} days</li>
                    <li>Sessions added: ${preview.summary.total_sessions_added}</li>
                    <li>Price adjustment: $${preview.summary.total_price_adjustment}</li>
                </ul>
            </div>
        `;

        if (preview.packages && preview.packages.length > 0) {
            html += '<div class="space-y-2 max-h-64 overflow-y-auto">';
            preview.packages.slice(0, 5).forEach(pkg => {
                html += `
                    <div class="bg-white border rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900">${pkg.user_name}</p>
                                <p class="text-xs text-gray-500">${pkg.package_name}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">
                                    ${pkg.current_expiry} → ${pkg.new_expiry}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Sessions: ${pkg.current_sessions} → ${pkg.new_sessions}
                                </p>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            if (preview.packages.length > 5) {
                html += `<p class="text-sm text-gray-500 mt-2">... and ${preview.packages.length - 5} more packages</p>`;
            }
        }

        previewContainer.innerHTML = html;
        
        // Enable execute button
        document.getElementById('execute-extension').disabled = false;
    }

    function previewPricing() {
        const form = document.getElementById('bulk-pricing-form');
        const formData = new FormData(form);

        // Add preview flag
        formData.append('preview_only', '1');

        fetch('/admin/packages/bulk/preview-pricing', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPricingPreview(data.preview, data.confirmation_token);
            } else {
                showError(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while previewing pricing.');
        });
    }

    function displayPricingPreview(preview, confirmationToken) {
        currentConfirmationToken = confirmationToken;
        
        let html = `
            <div class="mb-4">
                <h4 class="text-lg font-semibold text-gray-900">Pricing Preview</h4>
                <p class="text-sm text-gray-600">${preview.affected_count} packages will be affected</p>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                <h5 class="font-medium text-green-900">Summary</h5>
                <ul class="text-sm text-green-800 mt-1">
                    <li>Total packages: ${preview.summary.total_packages}</li>
                    <li>Total adjustment: $${preview.summary.total_price_adjustment}</li>
                </ul>
            </div>
        `;

        if (preview.packages && preview.packages.length > 0) {
            html += '<div class="space-y-2 max-h-64 overflow-y-auto">';
            preview.packages.slice(0, 5).forEach(pkg => {
                html += `
                    <div class="bg-white border rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900">${pkg.user_name}</p>
                                <p class="text-xs text-gray-500">${pkg.package_name}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">
                                    Adjustment: $${pkg.price_adjustment}
                                </p>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            if (preview.packages.length > 5) {
                html += `<p class="text-sm text-gray-500 mt-2">... and ${preview.packages.length - 5} more packages</p>`;
            }
        }

        pricingPreviewContainer.innerHTML = html;
        
        // Enable execute button
        document.getElementById('execute-pricing').disabled = false;
    }

    function showConfirmationModal(type) {
        if (!currentConfirmationToken) {
            showError('Please preview the operation first');
            return;
        }

        const summary = type === 'extension' ? 
            document.getElementById('preview-container').innerHTML :
            document.getElementById('pricing-preview-container').innerHTML;

        document.getElementById('confirmation-summary').innerHTML = summary;
        confirmationModal.classList.remove('hidden');
        confirmationModal.classList.add('flex');
    }

    function hideConfirmationModal() {
        confirmationModal.classList.add('hidden');
        confirmationModal.classList.remove('flex');
    }

    function executeOperation() {
        if (!currentConfirmationToken) {
            showError('No confirmation token available');
            return;
        }

        hideConfirmationModal();
        showProgressModal();

        // Determine operation type
        const activeTab = document.querySelector('.tab-button.text-blue-600').dataset.tab;
        const endpoint = activeTab === 'extension' ? 
            '/admin/packages/bulk/execute-extension' : 
            '/admin/packages/bulk/execute-pricing';

        const formId = activeTab === 'extension' ? 'bulk-extension-form' : 'bulk-pricing-form';
        const form = document.getElementById(formId);
        const formData = new FormData(form);

        // Add confirmation token
        formData.append('confirmation_token', currentConfirmationToken);

        fetch(endpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentOperationId = data.result.bulk_operation_id;
                startProgressTracking(currentOperationId);
            } else {
                hideProgressModal();
                showError(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            hideProgressModal();
            showError('An error occurred while executing the operation.');
        });
    }

    function showProgressModal() {
        progressModal.classList.remove('hidden');
        progressModal.classList.add('flex');
        
        // Reset progress
        document.getElementById('progress-bar').style.width = '0%';
        document.getElementById('progress-percentage').textContent = '0%';
        document.getElementById('progress-status').textContent = 'Initializing...';
    }

    function hideProgressModal() {
        progressModal.classList.add('hidden');
        progressModal.classList.remove('flex');
        
        if (progressInterval) {
            clearInterval(progressInterval);
            progressInterval = null;
        }
    }

    function startProgressTracking(operationId) {
        progressInterval = setInterval(() => {
            fetch(`/admin/packages/bulk/operation/${operationId}/status`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateProgress(data.status);
                        
                        // Check if operation is complete
                        if (data.status.status === 'completed' || 
                            data.status.status === 'completed_with_errors' ||
                            data.status.status === 'failed' ||
                            data.status.status === 'cancelled') {
                            clearInterval(progressInterval);
                            progressInterval = null;
                            
                            setTimeout(() => {
                                hideProgressModal();
                                showOperationResult(data.status);
                            }, 1000);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error tracking progress:', error);
                });
        }, 1000);
    }

    function updateProgress(status) {
        const progressBar = document.getElementById('progress-bar');
        const progressPercentage = document.getElementById('progress-percentage');
        const progressStatus = document.getElementById('progress-status');

        progressBar.style.width = status.progress_percentage + '%';
        progressPercentage.textContent = status.progress_percentage + '%';
        
        let statusText = '';
        switch (status.status) {
            case 'pending':
                statusText = 'Preparing operation...';
                break;
            case 'in_progress':
                statusText = `Processing ${status.successful_count + status.failed_count} of ${status.target_count}...`;
                break;
            case 'completed':
                statusText = 'Operation completed successfully!';
                break;
            case 'completed_with_errors':
                statusText = `Operation completed with ${status.failed_count} errors`;
                break;
            case 'failed':
                statusText = 'Operation failed';
                break;
            case 'cancelled':
                statusText = 'Operation cancelled';
                break;
        }
        
        progressStatus.textContent = statusText;
    }

    function showOperationResult(status) {
        let message = '';
        let type = 'success';

        switch (status.status) {
            case 'completed':
                message = `Operation completed successfully! ${status.successful_count} packages were processed.`;
                type = 'success';
                break;
            case 'completed_with_errors':
                message = `Operation completed with errors. ${status.successful_count} packages were processed, ${status.failed_count} failed.`;
                type = 'warning';
                break;
            case 'failed':
                message = 'Operation failed. Please check the logs for details.';
                type = 'error';
                break;
            case 'cancelled':
                message = 'Operation was cancelled.';
                type = 'info';
                break;
        }

        showAlert(message, type);
        
        // Reset form and preview
        clearForm(document.querySelector('.tab-content:not(.hidden) form').id);
        previewContainer.innerHTML = '<p class="text-gray-500 text-center">Select filters and extension options to see preview</p>';
        pricingPreviewContainer.innerHTML = '<p class="text-gray-500 text-center">Select filters and pricing options to see preview</p>';
        
        // Disable execute buttons
        document.getElementById('execute-extension').disabled = true;
        document.getElementById('execute-pricing').disabled = true;
        
        // Reset confirmation token
        currentConfirmationToken = null;
    }

    function cancelOperation(operationId) {
        fetch(`/admin/packages/bulk/operation/${operationId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                clearInterval(progressInterval);
                progressInterval = null;
                hideProgressModal();
                showAlert('Operation cancelled successfully', 'info');
            } else {
                showError(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while cancelling the operation.');
        });
    }

    function clearForm(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
        }
    }

    function showError(message) {
        showAlert(message, 'error');
    }

    function showAlert(message, type) {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `fixed top-4 right-4 z-50 max-w-md p-4 rounded-lg shadow-lg ${getAlertClasses(type)}`;
        alertDiv.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas ${getAlertIcon(type)}"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button class="inline-flex text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(alertDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.parentElement.removeChild(alertDiv);
            }
        }, 5000);
    }

    function getAlertClasses(type) {
        switch (type) {
            case 'success':
                return 'bg-green-50 border border-green-200 text-green-800';
            case 'error':
                return 'bg-red-50 border border-red-200 text-red-800';
            case 'warning':
                return 'bg-yellow-50 border border-yellow-200 text-yellow-800';
            case 'info':
                return 'bg-blue-50 border border-blue-200 text-blue-800';
            default:
                return 'bg-gray-50 border border-gray-200 text-gray-800';
        }
    }

    function getAlertIcon(type) {
        switch (type) {
            case 'success':
                return 'fa-check-circle';
            case 'error':
                return 'fa-exclamation-circle';
            case 'warning':
                return 'fa-exclamation-triangle';
            case 'info':
                return 'fa-info-circle';
            default:
                return 'fa-info-circle';
        }
    }
});