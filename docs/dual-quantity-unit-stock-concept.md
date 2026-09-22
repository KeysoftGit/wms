# Dual Quantity and Dual Unit Stock Concept

Dokumen ini menjelaskan konsep, aturan bisnis, dan kalkulasi **Dual Quantity** (`Qty` & `Qty2`) serta **Dual Unit** (`UnitID` & `UnitID2`) untuk pencatatan stok di `Buku_Stock`, backend API (`ikbl-wms`), dan pemantauan stok mobile (`ikbl_wms_app`).

## Goal

Menghilangkan asumsi konversi statis 1:1 atau konversi linier kaku antar-satuan yang tidak akurat untuk barang bertipe _variable weight / dual quantity_ (seperti baja, coil, dan lembaran).

Stok di `Buku_Stock` menyimpan dua dimensi kuantitas riil secara berdampingan:

- **Primary Quantity & Unit**: `Qty` dan `UnitID` (umumnya berat total, contoh: `KG`, `TON`)
- **Secondary Quantity & Unit**: `Qty2` dan `UnitID2` (umumnya jumlah potongan/pcs, contoh: `PCS`, `COIL`, `LEMBAR`)

---

## Prinsip Utama & Formula Matematika (Tanpa Hardcoded `if`)

Kalkulasi rasio/bobot tidak boleh menggunakan pengkondisian string nama satuan (tanpa `if ($unit == 'KG')`). Formula diturunkan secara langsung dari perbandingan kuantitas riil `Qty` terhadap `Qty2`.

### 1. Berat per Satuan Potong (Weight per Piece)

$$\text{Weight Per Piece} = \frac{\text{Qty}}{\text{Qty2}}$$

Formula ini menyatakan berapa berat rata-rata untuk setiap 1 potongan (`PCS`).

Contoh: `Qty = 100.00 KG`, `Qty2 = 10.00 PCS`
$$\text{Weight Per Piece} = \frac{100.00}{10.00} = 10.00 \text{ KG / PCS}$$

Artinya: Setiap 1 PCS memiliki berat rata-rata **10.00 KG**.

### 2. Aturan Tanda Kuantitas (Sign Convention)

- Kuantitas bernilai **positif** (`Qty > 0` & `Qty2 > 0`) untuk transaksi **MASUK** (misal: Goods Receiving ASN, Stock Adjustment +, Item Transfer Receive).
- Kuantitas bernilai **negatif** (`Qty < 0` & `Qty2 < 0`) untuk transaksi **KELUAR** (misal: Delivery Order Execute, Stock Adjustment -, Item Transfer Send).
- Nilai `weight_per_piece` selalu bernilai positif murni (diambil dari nilai absolut rasio).

---

## Aturan Tampilan & Filter Satuan (`unit_id`)

### 1. Penanganan `UnitID2` Kosong (`NULL`)

- Jika transaksi di `Buku_Stock` tidak memiliki `UnitID2` (atau `Qty2` bernilai `NULL`), maka `weight_per_piece` bernilai `NULL`.
- Pada UI Modal Detail pergerakan stok, `Qty 2` menampilkan nilai **`-`**.
- Pada kartu pergerakan stok (_list item card_), label `Qty 2` **tidak dirender** agar tampilan tetap bersih dan bebas klutter.

### 2. Perilaku Saat Filter Satuan Diaktifkan (Misal Filter `PCS`)

- Saat pengguna memilih filter satuan (misal `unit_id = 'PCS'`), seluruh daftar mutasi dan summary dihitung dalam satuan `PCS`.
- Untuk transaksi yang tidak memiliki kuantitas `PCS` (`Qty2` bernilai `NULL`), nilai kuantitas yang ditampilkan untuk filter `PCS` adalah **`0 PCS`** (bukan kembali berpindah ke satuan `KG`).

---

## Aturan Agregasi API Stock Monitoring (`StockMonitorController.php`)

Logic kalkulasi dual quantity ditempatkan di `App\Helpers\DualQuantityHelper`
agar bisa dipakai ulang oleh modul lain:

- `summarySelect($targetUnit)`
- `displayMovement($row, $targetUnit, $conversion)`
- `summary($summaryRow, $targetUnit, $conversion)`
- `weightPerPiece($qty, $qty2)`

### 1. Pergerakan Stok (Movements)

Setiap row mutasi mengembalikan nilai dari kedua kolom kuantitas beserta rasio berat per pcs (`weight_per_piece`):

```json
{
    "TransactionNo": "GR/2026/08/0001",
    "PartID": "PART-A",
    "WarehouseID": "WH-A",
    "BatchNo": "A0001",
    "Qty": 100.0,
    "UnitID": "KG",
    "Qty2": 10.0,
    "UnitID2": "PCS",
    "weight_per_piece": 10.0
}
```

Jika filter `unit_id = 'PCS'` aktif dan transaksi tidak memiliki kuantitas `PCS`:

