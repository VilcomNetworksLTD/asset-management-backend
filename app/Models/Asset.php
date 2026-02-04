Schema::create('assets', function (Blueprint $table) {
    $table->id();
    $table->string('asset_name');
    $table->string('asset_category');
    $table->string('serial_no')->unique();
    $table->foreignId('supplier_id')->constrained();
    $table->foreignId('employee_id')->nullable()->constrained();
    $table->foreignId('status_id')->constrained();
    $table->decimal('price', 10, 2);
    $table->string('warranty_details')->nullable();
    $table->string('license_info')->nullable();
    $table->timestamps();
});
