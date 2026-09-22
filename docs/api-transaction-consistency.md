# API Transaction Consistency

Dokumen ini menjadi acuan konsistensi implementasi **API transaction controllers**
yang dipakai oleh Mobile WMS (`ikbl_wms_app`). Tujuannya menyamakan cara pakai
helper stok, coil, dan dual quantity, serta memastikan `Buku_Stock` selalu
menyimpan `Qty2/UnitID2` supaya laporan stok dan kalkulasi rasio berat/pcs tidak
kehilangan data.

## Scope

Rules di dokumen ini berlaku untuk 7 controller API transaction:

- `app/Http/Controllers/Api/Transaction/PartUsageController.php`
- `app/Http/Controllers/Api/Transaction/StockAdjustmentController.php`
- `app/Http/Controllers/Api/Transaction/ItemTransferController.php`
- `app/Http/Controllers/Api/Transaction/LocationAdjustmentController.php`
- `app/Http/Controllers/Api/Transaction/PurchaseReturnExecuteController.php`
- `app/Http/Controllers/Api/Transaction/GoodsReceivingAsnController.php`
- `app/Http/Controllers/Api/Transaction/DeliveryOrderExecuteController.php`

Referensi helper:

- `app/Helpers/BukuStockHelper.php`
- `app/Helpers/CoilNoHelper.php`
- `app/Helpers/DualQuantityHelper.php`

Konsep terkait yang tetap berlaku:

- `docs/buku_stock_bacth_no.md` — batch-no-only identity
- `docs/dual-quantity-unit-stock-concept.md` — dual quantity concept
- `docs/batch-no-coil-no-concept.md` — coil no mapping

---

## Baseline Assumption

Untuk menyederhanakan flow API transaction, dokumen ini memakai asumsi baseline:

> **Semua master part yang masuk ke transaksi WMS pasti punya `Qty2` dan `UnitID2`.**
> Baik di source document (ASN, DO) maupun di `Buku_Stock` current stock.

Konsekuensi asumsi ini:

- **Insert `Buku_Stock` selalu berpasangan** `Qty1/UnitID1` + `Qty2/UnitID2`.
  Tidak ada branch "kalau tidak ada Qty2 → skip".
- **Response API detail** selalu render `qty2`, `unit_id2`, dan
  `weight_per_piece`. Tidak ada conditional visibility.
- **Kalau `Qty2` di source atau di current stock ternyata `null`/`0`**, itu
  dianggap **data anomaly**, bukan flow normal. Handle dengan:
  - Throw exception yang menyebutkan Part yang bermasalah, atau
  - Log warning + insert `null` (tetap tidak silent-drop tanpa jejak).
- Tidak perlu defensive `if ($qty2 !== null)` di response builder — helper
  sudah kembalikan tipe yang aman untuk consumer.

Kalau di masa depan ada part yang benar-benar single-unit (misal jasa/tenaga
kerja), asumsi ini harus di-revisit dan aturan branching dikembalikan.

---

## Uniform Code Style

Selain rules per-topik di bawah, **style kode antar controller harus similar**.
Baca satu controller = paham semua controller. Yang wajib seragam:

- **Urutan operasi** di store/update/delete (validate → auto number → loop
  detail → build stock row → insert).
- **Nama variable** untuk data yang sama: `$transactionDate`, `$warehouseId`,
  `$partId`, `$batchNo`, `$qtyDelta`, `$stockRow`, `$coilMap`.
- **Struktur array insert `Buku_Stock`** — urutan key sama di semua controller
  (lihat Rule 4 pattern).
- **Nama method helper** yang dipanggil — kalau ada 2 module beda cara
  panggil helper yang sama (misal `CoilNoHelper::get()` vs
  `lookupByPartBatch()`), harus disamakan.
- **Pattern reverse untuk update/delete** — semua module pakai urutan yang
  sama: reverse parent qty → delete `Buku_Stock` lama → delete detail lama
  → validate baru → insert baru → recalc parent.

Groupkan 7 controller jadi 2 archetype:

| Archetype | Behavior | Sumber Qty2/UnitID2 | Controllers |
|---|---|---|---|
| **Consume** | Kurangi stok existing | `Buku_Stock` current stock (proporsional via helper) | PartUsage, StockAdjustment, ItemTransfer, LocationAdjustment, PurchaseReturnExecute, **DO Execute** |
| **Source-Doc Copy** | Generate stok baru dari source document | Kolom `Qty2/UnitID2` di source detail | **GR ASN** (satu-satunya) |

**Kenapa DO Execute masuk Consume, bukan Source-Doc Copy:**

- DO Instruction dibuat di web, bisa saja stale sebelum di-execute.
- Yang paling akurat untuk `Qty2/UnitID2` adalah rasio current stock saat
  execute, bukan nilai yang tersimpan di `Trans_DeliveryOrderDT`.
- Semantik-nya sama seperti Part Usage: kurangin stok existing, ambil sekunder
  dari ledger.

**Kenapa GR ASN unik:**

- GR adalah **inbound pertama** dari ASN. Sebelum GR, stok fisik belum tercatat
  di receiving warehouse.
