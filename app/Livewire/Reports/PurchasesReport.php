<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Purchase\Entities\Purchase;

class PurchasesReport extends Component
{

    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $departments;
    public $start_date;
    public $end_date;
    public $department_id;
    public $purchase_status;
    public $payment_status;

    protected $rules = [
        'start_date' => 'required|date|before:end_date',
        'end_date'   => 'required|date|after:start_date',
    ];

    public function mount($departments) {
        $this->departments = $departments;
        $this->start_date = today()->subDays(30)->format('Y-m-d');
        $this->end_date = today()->format('Y-m-d');
        $this->departement_id = '';
        $this->purchase_status = '';
        $this->payment_status = '';
    }

    public function render() {
        $purchases = Purchase::with('department')
            ->whereDate('date', '<=', $this->end_date)
            ->when($this->department_id, function ($query) {
                return $query->where('department_id', $this->department_id);
            })
            ->when($this->purchase_status, function ($query) {
                return $query->where('status', $this->purchase_status);
            })
            ->when($this->payment_status, function ($query) {
                return $query->where('payment_status', $this->payment_status);
            })
            ->orderBy('date', 'desc')->paginate(10);

        return view('livewire.reports.purchases-report', [
            'purchases' => $purchases
        ]);
    }

    public function generateReport() {
        $this->validate();
        $this->render();
    }
}
