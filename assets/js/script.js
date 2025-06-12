// Form validation and submission handling
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
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

    // Flash message auto-hide
    const flashMessages = document.querySelectorAll('.alert-dismissible');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.remove();
        }, 5000);
    });

    // Dynamic form fields for penilaian
    const addKriteriaBtn = document.getElementById('addKriteria');
    const kriteriaContainer = document.getElementById('kriteriaContainer');
    
    if (addKriteriaBtn && kriteriaContainer) {
        addKriteriaBtn.addEventListener('click', function() {
            const index = kriteriaContainer.children.length;
            const template = `
                <div class="row mb-3 kriteria-row">
                    <div class="col-md-5">
                        <select name="kriteria_id[]" class="form-control" required>
                            <option value="">Pilih Kriteria</option>
                            ${getKriteriaOptions()}
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="number" name="nilai[]" class="form-control" 
                               step="0.01" min="0" required placeholder="Nilai">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-remove-kriteria">
                            Hapus
                        </button>
                    </div>
                </div>
            `;
            kriteriaContainer.insertAdjacentHTML('beforeend', template);
        });

        // Remove kriteria row
        kriteriaContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-kriteria')) {
                e.target.closest('.kriteria-row').remove();
            }
        });
    }

    // Calculate button handler
    const calculateBtn = document.getElementById('calculateBtn');
    if (calculateBtn) {
        calculateBtn.addEventListener('click', async function() {
            try {
                calculateBtn.disabled = true;
                calculateBtn.innerHTML = '<span class="spinner"></span> Menghitung...';
                
                const response = await fetch('calculate.php');
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'hasil.php';
                } else {
                    alert(result.message || 'Terjadi kesalahan saat menghitung.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghitung.');
            } finally {
                calculateBtn.disabled = false;
                calculateBtn.innerHTML = 'Hitung';
            }
        });
    }
});

// Helper function to format numbers
function formatNumber(number, decimals = 2) {
    return Number(number).toFixed(decimals);
}

// Helper function to get kriteria options from the server
async function getKriteriaOptions() {
    try {
        const response = await fetch('get_kriteria.php');
        const kriteria = await response.json();
        return kriteria.map(k => 
            `<option value="${k.id}">${k.nama} (Bobot: ${k.bobot})</option>`
        ).join('');
    } catch (error) {
        console.error('Error fetching kriteria:', error);
        return '';
    }
}

// Table sorting
function sortTable(table, column, type = 'string') {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = table.getAttribute('data-sort') !== 'asc';
    
    rows.sort((a, b) => {
        let aValue = a.cells[column].textContent.trim();
        let bValue = b.cells[column].textContent.trim();
        
        if (type === 'number') {
            aValue = parseFloat(aValue);
            bValue = parseFloat(bValue);
        }
        
        if (aValue < bValue) return isAscending ? -1 : 1;
        if (aValue > bValue) return isAscending ? 1 : -1;
        return 0;
    });
    
    rows.forEach(row => tbody.appendChild(row));
    table.setAttribute('data-sort', isAscending ? 'asc' : 'desc');
    
    // Update sort indicators
    table.querySelectorAll('th').forEach(th => th.classList.remove('asc', 'desc'));
    table.querySelector(`th:nth-child(${column + 1})`).classList.add(isAscending ? 'asc' : 'desc');
}

// Export table to Excel
function exportToExcel(tableId, fileName) {
    const table = document.getElementById(tableId);
    const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet 1" });
    XLSX.writeFile(wb, `${fileName}.xlsx`);
}
