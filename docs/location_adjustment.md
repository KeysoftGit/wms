# Location Adjustment

Dokumen ini menjelaskan konsep module **Location Adjustment** untuk membetulkan
posisi stok antar warehouse saat kondisi fisik berbeda dengan catatan
`Buku_Stock`.

## Goal

Location Adjustment dipakai untuk kasus barang secara fisik ditemukan di
warehouse tertentu, tetapi saldo sistem `Buku_Stock` masih tercatat di warehouse
lain.

Contoh:

- Warehouse A punya batch `A01` sampai `A05`.
- Warehouse B punya batch `B01` sampai `B05`.
- Saat audit pagi semua sesuai.
- Siang hari batch `A05` ditemukan fisik di Warehouse B, tetapi `Buku_Stock`
  masih mencatat saldo `A05` di Warehouse A.
- Sistem membuat Location Adjustment:
  - `WarehouseIDFrom = Warehouse A`
  - `WarehouseIDTo = Warehouse B`
  - staff from dan staff to sama, yaitu staff yang melakukan scan/audit
  - detail: part + batch `A05` + qty fisik yang ditemukan di Warehouse B

## Prinsip Utama

`Location Adjustment` bukan flow request transfer biasa. Flow ini adalah koreksi
lokasi berdasarkan hasil scan fisik.

Aturan intinya:

- Warehouse pertama yang discan adalah **warehouse fisik yang sedang diaudit**.
- Barcode part/item yang discan adalah barang yang benar-benar ada di warehouse
  fisik tersebut.
- Sistem compare posisi fisik dengan saldo `Buku_Stock`.
- Jika saldo `Buku_Stock` untuk `PartID + BatchNo` sudah ada di warehouse yang
  sedang discan, tidak perlu membuat Location Adjustment.
- Jika saldo `Buku_Stock` untuk `PartID + BatchNo` ada di warehouse lain, buat
  Location Adjustment dari warehouse sistem ke warehouse fisik.
- Jika saldo tidak ada di warehouse manapun, jangan auto-create adjustment. Ini
  harus dianggap stock anomaly lain dan perlu review manual.

## Stock Identity

Mengikuti rencana `Buku_Stock` batch-no-only, identitas stok yang dipakai:

```text
PartID + WarehouseID + BatchNo
```

Kolom berikut tidak dipakai untuk menentukan lokasi stok:

- `SerialNo`
- `ExpDate`
- `BIN`
- `LOC`

## Backend Storage

Untuk tahap awal, module ini bisa reuse table/model Direct Item Transfer:

- Header: `Trans_DirectItemTransferHD`
- Detail: `Trans_DirectItemTransferDT`
- Ledger: `Buku_Stock`

Namun controller, route, permission, dan transaction type tetap dipisah sebagai
module baru.

Perubahan flow terbaru:

- Tambah kolom nullable `WarehouseIDFrom` di `Trans_DirectItemTransferDT`.
- Untuk Location Adjustment, `Trans_DirectItemTransferHD.WarehouseIDFrom` tidak
  merepresentasikan source warehouse real. Isi default `XXX`.
- Warehouse default `XXX` wajib tersedia di `Ms_Warehouse`; backend harus
  membuatnya dengan `firstOrCreate` sebelum dipakai sebagai
  `Trans_DirectItemTransferHD.WarehouseIDFrom`.
- Source warehouse real disimpan per detail di
  `Trans_DirectItemTransferDT.WarehouseIDFrom`.
- `Trans_DirectItemTransferHD.WarehouseIDTo` tetap warehouse fisik hasil scan
  pertama.
- Dalam sekali proses Location Adjustment, satu header bisa punya banyak detail
  dari banyak source warehouse.
- Karena proses ini berbeda dari Direct Item Transfer biasa, module tetap harus
  punya controller/API/permission sendiri walaupun table header/detail yang
  dipakai sama.

Rekomendasi:

- Controller API baru: `Api\Transaction\LocationAdjustmentController`
- Route prefix baru: `/api/location-adjustment`
- Permission baru:
  - `location_adjustment.view`
  - `location_adjustment.add`
  - `location_adjustment.edit`
  - `location_adjustment.delete`
- `Buku_Stock.TransactionType = LOCATION_ADJUSTMENT`

Catatan penting: karena header/detail reuse table Direct Item Transfer, perlu
cara membedakan transaksi direct transfer biasa vs location adjustment.
Prioritas terbaik:

1. Tambah kolom marker di header, misalnya `SourceModule` atau
   `TransactionCategory`.
2. Kalau schema belum boleh diubah, pakai `Buku_Stock.TransactionType` sebagai
   sumber filter dengan `whereExists` dari header ke `Buku_Stock`.
