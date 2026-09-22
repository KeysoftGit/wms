# Batch No and Coil No Concept

Dokumen ini menjelaskan konsep hubungan **Batch No** dan **Coil No** untuk stok,
master data, dan API Stock Monitoring.

## Goal

Batch No tetap menjadi bagian dari identitas stok `Buku_Stock`:

```text
PartID + WarehouseID + BatchNo
```

Coil No adalah informasi tambahan yang melekat ke batch untuk part tertentu.
Coil No tidak mengganti Batch No sebagai identitas stok dan tidak dipakai untuk
filter perhitungan saldo stok.

## Tabel dan Model

Model yang dipakai:

- `App\Models\MsBatchNo`
  - Table: `Ms_BatchNo`
  - Relasi part lewat `PartID`
- `App\Models\MsCoil`
  - Table: `Ms_Coil`
  - Primary key: `CoilID`
- `App\Models\PartBatchCoil`
  - Table: `PartBatchCoil`
  - Mapping antara `PartID`, `BatchNo`, dan coil
- `App\Helpers\CoilNoHelper`
  - `get($partId, $batchNo)`
  - `lookupByPartBatch($rows)`
  - Sumber helper bersama untuk lookup Coil No pada API batch-only

Konsep mapping utama:

```text
BatchNo -> CoilNo
```

Relasi bisnisnya:

```text
1 CoilNo -> banyak BatchNo
1 BatchNo -> maksimal 1 CoilNo
```

Karena `BatchNo` bersifat unique, Coil No bisa dicari hanya dengan `BatchNo`.
Jika `PartID` tersedia di endpoint, backend boleh tetap memakai
`PartID + BatchNo` sebagai filter tambahan agar lookup lebih ketat.

Yang tidak valid:

```text
PartID -> CoilNo
```

`PartID` saja tidak cukup, karena satu part bisa punya banyak batch dan batch
tersebut bisa mengarah ke coil yang berbeda.

Jika batch tersebut tidak punya mapping coil, response API harus mengembalikan
coil sebagai `null`.

## Aturan Batch No

Batch No adalah stock attribute utama pada flow batch-only.

Aturan utama:

- Batch No boleh `null` jika transaksi memang tidak punya batch.
- Batch No tetap dipakai bersama `PartID` dan `WarehouseID` untuk stok.
- Batch kosong/null tidak boleh dipaksa menjadi string kosong untuk lookup coil.
- Jika request memakai sentinel `__NULL__`, backend memperlakukannya sebagai
  explicit `whereNull(BatchNo)`.

## Aturan Coil No

Coil No adalah metadata batch.

Aturan utama:

- Coil No dicari berdasarkan `BatchNo`.
- Jika `PartID` tersedia, lookup boleh memakai `PartID + BatchNo`.
- `PartID` saja tidak boleh dipakai untuk lookup Coil No.
- Jika `BatchNo` bernilai `null`, Coil No harus `null`.
- Jika mapping `PartBatchCoil` tidak ditemukan, Coil No harus `null`.
- Coil No tidak dipakai untuk menghitung saldo stok.
- Coil No tidak boleh mengubah grouping stok batch-only.
- Endpoint API boleh menampilkan Coil No untuk membantu mobile menampilkan
  informasi batch.

## API Stock Monitoring

Endpoint:

```text
GET /api/stock-monitor/movements
GET /api/stock-monitor/detail-options?target=batch_no
GET /api/stock-adjustment/details
GET /api/stock-adjustment/stock-detail
GET /api/direct-item-transfer/details
GET /api/goods-receiving-asn/asn-details
GET /api/goods-receiving-asn/details
```

Behavior:

- `movements` mengembalikan `CoilNo` pada setiap row movement.
- `detail-options` untuk `target=batch_no` mengembalikan `coil_no` pada setiap
  option batch.
- Stock Adjustment detail mengembalikan `CoilNo` pada setiap row detail.
- Stock Adjustment stock detail mengembalikan `coil_no`.
- Item Transfer detail mengembalikan `CoilNo` pada setiap row detail setelah
  `BatchNo` di-resolve dari `Buku_Stock`.
- Goods Receiving ASN list part (`asn-details`) mengembalikan `coil_no` langsung
  dari `Trans_AdvanceShippingNoticeDT.CoilNo`.
- Goods Receiving ASN saved detail (`details`) mengembalikan `CoilNo` dan
  `coil_no` dari `Trans_AdvanceShippingNoticeDT.CoilNo` berdasarkan referensi
  detail ASN.
- Lookup coil memakai `CoilNoHelper`, yang membaca model `PartBatchCoil` dan
  `MsCoil`, kecuali Goods Receiving ASN yang memang membaca kolom `CoilNo` dari
  ASN detail sebagai source document.
- Jika coil tidak ada, field coil tetap dikirim dengan nilai `null`.

Contoh response movement:

```json
{
  "PartID": "PART-001",
  "WarehouseID": "WH-A",
  "BatchNo": "BATCH-001",
  "CoilNo": "COIL-001",
  "Qty": 10
}
```

Contoh response detail option:

```json
{
  "value": "BATCH-001",
  "total_qty": 10,
  "coil_no": "COIL-001"
}
```

Jika tidak ada coil:

```json
{
  "value": "BATCH-002",
  "total_qty": 5,
  "coil_no": null
}
```

## Compatibility Notes

Batch-only stock identity tetap mengikuti dokumen:

- `docs/buku_stock_bacth_no.md`
- `docs/buku_stock_bacth_only.md`

Coil No hanya menambah informasi pada response. Perubahan ini tidak boleh
menghidupkan kembali identitas stok lama seperti `SerialNo`, `ExpDate`, `BIN`,
atau `LOC`.