```json
{
    "TransactionNo": "GR/2026/08/0010",
    "Qty": 0.0,
    "UnitID": "PCS",
    "Qty2": null,
    "UnitID2": null,
    "weight_per_piece": null
}
```

### 2. Summary Stok (Stock Summary)

Summary menghitung agregasi independen untuk `Qty` (Primary) dan `Qty2` (Secondary):

- `stock_in` = $\sum \max(\text{Qty}, 0)$
- `stock_out` = $\left| \sum \min(\text{Qty}, 0) \right|$
- `final_stock` = $\sum \text{Qty}$
- `final_stock_qty` = $\sum \text{Qty}$ sebagai total primary quantity mentah
- `stock_in_qty2` = $\sum \max(\text{Qty2}, 0)$
- `stock_out_qty2` = $\left| \sum \min(\text{Qty2}, 0) \right|$
- `final_stock_qty2` = $\sum \text{Qty2}$
- `weight_per_piece` = $\frac{\text{final\_stock\_qty}}{\text{final\_stock\_qty2}}$ (jika $\text{final\_stock\_qty2} \neq 0$, sebaliknya `null`)

Catatan: saat filter `unit_id` aktif, `final_stock` mengikuti satuan yang
dipilih user. Karena itu `weight_per_piece` tidak boleh memakai `final_stock`
langsung; harus memakai `final_stock_qty` agar rasio tetap `Qty / Qty2`.

### 3. Detail Options (Batch Picker)

Endpoint `detail-options?target=batch_no` mengembalikan total `Qty`, total `Qty2`, serta `weight_per_piece` untuk batch tersebut:

```json
{
    "value": "A0001",
    "total_qty": 100.0,
    "total_qty2": 10.0,
    "weight_per_piece": 10.0,
    "coil_no": "COIL-001"
}
```

### 4. Input Transaksi Dengan Pilihan Unit

Untuk modul transaksi stok yang mengeluarkan atau menyesuaikan stok, user boleh
memilih unit input terlebih dahulu:

- Modal transaksi menampilkan satu field `Input Qty` dan pilihan `Input Unit`.
- Jika user memilih unit primary (`UnitID`), `Input Qty` adalah Qty 1. Sistem
  menghitung Qty 2 dari rasio current stock:
  `input_qty_1 / current_qty_1 * current_qty_2`.
- Jika user memilih unit secondary (`UnitID2`), `Input Qty` adalah Qty 2. Sistem
  menghitung Qty 1 dari rasio current stock:
  `input_qty_2 / current_qty_2 * current_qty_1`.
- Rasio yang dipakai selalu berasal dari current stock terpilih
  (`PartID + WarehouseID + BatchNo` jika batch dipilih).
- Jika current stock tidak punya `Qty2/UnitID2`, pilihan secondary tidak
  ditampilkan dan preview Qty 2 tetap `-`.
- `Conversion` pada master part unit tidak dipakai untuk rasio coil Qty 1 dan
  Qty 2. Field tersebut hanya ditampilkan sebagai informasi master unit.

Standar urutan field pada modal transaksi stok:

`Part -> Warehouse -> Batch No -> Coil No -> Input Unit -> Input Qty -> Qty 1 -> Qty 2 -> Notes`

Aturan penerapan untuk transaksi stok lain:

- `Part` dan `Warehouse` dipilih lebih dahulu karena menjadi dasar pencarian
  stok.
- `Batch No` dipilih setelah warehouse. `Coil No` hanya readonly dan diambil
  dari kombinasi `PartID + BatchNo`; jika tidak ada, tampilkan `-`.
- `Input Unit` menyediakan unit primary dan secondary yang tersedia pada stok
  terpilih. User mengisi quantity pada `Input Qty` sesuai unit tersebut.
- `Qty 1` dan `Qty 2` hanya readonly sebagai hasil konversi, bukan field input
  kedua.
- `Notes` diletakkan paling akhir dan menggunakan textarea.
- Pola ini menjadi acuan untuk Part Usage, Item Transfer, Stock Adjustment,
  Stock Opname, dan transaksi stok lain yang membutuhkan dual quantity.

Normalisasi sebelum simpan:

- Payload transaksi tetap menyimpan Qty primary-normalized sebagai dasar
  validasi dan insert `Buku_Stock.Qty`.
- `Buku_Stock.Qty2` dan `Buku_Stock.UnitID2` tetap diisi oleh
  `DualQuantityHelper::bukuStockSecondaryColumns(...)` berdasarkan delta Qty 1.
- Untuk Direct Item Transfer web, jika user memilih `UnitID2`, backend juga
  menormalisasi Qty primary dari rasio current stock sebelum validasi dan insert.

### 5. Kasus Khusus: Goods Receiving (ASN)

