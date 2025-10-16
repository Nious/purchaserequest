<?php

namespace Modules\Approval\DataTables;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ApprovalRuleUsersDataTable extends DataTable
{
    public function dataTable($query) {
        return datatables()
            ->query($query)
            ->addColumn('action', function ($data) {
                return view('approval::approval_rule_users.partials.actions', compact('data'));
            });
    }

    public function query() {
        return DB::table('approval_rule_users')
            ->join('users', 'approval_rule_users.user_id', '=', 'users.id')
            ->join('approval_rules', 'approval_rule_users.rule_id', '=', 'approval_rules.id')
            ->select(
                'approval_rule_users.id',
                'approval_rules.rule_name',
                'users.name as user_name',
                'approval_rule_users.created_at'
            );
    }

    public function html() {
        return $this->builder()
            ->setTableId('approval-rule-users-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom("<'row'<'col-md-3'l><'col-md-5 mb-2'B><'col-md-4'f>> .
                                'tr' .
                                <'row'<'col-md-5'i><'col-md-7 mt-2'p>>")
            ->orderBy(2)
            ->buttons(
                Button::make('excel')->text('<i class="bi bi-file-earmark-excel-fill"></i> Excel'),
                Button::make('print')->text('<i class="bi bi-printer-fill"></i> Print'),
                Button::make('reset')->text('<i class="bi bi-x-circle"></i> Reset'),
                Button::make('reload')->text('<i class="bi bi-arrow-repeat"></i> Reload')
            );
    }

    protected function getColumns() {
        return [
            Column::make('id')->addClass('text-center'),
            Column::make('rule_name')->title('Approval Rule')->addClass('text-center'),
            Column::make('user_name')->title('User')->addClass('text-center'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->addClass('text-center'),
            Column::make('created_at')->visible(false),
        ];
    }

    protected function filename(): string {
        return 'approval_rule_users_' . date('YmdHis');
    }
}
