<{{$php_start}}

namespace {{$ct_namespace}};

use hws\rmc\Controller3;
use Illuminate\Http\Request;

class {{$ct_name}} extends Controller3
{

    public $model = NincsIlyenModelLOL::class;

    public $select = [
        '*',
    ];

@if($list)
    public function list(){
        return $this->defaultList();
    }

@endif
@if($view)
    public function view(){
        return $this->defaultView();
    }

@endif
@if($create)
    public function create(){
        return $this->defaultCreate();
    }

@endif
@if($update)
    public function update(Request $request,$id){
        return $this->defaultUpdate();
    }

@endif
@if($delete)
    public function delete(Request $request,$id){
        return $this->defaultDelete();
    }

@endif
@if($export)
    public function export(Request $request){
        return $this->defaultExport('file_name');
    }

@endif
@if($csv)
    public function csv(Request $request){
        return 'csv';
    }

@endif
@if($pdf)
    public function pdf(Request $request){
        return 'pdf';
    }

@endif
@if($xls)
    public function xls(Request $request){
        return 'xls';
    }

@endif
@if($import)
    public function import(Request $request){
        return 'import';
    }
    
@endif
}
