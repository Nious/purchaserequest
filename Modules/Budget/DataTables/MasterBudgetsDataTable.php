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
                return $data->grandtotal_formatted;
            })
            ->addColumn('bulan_text', function ($data) {
                return $data->bulan_text; // pakai accessor
            })
            ->addColumn('department_name', function ($data) {
                if ($data->department_id === 0) {
                    return 'All Departement';
                } elseif ($data->department) {
                    return $data->department->department_name;
                } else {
                    return '-';
                }
            })
            ->addColumn('used_budget', function ($data) {
                return $data->used_amount_formatted;
            })
            ->addColumn('remaining_budget', function ($data) {
                return $data->remaining_formatted;
            })
            ->addColumn('approval', function ($data) {
                // Ambil status, default ke 'pending', dan ubah ke huruf kecil
                $status = strtolower($data->status ?? 'pending');
            
                // Tampilkan badge berdasarkan status
                return match ($status) {
                    'approved' => '<span class="badge bg-success">approved</span>',
                    'rejected' => '<span class="badge bg-danger">rejected</span>',
                    default    => '<span class="badge bg-warning text-dark">pending</span>', // Menangani 'pending' atau status tak terduga
                };
            })


            ->addColumn('action', function ($data) {
                return view('budget::master_budget.partials.actions', compact('data'));
            })
            ->rawColumns(['approval', 'action']);
    }


    public function query(MasterBudget $model)
    {
        
        return $model->newQuery()
        ->select('master_budget.*') // pastikan sesuai dengan nama tabel di database
        ->with('department');
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
                    [
                        'text' => '<i class="bi bi-printer-fill"></i> Print PDF',
                        'className' => 'btn-primary', // (Opsional)
                        'action' => "
                            function ( e, dt, node, config ) {
                                // 1. Dapatkan nilai search saat ini dari DataTable
                                var searchValue = dt.search();
                                
                                // 2. Buat URL ke rute printAll
                                var url = '".route('master_budget.printAll')."';
                                
                                // 3. Tambahkan parameter search ke URL
                                url += '?search=' + encodeURIComponent(searchValue);
                                
                                // 4. Buka URL di tab baru
                                window.open(url, '_blank');
                            }
                        "
                    ],
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
            Column::computed('used_budget')->title('Used Budget')->addClass('text-end'),
            Column::computed('remaining_budget')->title('Remaining Budget')->addClass('text-end'),
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