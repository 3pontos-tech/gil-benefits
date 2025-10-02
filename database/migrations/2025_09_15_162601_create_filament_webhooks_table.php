<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inbound_webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('source');
            $table->string('event');
            $table->longText('url');
            $table->longText('payload');

            $table->timestamps();
            $table->softDeletes();
        });
    }
};
