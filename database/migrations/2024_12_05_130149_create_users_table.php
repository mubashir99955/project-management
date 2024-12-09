<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->string('country', 50)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->enum('is_verified', ['true', 'false'])->default('false');
            $table->enum('account_status', ['active', 'inactive','blocked'])->default('inactive');
            $table->integer('otp_status')->default(0);
            $table->integer('otp_attempts')->default(0);
            $table->string('otp', 15)->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('ban_until')->nullable(); // Adding the ban_until column
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
