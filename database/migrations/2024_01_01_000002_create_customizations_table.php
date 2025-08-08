<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('customizations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('price');
            $table->enum('type', ['topping', 'side']);
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('customizations');
    }
};
