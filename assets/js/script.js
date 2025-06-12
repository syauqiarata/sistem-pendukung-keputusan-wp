// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 3000);
    });
});

// Form validation function
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            showError(input, 'Field ini harus diisi');
        } else {
            removeError(input);
            
            // Additional validation based on type
            if (input.type === 'number') {
                const val = parseFloat(input.value);
                const min = parseFloat(input.getAttribute('min'));
                const max = parseFloat(input.getAttribute('max'));
                
                if (min !== null && val < min) {
                    isValid = false;
                    showError(input, `Nilai minimum adalah ${min}`);
                } else if (max !== null && val > max) {
                    isValid = false;
                    showError(input, `Nilai maksimum adalah ${max}`);
                }
            }
        }
    });
    
    return isValid;
}

// Show error message
function showError(input, message) {
    removeError(input);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '0.25rem';
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
    input.style.borderColor = '#dc3545';
}

// Remove error message
function removeError(input) {
    const errorDiv = input.parentNode.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
        input.style.borderColor = '';
    }
}

// Modal handling
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Table sorting
function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = table.getAttribute('data-sort') !== 'asc';
    
    rows.sort((a, b) => {
        let aVal = a.cells[column].textContent.trim();
        let bVal = b.cells[column].textContent.trim();
        
        // Check if values are numbers
        if (!isNaN(aVal) && !isNaN(bVal)) {
            aVal = parseFloat(aVal);
            bVal = parseFloat(bVal);
        }
        
        if (aVal < bVal) return isAscending ? -1 : 1;
        if (aVal > bVal) return isAscending ? 1 : -1;
        return 0;
    });
    
    rows.forEach(row => tbody.appendChild(row));
    table.setAttribute('data-sort', isAscending ? 'asc' : 'desc');
}

// Format number with specified decimal places
function formatNumber(number, decimals = 4) {
    return Number(number).toFixed(decimals);
}

// Chart creation for hasil page
function createChart(labels, values, elementId) {
    const ctx = document.getElementById(elementId).getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nilai V',
                data: values,
                backgroundColor: 'rgba(52, 152, 219, 0.5)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Dynamic form fields
function addKriteriaField() {
    const container = document.getElementById('kriteriaContainer');
    const index = container.children.length;
    
    const row = document.createElement('div');
    row.className = 'row form-group kriteria-row';
    row.innerHTML = `
        <div class="col">
            <select name="kriteria_id[]" class="form-control" required>
                <option value="">Pilih Kriteria</option>
                ${getKriteriaOptions()}
            </select>
        </div>
        <div class="col">
            <input type="number" name="nilai[]" class="form-control" 
                   step="0.01" min="0" required placeholder="Nilai">
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-danger" onclick="removeKriteriaField(this)">
                Hapus
            </button>
        </div>
    `;
    
    container.appendChild(row);
}

function removeKriteriaField(button) {
    button.closest('.kriteria-row').remove();
}

// AJAX request helper
async function fetchData(url, options = {}) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}