- Tidak ada current stock untuk di-proporsional-kan.
- ASN detail sudah mengunci `Qty2/UnitID2/CoilNo/GrossWeight` saat dibuat →
  jadi source of truth.
- Row minus GR ASN (dari virtual ASN warehouse) juga pakai `Qty2` yang sama
  dengan sign negatif — konsisten dengan row plus.

Semua controller dalam satu archetype harus **hampir bisa di-diff line-by-line**
— yang beda cuma `TransactionType`, table detail, dan spesifik business rule
(misal 2-row transfer, header split location adjustment).

---

## Rule 1: Calculate Stock (BukuStockHelper)

### Signature Wajib

```php
BukuStockHelper::calculateCurrentStockByBatchNo(
    string $partId,
    ?string $warehouseId,
    ?string $batchNo,
    string $transactionDate  // format 'Y-m-d'
): float
```

### Aturan

- **Semua** validasi stok API transaction wajib pakai method ini. Jangan pakai
  `calculateCurrentStock()` (tanpa batch/date) untuk validasi transaksi.
- `transactionDate` **wajib** dikirim, ambil dari `transaction_date` request
  yang sudah di-parse ke `'Y-m-d'` via Carbon.
- `warehouseId` wajib untuk transaksi yang tie ke warehouse spesifik.
  Untuk transfer, kirim `WarehouseIDFrom` sebagai source.
- `batchNo` boleh `null` — helper akan `whereNull` otomatis.
- Untuk **update** (reverse + reinsert), jalankan calculate **setelah** delete
  row `Buku_Stock` lama supaya transaksi tidak block dirinya sendiri.

### Pattern Standar

```php
$transactionDate = Carbon::parse($request->transaction_date)->format('Y-m-d');

$currentStock = BukuStockHelper::calculateCurrentStockByBatchNo(
    $detail['part_id'],
    $warehouseId,
    $detail['batch_no'],
    $transactionDate
);

if ($currentStock < $detail['qty']) {
    throw new \Exception("Stok tidak cukup untuk Part {$partId} Batch {$batchNo}. Available: {$currentStock}");
}
```

### Error Message

Wajib menyebutkan identitas stok yang gagal:

- `Part`
- `Warehouse`
- `Batch No`
- (opsional) `Available qty`

Jangan hanya `"stok tidak cukup"`.

---

## Rule 2: Get Coil No (CoilNoHelper)

### Signature Yang Tersedia

```php
// Bulk lookup untuk collection detail (default untuk endpoint `details`)
CoilNoHelper::lookupByPartBatch(Collection $rows): Collection

// Single lookup untuk 1 part+batch (dipakai di dalam loop insert/preview)
CoilNoHelper::get(?string $partId, ?string $batchNo): ?string
```

### Aturan

- **Default: pakai `lookupByPartBatch()`** untuk semua endpoint yang return
  collection detail (list/details), karena hanya 1x query batch untuk semua row.
- **`get()` hanya boleh dipakai untuk single-item context**:
  - Preview stock (getStockDetail)
  - Insert per-detail di dalam loop kecil
  - Jangan dipanggil dari loop besar di endpoint list/details — refactor ke
    `lookupByPartBatch()` di luar loop.
- Coil No tidak boleh di-lookup pakai `PartID` saja. Lookup wajib berbasis
  `BatchNo` (dengan `PartID` opsional sebagai filter tambahan).
- **Exception khusus GR ASN**: `CoilNo` disimpan di `Trans_AdvanceShippingNoticeDT.CoilNo`.
  Ini source document, jadi prioritas ambil dari kolom itu; fallback ke helper
  boleh untuk row yang batch-nya tidak punya coil di ASN.

### Pattern Standar (Bulk)

```php
// Di endpoint /details
$details = $query->get();

$coilMap = CoilNoHelper::lookupByPartBatch(
    $details->map(fn ($d) => ['part_id' => $d->PartID, 'batch_no' => $d->BatchNo])
);

$details->transform(function ($row) use ($coilMap) {
    $row->CoilNo = $coilMap->get(CoilNoHelper::key($row->PartID, $row->BatchNo))
        ?? $coilMap->get(CoilNoHelper::batchKey($row->BatchNo));
    return $row;
});
```

### Pattern Standar (Single)

```php
$coilNo = CoilNoHelper::get($partId, $batchNo);
```

Jika `BatchNo` null/empty, helper return `null` — tidak perlu guard manual.

---

## Rule 3: Dual Quantity Helper

### Signature Yang Tersedia

