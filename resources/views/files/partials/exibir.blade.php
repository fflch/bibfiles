<div class=row style="border: 10px;"> 
{{ $files->appends(request()->query())->links() }}
@can('admin') 
<a href="{{ route('download_planilha') }}" class="btn btn-info" style="position: absolute; right: 0; margin: 5px;">
<i class="fa fa-file" aria-hidden="true"></i> Exportar planilha </a>
@endcan('admin')
</div>

<table class="table table-striped">
    <thead>
            <tr>
            <th>Nome</th>
            <th>Arquivo</th>
        @can('admin')
            <th>Download (Apenas Administrador)</th>
            <th>Ações</th>
        @endcan('admin') 
</thead>

<tbody>
        @foreach($files as $arquivo)
            <tr>
                <td>{{$arquivo->name}}</td>
                <td>            
                <a href="/{{$arquivo->original_name}}" type="application/pdf image/bmp" target="pdf-frame"><i class="fas fa-file"></i> {{$arquivo->original_name}} </a>
                </td>
        @can('admin')
                <td>
                    <a href="/files/{{$arquivo->id}}" type="application/pdf image/bmp" target="pdf-frame"><i class="fas fa-file"></i></a>
                <td>

            <form method="post" action="/files/{{$arquivo->id}}">         
                @csrf
                @method('delete')
                <button class="btn btn-default btn-sm" type="submit" onclick="return confirm('Tem certeza que deseja deletar?')"><i class="fas fa-trash-alt"></i></button>
            </form></td>
        @endcan('admin')
            </tr>
        @endforeach
    </tbody>
</table>

{{ $files->appends(request()->query())->links() }}
