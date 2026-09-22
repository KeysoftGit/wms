# Rencana Buku Stock Batch No Only

Dokumen ini menjelaskan rencana perubahan umum untuk identitas stok di
`Buku_Stock`.

## Goal

Identitas stok `Buku_Stock` nantinya hanya menggunakan:

- `PartID`
- `WarehouseID`
- `BatchNo`

Aplikasi tidak lagi menggunakan kolom berikut sebagai atribut identitas stok
di `Buku_Stock`:

- `SerialNo`
- `ExpDate`
- `BIN`
- `LOC`

Perubahan ini direncanakan berlaku secara umum untuk API, web, helper logic,
dan modul transaksi. Implementasinya tetap perlu bertahap supaya risiko lebih
terkendali.

## Catatan Penamaan

Nama kolom di database/kode adalah `BatchNo`.

Nama file dokumen ini mengikuti request `buku_stock_bacth_no.md`, tetapi
implementasi tetap harus memakai penulisan existing `BatchNo`, kecuali schema
database memang di-rename lewat migrasi terpisah.

## Area Yang Terdampak

Referensi utama identitas stok tersebar di:

- `app/Models/BukuStock.php`
- `app/Helpers/BukuStockHelper.php`
- `app/Http/Controllers/HelperController.php`
- `app/Http/Controllers/Api/StockMonitorController.php`
- `app/Http/Requests/StockMonitor/*`
- Web Stock Monitoring views/controllers
- Transaction controllers that insert or consume `Buku_Stock`
- API transaction controllers that insert or consume `Buku_Stock`

Contoh modul transaksi yang perlu dicek satu per satu:

- Goods Receipt
- Delivery Order
- Part Usage
- Stock Adjustment
- Stock Opname
- Purchase Return
- Item Transfer
- Direct Purchase execution

## Target Perilaku

Stock availability, stock monitoring, dan validasi konsumsi stok harus
melakukan grouping/filter stok berdasarkan:

```text
PartID + WarehouseID + BatchNo
```

Stok tidak lagi dipisahkan berdasarkan:

```text
SerialNo + ExpDate + BIN + LOC
```

Kalau row lama masih memiliki nilai di `SerialNo`, `ExpDate`, `BIN`, atau
`LOC`, logic baru harus mengabaikan nilai tersebut saat menghitung identitas
stok.

## Aturan Helper

`BukuStockHelper::calculateCurrentStock()` nantinya harus menjadi sumber utama
untuk perhitungan stok dengan field:

- `PartID`
- optional `WarehouseID`
- optional `BatchNo`

Rekomendasi signature final:

```php
public static function calculateCurrentStock(
    string $partId,
    ?string $warehouseId = null,
    ?string $batchNo = null
): float
```

Saat migrasi, hindari langsung mengubah signature helper sebelum semua caller
diaudit. Langkah antara yang lebih aman adalah menambah method helper baru,
memindahkan caller satu per satu, lalu menghapus parameter identitas lama.

## Aturan API

Endpoint API tidak lagi menerima filter identitas stok untuk:

- `serial_no`
- `exp_date`
- `bin`
- `loc`

Response API tidak lagi expose field tersebut pada response yang mewakili
identitas stok atau detail stock monitoring.

Untuk endpoint pilihan detail stok, hanya `batch_no` yang tetap menjadi stock
attribute option.

## Aturan Web

Halaman web harus menghapus control dan kolom identitas stok untuk:

- Serial No
- Exp Date
- BIN
- LOC

Batch No tetap menjadi satu-satunya field filter/display atribut stok.

Kalau target pertama adalah Flutter, perubahan web sebaiknya dilakukan setelah
perilaku API stabil.

## Aturan Transaksi

Modul transaksi tidak lagi bergantung pada input `SerialNo`, `ExpDate`, `BIN`,
dan `LOC` untuk validasi stok dan insert `Buku_Stock`.

Saat insert `Buku_Stock`, modul hanya perlu mengisi:

- `PartID`
- `WarehouseID`
- `BatchNo`
- transaction metadata
- `Qty`

Field identitas yang dihapus dari logic bisa dibiarkan `null` selama kolomnya
masih ada di database.

## Dampak Serial Number

Kode saat ini memakai `MsPart.WithSerialNo` di beberapa flow. Menghapus
`SerialNo` dari `Buku_Stock` akan mengubah makna stok yang sebelumnya
dikontrol berdasarkan serial.

Sebelum menghapus validasi serial secara global, pastikan business rule baru:

