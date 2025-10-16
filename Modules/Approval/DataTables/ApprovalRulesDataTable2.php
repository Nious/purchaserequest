<?php

namespace Modules\Approval\DataTables;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ApprovalRulesDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->query($query)
            ->addColumn('action', function ($data) {
                return view('approval::approval_rules.partials.actions', compact('data'));
            });
    }

    public function query()
    {
        return DB::table('approval_rules')
            ->join('approval_types', 'approval_rules.approval_types_id', '=', 'approval_types.id')
            ->join('approval_rule_users', 'approval_rules.approval_rule_users_id', '=', 'approval_rule_users.id')
            ->join('users', 'approval_rule_users.user_id', '=', 'users.id')
            ->select(
                'approval_rules.id',
                'approval_types.approval_name as type_name',
                'users.name as user_name',
                'approval_rules.level',
                'approval_rules.amount_limit',
                'approval_rules.is_active',
                'approval_rules.created_at'
            );
    }


    public function html()
    {
        return $this->builder()
            ->setTableId('approval-rules-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom("<'row'<'col-md-3'l><'col-md-5 mb-2'B><'col-md-4'f>> .
                    'tr' .
                    <'row'<'col-md-5'i><'col-md-7 mt-2'p>>")
            ->orderBy(1)
            ->buttons(
                Button::make('excel')
                    ->text('<i class="bi bi-file-earmark-excel-fill"></i> Excel'),
                Button::make('print')
                    ->text('<i class="bi bi-printer-fill"></i> Print'),
                Button::make('reset')
                    ->text('<i class="bi bi-x-circle"></i> Reset'),
                Button::make('reload')
                    ->text('<i class="bi bi-arrow-repeat"></i> Reload')
            );
    }

    protected function getColumns()
    {
        return [
            Column::make('id')
                ->addClass('text-center'),

            Column::make('type_name')
                ->title('Approval Type')
                ->addClass('text-center'),

            Column::make('user_name')
                ->title('User')
                ->addClass('text-center'),

            Column::make('amount')
                ->addClass('text-center'),

            Column::make('is_active')
                ->addClass('text-center'),

            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->addClass('text-center'),

            Column::make('created_at')
                ->visible(false),
        ];
    }

    protected function filename(): string
    {
        return 'approval_rules_' . date('YmdHis');
    }
}
