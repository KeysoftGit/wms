<div class="modal fade" id="journalViewModal" tabindex="-1" role="dialog" aria-labelledby="journalViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Journal - <span id="journalViewTransactionNo"></span></h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <div id="journalViewLoading" class="text-center py-5">
                        <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                    </div>
                    <div id="journalViewBody" class="d-none">
                        <div class="d-flex flex-wrap mb-3">
                            <div class="me-5 mb-2">
                                <div class="text-muted fs-sm">Transaction No</div>
                                <div class="fw-semibold" id="journalViewInfoTransactionNo"></div>
                            </div>
                            <div class="me-5 mb-2">
                                <div class="text-muted fs-sm">Transaction Date</div>
                                <div class="fw-semibold" id="journalViewDate"></div>
                            </div>
                            <div class="me-5 mb-2">
                                <div class="text-muted fs-sm">Transaction Type</div>
                                <div class="fw-semibold" id="journalViewType"></div>
                            </div>
                        </div>
                        <div class="table-responsive w-100 journal-detail-table-wrap">
                            <table class="table table-bordered journal-detail-table">
                                <thead>
                                    <tr>
                                        <th>Account No</th>
                                        <th>Currency ID</th>
                                        <th class="text-end">Rate</th>
                                        <th class="text-end">Original Amount</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="journalViewTableBody"></tbody>
                                <tfoot>
                                    <tr class="fw-semibold">
                                        <td colspan="4" class="text-end">Total</td>
                                        <td class="text-end" id="journalViewTotalDebit"></td>
                                        <td class="text-end" id="journalViewTotalCredit"></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div id="journalViewEmpty" class="d-none text-center py-5 text-muted">
                        Journal for this transaction has not been created yet.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var journalViewDataUrlTemplate = '{{ route('accounting.journal.by_transaction.data', ['transactionNo' => 'JOURNAL_TX_PLACEHOLDER']) }}';

    function journalViewEscapeHtml(text){
        return $('<div>').text(text || '').html();
    }

    function showJournalModal(transactionNo){
        $('#journalViewTransactionNo').text(transactionNo);
        $('#journalViewLoading').removeClass('d-none');
        $('#journalViewBody').addClass('d-none');
        $('#journalViewEmpty').addClass('d-none');
        $('#journalViewTableBody').empty();
        $('#journalViewModal').modal('show');

        $.ajax({
            url: journalViewDataUrlTemplate.replace('JOURNAL_TX_PLACEHOLDER', transactionNo),
            type: 'GET',
        }).then(function (result){
            $('#journalViewLoading').addClass('d-none');

            if(!result.found){
                $('#journalViewEmpty').removeClass('d-none');
                return;
            }

            $('#journalViewInfoTransactionNo').text(result.transactionNo);
            $('#journalViewDate').text(result.date);
            $('#journalViewType').text(result.type);

            var rows = '';
            (result.details || []).forEach(function (d){
                rows += '<tr>'
                    + '<td>' + journalViewEscapeHtml(d.account) + '</td>'
                    + '<td>' + journalViewEscapeHtml(d.currency) + '</td>'
                    + '<td class="text-end">' + journalViewEscapeHtml(d.rate) + '</td>'
                    + '<td class="text-end">' + journalViewEscapeHtml(d.original_amount) + '</td>'
                    + '<td class="text-end">' + journalViewEscapeHtml(d.debit) + '</td>'
                    + '<td class="text-end">' + journalViewEscapeHtml(d.credit) + '</td>'
                    + '<td>' + journalViewEscapeHtml(d.notes) + '</td>'
                    + '</tr>';
            });
            $('#journalViewTableBody').html(rows);

            var balanced = result.totalDebit === result.totalCredit;
            $('#journalViewTotalDebit').text(result.totalDebit)
                .toggleClass('text-danger', !balanced);
            $('#journalViewTotalCredit').text(result.totalCredit)
                .toggleClass('text-danger', !balanced);

            $('#journalViewBody').removeClass('d-none');
        }).catch(function (){
            $('#journalViewLoading').addClass('d-none');
            $('#journalViewEmpty').removeClass('d-none');
        });
    }
</script>
