<{{$php_start}}

namespace {{$name_space}};

use hws\rmc\Model;

class {{$model_name}} extends Model
{
    protected $table = '{{$table}}';

@if($primaryKey)
    protected $primaryKey = '{{$primaryKey}}';

@endif
@if($useStatus)
    static $useStatus = true;

@endif
@if($timestamps)
    public $timestamps = false;

@endif
    public function defaultCollection(){
        return $this->toArray();
    }

@if(!empty($fillable))
    protected $fillable = [
    @foreach ($fillable as $value)
    "{{$value}}",
    @endforeach
];

@endif
@if(!empty($casts))
    protected $casts = [
    @foreach ($casts as $key => $value)
    "{{$key}}" => "{{$value}}",
    @endforeach
];

@endif
@if(!empty($validation))
    protected $validation = [
    @foreach ($validation as $key => $value)
    "{{$key}}" => "{{$value}}",
    @endforeach
];

@endif
@if(!empty($labels))
    protected $labels = [
    @foreach ($labels as $key => $value)
    "{{$key}}" => "{{$value}}",
    @endforeach
];

@endif
@if(!empty($relations))
@foreach ($relations as $key => $value)
    public function {{$value['name']}}(){
        return $this->{{$value['rel']}}({{$value['class']}}::class,"{{$value['foreign_key']}}","{{$value['local_key']}}");
    }

@endforeach
@endif
}