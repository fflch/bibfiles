<?php

namespace App\Http\Controllers;

use Response;
use App\Models\File;
use Illuminate\Http\Request;
use App\Http\Requests\FileRequest;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index(Request $request, File $file){
        if ($request->busca != null){
            $files = File::where('original_name','LIKE',"%{$request->busca}%")->
                          orWhere('name','LIKE',"%{$request->busca}%")->paginate(10);
        } else {
            $files = File::paginate(10);
        }
        return view('files.index')->with('files',$files);
    }    

    public function create(Request $request){
        $this->authorize('admin');
        return view('files.create');
    }    

    public function store(FileRequest $request)
    {
        $this->authorize('admin');
        $validated = $request->validated();

        $file = new File;
        $file->original_name = $request->file('file')->getClientOriginalName();
        $file->name = $request->name;

        $file->path = $request->file('file')->store('.');
        $file->user_id = auth()->user()->id;

        request()->session()->flash('alert-success', 'Arquivo enviado com sucesso');

        $file->save();
        return redirect('/files');
    }

    public function show(File $file)
    {
        $this->authorize('admin');
        return Storage::download($file->path, $file->original_name);
    }

    public function destroy(File $file)
    {
        $this->authorize('admin');
        Storage::delete($file->path);
        $file->delete();
        request()->session()->flash('alert-success', 'Arquivo Deletado');
        return back();
    } 

}