```php
// Rasio berat per pcs (Qty / Qty2)
DualQuantityHelper::weightPerPiece(?float $qty, ?float $qty2): ?float

// Ambil current stock Qty1/Qty2/UnitID2 dari Buku_Stock berdasarkan identity
DualQuantityHelper::currentStockSecondaryQuantity(
    string $partId,
    ?string $warehouseId,
    ?string $batchNo = null,
    ...
): array  // ['qty1', 'qty2', 'unit_id2']

// Hitung Qty2 proporsional untuk delta Qty1 (dipakai saat consume/insert stock)
DualQuantityHelper::proportionalQty2ForStockDelta(
    float $qtyDelta,
    string $partId,
    ?string $warehouseId,
    ?string $batchNo = null,
    ...
    int $decimals = 2
): array  // ['qty2', 'unit_id2', 'current_qty1', 'current_qty2']

// Bungkus proportional jadi kolom siap-insert ke Buku_Stock
DualQuantityHelper::bukuStockSecondaryColumns(
    float $qtyDelta,
    string $partId,
    ?string $warehouseId,
    ?string $batchNo = null,
    ...
): array  // ['Qty2' => ..., 'UnitID2' => ...]

// Query builder select untuk stock movement (dipakai StockMonitorController)
DualQuantityHelper::summarySelect(?string $targetUnit = null): string
DualQuantityHelper::displayMovement($row, ?string $targetUnit, float $conv): array
DualQuantityHelper::summary($summaryRow, ?string $targetUnit, float $conv): array
```

### Formula Baku

**Weight per piece** (rasio berat per potongan):

```text
weight_per_piece = |Qty| / |Qty2|      (jika Qty2 != 0)
weight_per_piece = null                 (jika Qty2 null atau 0)
```

**Qty2 proporsional** (saat consume/adjust stok yang punya rasio dual qty):

```text
sign = (qtyDelta < 0) ? -1 : 1
qty2 = round(|qtyDelta| / |currentQty1| * |currentQty2|, 2) * sign
```

Aturan:

- `qty` dan `qty2` yang dikirim ke `weightPerPiece()` **selalu nilai absolut**
  (untuk display). Sign convention hanya berlaku di ledger.
- Jangan hitung `weight_per_piece` manual di controller. Selalu via helper.
- Jangan pakai `if ($unit == 'KG') ...`. Rasio diturunkan dari perbandingan
  `Qty / Qty2`, bukan hardcoded per satuan.

### Kapan Pakai Yang Mana

| Kebutuhan | Method |
|---|---|
| Display `weight_per_piece` di response detail | `weightPerPiece($qty, $qty2)` |
| Ambil summary `Qty1/Qty2/UnitID2` current stock | `currentStockSecondaryQuantity(...)` |
| Hitung `Qty2` proporsional untuk delta stok yang mau di-insert | `proportionalQty2ForStockDelta(...)` |
| Build kolom `[Qty2, UnitID2]` siap insert `Buku_Stock` | `bukuStockSecondaryColumns(...)` |
| Aggregation query stok monitoring | `summarySelect/summary/displayMovement` |

---

## Rule 4: Insert Buku_Stock (Qty2 & UnitID2 Wajib)

### Prinsip

Setiap insert `Buku_Stock` **wajib** mengisi `Qty2` dan `UnitID2`. Baseline
assumption (semua part punya dual quantity) berlaku di sini — tidak ada
branch skip. Ini adalah sumber inkonsistensi utama saat ini: 4 dari 7
controller belum insert kolom ini.

### Sumber Nilai

Ada 2 pola tergantung jenis transaksi:

**A. Source-Doc Copy — hanya GR ASN**
- Copy langsung dari `Trans_AdvanceShippingNoticeDT` (`Qty2`, `UnitID2`,
  `CoilNo`, `GrossWeight`).
- Sign menyesuaikan arah row: `+` untuk row plus (receiving warehouse),
  `-` untuk row minus (virtual ASN warehouse).
- Dipakai karena ASN adalah inbound pertama — belum ada current stock untuk
  di-proporsional-kan.

**B. Consume — semua controller lain** (Part Usage, Stock Adjustment,
Item Transfer, Location Adjustment, Purchase Return, **DO Execute**):
- Hitung proporsional pakai
  `DualQuantityHelper::bukuStockSecondaryColumns($qtyDelta, ...)`.
- `qtyDelta` wajib membawa sign yang benar (negatif untuk keluar,
  positif untuk masuk).
- Current stock dijamin ada (karena consume berarti stok pernah masuk lewat
  pola A), jadi proportional calculation selalu punya basis.
- **DO Execute** masuk pola ini — jangan copy `Qty2/UnitID2` dari
  `Trans_DeliveryOrderDT`. Ambil dari Buku_Stock supaya rasio akurat saat
  execute, bukan saat DO Instruction dibuat.

### Pattern Standar (Consume Stock)

Template canonical yang dipakai identik oleh **PartUsage, StockAdjustment,
ItemTransfer, LocationAdjustment, PurchaseReturnExecute**. Yang beda hanya
`TransactionType` dan (untuk transfer/adjustment) row kedua.

```php
$stockRow = [
    'PartID' => $detail['part_id'],
    'WarehouseID' => $warehouseId,
    'Sequence' => $sequence,
    'UnitID' => $detail['unit_id'],
    'Qty' => $qtyDelta,   // negatif untuk keluar
    'BatchNo' => $detail['batch_no'],
    'SerialNo' => null,
    'ExpDate' => null,
    'BIN' => null,
    'LOC' => null,
    'TransactionNo' => $transactionNo,
    'TransactionDate' => $transactionDate,
    'TransactionType' => $transactionType,  // per module, lihat tabel di bawah
    'CreatedBy' => $userId,
    'EntryTime' => now(),
];

// Wajib: append Qty2/UnitID2
$stockRow = array_merge(
    $stockRow,
    DualQuantityHelper::bukuStockSecondaryColumns(
        $qtyDelta,
        $detail['part_id'],
        $warehouseId,
        $detail['batch_no']
    )
);

BukuStock::create($stockRow);
```

