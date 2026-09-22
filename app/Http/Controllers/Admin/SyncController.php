<?php

namespace App\Http\Controllers\Admin;

use App\Models\Menu;
use App\Models\MsWarehouse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class SyncController extends Controller
{

    public function index()
    {
        $tasks = [

            ['id' => 1, 'nama' => 'Sync WMS', 'route' => 'sync.wms', 'notes' => 'Menu and master tables'],
        ];

        return view('admin.sync.index', compact('tasks'));
    }

    public function syncWms()
    {
        $db_name = session('db_database');
        try {

            if (!Schema::hasColumn('Ms_User', 'jwt_token')) {
                DB::statement("ALTER TABLE [Ms_User] ADD jwt_token NVARCHAR (MAX) NULL");
            }

            if (!Schema::hasTable('Ms_LOC')) {
                DB::statement("
                    CREATE TABLE [$db_name].[dbo].[Ms_LOC] (
                        id BIGINT IDENTITY(1,1) PRIMARY KEY,
                        WarehouseID NVARCHAR(50) NOT NULL,
                        LOC NVARCHAR(100) NOT NULL,
                        created_at DATETIME DEFAULT GETDATE(),
                        updated_at DATETIME DEFAULT GETDATE(),
                        CONSTRAINT FK_Ms_LOC_Warehouse FOREIGN KEY (WarehouseID) REFERENCES [$db_name].[dbo].[Ms_Warehouse](WarehouseID)
                    )
                ");
            }

            if (!Schema::hasTable('Ms_BIN')) {
                DB::statement("
                    CREATE TABLE [$db_name].[dbo].[Ms_BIN] (
                        id BIGINT IDENTITY(1,1) PRIMARY KEY,
                        loc_id BIGINT NOT NULL,
                        BIN NVARCHAR(100) NOT NULL,
                        created_at DATETIME DEFAULT GETDATE(),
                        updated_at DATETIME DEFAULT GETDATE(),
                        CONSTRAINT FK_Ms_BIN_LOC FOREIGN KEY (loc_id) REFERENCES [$db_name].[dbo].[Ms_LOC](id)
                    )
                ");
            }

            if (!Schema::hasTable('Ms_PartBIN')) {
                DB::statement("
                    CREATE TABLE [$db_name].[dbo].[Ms_PartBIN] (
                        id BIGINT IDENTITY(1,1) PRIMARY KEY,
                        PartID NVARCHAR(50) NOT NULL,
                        bin_id BIGINT NOT NULL,
                        created_at DATETIME DEFAULT GETDATE(),
                        updated_at DATETIME DEFAULT GETDATE(),
                        CONSTRAINT FK_Ms_PartBIN_Part FOREIGN KEY (PartID) REFERENCES [$db_name].[dbo].[Ms_Part](PartID),
                        CONSTRAINT FK_Ms_PartBIN_BIN FOREIGN KEY (bin_id) REFERENCES [$db_name].[dbo].[Ms_BIN](id)
                    )
                ");
            }

            if (!Schema::hasTable('Ms_QR')) {
                DB::statement("
                    CREATE TABLE [$db_name].[dbo].[Ms_QR] (
                        id BIGINT IDENTITY(1,1) PRIMARY KEY,
                        code NVARCHAR(30) NOT NULL UNIQUE,
                        json_value NVARCHAR(MAX) NOT NULL,
                        json_display NVARCHAR(MAX) NOT NULL,
                        show_content BIT NOT NULL DEFAULT 0,
                        created_at DATETIME DEFAULT GETDATE(),
                        updated_at DATETIME DEFAULT GETDATE(),
                        CONSTRAINT CK_Ms_QR_json_value CHECK (ISJSON(json_value) = 1),
                        CONSTRAINT CK_Ms_QR_json_display CHECK (ISJSON(json_display) = 1)
                    )
                ");
            }

            if (!Schema::hasColumn('Trans_DeliveryOrderDT', 'BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DeliveryOrderDT] ADD BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_DeliveryOrderDT', 'LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DeliveryOrderDT] ADD LOC NVARCHAR(255) NULL");
            }

            if (
                Schema::hasColumn('Trans_DeliveryOrderHD', 'VehicleID')
                && !$this->isColumnNullable('Trans_DeliveryOrderHD', 'VehicleID')
            ) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DeliveryOrderHD] ALTER COLUMN VehicleID NVARCHAR(50) NULL");
            }

            if (
                Schema::hasColumn('Trans_DeliveryOrderHD', 'DriverID')
                && !$this->isColumnNullable('Trans_DeliveryOrderHD', 'DriverID')
            ) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DeliveryOrderHD] ALTER COLUMN DriverID NVARCHAR(50) NULL");
            }

            if (!Schema::hasColumn('Trans_PartUsageDT', 'BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_PartUsageDT] ADD BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_PartUsageDT', 'LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_PartUsageDT] ADD LOC NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_DirectPurchaseDT', 'BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DirectPurchaseDT] ADD BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_DirectPurchaseDT', 'LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DirectPurchaseDT] ADD LOC NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_PurchaseReturnDT', 'BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_PurchaseReturnDT] ADD BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_PurchaseReturnDT', 'LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_PurchaseReturnDT] ADD LOC NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_StockOpnameDT', 'BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_StockOpnameDT] ADD BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_StockOpnameDT', 'LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_StockOpnameDT] ADD LOC NVARCHAR(255) NULL");
            }


            if (!Schema::hasColumn('Trans_InventoryAdjustmentDT', 'BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_InventoryAdjustmentDT] ADD BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_InventoryAdjustmentDT', 'LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_InventoryAdjustmentDT] ADD LOC NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_InventoryAdjustment_Execution', 'BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_InventoryAdjustment_Execution] ADD BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_InventoryAdjustment_Execution', 'LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_InventoryAdjustment_Execution] ADD LOC NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_DirectItemTransferDT', 'BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DirectItemTransferDT] ADD BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_DirectItemTransferDT', 'LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DirectItemTransferDT] ADD LOC NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_DirectItemTransferDT', 'NEW_BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DirectItemTransferDT] ADD NEW_BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_DirectItemTransferDT', 'NEW_LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DirectItemTransferDT] ADD NEW_LOC NVARCHAR(255) NULL");
            }

            // Buat butuhan Location Adjustment
            if (!Schema::hasColumn('Trans_DirectItemTransferDT', 'WarehouseIDFrom')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_DirectItemTransferDT] ADD WarehouseIDFrom NVARCHAR(50) NULL");
            }

            if (!Schema::hasColumn('Trans_ItemTransferExecuteDT', 'BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_ItemTransferExecuteDT] ADD BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_ItemTransferExecuteDT', 'LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_ItemTransferExecuteDT] ADD LOC NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_ItemTransferReceiveDT', 'BIN')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_ItemTransferReceiveDT] ADD BIN NVARCHAR(255) NULL");
            }

            if (!Schema::hasColumn('Trans_ItemTransferReceiveDT', 'LOC')) {
                DB::statement("ALTER TABLE [$db_name].[dbo].[Trans_ItemTransferReceiveDT] ADD LOC NVARCHAR(255) NULL");
            }

            MsWarehouse::firstOrCreate(
                ['WarehouseID' => 'XXX'],
                [
                    'WarehouseName' => 'NONE',
                    'CreatedBy' => auth()->user()->UserID ?? 'SYSTEM',
                    'EntryTime' => date('Y-m-d H:i:s'),
                    'LastUpdateBy' => auth()->user()->UserID ?? 'SYSTEM',
                    'LastUpdate' => date('Y-m-d H:i:s'),
                    'Active' => 1,
                    'IsAuto' => 0,
                ]
            );

            $datas = [
                [
                    'Name' => 'qr_generator',
                    'DisplayName' => 'QR Generator',
                    'actions' => 'view,add,edit,delete'
                ],
                [
                    'Name' => 'pr_execute',
                    'DisplayName' => 'Purchase Return Execute',
                    'actions' => 'view,add'
                ],
                [
                    'Name' => 'location_adjustment',
                    'DisplayName' => 'Location Adjustment',
                    'actions' => 'view,add,edit,delete'
                ],
                [
                    'Name' => 'do_execute',
                    'DisplayName' => 'Delivery Order Execute',
                    'actions' => 'view,add'
                ],
            ];


            foreach ($datas as $data) {
                Menu::updateOrCreate(
                    ['Name' => $data['Name']],
                    [
                        'DisplayName' => $data['DisplayName'],
                        'actions'    => $data['actions'],
                    ]
                );
            }


            $menus = Menu::all();

            foreach ($menus as $menu) {
                $actions = explode(',', $menu->actions);

                foreach ($actions as $action) {
                    $permission = $menu->Name . '.' . $action;
                    $check = Permission::where('name', $permission)->first();
                    if (!$check) {
                        Permission::create([
                            'name' => $permission,
                            'guard_name' => 'web',
                        ]);
                    }
                }
            }

            return redirect()->back()->with('success', 'Sync WMS Done. ' . $db_name);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Sync WMS Failed: ' . $e->getMessage());
        }
    }

    private function isColumnNullable(string $table, string $column): bool
    {
        $columnInfo = DB::table('INFORMATION_SCHEMA.COLUMNS')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first();

        return strtoupper((string) ($columnInfo->IS_NULLABLE ?? 'NO')) === 'YES';
    }
}
