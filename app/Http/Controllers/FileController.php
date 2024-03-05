<?php

namespace App\Http\Controllers;

use Response;
use App\Models\File;
use Illuminate\Http\Request;
use App\Http\Requests\FileRequest;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel;
use App\Exports\FileExcelExport;

class FileController extends Controller
{
    public function index(Request $request, File $file){
        if ($request->busca != null){
            $files = File::where('original_name','LIKE',"%{$request->busca}%")
                        ->orWhere('name','LIKE',"%{$request->busca}%")->paginate();
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

    public function fileExcel(Request $request, Excel $excel)
    {
        $this->authorize('admin');
        $headings = ['Arquivo', 'Data de envio', 'Hora envio', 'Usuário'];
        $files = File::where('original_name','LIKE',"%{$request->busca}%")
                        ->orWhere('name','LIKE',"%{$request->busca}%")->get();

        //array para esconder o token e user_id que vem dentro do obj
        $aux =[];
        foreach($files as $file){

            $aux[] = [
                'Arquivo'               => $file->original_name,
                'Data de Envio'         => $file->created_at->format('d/m/Y'),
                'Hora Envio'            => $file->created_at->format('H:i:s'),
                'Usuario'               => $file->user->name,
            ];

        }
        $export = new FileExcelExport($aux, $headings);
        return $excel->download($export, 'files.xlsx');
    }
}

