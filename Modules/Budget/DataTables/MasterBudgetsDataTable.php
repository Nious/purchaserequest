<?php

namespace Modules\Budget\DataTables;

use Modules\Budget\Entities\MasterBudget;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class MasterBudgetsDataTable extends DataTable
{

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->editColumn('grandtotal', function ($data) {
                return 'Rp ' . number_format($data->grandtotal, 0, ',', '.');
            })
            ->addColumn('bulan_text', function ($data) {
                return $data->bulan_text; // pakai accessor
            })
            ->addColumn('department_name', function ($data) {
                return $data->department ? $data->department->department_name : '-';
            })
            ->addColumn('approval', function ($data) {
                if ($data->approval_status == 'Approved') {
                    return '<span class="badge bg-success">Approved</span>';
                } elseif ($data->approval_status == 'Pending') {
                    return '<span class="badge bg-danger">Pending</span>';
                } else {
                    return '<span class="badge bg-secondary">Draft</span>';
                }
            })
            
            ->addColumn('action', function ($data) {
                return view('budget::master_budget.partials.actions', compact('data'));
            })
            ->rawColumns(['approval', 'action']);
    }


    public function query(MasterBudget $model)
    {
        return $model->newQuery()->with('department');
    }


    public function html()
    {
        return $this->builder()
            ->setTableId('master_budget-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom("<'row'<'col-md-3'l><'col-md-5 mb-2'B><'col-md-4'f>>" .
                  "tr" .
                  "<'row'<'col-md-5'i><'col-md-7 mt-2'p>>")
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
            Column::make('no_budgeting')->title('No. Budgeting PR')->addClass('text-center'),
            Column::make('tgl_penyusunan')->title('Tgl. Penyusunan')->addClass('text-center'),
            Column::make('description')->title('Deskripsi')->addClass('text-center'),
            Column::computed('bulan_text')->title('Bulan')->addClass('text-center'),
            Column::computed('department_name')->title('Departemen')->addClass('text-center'),
            Column::make('grandtotal')->title('Total Budget')->addClass('text-end'),
            Column::computed('approval')->title('Approval')->addClass('text-center'),
            Column::computed('action')->title('Ubah / Hapus')->exportable(false)->printable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'master_budget' . date('YmdHis');
    }

    public function getPeriodeAttribute()
    {
        return $this->periode_awal . ' - ' . $this->periode_akhir;
    }

}