**TransactionType per module:**

| Controller | Row(s) | `TransactionType` | Sign `Qty` |
|---|---|---|---|
| PartUsageController | 1 row | `PART_USAGE` | negatif |
| StockAdjustmentController | 1 row | `STOCK_ADJUSTMENT` | positif atau negatif (selisih) |
| ItemTransferController | 2 row | `DIRECT_ITEM_TRANSFER` | row1: negatif (source WH), row2: positif (target WH) |
| LocationAdjustmentController | 2 row | `LOCATION_ADJUSTMENT` | row1: negatif (source WH), row2: positif (physical WH) |
| PurchaseReturnExecuteController | 1 row | `PURCHASE_RETURN` | negatif |
| DeliveryOrderExecuteController | 1 row | `DELIVERY_ORDER` | negatif |

**Untuk transfer & location adjustment** (2 row per detail):

Hitung `Qty2/UnitID2` **sekali** dari warehouse **source**, lalu **mirror**
sign untuk target. Kalau target warehouse baru menerima batch tersebut untuk
pertama kali, current stock target = 0 → proportional bakal null. Jadi source
adalah source of truth, target tinggal balik sign-nya.

```php
// Hitung sekali dari source
$secondary = DualQuantityHelper::proportionalQty2ForStockDelta(
    -1 * $qty,           // sign source (keluar)
    $partId,
    $warehouseIdFrom,
    $batchNo
);

$qty2Source = $secondary['qty2'];         // negatif
$qty2Target = $secondary['qty2'] !== null // positif (mirror)
    ? -1 * $secondary['qty2']
    : null;
$unitId2 = $secondary['unit_id2'];

// Row 1: source (negatif)
$sourceRow = array_merge($baseRowSource, [
    'Qty2' => $qty2Source,
    'UnitID2' => $unitId2,
]);

// Row 2: target (positif)
$targetRow = array_merge($baseRowTarget, [
    'Qty2' => $qty2Target,
    'UnitID2' => $unitId2,
]);
```

Alasan mirror: transfer/adjustment memindahkan **fisik yang sama** ke warehouse
lain, jadi Qty2 pasti identik magnitude-nya, hanya sign yang beda.

Untuk consume 1-row (`PartUsage`, `StockAdjustment`, `PurchaseReturnExecute`),
tetap pakai `bukuStockSecondaryColumns()` langsung — lebih ringkas.

### Pattern Standar (Copy dari Source Doc)

```php
// GR ASN dan DO Execute
$stockRow = [
    // ... kolom lain
    'Qty' => $qty,
    'Qty2' => $sourceDetail->Qty2 !== null ? (float) $sourceDetail->Qty2 * $sign : null,
    'UnitID2' => $sourceDetail->UnitID2,
];
```

Untuk GR ASN yang punya row plus (GR) dan row minus (ASN in-transit), keduanya
harus konsisten pakai `Qty2/UnitID2` yang sama dari `Trans_AdvanceShippingNoticeDT`
dengan sign yang sesuai.

### Guard Schema

Helper `bukuStockSecondaryColumns()` sudah cek `Schema::hasColumn('Buku_Stock', 'Qty2')`
otomatis. Kalau kolom tidak ada, return empty array — safe untuk `array_merge`.

Jangan bikin guard `Schema::hasColumn` manual di controller. Delegate ke helper.

### Data Anomaly Handling

Kalau `bukuStockSecondaryColumns()` return `['Qty2' => null, 'UnitID2' => null]`
padahal transaksi berhasil validasi Qty1, berarti data anomaly:

- Master part harusnya dual-quantity tapi current stock tidak punya `Qty2`.
- Kemungkinan penyebab: transaksi lama yang di-insert sebelum dual quantity
  aktif, atau ada migrasi/import data yang skip `Qty2`.

Behavior yang disarankan:

- Tetap insert row `Buku_Stock` dengan `Qty2 = null, UnitID2 = null`
  (jangan block transaksi).
- Log warning ke `storage/logs/laravel.log` dengan konteks
  `PartID + WarehouseID + BatchNo + TransactionNo` supaya bisa di-trace.
- Jangan sembunyikan lewat try/catch — biarkan helper apa adanya, log di
  level controller setelah panggil helper.

### Reverse (Update/Delete)

Saat reverse row `Buku_Stock` lama sebelum reinsert:

- Cukup `delete()` row lama berdasarkan `TransactionNo + TransactionType`.
- Setelah delete, `bukuStockSecondaryColumns()` akan menghitung ulang rasio
  berdasarkan stok terbaru — jadi tidak perlu simpan `Qty2` lama.

---

## Rule 5: Load Data Dari Transaction Asal

### 5.1 Goods Receiving ASN (dari Trans_AdvanceShippingNoticeDT)

- `Qty2`, `UnitID2`, `CoilNo`, `GrossWeight` **wajib ambil dari
  `Trans_AdvanceShippingNoticeDT`**, bukan dari `Buku_Stock`.
