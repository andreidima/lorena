<?php

use App\Support\Modules\ModuleRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schemas = ModuleRegistry::allEntitySchemas();

        foreach ($schemas as $tableName => $schema) {
            Schema::create($tableName, function (Blueprint $table) use ($schema) {
                $table->id();

                foreach ($schema['columns'] as $column) {
                    $name = $column['name'];
                    $type = $column['type'] ?? 'string';

                    switch ($type) {
                        case 'integer':
                            $table->integer($name)->nullable();
                            break;
                        case 'bigint':
                            $table->unsignedBigInteger($name)->nullable();
                            $table->index($name);
                            break;
                        case 'decimal':
                            $table->decimal($name, 15, 2)->nullable();
                            break;
                        case 'text':
                            $table->text($name)->nullable();
                            break;
                        default:
                            $table->string($name, 255)->nullable();
                            break;
                    }
                }

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $tables = array_keys(ModuleRegistry::allEntitySchemas());
        $tables = array_reverse($tables);

        foreach ($tables as $tableName) {
            Schema::dropIfExists($tableName);
        }
    }
};
