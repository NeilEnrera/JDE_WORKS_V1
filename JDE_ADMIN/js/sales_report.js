document.addEventListener('DOMContentLoaded', function() {
    // ── Filter visibility ─────────────────────────────────────────────────────
    const rangeSelect = document.getElementById('paramRange');
    const yearSelect  = document.getElementById('yearSelect');
    const yearHidden  = document.getElementById('yearHidden');

    function updateFilterVisibility() {
        if (!rangeSelect) return;
        const val = rangeSelect.value;
        document.querySelectorAll('.range-daily, .range-monthly, .range-yearly')
            .forEach(el => el.style.display = 'none');
        document.querySelectorAll(`.range-${val}`)
            .forEach(el => el.style.display = '');
    }

    if (rangeSelect) {
        rangeSelect.addEventListener('change', updateFilterVisibility);
        updateFilterVisibility(); // Initial run
    }
    if (yearSelect && yearHidden) {
        yearSelect.addEventListener('change', () => { yearHidden.value = yearSelect.value; });
    }

    // ── Custom Date Picker (Flatpickr) ────────────────────────────────────────
    const commonPickerOpt = {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        allowInput: true,
        prevArrow: '<i class="bi bi-chevron-left"></i>',
        nextArrow: '<i class="bi bi-chevron-right"></i>',
    };

    const datePicker = document.getElementById('datePicker');
    if (datePicker) flatpickr(datePicker, commonPickerOpt);

    const startPicker = document.getElementById('startDatePicker');
    if (startPicker) flatpickr(startPicker, commonPickerOpt);

    const endPicker = document.getElementById('endDatePicker');
    if (endPicker) flatpickr(endPicker, commonPickerOpt);

    // ── Table Live Search ─────────────────────────────────────────────────────
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#salesTableBody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }
});

// ── PDF Download Function (Global scope for onclick) ──────────────────────
function openPDF() {
    const btn = document.querySelector('.btn-sr-pdf');
    const originalHTML = btn.innerHTML;
    const source = document.querySelector('#hiddenPDFSource .pdf-container');

    if (!source) {
        alert('Report template not ready. Please refresh the page.');
        return;
    }
    
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Downloading...';
    btn.style.pointerEvents = 'none';

    // Get dynamic filename if provided
    const reportFilename = (typeof window.SALES_REPORT_FILENAME !== 'undefined') 
        ? window.SALES_REPORT_FILENAME 
        : 'Sales_Report.pdf';

    const opt = {
        margin:      10,
        filename:    reportFilename,
        image:       { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, allowTaint: true },
        jsPDF:       { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:   { mode: ['avoid-all', 'css', 'legacy'] }
    };

    html2pdf().set(opt).from(source).save().then(() => {
        btn.innerHTML = originalHTML;
        btn.style.pointerEvents = 'auto';
    }).catch(err => {
        console.error('PDF Error:', err);
        alert('Could not generate PDF. Please try again or check browser settings.');
        btn.innerHTML = originalHTML;
        btn.style.pointerEvents = 'auto';
    });
}