- Serial number tidak lagi ditrack di stok, atau
- Serial number ditrack di tempat lain di luar `Buku_Stock`, atau
- Serial number hanya informasi dan bukan bagian dari stock availability.

Jangan hapus validasi serial dari modul transaksi sebelum aturan ini jelas.

## Dampak BIN dan LOC

File, master data, dan module terkait BIN/LOC tetap dipertahankan:

- `Ms_LOC`
- `Ms_BIN`
- `Ms_PartBIN`
- `PartBinHelper`

Namun logic tersebut tidak boleh dipakai lagi oleh flow `Buku_Stock`.

Artinya:

- Jangan gunakan `BIN` dan `LOC` sebagai filter identitas stok.
- Jangan gunakan `BIN` dan `LOC` dalam validasi ketersediaan stok.
- Jangan panggil `PartBinHelper` dari flow yang menghitung, consume, atau insert
  `Buku_Stock`.
- Jangan hapus file/controller/model/master BIN-LOC pada phase ini.
- Kalau module BIN-LOC masih ada di menu, itu hanya dipertahankan sebagai module
  existing dan bukan bagian dari identitas stok `Buku_Stock`.

## Rekomendasi Tahapan Migrasi

### Phase 1: API Stock Monitoring

Mulai dari API yang dipakai Flutter:

- `app/Http/Requests/StockMonitor/GetStockMovementsRequest.php`
- `app/Http/Requests/StockMonitor/GetStockDetailOptionsRequest.php`
- `app/Http/Controllers/Api/StockMonitorController.php`

Batasi request/response/filter ke:

- `part_id`
- `warehouse_id`
- `unit_id`
- `batch_no`
- pagination

### Phase 2: Shared Stock Detail Endpoint

Update logic pilihan detail stok yang dipakai bersama:

- `app/Http/Controllers/HelperController.php`

Hanya pertahankan `batch_no` sebagai target available stock detail.

Phase ini bisa berdampak ke layar web, jadi lakukan setelah semua caller dicek.

### Phase 3: Stock Calculation Helper

Update atau ganti:

- `app/Helpers/BukuStockHelper.php`

Pindahkan perhitungan stok ke `PartID + WarehouseID + BatchNo`.

Audit semua caller sebelum mengubah signature method.

### Phase 4: Modul Transaksi

Update modul yang create, edit, delete, atau validate row `Buku_Stock`.

Untuk phase awal transaksi, fokus hanya API yang dipakai Flutter. Web/Blade
dikerjakan nanti di phase terpisah.

Untuk setiap modul:

1. Hapus handling form/request untuk `SerialNo`, `ExpDate`, `BIN`, dan `LOC`.
2. Ubah validasi stok supaya hanya memakai `BatchNo`.
3. Insert row `Buku_Stock` dengan field identitas lama sebagai `null`.
4. Update response API.
5. Jalankan syntax check dan test transaksi per modul.

File transaksi API yang sudah teridentifikasi:

- `app/Http/Controllers/Api/Transaction/PartUsageController.php` - selesai
- `app/Http/Requests/Transaction/PartUsage/StoreTransactionRequest.php` - selesai
- `app/Http/Requests/Transaction/PartUsage/UpdateTransactionRequest.php` - selesai
- `app/Http/Controllers/Api/Transaction/StockAdjustmentController.php` - selesai
- `app/Http/Requests/Transaction/StockAdjustment/GetStockDetailRequest.php` - selesai
- `app/Http/Requests/Transaction/StockAdjustment/StoreTransactionRequest.php` - selesai
- `app/Http/Requests/Transaction/StockAdjustment/UpdateTransactionRequest.php` - selesai
- `app/Http/Controllers/Api/Transaction/ItemTransferController.php` - selesai
- `app/Http/Requests/Transaction/ItemTransfer/StoreTransactionRequest.php` - selesai
- `app/Http/Requests/Transaction/ItemTransfer/UpdateTransactionRequest.php` - selesai

Urutan yang disarankan:

1. API Part Usage.
2. API Stock Adjustment.
3. API Item Transfer.

Alasan urutan ini: mulai dari API yang kemungkinan paling dekat dengan Flutter,
dan `ItemTransfer` ditaruh setelahnya karena saat ini masih kuat memakai
`BIN/LOC`.

Catatan implementasi API Part Usage:

- Request store/update hanya menerima `batch_no` sebagai detail stok.
- Response detail tidak lagi memilih `SerialNo`, `ExpDate`, `BIN`, dan `LOC`.
- Validasi stok memakai `BukuStockHelper::calculateCurrentStockByBatchNo()`
  dengan `transaction_date` transaksi, sehingga stok dihitung sampai tanggal
  transaksi tersebut.
