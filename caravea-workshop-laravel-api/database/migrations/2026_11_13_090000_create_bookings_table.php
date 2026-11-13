<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Besides the table, this creates the triggers that enforce "no overlapping
     * bookings in the same service bay" inside the database. Application-level
     * validation alone cannot guarantee the rule, because two requests can both
     * pass validation before either inserts. SQLite allows only one writer at a
     * time and a BEFORE trigger runs inside the write statement, so the check
     * and the write it guards are atomic with respect to every other writer.
     *
     * Intervals are half-open [starts_at, ends_at), so back-to-back bookings are
     * allowed. Datetimes are stored as UTC "Y-m-d H:i:s" strings, which compare
     * correctly as text.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            throw new RuntimeException('The booking overlap triggers are only implemented for SQLite.');
        }

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('rego');
            $table->unsignedSmallInteger('service_bay');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['service_bay', 'starts_at']);
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER bookings_prevent_overlap_on_insert
            BEFORE INSERT ON bookings
            WHEN NEW.status <> 'cancelled'
            BEGIN
                SELECT RAISE(ABORT, 'bookings_service_bay_overlap')
                WHERE EXISTS (
                    SELECT 1 FROM bookings
                    WHERE service_bay = NEW.service_bay
                      AND status <> 'cancelled'
                      AND starts_at < NEW.ends_at
                      AND ends_at > NEW.starts_at
                );
            END;
            SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER bookings_prevent_overlap_on_update
            BEFORE UPDATE OF service_bay, starts_at, ends_at, status ON bookings
            WHEN NEW.status <> 'cancelled'
            BEGIN
                SELECT RAISE(ABORT, 'bookings_service_bay_overlap')
                WHERE EXISTS (
                    SELECT 1 FROM bookings
                    WHERE service_bay = NEW.service_bay
                      AND status <> 'cancelled'
                      AND starts_at < NEW.ends_at
                      AND ends_at > NEW.starts_at
                      AND id <> NEW.id
                );
            END;
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