3. Nomor transaksi tetap memakai prefix dari `Ms_AutoNumber.Inventory10` karena
   header/detail reuse table Direct Item Transfer.

Tanpa marker, list Direct Item Transfer lama berpotensi ikut menampilkan data
Location Adjustment karena table header-nya sama.

## Flow Flutter

### 1. Mulai Sesi Audit

User masuk menu `Location Adjustment`, lalu scan QR warehouse terlebih dahulu.

QR warehouse sekarang bisa berisi payload:

```json
{
  "Code": "WH-A",
  "WarehouseID": "WH-A"
}
```

Flutter lookup ke:

```text
GET /api/qr/{code}
```

Lalu app mengambil `value.WarehouseID` sebagai `physical_warehouse_id`.

Di web warehouse:

- Saat create warehouse baru, QR warehouse otomatis dibuat ke `Ms_QR`.
- Saat buka show warehouse, sistem hanya mencari QR yang sudah ada.
- Jika QR belum ada, show warehouse menampilkan tombol `Generate QR`.
- QR warehouse tidak ditampilkan di list QR Generator karena QR Generator tetap
  difilter untuk QR part/item yang punya `PartID`.

Jika QR tidak punya `WarehouseID`, tampilkan error:

```text
QR ini bukan QR warehouse.
```

### 2. Scan Item

Setelah warehouse fisik terpilih, user scan barcode part/item.

QR item minimal harus punya:

- `PartID`
- `BatchNo`
- `UnitID` jika ingin qty mengikuti unit label

`WarehouseID` pada QR item tidak dijadikan sumber lokasi utama. Lokasi fisik
tetap dari QR warehouse yang discan pertama, karena barang bisa saja sudah
pindah tanpa catatan.

### 3. Compare Ke Buku Stock

Untuk setiap scan item, backend/app perlu mengetahui saldo sistem:

```text
PartID + BatchNo, grouped by WarehouseID
```

Expected API helper baru:

```text
GET /api/location-adjustment/stock-location-check
```

Query:

| Param | Required | Notes |
|---|---|---|
| `physical_warehouse_id` | yes | Warehouse hasil scan pertama |
| `part_id` | yes | Dari QR item |
| `batch_no` | no | Dari QR item. Omit kalau batch kosong, atau kirim `__NULL__` jika explicit blank |
| `unit_id` | no | Unit display/input qty |

Response konsep:

```json
{
  "meta": { "code": 200, "status": "success", "message": "Stock location checked successfully" },
  "data": {
    "part_id": "PART-001",
    "batch_no": "A05",
    "physical_warehouse_id": "WH-B",
    "system_locations": [
      { "warehouse_id": "WH-A", "qty": 1.0 },
      { "warehouse_id": "WH-B", "qty": 0.0 }
    ],
    "matched": false,
    "suggested_from_warehouse_id": "WH-A",
    "suggested_to_warehouse_id": "WH-B",
    "max_adjustable_qty": 1.0,
    "requires_manual_review": false
  }
}
```

Rule response:

- `matched = true` kalau saldo positif sudah ada di `physical_warehouse_id`.
- `matched = false` dan `suggested_from_warehouse_id` terisi kalau saldo positif
  hanya ada di satu warehouse lain.
- `requires_manual_review = true` kalau saldo positif ada di lebih dari satu
  warehouse lain, atau tidak ada saldo positif sama sekali.

### 4. Buat Draft Adjustment Otomatis

Jika `matched = true`, scan dianggap sesuai dan tidak masuk draft adjustment.

Jika `matched = false` dan tidak perlu manual review, Flutter menambahkan line
ke draft:

- `warehouse_id_from = suggested_from_warehouse_id`
- `warehouse_id_to = physical_warehouse_id`
- `staff_in_charge_id_from = current staff`
- `staff_in_charge_id_to = current staff`
- `part_id`
- `unit_id`
- `batch_no`
- `qty = 1` untuk satu scan, atau bertambah jika batch yang sama discan lagi

Merge key line:

```text
WarehouseIDFrom + WarehouseIDTo + PartID + UnitID + BatchNo
```

Kalau key sama, qty ditambah. Kalau beda source warehouse, buat line/group
terpisah.

### 5. Submit

Flutter submit draft ke:

```text
POST /api/location-adjustment
```

Payload konsep:

```json
{
  "transaction_no": null,
  "transaction_date": "2026-08-05",
  "physical_warehouse_id": "WH-B",
  "staff_in_charge_id": "EMP-001",
  "notes": "Audit lokasi warehouse WH-B",
  "details": [
    {
      "warehouse_id_from": "WH-A",
      "part_id": "PART-001",
      "unit_id": "PCS",
      "conversion": 1,
      "qty": 1,
      "batch_no": "A05"
    }
  ]
}
```