Pada modul **Goods Receiving (ASN)** (`GoodsReceivingAsnController.php`), nilai `CoilNo`, `Qty2`, `UnitID2`, `GrossWeight`, dan `weight_per_piece` diambil secara presisi dari rujukan baris detail ASN (`Trans_AdvanceShippingNoticeDT`) melalui helper `asnDetailInfoById`.
Data kuantitas & satuan sekunder ini secara otomatis dipetakan ke `AsnDetailItem` dan `GoodsReceivingDT` di Flutter.

### 6. Delivery Order Execute (DO)

Pada modul **Delivery Order Execute** (`DeliveryOrderExecuteController.php`), response detail mengembalikan:

- `coil_no` / `CoilNo`: Di-lookup otomatis melalui `CoilNoHelper::lookupByPartBatch`.
- `qty2`: Kuantitas sekunder dari `Trans_DeliveryOrderDT.Qty2`.
- `unit_id2`: Satuan sekunder dari `Trans_DeliveryOrderDT.UnitID2`.
- `weight_per_piece`: Rasio bobot per lembar/pcs (`qty / qty2`).

Di Flutter, `DeliveryOrderDT` memparsing `coilNo`, `qty2`, `unitId2`, dan `weightPerPiece` untuk ditampilkan pada seluruh halaman (Detail, Scan Verification, dan Ringkasan).

---

## Logging & Resilience Backend

- Semua exceptions pada `StockMonitorController` ditangkap dengan `catch (\Throwable $e)` dan dicatat lengkap beserta stack trace-nya ke `storage/logs/laravel.log`.

---

## API Transaction Detail Extensions

### Stock Adjustment

Endpoint:

```text
GET /api/stock-adjustment/details
GET /api/stock-adjustment/stock-detail
```

Response tambahan:

- `details[].Qty2`
- `details[].Qty2Stock`
- `details[].UnitID2`
- `details[].weight_per_piece`
- `stock-detail.data.qty2_stock`
- `stock-detail.data.unit_id2`
- `stock-detail.data.weight_per_piece`

Catatan:

- `qty_stock` tetap primary stock (`Qty`).
- `qty2_stock` adalah total current secondary stock (`SUM(Qty2)`) berdasarkan
  `PartID + WarehouseID + BatchNo`.
- `weight_per_piece = qty_stock / qty2_stock` jika `qty2_stock` tidak nol.
- Field ini hanya untuk display/preview; payload create/update Stock Adjustment
  belum berubah.

### Direct Item Transfer

Endpoint:

```text
GET /api/direct-item-transfer/details
```

Response tambahan per detail:

- `QtyBase`
- `UnitIDBase`
- `Qty2`
- `UnitID2`
- `weight_per_piece`

Catatan:

- Data diambil dari row source `Buku_Stock` transaksi tersebut
  (`WarehouseIDFrom`, `Qty < 0`, per `Sequence`).
- `QtyBase` dan `Qty2` dikembalikan sebagai nilai absolut agar mudah
  ditampilkan di mobile.
- Payload create/update Direct Item Transfer belum berubah.

### Part Usage

Endpoint:

```text
GET /api/part-usage/details
```

Response tambahan per detail:

- `CoilNo`
- `QtyBase`
- `UnitIDBase`
- `Qty2`
- `UnitID2`
- `weight_per_piece`

Catatan:

- Data diambil dari row source `Buku_Stock` transaksi `PART_USAGE` berdasarkan
  `TransactionNo + PartID + WarehouseID + BatchNo`.
- `QtyBase` dan `Qty2` dikembalikan sebagai nilai absolut.
- Payload create/update Part Usage belum berubah.

### Location Adjustment

Endpoint:

```text
GET /api/location-adjustment/details
```

Response tambahan per detail:

- `CoilNo`
- `QtyBase`
- `UnitIDBase`
- `Qty2`
- `UnitID2`
- `weight_per_piece`

Catatan:

- Data diambil dari row source `Buku_Stock` transaksi `LOCATION_ADJUSTMENT`
  (`Qty < 0`, per `Sequence`).
- `QtyBase` dan `Qty2` dikembalikan sebagai nilai absolut.
- Payload create/update Location Adjustment belum berubah.

### Goods Receiving ASN

Endpoint:

```text
GET /api/goods-receiving-asn/asn-details
GET /api/goods-receiving-asn/details
```

Response tambahan:

- `asn-details.details[].qty2_asn`
- `asn-details.details[].unit_id2`
- `asn-details.details[].gross_weight`
- `asn-details.details[].weight_per_piece`
- `details[].Qty2`
- `details[].UnitID2`
- `details[].GrossWeight`
- `details[].weight_per_piece`

Catatan:

- Berbeda dari module lain, GR ASN tidak mengambil `Qty2/UnitID2/GrossWeight`
  dari `Buku_Stock`.
- Source data langsung dari `Trans_AdvanceShippingNoticeDT`, karena kolom
  pendukung sudah tersedia pada detail ASN.
- Payload create/update Goods Receiving ASN belum berubah.

---

## Dokumen Terkait

- [Batch No & Coil No Concept](batch-no-coil-no-concept.md)
