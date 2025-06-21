<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .controls {
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .add-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }

        .clinics-grid {
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .clinic-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            position: relative;
            overflow: hidden;
        }

        .clinic-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .clinic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .clinic-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .clinic-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .clinic-info h3 {
            color: #2c3e50;
            font-size: 1.3em;
            margin-bottom: 5px;
        }

        .clinic-info .email {
            color: #6c757d;
            font-size: 0.9em;
        }

        .clinic-details {
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #495057;
        }

        .detail-icon {
            width: 20px;
            margin-right: 10px;
            color: #667eea;
        }

        .clinic-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }

        .btn-edit {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .no-clinics {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-clinics h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .close:hover {
            opacity: 0.7;
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Loading state for buttons */
        .btn-loading {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .alert {
            padding: 15px;
            margin: 20px 0;
            border: 1px solid transparent;
            border-radius: 8px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: none;
            }

            .clinics-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .clinic-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Clinic Management System</h1>
            <p>Manage your healthcare network efficiently</p>
        </div>

        <div class="controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search clinics by name, location, or email...">
                <span class="search-icon">🔍</span>
            </div>
            <a href="add_clinic.php" class="add-btn">
                ➕ Add New Clinic
            </a>
        </div>

        <div class="clinics-grid" id="clinicsGrid">
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading clinics...</p>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Clinic Details</h2>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Clinic</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editClinicId">
                    
                    <div class="form-group">
                        <label for="editClinicName">Clinic Name *</label>
                        <input type="text" id="editClinicName" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editClinicEmail">Email *</label>
                        <input type="email" id="editClinicEmail" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editContactNumber">Contact Number *</label>
                        <input type="tel" id="editContactNumber" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editLocation">Location *</label>
                        <input type="text" id="editLocation" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editAvailableTiming">Available Timing *</label>
                        <input type="text" id="editAvailableTiming" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editAbout">About</label>
                        <textarea id="editAbout" placeholder="Tell us about the clinic..."></textarea>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-edit" style="padding: 12px 30px;">
                            💾 Update Clinic
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let clinicsData = [];

        // Load clinics when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadClinics();
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', function() {
                filterClinics(this.value);
            });
        });

        // Load clinics from server
        async function loadClinics() {
            try {
                const response = await fetch('fetch_clinics.php');
                const data = await response.json();
                
                if (data.status === 'success') {
                    clinicsData = data.clinics;
                    displayClinics(clinicsData);
                } else {
                    showError(data.message || 'Failed to load clinics');
                }
            } catch (error) {
                console.error('Error loading clinics:', error);
                showError('Failed to load clinics. Please try again.');
            }
        }

        // Display clinics in grid
        function displayClinics(clinics) {
            const grid = document.getElementById('clinicsGrid');
            
            if (clinics.length === 0) {
                grid.innerHTML = `
                    <div class="no-clinics">
                        <h3>No clinics found</h3>
                        <p>Try adjusting your search or add a new clinic.</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = clinics.map(clinic => `
                <div class="clinic-card">
                    <div class="clinic-header">
                        <div class="clinic-avatar">
                            ${clinic.clinic_name.charAt(0).toUpperCase()}
                        </div>
                        <div class="clinic-info">
                            <h3>${escapeHtml(clinic.clinic_name)}</h3>
                            <div class="email">${escapeHtml(clinic.clinic_email)}</div>
                        </div>
                    </div>
                    
                    <div class="clinic-details">
                        <div class="detail-item">
                            <span class="detail-icon">📞</span>
                            <span>${escapeHtml(clinic.contact_number)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">📍</span>
                            <span>${escapeHtml(clinic.location)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">🕒</span>
                            <span>${escapeHtml(clinic.available_timing)}</span>
                        </div>
                    </div>
                    
                    <div class="clinic-actions">
                        <button class="btn btn-view" onclick="viewClinic(${clinic.clinic_id})">
                            👁️ View
                        </button>
                        <button class="btn btn-edit" onclick="editClinic(${clinic.clinic_id})">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-delete" onclick="confirmDelete(${clinic.clinic_id}, '${escapeForAttribute(clinic.clinic_name)}')">
                            🗑️ Delete
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Filter clinics based on search
        function filterClinics(searchTerm) {
            if (!searchTerm.trim()) {
                displayClinics(clinicsData);
                return;
            }

            const filtered = clinicsData.filter(clinic => 
                clinic.clinic_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                clinic.clinic_email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                clinic.location.toLowerCase().includes(searchTerm.toLowerCase())
            );

            displayClinics(filtered);
        }

        // View clinic details
        function viewClinic(clinicId) {
            const clinic = clinicsData.find(c => c.clinic_id == clinicId);
            if (!clinic) {
                alert('Clinic not found');
                return;
            }

            const modalBody = document.getElementById('viewModalBody');
            modalBody.innerHTML = `
                <div style="text-align: center; margin-bottom: 30px;">
                    <div class="clinic-avatar" style="width: 80px; height: 80px; font-size: 32px; margin: 0 auto 15px;">
                        ${clinic.clinic_name.charAt(0).toUpperCase()}
                    </div>
                    <h2 style="color: #2c3e50; margin-bottom: 5px;">${escapeHtml(clinic.clinic_name)}</h2>
                    <p style="color: #6c757d;">${escapeHtml(clinic.clinic_email)}</p>
                </div>
                
                <div class="clinic-details">
                    <div class="detail-item" style="margin-bottom: 15px; font-size: 16px;">
                        <span class="detail-icon">📞</span>
                        <span><strong>Contact:</strong> ${escapeHtml(clinic.contact_number)}</span>
                    </div>
                    <div class="detail-item" style="margin-bottom: 15px; font-size: 16px;">
                        <span class="detail-icon">📍</span>
                        <span><strong>Location:</strong> ${escapeHtml(clinic.location)}</span>
                    </div>
                    <div class="detail-item" style="margin-bottom: 15px; font-size: 16px;">
                        <span class="detail-icon">🕒</span>
                        <span><strong>Available Timing:</strong> ${escapeHtml(clinic.available_timing)}</span>
                    </div>
                    ${clinic.about ? `
                        <div class="detail-item" style="margin-bottom: 15px; font-size: 16px;">
                            <span class="detail-icon">📝</span>
                            <span><strong>About:</strong> ${escapeHtml(clinic.about)}</span>
                        </div>
                    ` : ''}
                    <div class="detail-item" style="margin-bottom: 15px; font-size: 16px;">
                        <span class="detail-icon">📅</span>
                        <span><strong>Registered:</strong> ${new Date(clinic.created_at).toLocaleDateString()}</span>
                    </div>
                </div>
            `;
            
            document.getElementById('viewModal').style.display = 'block';
        }

        // Edit clinic
        function editClinic(clinicId) {
            const clinic = clinicsData.find(c => c.clinic_id == clinicId);
            if (!clinic) {
                alert('Clinic not found');
                return;
            }

            // Populate form
            document.getElementById('editClinicId').value = clinic.clinic_id;
            document.getElementById('editClinicName').value = clinic.clinic_name;
            document.getElementById('editClinicEmail').value = clinic.clinic_email;
            document.getElementById('editContactNumber').value = clinic.contact_number;
            document.getElementById('editLocation').value = clinic.location;
            document.getElementById('editAvailableTiming').value = clinic.available_timing;
            document.getElementById('editAbout').value = clinic.about || '';

            document.getElementById('editModal').style.display = 'block';
        }

        // Handle edit form submission
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '⏳ Updating...';
            submitBtn.classList.add('btn-loading');
            
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('clinic_id', document.getElementById('editClinicId').value);
            formData.append('clinic_name', document.getElementById('editClinicName').value);
            formData.append('clinic_email', document.getElementById('editClinicEmail').value);
            formData.append('contact_number', document.getElementById('editContactNumber').value);
            formData.append('location', document.getElementById('editLocation').value);
            formData.append('available_timing', document.getElementById('editAvailableTiming').value);
            formData.append('about', document.getElementById('editAbout').value);

            try {
                const response = await fetch('manage_clinic.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('Clinic updated successfully!');
                    closeModal('editModal');
                    loadClinics(); // Reload the list
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error updating clinic:', error);
                alert('Failed to update clinic. Please try again.');
            } finally {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.classList.remove('btn-loading');
            }
        });

        // Confirm delete - separate function to avoid HTML attribute issues
        function confirmDelete(clinicId, clinicName) {
            if (confirm(`Are you sure you want to delete "${clinicName}"?\n\nThis action cannot be undone and will fail if there are:\n- Existing appointments\n- Associated doctors\n\nClick OK to proceed or Cancel to abort.`)) {
                deleteClinic(clinicId, clinicName);
            }
        }

        // Delete clinic - improved with better error handling
        async function deleteClinic(clinicId, clinicName) {
            console.log('Attempting to delete clinic:', clinicId, clinicName);
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('clinic_id', clinicId);

                console.log('Sending delete request...');
                
                const response = await fetch('manage_clinic.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.status === 'success') {
                    alert('Clinic deleted successfully!');
                    loadClinics(); // Reload the list
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            } catch (error) {
                console.error('Error deleting clinic:', error);
                alert('Failed to delete clinic. Please check the console for details and try again.');
            }
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Utility function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Utility function to escape HTML attributes
        function escapeForAttribute(text) {
            if (!text) return '';
            return text.replace(/'/g, "&#39;").replace(/"/g, "&quot;");
        }

        // Show error message
        function showError(message) {
            document.getElementById('clinicsGrid').innerHTML = `
                <div class="no-clinics">
                    <h3>Error</h3>
                    <p>${message}</p>
                    <button onclick="loadClinics()" class="btn btn-view" style="margin-top: 15px;">
                        🔄 Retry
                    </button>
                </div>
            `;
        }

        // Show success message
        function showSuccess(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success';
            alertDiv.textContent = message;
            document.body.insertBefore(alertDiv, document.body.firstChild);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    </script>
</body>
</html>