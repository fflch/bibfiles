@extends('main')

@section('content')

<div class="card">
    <div class="card-header"><b>Solicitações realizadas no sistema</b></div>
        <div class="card-body">

        <div>

        Total de Pedidos: {{$total}}<br>
        Total de Pedidos Aprovados: {{$total_autorizado}}<br>
        Total de Pedidos Negados: {{$total_negados}}<br>
        Total de Pedidos Pendentes: {{$total_pendentes}}<br><br>
        

        </div>      

        <a href="/gerarExcel/?busca={{ request()->get('busca') }}" class="btn btn-info">
        <i class="fa fa-file" aria-hidden="true"></i>
        Exportar planilha
        
        </a>
        <br><br>

            <form method="get" action="/pedidos_realizados">
                <div class="row">
                    <div class=" col-sm input-group">

                        <input type="text" class="form-control" name="busca" value="{{Request()->busca}}" placeholder="Busca por nome do requisitante">  

                        <span class="input-group-btn">
                                <button type="submit" class="btn btn-success"> Buscar </button>
                        </span>
                    </div>
                </div>
            </form>
            <br>


            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Arquivo Requisitado</th>
                        <th>Status</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Finalidade</th>
                        <th>Justificativa para Acesso Negado</th>
                        <th>Data do Pedido</th>
                        <th>Data da Análise</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedidos->sortBy('created_at') as $pedido)
                        <tr>
                            <td>             
                                {{ $pedido->file->name }}  ({{ $pedido->file->original_name }})
                            </td>
                            <td>            
                                @if($pedido->negado == true)
                                    Acesso Negado
                                @elseif($pedido->negado =! true || $pedido->autorizador_id == true)
                                    Acesso Autorizado
                                @else
                                    Pendente
                                @endif
                            </td>
                            <td>            
                                {{$pedido->nome}}
                            </td>
                            <td>
                                {{$pedido->email}}
                            </td>
                            <td>
                                {{$pedido->finalidade}} 
                            </td>
                            <td>
                                {{$pedido->justificativa}} 
                            </td>
                            <td>
                                {{ date('d/m/Y', strtotime($pedido->created_at)) }}
                            </td> 
                            <td>
                                @if($pedido->autorizado_em) 
                                    {{ date('d/m/Y', strtotime($pedido->autorizado_em)) }}
                                @endif
                            </td>                
                        </tr>
                    @endforeach
                </tbody>
            </table>


    </div>  
</div>
@endsection