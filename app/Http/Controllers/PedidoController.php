<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\File;
use App\Models\Pedido;
use App\Http\Requests\PedidoRequest;
use App\Mail\pedido_autorizacao_mail;
use App\Mail\acesso_autorizado_mail;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;

class PedidoController extends Controller
{
    public function file_by_name(Request $request, $file_by_name){
        $file = File::where('original_name', $file_by_name)->first();
        if($file){
            return redirect("/pedidos/{$file->id}");
        } else {
            $request->session()->flash('alert-danger',
                "Arquivo não encontrado");
            return redirect('/');
        }   
    }

    public function retro(Request $request, $letra, $file_by_name){
        # podemos ignorar $letra
        $file = File::where('original_name', $file_by_name)->first();
        if($file){
            return redirect("/pedidos/{$file->id}");
        } else {
            $request->session()->flash('alert-danger',
                "Arquivo não encontrado");
            return redirect('/');
        }
    }

    public function create(File $file){
        return view('pedidos.create')->with([
            'file'   => $file,
            'pedido' => new Pedido(), 
        ]);
    }  

    public function store(PedidoRequest $request, Pedido $pedido){
        $validated = $request->validated();
        $pedido = Pedido::create($validated);
        Mail::queue(new pedido_autorizacao_mail($pedido));
        request()->session()->flash('alert-success', 'Solicitação de acesso enviada com sucesso.
            Aguarde instruções via e-mail.');
        return back();
    } 

    public function pendentes(){
        $this->authorize('admin');
        $pedidos = Pedido::whereNull('autorizador_id')->get();
        return view('pedidos.pendentes',[
            'pedidos' => $pedidos
        ]);
    }

    public function autorizar(Pedido $pedido){
        $this->authorize('admin');
        $pedido->autorizador_id = Auth::user()->id;
        $pedido->autorizado_em = Carbon::now();
        $url = URL::temporarySignedRoute('acesso_autorizado', now()->addMinutes(2880), [
            'file_id'   => $pedido->file_id,
            'pedido_id' => $pedido->id
        ]);

        Mail::queue(new acesso_autorizado_mail($url,$pedido->email));
        request()->session()->flash('alert-info',
            'Autorização do arquivo enviada com sucesso para o email: ' . $pedido->email);
        $pedido->save();
        return back();
    }

    public function acesso_autorizado(Request $request)
    {
        if ($request->hasValidSignature()) {
            $file = File::find($request->file_id);
            return Storage::download($file->path, $file->original_name);
        } else {
            $request->session()->flash('alert-danger',
                "Solicitação expirada. Faça uma nova requisição!");
            return redirect('/');
        }
    }   

    public function index(Request $request){

        $this->authorize('admin');

        $total = Pedido::get('id')->count();
        $total_autorizado = Pedido::where('autorizado_em','!=',null)->count();
        $total_pendentes = Pedido::where('autorizado_em','=',null)->count();

        if(isset($request->busca)) {
                $pedidos = Pedido::where('nome','LIKE',"%{$request->busca}%")->get();  
        } else {
            $pedidos = Pedido::get();

            if($request->type){
                $export = new FastExcel($this->excel($pedidos));
                return $export->download('estagios.xlsx');
            }
        }
        return view('pedidos.realizados')->with([
            'pedidos' => $pedidos,
            'total' => $total,
            'total_autorizado' => $total_autorizado,
            'total_pendentes' => $total_pendentes,
        ]);

    }

    private function excel($pedidos){
        $aux =[];
        foreach($pedidos as $pedido){
            $aux[] = [
                'Arquivo Requisitado' => $pedido->file->name,
                'Nome'                => $pedido->nome,
                'Email'               => $pedido->email,
                'Finalidade'          => $pedido->finalidade,
                'Data do Pedido'      => $pedido->created_at->format('d/m/Y'), 
                'Data de Autorização' => $pedido->autorizado_em,
            ];

        }
        return collect($aux);

}
}