<{{$php_start}}

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


class {{$cname}} extends Migration
{

    public $sql_up = [
    @foreach ($sql_up as $value)
    "{!!$value!!}",
    @endforeach
];

    public $sql_down = [
    @foreach ($sql_down as $value)
    "{!!$value!!}",
    @endforeach
];    

    public function up(){
        foreach($this->sql_up as $sq){
            DB::statement($sq);
        }
	}

	public function down(){
        foreach($this->sql_down as $sq){
            DB::statement($sq);
        }
    }

}