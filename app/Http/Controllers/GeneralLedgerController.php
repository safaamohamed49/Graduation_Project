<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralLedgerController extends Controller
{
    private function authorizePermission(string $permission, string $message): void
    {
        if (!auth()->user()?->hasPermission($permission)) {
            abort(403, $message);
        }
    }

    private function isAdmin(): bool
    {
        $user = auth()->user();

        return $user?->hasPermission('*') === true
            || optional($user?->role)->code === 'admin';
    }

    public function index(Request $request): Response
    {
        $this->authorizePermission('reports.view', 'ليس لديك صلاحية لعرض دفتر الأستاذ.');

        $user = auth()->user();

        $filters = [
            'search' => trim((string) $request->get('search', '')),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'account_id' => $request->get('account_id'),
            'branch_id' => $request->get('branch_id'),
            'status' => $request->get('status'),
            'source_type' => $request->get('source_type'),
        ];

        $query = JournalEntryLine::query()
            ->with([
                'account:id,code,name,type,nature',
                'journalEntry:id,entry_number,entry_date,branch_id,description,source_type,source_id,status,created_by_user_id',
                'journalEntry.branch:id,name',
            ])
            ->whereHas('journalEntry')
            ->when(!$this->isAdmin(), function ($q) use ($user) {
                $q->whereHas('journalEntry', function ($entryQuery) use ($user) {
                    $entryQuery->where('branch_id', $user->branch_id);
                });
            });

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('account', function ($accountQuery) use ($search) {
                        $accountQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('journalEntry', function ($entryQuery) use ($search) {
                        $entryQuery->where('entry_number', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereHas('journalEntry', function ($entryQuery) use ($filters) {
                $entryQuery->whereDate('entry_date', '>=', $filters['date_from']);
            });
        }

        if (!empty($filters['date_to'])) {
            $query->whereHas('journalEntry', function ($entryQuery) use ($filters) {
                $entryQuery->whereDate('entry_date', '<=', $filters['date_to']);
            });
        }

        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (!empty($filters['branch_id']) && $this->isAdmin()) {
            $query->whereHas('journalEntry', function ($entryQuery) use ($filters) {
                $entryQuery->where('branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['status'])) {
            $query->whereHas('journalEntry', function ($entryQuery) use ($filters) {
                $entryQuery->where('status', $filters['status']);
            });
        }

        if (!empty($filters['source_type'])) {
            $query->whereHas('journalEntry', function ($entryQuery) use ($filters) {
                $entryQuery->where('source_type', $filters['source_type']);
            });
        }

        $totals = [
            'debit' => (float) (clone $query)->sum('debit'),
            'credit' => (float) (clone $query)->sum('credit'),
        ];

        $lines = (clone $query)
            ->orderBy(
                JournalEntry::select('entry_date')
                    ->whereColumn('journal_entries.id', 'journal_entry_lines.journal_entry_id')
            )
            ->orderBy(
                JournalEntry::select('id')
                    ->whereColumn('journal_entries.id', 'journal_entry_lines.journal_entry_id')
            )
            ->orderBy('journal_entry_lines.id')
            ->paginate(50)
            ->withQueryString();

        $printLines = (clone $query)
            ->orderBy(
                JournalEntry::select('entry_date')
                    ->whereColumn('journal_entries.id', 'journal_entry_lines.journal_entry_id')
            )
            ->orderBy(
                JournalEntry::select('id')
                    ->whereColumn('journal_entries.id', 'journal_entry_lines.journal_entry_id')
            )
            ->orderBy('journal_entry_lines.id')
            ->limit(1000)
            ->get();

        $printLines->load([
            'account:id,code,name,type,nature',
            'journalEntry:id,entry_number,entry_date,branch_id,description,source_type,source_id,status,created_by_user_id',
            'journalEntry.branch:id,name',
        ]);

        return Inertia::render('GeneralLedger/Index', [
            'lines' => $lines,
            'printLines' => $printLines,
            'filters' => $filters,
            'totals' => $totals,
            'accounts' => Account::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'type', 'nature', 'is_group']),
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'isAdmin' => $this->isAdmin(),
        ]);
    }
}