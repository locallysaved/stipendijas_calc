<?php

namespace App\Http\Controllers;

use App\Services\XlsxImportService;
use App\Models\Grades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class XlsxImportController extends Controller
{
    public function __construct(private XlsxImportService $importService) {}

    public function index()
    {
        // Order by id since grades has no timestamps
        $grades = Grades::with('subject')->orderBy('id')->paginate(20);
        return view('xlsx.index', compact('grades'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'subjects_file' => 'required|file|mimes:xlsx,xls|max:20480',
            'grades_file'   => 'required|file|mimes:xlsx,xls|max:20480',
        ]);

        // Subjects must be imported first — grades FK depends on it
        $subjectsPath = $request->file('subjects_file')->store('xlsx-imports');
        $statsS = $this->importService->importSubjects(Storage::path($subjectsPath));

        $gradesPath = $request->file('grades_file')->store('xlsx-imports');
        $statsG = $this->importService->importGrades(Storage::path($gradesPath));

        $errorCount = count($statsS['errors']) + count($statsG['errors']);

        return redirect()->route('xlsx.index')->with('success',
            "Priekšmeti: {$statsS['imported']} importēti, {$statsS['skipped']} izlaisti. " .
            "Atzīmes: {$statsG['imported']} importētas, {$statsG['skipped']} izlaistas." .
            ($errorCount > 0 ? " ⚠ $errorCount rindās bija kļūdas." : '')
        );
    }
}