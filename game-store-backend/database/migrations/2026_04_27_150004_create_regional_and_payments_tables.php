<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v2.0 / Phase 1 / Batch C — региональные настройки + крипто-платежи.
 *
 * region_settings — хранит контент-блоки, видимые в определённых регионах
 * (Phase 7: расширенный функционал для РФ юзеров).
 *
 * pending_payments — окно оплаты крипто-транзакции (Phase 5: TRC-20/BEP-20/TRX
 * через TronGrid + BscScan). Юзер видит уникальную дробную сумму и адрес,
 * фоновая job ищет матч в blockchain.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── region_settings: контент по регионам ──────────────────────────
        // Гибкая модель ключ-значение. Примеры:
        //   region_code='RU', key='extra_posts', value=[post_id_1, post_id_2]
        //   region_code='RU', key='shop_banner', value={title:'...', cta_url:'...'}
        //   region_code='RU', key='delivery_methods', value=['telegram_bot','email']
        Schema::create('region_settings', function (Blueprint $table) {
            $table->id();
            $table->string('region_code', 8);                    // 'RU', 'EU', 'OTHER', 'ALL'
            $table->string('key', 64);                           // 'extra_posts', 'shop_banner', etc.
            $table->json('value');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();             // для админки: «что это и зачем»
            $table->timestamps();

            $table->unique(['region_code', 'key']);
            $table->index('is_active');
        });

        // ── pending_payments: окна оплаты криптой ─────────────────────────
        // Workflow (Phase 5):
        //   1. Юзер жмёт «Оплатить» → бэк создаёт pending с уникальной
        //      дробной суммой (16.473921 USDT, не 16.50)
        //   2. Юзер видит адрес + QR + 15-мин таймер
        //   3. Cron job каждые 30 сек дёргает TronGrid/BscScan, ищет
        //      транзакцию с этой суммой на наш адрес
        //   4. Найдено и подтверждено → status=confirmed, выдаём ключ
        //   5. Не найдено за 15 мин → status=expired, юзер пробует снова
        Schema::create('pending_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('order_id')
                  ->nullable()
                  ->constrained('orders')
                  ->nullOnDelete();                              // если платёж за конкретный заказ
            $table->enum('crypto_currency', ['USDT_TRC20', 'USDT_BEP20', 'TRX']);
            $table->decimal('amount_rub', 12, 2);                // изначальная цена в рублях
            $table->decimal('amount_crypto', 18, 8);             // сумма в крипте с уникальным дробным хвостом
            $table->decimal('exchange_rate', 18, 8);             // курс на момент создания (rub per 1 crypto unit)
            $table->string('recipient_address');                 // твой кошелёк-получатель
            $table->enum('status', ['pending', 'confirmed', 'expired', 'failed'])
                  ->default('pending');
            $table->string('transaction_hash')->nullable();       // hash в blockchain после confirm
            $table->unsignedSmallInteger('confirmations')->default(0);
            $table->timestamp('expires_at');                     // 15 мин после created_at
            $table->timestamp('confirmed_at')->nullable();
            $table->json('metadata')->nullable();                // на случай хранения ответа TronGrid целиком
            $table->timestamps();

            $table->index(['user_id', 'status']);                // "мои незавершённые платежи"
            $table->index(['status', 'expires_at']);             // cron очистки expired
            $table->unique('transaction_hash');                  // защита от двойного учёта
            $table->index(['recipient_address', 'amount_crypto', 'status']);  // поиск матча в блокчейне
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_payments');
        Schema::dropIfExists('region_settings');
    }
};
