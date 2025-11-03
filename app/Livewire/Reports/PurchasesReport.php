<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Purchase\Entities\Purchase;
use Modules\Department\Entities\Departments;

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

    public function mount($departments)
    {
        $user = auth()->user();

        $this->departments = $departments;
        $this->start_date = today()->subDays(30)->format('Y-m-d');
        $this->end_date = today()->format('Y-m-d');
        $this->department_id = ''; // fix typo (tadi kamu pakai `$this->departement_id`)
        $this->purchase_status = '';
        $this->payment_status = '';

        // 🔒 Jika user bukan admin, kunci department_id ke miliknya
        if ($user->department_id != 0) {
            $this->department_id = $user->department_id;
        }
    }

    public function render()
    {
        $user = auth()->user();

        $query = Purchase::with('department')
            ->whereDate('date', '<=', $this->end_date)
            ->whereDate('date', '>=', $this->start_date);

        // 🔒 Filter departemen berdasarkan user login
        if ($user->department_id != 0) {
            // Jika user punya department_id tertentu, hanya tampilkan miliknya
            $query->where('department_id', $user->department_id);
        } elseif ($this->department_id) {
            // Jika admin memilih departemen tertentu
            $query->where('department_id', $this->department_id);
        }

        // Filter status pembelian
        if ($this->purchase_status) {
            $query->where('status', $this->purchase_status);
        }

        // Filter status pembayaran
        if ($this->payment_status) {
            $query->where('payment_status', $this->payment_status);
        }

        $purchases = $query->orderBy('date', 'desc')->paginate(10);

        return view('livewire.reports.purchases-report', [
            'purchases' => $purchases,
        ]);
    }

    public function generateReport() {
        $this->validate();
        $this->render();
    }
}
