<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('base_unit')->default('piece');
            $t->decimal('selling_price', 12, 2)->default(0);
            $t->decimal('quantity_on_hand', 14, 3)->default(0);
            $t->decimal('reorder_level', 14, 3)->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('is_sample')->default(false);
            $t->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('contact_person')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->boolean('is_active')->default(true);
            $t->boolean('is_sample')->default(false);
            $t->timestamps();
        });

        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->nullable()->unique();
            $t->foreignId('supplier_id')->constrained();
            $t->foreignId('created_by')->constrained('users');
            $t->text('notes')->nullable();
            $t->string('status')->default('pending');   // pending | partial | received
            $t->timestamp('received_at')->nullable();
            $t->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->decimal('ordered_qty', 14, 3);
            $t->decimal('received_qty', 14, 3)->default(0);
            $t->decimal('unit_cost', 12, 2)->default(0);
        });

        Schema::create('deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained();
            $t->string('delivery_reference')->nullable();
            $t->foreignId('received_by')->constrained('users');
            $t->timestamp('received_at');
            $t->timestamps();
        });

        Schema::create('delivery_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $t->foreignId('purchase_item_id')->constrained();
            $t->decimal('quantity', 14, 3);
        });

        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->string('receipt_no')->nullable()->unique();
            $t->foreignId('cashier_id')->constrained('users');
            $t->timestamp('sale_date');
            $t->decimal('total_amount', 14, 2)->default(0);
            $t->decimal('amount_paid', 14, 2)->default(0);
            $t->decimal('change_amount', 14, 2)->default(0);
            $t->string('status')->default('completed'); // completed | voided
            $t->foreignId('voided_by')->nullable()->constrained('users');
            $t->timestamp('voided_at')->nullable();
            $t->string('void_reason')->nullable();
            $t->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->decimal('quantity', 14, 3);
            $t->decimal('unit_price', 12, 2);   // price at time of sale
            $t->decimal('line_total', 14, 2);
        });

        Schema::create('stock_adjustments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained();
            $t->foreignId('requested_by')->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->decimal('expected_qty', 14, 3);
            $t->decimal('counted_qty', 14, 3);
            $t->decimal('quantity_delta', 14, 3);
            $t->string('reason')->nullable();
            $t->string('status')->default('pending');   // pending | approved | rejected
            $t->timestamp('requested_at');
            $t->timestamp('approved_at')->nullable();
        });

        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained();
            $t->foreignId('recorded_by')->constrained('users');
            $t->string('movement_type');                // opening | delivery | sale | adjustment | reversal
            $t->decimal('quantity_delta', 14, 3);
            $t->foreignId('purchase_item_id')->nullable()->constrained();
            $t->foreignId('delivery_item_id')->nullable()->constrained();
            $t->foreignId('sale_item_id')->nullable()->constrained();
            $t->foreignId('adjustment_id')->nullable()->constrained('stock_adjustments');
            $t->unsignedBigInteger('reverses_movement_id')->nullable();
            $t->string('reference')->nullable();
            $t->string('reason')->nullable();
            $t->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('activity');
            $t->string('details')->nullable();
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'activity_logs', 'stock_movements', 'stock_adjustments', 'sale_items', 'sales', 'delivery_items', 'deliveries', 'purchase_items', 'purchases', 'suppliers', 'products', 'categories'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
