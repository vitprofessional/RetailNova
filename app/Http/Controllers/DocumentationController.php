<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentationController extends Controller
{
    /**
     * Documentation sections configuration
     */
    private function getSections()
    {
        return [
            'overview' => 'System Overview',
            'dashboard' => 'Dashboard',
            'customers' => 'Customer Management',
            'suppliers' => 'Supplier Management',
            'products' => 'Product Management',
            'sales' => 'Sales Management',
            'services' => 'Service Management',
            'accounts' => 'Account Management',
            'reports' => 'Reports & Analytics',
            'settings' => 'Business Settings',
        ];
    }

    /**
     * Main documentation index
     */
    public function index()
    {
        $sections = $this->getSections();
        return view('documentation.index', compact('sections'));
    }

    /**
     * Show specific documentation section
     */
    public function show($section)
    {
        $sections = $this->getSections();
        
        if (!array_key_exists($section, $sections)) {
            abort(404);
        }

        $title = $sections[$section];
        return view('documentation.show', compact('section', 'title', 'sections'));
    }

    /**
     * Download full documentation as PDF
     */
    public function downloadPdf()
    {
        $sections = $this->getSections();
        
        // Load all section views
        $content = '';
        foreach (array_keys($sections) as $section) {
            $content .= view('documentation.sections.' . $section, [
                'section' => $section,
                'title' => $sections[$section],
                'sections' => $sections,
                'isPdf' => true
            ])->render();
        }

        $pdf = Pdf::loadView('documentation.pdf', [
            'content' => $content,
            'sections' => $sections
        ]);

        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download('RetailNova-Documentation-' . date('Y-m-d') . '.pdf');
    }

    /**
     * View printable documentation
     */
    public function print()
    {
        $sections = $this->getSections();
        
        // Load all section views for printing
        $content = '';
        foreach (array_keys($sections) as $section) {
            $content .= view('documentation.sections.' . $section, [
                'section' => $section,
                'title' => $sections[$section],
                'sections' => $sections,
                'isPrint' => true
            ])->render();
        }

        return view('documentation.print', compact('content', 'sections'));
    }

    /**
     * Download specific section as PDF
     */
    public function downloadSectionPdf($section)
    {
        $sections = $this->getSections();
        
        if (!array_key_exists($section, $sections)) {
            abort(404);
        }

        $title = $sections[$section];
        $content = view('documentation.sections.' . $section, [
            'section' => $section,
            'title' => $title,
            'sections' => $sections,
            'isPdf' => true
        ])->render();

        $pdf = Pdf::loadView('documentation.pdf', [
            'content' => $content,
            'sections' => [$section => $title],
            'singleSection' => true
        ]);

        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download('RetailNova-' . ucfirst($section) . '-' . date('Y-m-d') . '.pdf');
    }
}
