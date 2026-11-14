/**
 * Kayan ERP — Finance & Accounting Module Interactions
 * Real-time Disbursement Polling & A4 Printable Audit Reports
 */

export function initFinancePage() {
    // 1. Delegated Print Trigger for Closing, General Ledger, and Monthly Breakdown
    document.querySelectorAll('[data-action="print-closing"]').forEach((btn) => {
        if (!btn.__printBound) {
            btn.__printBound = true;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                window.print();
            });
        }
    });
}
