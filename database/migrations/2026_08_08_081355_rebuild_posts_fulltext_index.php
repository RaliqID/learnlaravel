<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Force InnoDB to fully build the FULLTEXT index. An index created on an
     * empty table is not "activated" until rebuilt, so newly inserted rows are
     * not matchable by MATCH() ... AGAINST() until an OPTIMIZE runs.
     */
    public function up(): void
    {
        DB::statement('OPTIMIZE TABLE posts');
    }

    public function down(): void
    {
        //
    }
};
