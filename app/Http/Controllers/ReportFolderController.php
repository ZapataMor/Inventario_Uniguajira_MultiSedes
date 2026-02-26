<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReportFolder;

class ReportFolderController extends Controller
{
    /**
     * Vista principal (carpetas)
     */
    public function index(Request $request)
    {
        $folders = ReportFolder::withCount('reports')->get();

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('reports.folders.index', compact('folders'));
            return $view->renderSections()['content'];
        }

        return view('reports.folders.index', compact('folders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombreCarpeta' => 'required|string|max:255|unique:report_folders,name'
        ]);

        $folder = ReportFolder::create([
            'name' => trim($request->nombreCarpeta)
        ]);

        return response()->json([
            'success' => true,
            'folder' => $folder
        ]);
    }

    public function rename(Request $request)
    {
        $request->validate([
            'folder_id' => 'required|exists:report_folders,id',
            'nombre'    => 'required|string|max:255'
        ]);

        ReportFolder::findOrFail($request->folder_id)
            ->update(['name' => $request->nombre]);

        return response()->json(['success' => true]);
    }

    public function destroy(int $id)
    {
        $folder = ReportFolder::findOrFail($id);

        if ($folder->reports()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'La carpeta contiene reportes.'
            ], 400);
        }

        $folder->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Mostrar reportes dentro de una carpeta
     */
    public function show(int $folderId)
    {
        $folder = ReportFolder::with('reports')->findOrFail($folderId);

        return view('reports.folders.show', compact('folder'));
    }

}

