<?php

namespace App\Livewire\Reports;

use Livewire\Component;
// use Livewire\WithPagination; // Kita tidak menggunakan paginasi otomatis lagi
use Modules\Budget\Entities\MasterBudget;
use Modules\Department\Entities\Departments;

class MasterBudgetReport extends Component
{
    // use WithPagination; // Hapus ini

    public $start_date, $end_date, $department_id, $status;
    public $departments;
    // Hapus public $masterBudgets;

    public function mount()
    {
        $this->departments = Departments::all();
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
        $this->status = '';

        $user = auth()->user();
        $this->department_id = $user->department_id != 0 ? $user->department_id : '';
    }

    public function generateReport()
    {
        $this->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        // Tidak perlu resetPage() lagi
    }

    public function printReport()
    {
        $this->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);
        
        $queryParams = http_build_query([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'department_id' => $this->department_id,
            'status' => $this->status,
        ]);

        $url = route('reports.master_budget.print') . '?' . $queryParams;
        $this->dispatch('open-new-tab', $url);
    }

    public function render()
    {
        $query = MasterBudget::with(['department', 'purchases']) // Eager load relasi
            ->whereBetween('tgl_penyusunan', [$this->start_date, $this->end_date]);

        $userDept = auth()->user()->department_id;

        if ($userDept != 0) {
            $query->where(function ($q) use ($userDept) {
                $q->where('department_id', $userDept)
                  ->orWhere('department_id', 0);
            });
        } else {
            if ($this->department_id !== '' && $this->department_id !== null) {
                if ($this->department_id == 0) {
                    $query->where('department_id', 0);
                } else {
                    $query->where(function ($q) {
                        $q->where('department_id', $this->department_id)
                          ->orWhere('department_id', 0);
                    });
                }
            }
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        // Ambil SEMUA data, jangan paginasi
        $allBudgets = $query->orderBy('department_id')->orderBy('tgl_penyusunan', 'desc')->get();

        // Kelompokkan berdasarkan department_id
        $groupedBudgets = $allBudgets->groupBy('department_id');

        // Pisahkan budget "Over Budget" (department_id = 0)
        $overBudgets = $groupedBudgets->pull(0); // pull() akan mengambil dan menghapus dari koleksi utama

        // Sisanya adalah budget per departemen
        $departmentBudgets = $groupedBudgets;

        return view('livewire.reports.master-budget-report', [
            'departmentBudgets' => $departmentBudgets,
            'overBudgets' => $overBudgets
        ]);
    }
}