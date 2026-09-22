# Keyone Online - New Module Development Guide

This guide outlines the standard architectural patterns and steps required to implement a new functional module in the Keyone Online application.

---

## 1. Routing Architecture

The project uses a modular routing system. Routes are organized into functional subdirectories within the `routes/` folder.

### Step A: Create the Route File
Create a new file in a relevant subdirectory (e.g., `routes/production/my_module.php`).
```php
use App\Http\Controllers\Production\MyModuleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['custom_auth', 'check_login'])->group(function (){
    Route::middleware(['permission:admin|my_module.view'])->group(function (){
        Route::get('/my_module', [MyModuleController::class, 'index'])->name('my_module');
        Route::get('/my_module/datatable', [MyModuleController::class, 'datatable'])->name('my_module.datatable');
    });
    // Add add, store, edit, update, delete routes with respective permissions...
});
```

### Step B: Register the Route File
Open `app/Providers/RouteServiceProvider.php` and add your new route file to the `boot()` method's `routes()` closure.
```php
Route::middleware('web')
    ->namespace($this->namespace)
    ->group(base_path('routes/production/my_module.php'));
```

---

## 2. Sidebar Navigation

The navigation menu is defined in `resources/views/partials/admin/_navigation.blade.php`.

### Pattern for Main Modules
1.  Define a permission array.
2.  Use a `nav-main-heading` for the section title.
3.  Use `nav-main-item` for the link.
4.  Gate access using `auth()->user()->hasAnyPermission($permissions)`.

```html
{{-- MY MODULE --}}
@php
    $permission_my_module = ['admin', 'my_module.view'];
@endphp
@if (auth()->user()->hasAnyPermission($permission_my_module))
    <li class="nav-main-heading">Heading Name</li>
    <li class="nav-main-item">
        <a class="nav-main-link {{ \Illuminate\Support\Facades\Route::is('my_module') ? 'active' : '' }}"
            href="{{ route('my_module') }}">
            <i class="nav-main-link-icon fa fa-fw fa-icon-name"></i>
            <span class="nav-main-link-name">Module Name</span>
        </a>
    </li>
@endif
```

---

## 3. Controller Implementation

Controllers should follow the pattern established in `SalesOrderController` or `DirectProductionController`.

### Key Standards:
- **Namespacing**: Use the subdirectory namespace (e.g., `namespace App\Http\Controllers\Production;`).
- **Eager Loading**: Always use `.with([...])` in `show` and `edit` methods to prevent N+1 query issues.
- **Validation**: Use `$this->validate($request, [...])` at the start of `store` and `update`.
- **Transactions**: Wrap all database-modifying logic in `DB::beginTransaction()`, `try...catch`, and `DB::commit()`.
- **Stock Logic**:
    - **Creation**: Verify sufficient stock for consumption items before processing. Throw an `\Exception` to trigger a rollback if stock is insufficient.
    - **Update/Delete**: Strictly validate stock levels before "reversing" or decrementing production output.
    - **Identity Lookup**: Use `where('id', $id)->first()` for lookups instead of `find($id)` if using auto-incrementing identity columns on non-PK tables.

---

## 4. View Implementation

The project uses a hybrid of **Laravel Blade** and **Vue.js 2.7**.

### Index View (`index.blade.php`)
- Uses **DataTables** with server-side processing.
- Format numeric columns in the backend controller using `auto_numeric_format($value)` for consistency.

### Add/Edit Views (`add.blade.php`)
- **Vue.js**: Use a Vue instance to manage form state and dynamic arrays (e.g., line items/details).
- **Select2**: Use the `<select2>` Vue component for searchable dropdowns. Add `select2=true` to the AJAX URL to show `ID - Name` format.
- **Flatpickr**: Standard date picker (`.js-flatpickr`).
- **AutoNumeric**: Use `<vue-autonumeric>` for currency and high-precision inputs. Always set `allowDecimalPadding: false` to hide trailing zeros.

---

## 5. Synchronization & Permissions

New modules must be registered in `app/Http/Controllers/Admin/SyncController.php`.

### Step A: Define the Task
Add the module to the `$tasks` array in the `index()` method.

### Step B: Implement Sync Logic
Create a sync method that:
1.  Adds the entry to the `menus` table.
2.  Generates permissions for each action (`view,add,edit,delete`).
3.  Alters/Creates necessary database columns (e.g., `BIGINT IDENTITY` `id` columns, `IsAuto`, `LastDigit`).

```php
public function syncMyModule() {
    $db_name = session('db_database');
    // Logic to add menu, permissions, and alter tables...
}
```