- Alasannya: ASN adalah source of truth untuk niat penerimaan. Buku_Stock
  hanya representasi ledger.
- Helper yang tersedia: `asnDetailInfoById($detailIds): Collection` di
  `GoodsReceivingAsnController` — mengembalikan collection keyed by ASN detail
  id dengan `CoilNo`, `Qty2`, `UnitID2`, `GrossWeight`.
- Pattern:

```php
$asnInfo = $this->asnDetailInfoById($grDetails->pluck('GR_ASNDetailID'));

$grDetails->transform(function ($row) use ($asnInfo) {
    $info = $asnInfo->get($row->GR_ASNDetailID);
    $row->Qty2 = $info->Qty2 ?? null;
    $row->UnitID2 = $info->UnitID2 ?? null;
    $row->GrossWeight = $info->GrossWeight ?? null;
    $row->CoilNo = $info->CoilNo ?? null;
    $row->weight_per_piece = DualQuantityHelper::weightPerPiece($row->Qty, $row->Qty2);
    return $row;
});
```

- Fallback ke `DualQuantityHelper::currentStockSecondaryQuantity()` hanya jika
  ASN detail tidak punya `Qty2`.

### 5.2 Delivery Order Execute (dari Buku_Stock — Consume archetype)

- `Qty2`, `UnitID2` **wajib ambil dari `Buku_Stock` current stock**, bukan
  dari `Trans_DeliveryOrderDT`.
- Alasannya:
  - DO Instruction dibuat di web dan bisa saja stale sebelum di-execute
    (interval Office → gudang bisa berjam-jam atau berhari-hari).
  - Rasio `Qty/Qty2` di stok bisa berubah kalau ada mutasi lain di batch yang
    sama antara "DO dibuat" dan "DO di-execute".
  - Baca dari ledger memastikan pengurangan konsisten dengan saldo saat itu.
- `CoilNo` tetap ambil dari `PartBatchCoil` via `CoilNoHelper` — DO tidak
  simpan CoilNo sendiri.
- Pattern saat execute (insert `Buku_Stock` minus):

```php
$qtyDelta = -1 * (float) $detail->Qty;   // negatif untuk keluar

$stockRow = array_merge($baseRow, [
    'Qty' => $qtyDelta,
    // ...
], DualQuantityHelper::bukuStockSecondaryColumns(
    $qtyDelta,
    $detail->PartID,
    $selectedWarehouseId,   // rak/warehouse yang dipilih staff
    $detail->BatchNo
));
```

- **Kalau frontend butuh preview `Qty2/UnitID2`** sebelum execute (di layar
  scan/summary), ambil juga via `currentStockSecondaryQuantity()` — jangan
  render field `Qty2` dari `Trans_DeliveryOrderDT` karena bisa stale.
- Jangan copy `Qty2/UnitID2` dari `Trans_DeliveryOrderDT` — kolom itu boleh
  dianggap **informational only** untuk histori DO Instruction.

### 5.3 Purchase Return Execute (proportional dari current stock)

- Purchase Return execute selalu consume stok existing dari warehouse referensi
  (biasanya warehouse GR/DP), jadi treat sama dengan flow consume lain.
- Hitung `Qty2` proporsional via
  `DualQuantityHelper::proportionalQty2ForStockDelta($qtyDelta, ...)` atau
  langsung `bukuStockSecondaryColumns()`.
- Formula proporsional (sudah di helper):

```text
qty2 = round(|stockQty| / |availableQty1| * |availableQty2|, 2) * sign
```

- `UnitID2` diambil dari `Buku_Stock` row terbaru untuk part+warehouse+batch
  tersebut (via `currentStockSecondaryQuantity()`).
- Karena baseline assumption menyatakan semua part punya dual quantity,
  fallback ke `Qty2 = null` cuma terjadi kalau ada data anomaly (lihat
  Rule 4 — Data Anomaly Handling).

### 5.4 Ringkasan Priority Source

| Module | Qty2/UnitID2 Source | Method | Archetype |
|---|---|---|---|
| GR ASN | `Trans_AdvanceShippingNoticeDT` | `asnDetailInfoById()` | Source-Doc Copy |
| DO Execute | `Buku_Stock` current stock | `bukuStockSecondaryColumns()` | Consume |
| PR Execute | `Buku_Stock` current stock | `bukuStockSecondaryColumns()` | Consume |
| Part Usage | `Buku_Stock` current stock | `bukuStockSecondaryColumns()` | Consume |
| Stock Adjustment | `Buku_Stock` current stock | `bukuStockSecondaryColumns()` | Consume |
| Item Transfer | `Buku_Stock` current stock (source WH) | `proportionalQty2ForStockDelta()` + mirror | Consume |
| Location Adjustment | `Buku_Stock` current stock (source WH) | `proportionalQty2ForStockDelta()` + mirror | Consume |

---

## Compliance Status

Status per controller **setelah** implementasi Tier 1 & Tier 2.

