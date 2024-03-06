    public function {{$value['name']}}(){
        return $this->{{$value['rel']}}({{$value['class']}}::class,"{{$value['foreign_key']}}","{{$value['local_key']}}");
    }