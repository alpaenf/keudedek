<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubmissionTemplate;
use App\Models\SubmissionTemplateField;
use App\Models\TransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionTemplateController extends Controller
{
    public function index(): Response
    {
        $templates = SubmissionTemplate::with(['transactionType', 'fields'])->latest()->paginate(10);
        $transactionTypes = TransactionType::where('is_active', true)->get();

        return Inertia::render('Admin/Templates/Index', [
            'templates' => $templates,
            'transactionTypes' => $transactionTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:submission_templates,code',
            'name' => 'required|string|max:150',
            'transaction_type_id' => 'nullable|exists:transaction_types,id',
            'version' => 'nullable|string|max:20',
        ]);

        $template = SubmissionTemplate::create([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'transaction_type_id' => $request->transaction_type_id,
            'version' => $request->version ?: 'v1.0',
            'effective_date' => now(),
            'is_active' => true,
        ]);

        // Create default fields
        $defaults = [
            ['code' => 'title', 'label' => 'Judul Kegiatan', 'type' => 'TEXT', 'req' => true, 'order' => 1],
            ['code' => 'beneficiary_name', 'label' => 'Nama Rekanan', 'type' => 'TEXT', 'req' => true, 'order' => 2],
            ['code' => 'reference_no', 'label' => 'Nomor Surat', 'type' => 'TEXT', 'req' => false, 'order' => 3],
        ];

        foreach ($defaults as $d) {
            SubmissionTemplateField::create([
                'submission_template_id' => $template->id,
                'field_code' => $d['code'],
                'label' => $d['label'],
                'data_type' => $d['type'],
                'is_required' => $d['req'],
                'is_editable' => true,
                'order_index' => $d['order'],
            ]);
        }

        return redirect()->back()->with('success', "Format pengajuan {$template->name} berhasil ditambahkan.");
    }
}
