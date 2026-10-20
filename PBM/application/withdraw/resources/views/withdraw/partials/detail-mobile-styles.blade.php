@once
    @push('styles')
        <style>
            @media (max-width: 1023px) {
                .withdraw-detail-overlay {
                    padding: 0 !important;
                    overflow-x: hidden;
                }

                .withdraw-detail-overlay > .flex.min-h-screen {
                    padding: 0 !important;
                    min-height: 100dvh;
                    align-items: stretch !important;
                }

                .withdraw-detail-dialog {
                    border-radius: 0 !important;
                    margin: 0 !important;
                    min-height: 100dvh;
                    max-width: 100% !important;
                    overflow-x: hidden;
                }

                .withdraw-detail-dialog > .relative > .relative.overflow-hidden {
                    border-radius: 0 !important;
                    padding: 0.75rem 1rem !important;
                }

                .withdraw-detail-dialog > .relative > .relative.overflow-hidden .h-11 {
                    height: 2.25rem;
                    width: 2.25rem;
                    border-radius: 0.85rem;
                }

                .withdraw-detail-dialog > .relative > .relative.overflow-hidden h3 {
                    font-size: 1rem !important;
                    line-height: 1.3;
                }

                .withdraw-detail-dialog > .relative > div.px-6 {
                    padding: 0.75rem 1rem 1.15rem !important;
                }

                .withdraw-detail-sheet {
                    min-width: 0;
                    max-width: 100%;
                    overflow-x: hidden;
                }

                .withdraw-detail-sheet > .p-1 {
                    padding: 0;
                }

                .withdraw-detail-sheet .wd-heading {
                    display: flex;
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 0.5rem;
                    margin-bottom: 0.85rem;
                }

                .withdraw-detail-sheet .wd-status {
                    display: inline-flex;
                    width: fit-content;
                    max-width: 100%;
                    align-items: center;
                    border-radius: 9999px;
                    background: #fff1f2;
                    color: #be123c;
                    font-size: 0.75rem;
                    font-weight: 600;
                    line-height: 1.3;
                    padding: 0.3rem 0.7rem;
                    box-shadow: inset 0 0 0 1px #ffe4e6;
                }

                .withdraw-detail-sheet .wd-fields {
                    display: flex;
                    flex-direction: column;
                    gap: 0.85rem;
                }

                .withdraw-detail-sheet .wd-field,
                .withdraw-detail-sheet .wd-field-row {
                    display: flex !important;
                    flex-direction: column !important;
                    align-items: stretch !important;
                    width: 100% !important;
                    gap: 0.2rem !important;
                    grid-template-columns: minmax(0, 1fr) !important;
                }

                .withdraw-detail-sheet .wd-field-row {
                    gap: 0.85rem !important;
                }

                .withdraw-detail-sheet .wd-label {
                    width: auto !important;
                    text-align: left !important;
                    font-size: 11px !important;
                    font-weight: 600;
                    color: #64748b !important;
                }

                .withdraw-detail-sheet .wd-label-spacer {
                    display: none !important;
                }

                .withdraw-detail-sheet .wd-value {
                    width: 100% !important;
                    min-width: 0 !important;
                    min-height: 0 !important;
                    padding: 0 !important;
                    overflow-wrap: anywhere;
                    word-break: break-word;
                    line-height: 1.55;
                }

                .withdraw-detail-sheet .wd-table-wrap {
                    margin-left: 0;
                    margin-right: 0;
                    overflow-x: hidden;
                }

                .withdraw-detail-sheet .wd-table {
                    min-width: 0 !important;
                    width: 100% !important;
                    table-layout: fixed;
                    font-size: 12px;
                }

                .withdraw-detail-sheet .wd-table th,
                .withdraw-detail-sheet .wd-table td {
                    padding: 0.5rem 0.4rem !important;
                    overflow-wrap: anywhere;
                    word-break: break-word;
                    vertical-align: top;
                }

                .withdraw-detail-sheet .wd-table th:nth-child(1),
                .withdraw-detail-sheet .wd-table td:nth-child(1) {
                    width: 2.15rem;
                    text-align: center;
                }

                .withdraw-detail-sheet .wd-table th:nth-child(3),
                .withdraw-detail-sheet .wd-table td:nth-child(3) {
                    width: 5.6rem;
                    font-variant-numeric: tabular-nums;
                }

                .withdraw-detail-sheet .wd-table tfoot td[colspan="3"] {
                    text-align: left !important;
                    line-height: 1.5;
                }

                .withdraw-detail-sheet [data-withdraw-document-preview] {
                    min-width: 0;
                }

                .withdraw-detail-sheet [data-withdraw-document-preview] img {
                    max-height: 46vh;
                    max-width: 100%;
                }

                .withdraw-detail-sheet [data-withdraw-document-preview] a {
                    width: 100%;
                    justify-content: center;
                    padding-top: 0.55rem;
                    padding-bottom: 0.55rem;
                }

                .withdraw-detail-sheet [data-withdraw-document-preview] .mb-2.flex {
                    flex-direction: column;
                    align-items: stretch;
                }

                .withdraw-detail-sheet [data-withdraw-document-preview] .flex.items-center.justify-between,
                .withdraw-detail-sheet [data-withdraw-document-preview] .flex.flex-wrap {
                    flex-direction: column;
                    align-items: stretch;
                }

                .withdraw-detail-sheet [id$="-detail-applicant"],
                .withdraw-detail-sheet [id$="-detail-reviewer"],
                .withdraw-detail-sheet [id$="-detail-actors"] {
                    overflow-wrap: anywhere;
                    word-break: break-word;
                }

                .withdraw-detail-sheet [id$="-detail-approval-route"] > .relative.flex {
                    gap: 0.5rem;
                }

                .withdraw-detail-sheet [id$="-detail-approval-route"] .flex.items-start.justify-between {
                    flex-direction: column !important;
                    align-items: stretch !important;
                    gap: 0.55rem !important;
                }

                .withdraw-detail-sheet [id$="-detail-approval-route"] button {
                    width: 100%;
                    justify-content: center;
                    min-height: 2.25rem;
                }

                .wd-close-bar {
                    position: sticky;
                    bottom: 0;
                    z-index: 3;
                    display: block;
                    margin: 0.75rem -1rem -0.25rem;
                    padding: 0.75rem 1rem calc(0.85rem + env(safe-area-inset-bottom, 0px));
                    background: #fff;
                    border-top: 1px solid #f1f5f9;
                }

                .wd-close-bar button {
                    width: 100%;
                    padding-top: 0.7rem;
                    padding-bottom: 0.7rem;
                    transform: none !important;
                }
            }
        </style>
    @endpush
@endonce