| Controller | R1 Calc Stock | R2 CoilNo | R3 DualQty | R4 Insert Qty2 | R5 Source Load |
|---|---|---|---|---|---|
| PartUsageController | ✓ | ✓ bulk | ✓ | ✓ | n/a |
| StockAdjustmentController | ✓ | ✓ bulk + `get()` single | ✓ (helper) | ✓ | n/a |
| ItemTransferController | ✓ | ✓ bulk | ✓ | ✓ (2-row mirror) | n/a |
| LocationAdjustmentController | ✓ | ✓ bulk | ✓ | ✓ (2-row mirror) | n/a |
| PurchaseReturnExecuteController | ✓ | ✓ bulk | ✓ (helper) | ✓ | ✓ |
| GoodsReceivingAsnController | ✓ | ✓ bulk | ✓ | ✓ | ✓ ASN DT |
| DeliveryOrderExecuteController | ✓ | ✓ bulk | ✓ (helper) | ✓ | ✓ Buku_Stock |

Legend:
- ✓ compliant
- ⚠ partial / needs refactor
- ✗ missing / non-compliant

---

## Implementation Log

Riwayat perubahan konkret per controller untuk trace ke commit / PR.

### 2026-08-19 — Initial rollout (Tier 1 + Tier 2 sekaligus)

**Tier 1 — Data loss fix (insert Qty2/UnitID2 wajib):**

1. **PartUsageController** — `insertUsageRows()`:
   - Tambah `DualQuantityHelper::bukuStockSecondaryColumns($qtyDelta, $partId, $warehouseId, $batchNo)` sebelum insert.
   - `array_merge()` hasilnya ke row `BukuStock::insert()`.
   - `qtyDelta = -$usedQty` di-extract sebagai variabel supaya pass ke helper.

2. **ItemTransferController** — `insertTransferRows()`:
   - 2-row per detail (source + target warehouse).
   - Hitung `Qty2/UnitID2` sekali dari **source** warehouse via `bukuStockSecondaryColumns($baseQty * -1, ...)`.
   - **Mirror sign** untuk target row: `Qty2 target = -1 * Qty2 source`, `UnitID2` sama.
   - Alasan mirror: target WH bisa 0 stok kalau batch baru pertama masuk → helper akan return null. Source adalah source of truth.

3. **StockAdjustmentController**:
   - `insertAdjustmentRows()`: tambah `bukuStockSecondaryColumns($difference, ...)` ke row insert.
   - Refactor `getTransactionDetails()` dan `getStockDetail()`: ganti local helper `calculateCurrentStockQty2ByBatchNo()` + `currentStockUnitId2ByBatchNo()` → `DualQuantityHelper::currentStockSecondaryQuantity()`.
   - **Hapus** private method `calculateCurrentStockQty2ByBatchNo`, `currentStockUnitId2ByBatchNo`, dan `applyNullableStockFilter` (tidak lagi terpakai).

4. **LocationAdjustmentController** — `insertAdjustmentRows()`:
   - Sama pattern dengan ItemTransfer (2-row mirror).
   - Hitung `Qty2` dari source WH (`warehouse_id_from`), mirror ke physical WH row.

**Tier 2 — Consistency & performance:**

5. **GoodsReceivingAsnController**:
   - `getTransactionDetails()`: pre-fetch `$coilMap = CoilNoHelper::lookupByPartBatch($detailRows)` sebelum loop. Ganti `CoilNoHelper::get()` di dalam loop → `$coilMap->get(CoilNoHelper::key(...))` dengan fallback ke `batchKey()`.
   - `buildAsnDetailRows()`: sama refactor — bulk coil lookup di luar `.map()`, dipakai dalam closure.
   - `CoilNoHelper::get()` di `validateDetails()` (line ~786) dibiarkan karena loop bounded oleh input user (validation context, bukan display).

6. **PurchaseReturnExecuteController** — `buildStockRows()`:
   - Buang variabel `$hasQty2Column` dan `$hasUnitId2Column` + branching manual.
   - Buang fallback dua-tier (`secondaryQuantityForDetail()` dipanggil dua kali).
   - Ganti dengan single `DualQuantityHelper::bukuStockSecondaryColumns($qtyDelta, ...)` yang di-`array_merge()` ke row insert.
   - `$hasBatchColumn` tetap dipertahankan karena batch column check bukan tanggung jawab DualQuantityHelper.
   - Method `secondaryQuantityForDetail()` masih dipakai di `getTransactionDetails()` dan `debtDualQuantityColumns()` — tidak dihapus (scope lain).

7. **DeliveryOrderExecuteController**:
   - Import `DualQuantityHelper`.
   - `getTransactionDetails()`:
     - Query `executedStocks` sekarang juga load `Qty2, UnitID2` (dari `Buku_Stock`).
     - Response `qty2/unit_id2` diambil dari:
       - jika sudah executed → `executedStock->Qty2/UnitID2` (Buku_Stock)
       - jika pending → `DualQuantityHelper::proportionalQty2ForStockDelta(-detail.Qty, ...)` (proportional dari instruction warehouse current stock)
     - Tambah field `weight_per_piece` via `DualQuantityHelper::weightPerPiece()`.
     - **Hapus** copy dari `Trans_DeliveryOrderDT.Qty2/UnitID2` (source salah per rule 5.2).
   - `insertExecutionRows()`:
     - Ganti `'Qty2' => $detail->Qty2 * -1` + `'UnitID2' => $detail->UnitID2` (dari DO DT) → `DualQuantityHelper::bukuStockSecondaryColumns($qtyDelta, ..., $selectedWarehouseId, $batchNo)` (dari Buku_Stock current stock di selected warehouse).

