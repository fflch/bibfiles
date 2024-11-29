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
use App\Mail\acesso_negado_mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel;
use App\Exports\ExcelExport;

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
        return redirect('/');
        #return back();
    }

    public function pendentes(){
        $this->authorize('admin');
        $pedidos = Pedido::whereNull('autorizador_id')->get();
        return view('pedidos.pendentes',[
            'pedidos' => $pedidos
        ]);
    }

    public function autorizar(Request $request, Pedido $pedido){
        $this->authorize('admin');

        $pedido->autorizado_em = Carbon::now();
        $pedido->autorizador_id = Auth::user()->id;
        if($request->autorizar_action == 'acesso_autorizado')
        {
            $url = URL::temporarySignedRoute('acesso_autorizado', now()->addMinutes(2880), [
                'file_id'   => $pedido->file_id,
                'pedido_id' => $pedido->id
            ]);

            Mail::queue(new acesso_autorizado_mail($url,$pedido->email));
            request()->session()->flash('alert-info',
                'Autorização do arquivo enviada com sucesso para o email: ' . $pedido->email);
        }

        if($request->autorizar_action == 'acesso_negado')
        {
            $request->validate([
                'justificativa' => 'required',
            ],
            [
                'justificativa.required' => 'O campo justificativa é requerido.'
            ]);
            $pedido->negado = true;
            $pedido->justificativa = $request->justificativa;
            Mail::queue(new acesso_negado_mail($pedido->email,$pedido));
            request()->session()->flash('alert-info',
                'Aviso sobre o arquivo enviado com sucesso para o email: ' . $pedido->email);
        }
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
        $total_negados = Pedido::where('negado','=',true)->count();

        $pedidos = Pedido::when($request->busca, function ($query) use ($request) {
            $query->where('nome','LIKE',"%{$request->busca}%");
        })->get();

        return view('pedidos.realizados')->with([
            'pedidos' => $pedidos,
            'total' => $total,
            'total_autorizado' => $total_autorizado,
            'total_pendentes' => $total_pendentes,
            'total_negados' => $total_negados,
        ]);

    }

    public function gerarExcel(Request $request, Excel $excel){

        $headings = ['Arquivo Requisitado', 'Status do Pedido', 'Nome', 'E-mail', 'Finalidade', 'Justificativa de Acesso Negado', 'Data do Pedido', 'Data de Análise'];

        $pedidos = Pedido::where('nome','LIKE',"%{$request->busca}%")->get();

        $aux =[];
        foreach($pedidos as $pedido){
            if($pedido->negado == true){
                $status = 'Acesso Negado';
            }elseif($pedido->negado =! true || $pedido->autorizador_id == true){
                $status = 'Acesso Autorizado';
            }else{
                $status = 'Pendente';
            }

            $aux[] = [
                'Arquivo Requisitado'               => $pedido->file->name,
                'Status do Pedido'                  => $status,
                'Nome'                              => $pedido->nome,
                'Email'                             => $pedido->email,
                'Finalidade'                        => $pedido->finalidade,
                'Justificativa de Acesso Negado'    => $pedido->justificativa,
                'Data do Pedido'                    => $pedido->created_at->format('d/m/Y'),
                'Data de Análise'                   => $pedido->autorizado_em,
            ];

        }
        $export = new ExcelExport($aux, $headings);
        return $excel->download($export, 'pedidos.xlsx');
    }
}
