<?php

namespace Modules\TargetSale\DataTables;

use Modules\TargetSale\Entities\TargetSale;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class TargetSalesDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('month_name', function ($data) {
                return $data->month_name; // Menggunakan accessor dari Model
            })
            ->editColumn('target_amount', function ($data) {
                return $data->formatted_target; // Menggunakan accessor dari Model
            })
            // Kolom status dihapus karena tidak ingin ditampilkan
            ->addColumn('action', function ($data) {
                // Membuat tombol aksi langsung disini agar semua data bisa diedit (bypass logika status)
                $editUrl = route('target_sales.edit', $data->id);
                $deleteUrl = route('target_sales.destroy', $data->id);
                $csrf = csrf_field();
                $method = method_field('DELETE');

                return <<<HTML
                    <div class="btn-group">
                        <a href="$editUrl" class="btn btn-sm btn-warning me-1" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('Apakah Anda yakin ingin menghapus data ini?')){ document.getElementById('delete-form-{$data->id}').submit(); }">
                            <i class="bi bi-trash"></i>
                        </button>
                        <form id="delete-form-{$data->id}" action="$deleteUrl" method="POST" class="d-none">
                            $csrf
                            $method
                        </form>
                    </div>
                HTML;
            })
            ->rawColumns(['action']); // Hanya kolom action yang mengandung HTML
    }

    public function query(TargetSale $model)
    {
        return $model->newQuery()->orderBy('year', 'desc')->orderBy('month', 'desc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('target-sales-table')
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
            Column::computed('DT_RowIndex')
                ->title('No')
                ->width(50)
                ->addClass('text-center align-middle'),

            Column::make('year')
                ->title('Tahun')
                ->addClass('text-center align-middle'),

            Column::computed('month_name')
                ->title('Bulan')
                ->addClass('text-center align-middle'),

            Column::make('target_amount')
                ->title('Nominal Target')
                ->addClass('text-end align-middle'),
            
            Column::make('description')
                ->title('Keterangan')
                ->addClass('align-middle'),

            // Kolom Status Dihapus

            Column::computed('action')
                ->title('Aksi')
                ->exportable(false)
                ->printable(false)
                ->addClass('text-center align-middle'),
        ];
    }

    protected function filename(): string
    {
        return 'TargetSales_' . date('YmdHis');
    }
}