**Verification:**
- `php -l` lulus untuk 7 file yang diubah.
- Intelephense diagnostics muncul (P1009 "Undefined type DualQuantityHelper/CoilNoHelper") — false positive linter, class ada dan tetap runtime-valid.

**Belum dikerjakan / follow-up:**
- Web transaction controllers belum di-touch (Phase 5 di batch-no-only migration).
- `secondaryQuantityForDetail()` di PurchaseReturnExecuteController masih standalone — bisa di-refactor ke shared helper di iterasi berikutnya untuk full uniformity.
- Test end-to-end via mobile app untuk masing-masing flow belum dilakukan.

### 2026-08-19 — Testing round Rabu fixes

Bug & enhancement dari catatan testing round 1.

**GR ASN:**

1. **List detail cuma tampil 1 dari 2 part (Item 3)** — `getTransactionDetails()`:
   - Tambah `id` ke column list `TransGoodsReceivingDT` (sebelumnya tidak di-select, hanya `TransactionNo, PartID, Sequence, UnitID, Qty, BatchNo` + ASN detail column + rev columns).
   - Tambah `->orderBy('Sequence')->orderBy('id')` sebelum paginate. Sebelumnya tanpa orderBy, SQL Server bisa return urutan random dan pagination cursor jadi tidak stabil.

2. **Edit gagal load ASN (Item 4)** — `getAsnDetails()`:
   - Kalau `gr_no` di-provide (edit context), skip `findAuthorizedAsn()` yg apply warehouse filter.
   - Alih-alih: cek GR authorization dulu via `findAuthorizedGoodsReceiving($grNo)`, kalau lolos → load ASN langsung dari `DB::table` tanpa warehouse filter.
   - Alasan: user yg edit GR nya sendiri harus tetap bisa load ASN meskipun warehouse mapping-nya berubah setelah receiving.

3. **Update bad request (Item 5)** — `UpdateTransactionRequest` & `StoreTransactionRequest`:
   - Buang `exists:` validation untuk `warehouse_id` dan `details.*.part_id` — delegate ke controller yg punya error message lebih spesifik.
   - Ubah `details.*.source_detail_key` dari `required` → `nullable` — backend fallback ke lookup by PartID+Sequence.
   - Ubah `details.*.unit_id` dari `required` → `nullable` — backend derive dari ASN detail `unit_id_base`.
   - Ubah `details.*.qty_remaining` dari `required` → `nullable` — informational field, backend recompute sendiri.

**Location Adjustment:**

4. **Support input Qty2/UnitID2 (Item 8)** — `checkStockLocation()`:
   - Extend query: tambah `SUM(Qty2) as qty2` di aggregation per warehouse.
   - Response `system_locations[]` sekarang bawa `qty2`, `unit_id2`, `weight_per_piece` per row.
   - Response top-level tambah `max_adjustable_qty2` dan `suggested_unit_id2` untuk suggested source warehouse.
   - Tambah private helper `latestUnitId2($partId, $warehouseId, $batchNo)` — ambil `UnitID2` terbaru dari `Buku_Stock` per warehouse+batch.
   - **FE strategy:** kalau user input pakai Qty2, FE hitung Qty1 = `input_qty2 / suggested_qty2 * suggested_qty` sendiri, submit tetap ke API sebagai `qty` (Qty1).

5. **Simpan BatchNo di detail table (Item 9)** — `insertAdjustmentRows()`:
   - Tambah `Schema::hasColumn('Trans_DirectItemTransferDT', 'BatchNo')` check (cached per call).
   - Kalau kolom ada, tambah `'BatchNo' => $batchForInsert` ke detail row insert.
   - Buku_Stock BatchNo tetap in-place. Ini bikin batch persistent di 2 tempat, safer untuk tenant yg punya kolom.
   - Import `Illuminate\Support\Facades\Schema`.

**DO Execute:**

6. **List bawa Qty2 aggregate (Item 12)** — `getTransaction()`:
   - Tambah 2 `selectRaw` subquery: `total_qty` dan `total_qty2` (SUM dari `Trans_DeliveryOrderDT` per TransactionNo).
   - Expose di `formatTransaction()`: `total_qty` dan `total_qty2` (nullable — kalau semua Qty2 detail null, aggregate null).
   - FE bisa render "Total: 100 KG / 10 PCS" di list card DO tanpa call detail endpoint.

**FE-only (tidak perlu API change):**
- Item 6: Cicil GR sudah support di API (`qty_remaining` logic). Cuma pastikan FE update state per submit.
- Item 7: Hapus draft line Location Adjustment = FE state management.
- Item 11: Format qty di DO list = FE display.

**Verification:**
- `php -l` lulus untuk 4 file yang diubah (2 request + 2 controller).
- Semua diagnostic warnings intelephense = false positive.

### 2026-08-21 — Fix silent employee fallback