Backend dapat menyimpan header/detail sebagai Direct Item Transfer compatible:

- Header:
  - `WarehouseIDFrom`: `XXX` untuk Location Adjustment.
    Pastikan row warehouse `XXX` ada via `firstOrCreate`.
  - `WarehouseIDTo`: `physical_warehouse_id`
  - `StaffInChargeIDFrom`: `staff_in_charge_id`
  - `StaffInChargeIDTo`: `staff_in_charge_id`
- Detail: satu row per `WarehouseIDFrom + PartID + UnitID + BatchNo`.
  `WarehouseIDFrom` di detail adalah source warehouse real hasil compare.
  `Notes` detail dihardcode `LOCATION_ADJUSTMENT` sebagai marker UI web.
- `Buku_Stock`: dua row per detail:
  - minus dari warehouse source yang ada di detail
  - plus ke warehouse fisik
  - `TransactionType = LOCATION_ADJUSTMENT`

## Multi-Source Warehouse

Dalam satu sesi audit Warehouse B, bisa saja barang salah lokasi berasal dari
beberapa warehouse sistem: misalnya `A05` dari Warehouse A dan `C03` dari
Warehouse C.

Flow terbaru menangani case ini dalam satu transaksi:

- `Trans_DirectItemTransferHD.WarehouseIDFrom = XXX`
- `Trans_DirectItemTransferHD.WarehouseIDTo = Warehouse B`
- Setiap detail menyimpan source warehouse masing-masing di
  `Trans_DirectItemTransferDT.WarehouseIDFrom`
- `Buku_Stock` tetap insert minus/plus per detail:
  - minus ke warehouse source detail
  - plus ke warehouse tujuan header

Dengan ini, Flutter bisa submit satu payload berisi banyak line dan backend
tetap hanya membuat satu `TransactionNo`.

## Validasi Backend

Validasi minimal saat submit:

- `physical_warehouse_id` wajib warehouse aktif.
- `staff_in_charge_id` wajib employee aktif.
- Detail minimal 1.
- `details.*.warehouse_id_from` wajib, nullable hanya di level schema supaya
  Direct Item Transfer lama tidak terdampak.
- `details.*.warehouse_id_from` tidak boleh sama dengan `physical_warehouse_id`.
- `part_id`, `unit_id`, `conversion`, `qty` wajib valid.
- `qty > 0`, `conversion > 0`.
- Duplicate detail berdasarkan:

```text
warehouse_id_from + part_id + unit_id + batch_no
```

- Total qty per `warehouse_id_from + part_id + batch_no` tidak boleh melebihi
  saldo `Buku_Stock` source warehouse.
- Hitung stok source dengan `BukuStockHelper::calculateCurrentStockByBatchNo()`
  sampai `transaction_date` transaksi.
- Saat insert `Buku_Stock`, field `SerialNo`, `ExpDate`, `BIN`, `LOC` diisi
  `null`.

## Endpoint Yang Dibutuhkan

Status implementasi API:

- Controller: `app/Http/Controllers/Api/Transaction/LocationAdjustmentController.php`
- Requests: `app/Http/Requests/Transaction/LocationAdjustment/*`
- Route prefix: `/api/location-adjustment`
- Storage: reuse `Trans_DirectItemTransferHD`, `Trans_DirectItemTransferDT`, dan
  `Buku_Stock`
- Schema tambahan: `Trans_DirectItemTransferDT.WarehouseIDFrom` nullable untuk
  source warehouse real per detail Location Adjustment
- Marker ledger: `Buku_Stock.TransactionType = LOCATION_ADJUSTMENT`
- Menu/permission sync ditambahkan di
  `app/Http/Controllers/Admin/SyncController.php` dengan menu
  `location_adjustment` dan actions `view,add,edit,delete`
- Store/update API memakai satu header per submit:
  - `Trans_DirectItemTransferHD.WarehouseIDFrom = XXX`
  - `Trans_DirectItemTransferHD.WarehouseIDTo = physical_warehouse_id`
  - `Trans_DirectItemTransferDT.WarehouseIDFrom = details.*.warehouse_id_from`
  - `Trans_DirectItemTransferDT.Notes = LOCATION_ADJUSTMENT`
- Web Direct Item Transfer show menampilkan kolom `Warehouse From` untuk detail
  yang `Notes`-nya `LOCATION_ADJUSTMENT`.
- Direct Item Transfer API sudah mengecualikan transaksi dengan marker ini supaya
  data Location Adjustment tidak muncul di list/detail Direct Item Transfer.

### List