- Signature helper wajib mengirim `transactionDate`:
  `calculateCurrentStockByBatchNo($partId, $warehouseId, $batchNo, $transactionDate)`.
- Insert `Trans_PartUsageDT` dan `Buku_Stock` tetap mengisi kolom lama sebagai
  `null` selama kolom database masih ada.
- Validasi serial stock dihapus dari flow API Part Usage.

Catatan implementasi API Stock Adjustment:

- Request `getStockDetail`, store, dan update hanya menerima `batch_no` sebagai
  detail stok.
- Response detail tidak lagi memilih `SerialNo`, `ExpDate`, `BIN`, dan `LOC`.
- `getStockDetail` dan proses insert menghitung `QtyStock` memakai
  `BukuStockHelper::calculateCurrentStockByBatchNo()`.
- Proses insert store/update menghitung `QtyStock` sampai `transaction_date`.
  Endpoint preview `getStockDetail` tetap menghitung stok current selama request
  belum mengirim tanggal.
- Flow adjustment tetap memakai rumus `QtyOpname - QtyStock` untuk menentukan
  selisih yang diinsert ke `Trans_InventoryAdjustmentExecution` dan
  `Buku_Stock`.
- Insert `Trans_InventoryAdjustmentDT`, `Trans_InventoryAdjustmentExecution`,
  dan `Buku_Stock` tetap mengisi kolom lama sebagai `null` selama kolom database
  masih ada.
- Validasi serial stock dan validasi BIN/LOC dihapus dari flow API Stock
  Adjustment.

Catatan implementasi API Item Transfer:

- Request store/update hanya menerima `batch_no` sebagai detail stok.
- Response detail tidak lagi memilih `BIN`, `LOC`, `NEW_BIN`, dan `NEW_LOC` dari
  detail table. `BatchNo` diresponse dari row `Buku_Stock` source berdasarkan
  `TransactionNo` dan `Sequence`.
- Validasi stok source memakai
  `BukuStockHelper::calculateCurrentStockByBatchNo()` berdasarkan
  `PartID + WarehouseIDFrom + BatchNo + transaction_date`.
- Flow transfer tetap insert dua row `Buku_Stock` per detail: qty negatif dari
  warehouse source dan qty positif ke warehouse target.
- `BatchNo` yang sama dipakai untuk row source dan target.
- `Trans_DirectItemTransferDT` belum memiliki kolom `BatchNo`, jadi batch hanya
  menjadi identitas stok di `Buku_Stock` untuk phase API ini.
- Kolom legacy `SerialNo`, `ExpDate`, `BIN`, dan `LOC` di `Buku_Stock` diisi
  `null`.
- `Trans_DirectItemTransferDT` tidak diisi kolom `BIN`, `LOC`, `NEW_BIN`, atau
  `NEW_LOC` dari API batch-only karena beberapa tenant belum memiliki kolom
  tersebut.
- Validasi BIN/LOC dihapus dari flow API Item Transfer.

### Phase 5: Modul Web

Phase web dikerjakan nanti setelah API stabil.

### Phase 6: Database Cleanup

Setelah semua code path berhenti menggunakan kolom lama, baru putuskan apakah:

- kolom tetap dipertahankan nullable untuk kompatibilitas history, atau
- data dimigrasi/diarsipkan lalu kolom di-drop.

Jangan drop kolom sebelum semua report, import, export, API, dan flow transaksi
diaudit.

## Checklist Testing

- Summary stock monitoring melakukan grouping hanya berdasarkan Batch No.
- Stock detail options hanya mengembalikan Batch No.
- Konsumsi stok dari Part/Warehouse/Batch yang sama mengabaikan perbedaan lama di Serial/Exp/BIN/LOC.
- Update transaksi tidak memblokir dirinya sendiri saat revalidasi stok.
- Row lama `Buku_Stock` yang masih punya Serial/Exp/BIN/LOC tetap muncul di history.
- Unit conversion tetap berjalan.
- Request model Flutter tidak lagi mengirim field yang dihapus.
- Response model Flutter tidak lagi mengharapkan field yang dihapus.

## Catatan Kompatibilitas

Selama migrasi, hindari mencampur perilaku identitas stok lama dan baru dalam
satu flow transaksi. Satu modul sebaiknya sepenuhnya memakai identitas lama
atau sepenuhnya memakai Batch No only.