**Location Adjustment — `resolveEmployeeIdForUser()`:**

Sebelumnya method punya 5 tier fallback yang paling akhir pakai `MsEmployee::where('Active', 1)->first()` dan `$user->UserID`/`'SYSTEM'`. Efeknya: kalau user login tidak punya `Ms_User.EmployeeID` yang valid dan mobile tidak kirim `staff_in_charge_id`, sistem **silent-save first active employee** sebagai `StaffInChargeIDFrom/To` — audit trail jadi keliru tanpa error.

Fix:
- Buang fallback #4 (first active employee).
- Buang fallback #5 (return UserID / `'SYSTEM'`).
- Throw `ValidationException` dengan message `"User login belum ter-map ke Employee. Hubungi admin untuk set Employee ID di master User."` kalau 3 sumber pertama (Ms_User.EmployeeID, Ms_User.UserID sebagai EmployeeID, atau payload fallback_staff_id) tidak resolve.

Business rule tenant ini: **setiap user WMS wajib punya `Ms_User.EmployeeID` valid**. Fail loud lebih safe daripada silent-save orang yang salah.

Follow-up:
- Data cleanup: populate `Ms_User.EmployeeID` untuk semua user WMS existing lewat SQL/admin panel.
- FE mobile boleh tetap kirim `staff_in_charge_id` sebagai override — tapi bukan requirement.

---

## Migration Priority

Berdasarkan risiko data integrity, urutan pengerjaan:

**Tier 1 — Data loss risk (insert Qty2/UnitID2 missing):** ✓ **Selesai 2026-08-19**

1. ✓ `PartUsageController` — high frequency, consume stock
2. ✓ `ItemTransferController` — 2 row per detail, both missing
3. ✓ `StockAdjustmentController` — adjustment histori jadi tidak sinkron
4. ✓ `LocationAdjustmentController` — reuse table transfer, marker beda

**Tier 2 — Performance / consistency:** ✓ **Selesai 2026-08-19**

5. ✓ `GoodsReceivingAsnController` — migrate `CoilNoHelper::get()` ke bulk
6. ✓ `PurchaseReturnExecuteController` — cleanup manual `Schema::hasColumn`
7. ✓ `DeliveryOrderExecuteController` — pakai `DualQuantityHelper` untuk display

---

## Checklist Sebelum Merge

Setiap PR yang menyentuh API transaction controller wajib lolos checklist:

- [ ] `calculateCurrentStockByBatchNo` selalu kirim 4 argumen (termasuk
      `transaction_date`).
- [ ] Error message stok menyebutkan Part + Warehouse + Batch.
- [ ] CoilNo di endpoint list/details pakai `lookupByPartBatch()` (bukan `.get()`
      dalam loop).
- [ ] `weight_per_piece` di response dihitung via `DualQuantityHelper::weightPerPiece()`.
      Tidak ada manual `qty / qty2` di controller.
- [ ] Setiap insert `Buku_Stock` sudah `array_merge(...,
      DualQuantityHelper::bukuStockSecondaryColumns(...))`.
- [ ] Untuk GR ASN, `Qty2/UnitID2` di-copy dari `Trans_AdvanceShippingNoticeDT`
      dengan sign yang benar (row plus & row minus).
- [ ] Untuk DO Execute, `Qty2/UnitID2` diambil dari `Buku_Stock` current stock
      via `bukuStockSecondaryColumns()` — **bukan** dari `Trans_DeliveryOrderDT`.
- [ ] Update/delete flow: delete `Buku_Stock` lama **sebelum** validasi stok
      baru (supaya tidak self-block).
- [ ] Kolom legacy (`SerialNo`, `ExpDate`, `BIN`, `LOC`) diisi `null` di insert.
- [ ] `syntax check` (`php -l`) lulus untuk file yang diubah.

---

## Anti-Pattern

Contoh kode yang **tidak boleh** ada di API transaction controller:

```php
// ✗ Manual hitung weight_per_piece
$weight = $qty > 0 && $qty2 > 0 ? $qty / $qty2 : null;

// ✗ Hardcoded unit condition
if ($unit === 'KG') { ... }

// ✗ Skip Qty2 karena "ribet"
BukuStock::create([
    'Qty' => $qty,
    // Qty2 tidak diisi
]);

// ✗ Guard schema manual di controller
if (Schema::hasColumn('Buku_Stock', 'Qty2')) {
    $row['Qty2'] = $qty2;
}

// ✗ Defensive null-check di response builder (baseline assumption: selalu ada)
return [
    'qty2' => $qty2 !== null ? (float) $qty2 : null,
    'unit_id2' => $qty2 !== null ? $unitId2 : null,
    'weight_per_piece' => ($qty > 0 && $qty2 > 0) ? $qty / $qty2 : null,
];

// ✗ CoilNoHelper::get() di dalam loop besar
foreach ($details as $d) {
    $d->CoilNo = CoilNoHelper::get($d->PartID, $d->BatchNo);
}

// ✗ Calculate stock tanpa transaction_date
BukuStockHelper::calculateCurrentStock($partId, $warehouseId, $batchNo);
```

Ganti dengan pattern yang sudah didefinisikan di Rule 1-5.