```text
GET /api/location-adjustment
```

Query:

| Param | Required | Notes |
|---|---|---|
| `term` | no | Search transaction no/date |
| `warehouse_id` | no | Filter warehouse fisik atau source |
| `page` | no | Default 1 |
| `per_page` | no | Default 10 |

### Detail

```text
GET /api/location-adjustment/details
```

Query:

| Param | Required | Notes |
|---|---|---|
| `transaction_no` | yes | Nomor transaksi |
| `page` | no | Detail page |
| `per_page` | no | Detail per page |

### Stock Location Check

```text
GET /api/location-adjustment/stock-location-check
```

Dipakai setiap scan item untuk menentukan apakah scan sesuai atau perlu dibuat
draft adjustment.

### Store

```text
POST /api/location-adjustment
```

Membuat transaksi koreksi lokasi dan insert `Buku_Stock`.

### Update/Delete

```text
PUT /api/location-adjustment
DELETE /api/location-adjustment
```

Opsional untuk phase awal. Kalau transaksi correction sebaiknya immutable,
phase awal bisa hanya `view + add`, lalu edit/delete mengikuti kebutuhan audit.

## UX Flutter Yang Disarankan

Screen: `LocationAdjustmentFormPage`

State utama:

- `physicalWarehouse`
- `staffInCharge`
- `scanSessionStarted`
- `matchedScans`
- `adjustmentDraftLines`
- `manualReviewItems`

Alur UI:

1. Tampilkan step `Scan Warehouse`.
2. Setelah QR warehouse valid, lock warehouse fisik dan tampilkan nama warehouse.
3. User scan item berulang.
4. Untuk item yang cocok dengan warehouse fisik, tampilkan di list `Sesuai`.
5. Untuk item yang beda warehouse, tampilkan di list `Perlu Adjustment`.
6. Untuk item ambigu/tidak ada saldo, tampilkan di list `Review Manual`.
7. Tombol submit hanya aktif jika ada `adjustmentDraftLines`.
8. Jika tidak ada adjustment, user bisa finish session tanpa membuat transaksi.

Reuse komponen Flutter yang sudah ada:

- `QrRepository.getQR()` untuk lookup QR.
- `QrLookup` untuk baca `WarehouseID`, `PartID`, `BatchNo`, `UnitID`.
- `BarcodeScannerController` + `ScannerCaptureField` untuk hardware scanner.
- `ScanIconButton` + `CameraScannerWidget` untuk camera scanner.
- `StockMonitorRepository` bisa jadi acuan, tapi perlu endpoint check baru
  karena stock monitor sekarang hanya menampilkan movements, bukan menentukan
  source warehouse terbaik.
- Pola form, summary, dan submit bisa mengambil dari
  `ItemTransferFormController`, tetapi header `from/to` di Location Adjustment
  harus ditentukan dari hasil compare, bukan dipilih manual dari awal.

## Edge Case

- QR item tidak punya `PartID`: reject.
- QR item tidak punya `BatchNo`: boleh diproses sebagai batch kosong hanya jika
  bisnis memang mengizinkan stok batch kosong. Gunakan sentinel `__NULL__`
  untuk explicit blank.
- Batch ditemukan di lebih dari satu source warehouse: masukkan manual review,
  jangan auto-pilih source.
- Saldo source kurang dari qty scan: reject atau manual review.
- User scan warehouse baru saat sesi masih punya draft: minta konfirmasi clear
  session.
- User scan item sebelum scan warehouse: tampilkan error scan warehouse dulu.
- User tidak punya akses ke warehouse fisik/source: backend harus reject sesuai
  user warehouse mapping.

## Open Decisions

- Apakah Location Adjustment boleh edit/delete setelah dibuat, atau immutable.
- Apakah hasil scan yang sesuai perlu disimpan sebagai audit log, atau cukup
  tidak membuat transaksi.

## Decided

- Satu transaksi Location Adjustment boleh punya banyak source warehouse.
- Source warehouse real disimpan di `Trans_DirectItemTransferDT.WarehouseIDFrom`.
- Header `Trans_DirectItemTransferHD.WarehouseIDFrom` untuk module ini diisi
  default `XXX`.
- Warehouse default `XXX` dibuat dengan `firstOrCreate` supaya aman untuk tenant
  yang belum punya row tersebut.
- Nomor transaksi otomatis memakai prefix `Ms_AutoNumber.Inventory10`, sama
  seperti Direct Item Transfer web/API existing.
- Detail Location Adjustment memakai `Trans_DirectItemTransferDT.Notes =
  LOCATION_ADJUSTMENT`; web show membaca marker ini untuk menampilkan
  `WarehouseIDFrom` detail.
