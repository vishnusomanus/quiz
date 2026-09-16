<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('oauth_clients') && Schema::hasColumn('oauth_clients', 'user_id')) {
            Schema::table('oauth_clients', function (Blueprint $table): void {
                if (! Schema::hasColumn('oauth_clients', 'owner_id')) {
                    $table->nullableMorphs('owner');
                }

                if (! Schema::hasColumn('oauth_clients', 'redirect_uris')) {
                    $table->text('redirect_uris')->nullable();
                }

                if (! Schema::hasColumn('oauth_clients', 'grant_types')) {
                    $table->text('grant_types')->nullable();
                }
            });

            foreach (Passport::client()->cursor() as $client) {
                Model::withoutTimestamps(fn () => $client->forceFill([
                    'owner_id' => $client->user_id,
                    'owner_type' => $client->user_id
                        ? config('auth.providers.'.($client->provider ?: config('auth.guards.api.provider')).'.model')
                        : null,
                    'redirect_uris' => $client->redirect_uris,
                    'grant_types' => $client->grant_types,
                ])->save());
            }

            Schema::table('oauth_clients', function (Blueprint $table): void {
                $sm = Schema::getConnection()->getSchemaBuilder();

                $indexes = collect($sm->getIndexes('oauth_clients'))
                    ->pluck('name')
                    ->all();

                if (\in_array('oauth_clients_user_id_index', $indexes, true)) {
                    $table->dropIndex('oauth_clients_user_id_index');
                }

                $table->dropColumn([
                    'user_id',
                    'redirect',
                    'personal_access_client',
                    'password_client',
                ]);
            });
        }

        Schema::dropIfExists('oauth_personal_access_clients');

        if (! Schema::hasTable('oauth_device_codes')) {
            Schema::create('oauth_device_codes', function (Blueprint $table): void {
                $table->char('id', 80)->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->char('user_code', 8)->unique();
                $table->text('scopes');
                $table->boolean('revoked');
                $table->dateTime('user_approved_at')->nullable();
                $table->dateTime('last_polled_at')->nullable();
                $table->dateTime('expires_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_device_codes');

        if (! Schema::hasTable('oauth_personal_access_clients')) {
            Schema::create('oauth_personal_access_clients', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('client_id');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('oauth_clients') && Schema::hasColumn('oauth_clients', 'owner_id')) {
            Schema::table('oauth_clients', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->text('redirect')->nullable();
                $table->boolean('personal_access_client')->default(false);
                $table->boolean('password_client')->default(false);
            });

            Schema::table('oauth_clients', function (Blueprint $table): void {
                $table->dropMorphs('owner');
                $table->dropColumn(['redirect_uris', 'grant_types']);
            });
        }
    }
};
