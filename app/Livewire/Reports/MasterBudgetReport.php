<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Budget\Entities\MasterBudget;
use Modules\Department\Entities\Departments;

class MasterBudgetReport extends Component
{
    use WithPagination;

    public $start_date, $end_date, $department_id, $status;
    public $departments;
    protected $paginationTheme = 'bootstrap';

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

        $this->resetPage();
    }

    public function render()
{
    $query = MasterBudget::with('department')
        ->whereBetween('tgl_penyusunan', [$this->start_date, $this->end_date]);

    $userDept = auth()->user()->department_id;

    // 🔹 Jika user bukan admin (department_id ≠ 0)
    if ($userDept != 0) {
        // Tampilkan budget milik departemennya sendiri + yang department_id == 0
        $query->where(function ($q) use ($userDept) {
            $q->where('department_id', $userDept)
              ->orWhere('department_id', 0);
        });
    } else {
        // 🔹 Jika admin (department_id == 0)
        // Filter berdasarkan dropdown (jika dipilih)
        if ($this->department_id !== '' && $this->department_id !== null) {
            if ($this->department_id == 0) {
                // Tampilkan hanya yang 0 (All Departemen)
                $query->where('department_id', 0);
            } else {
                // Tampilkan departemen tertentu + budget umum
                $query->where(function ($q) {
                    $q->where('department_id', $this->department_id)
                      ->orWhere('department_id', 0);
                });
            }
        }
    }

    // 🔹 Filter status
    if ($this->status) {
        $query->where('status', $this->status);
    }

    $masterBudgets = $query->paginate(10);

    return view('livewire.reports.master-budget-report', [
        'masterBudgets' => $masterBudgets
    ]);
}
}
