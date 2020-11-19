<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class CreateSPSplitterCount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $config_database_definer = config('database.connections.' . config('database.default') . '.username');
        $procedureName = \App\Components\SQL_SP_VW_Identifier::SP_SPLITTER_COUNT;
        Db::connection()->getPdo()->exec("CREATE DEFINER=`$config_database_definer` @`%` FUNCTION `$procedureName` (str VARCHAR (200),delim CHAR (1)) RETURNS INT (11) RETURN (length(REPLACE (str,delim,concat(delim,' ')))-length(str))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ///no need to implement the down method for stored procedure
    }
}
