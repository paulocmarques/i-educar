<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class CreatePortalAcessoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared(
            '
                SET default_with_oids = true;
                
                CREATE TABLE portal.acesso (
                    cod_acesso integer DEFAULT nextval(\'portal.acesso_cod_acesso_seq\'::regclass) NOT NULL,
                    data_hora timestamp without time zone NOT NULL,
                    ip_externo character varying(50) DEFAULT \'\'::character varying NOT NULL,
                    ip_interno character varying(255) DEFAULT \'\'::character varying NOT NULL,
                    cod_pessoa integer DEFAULT 0 NOT NULL,
                    obs text,
                    sucesso boolean DEFAULT true NOT NULL
                );
            '
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('portal.acesso');
    }
}
