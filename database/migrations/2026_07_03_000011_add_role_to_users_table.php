<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(User::ROLE_PLAYER)->after('password');
        });

        User::query()->whereNull('role')->update(['role' => User::ROLE_PLAYER]);

        User::query()->updateOrCreate(
            ['email' => 'admin@yelgolf.local'],
            [
                'name' => 'admin',
                'password' => Hash::make(config('admin.password', 'test')),
                'role' => User::ROLE_ADMIN,
            ],
        );
    }

    public function down(): void
    {
        User::query()->where('email', 'admin@yelgolf.local')->where('role', User::ROLE_ADMIN)->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};