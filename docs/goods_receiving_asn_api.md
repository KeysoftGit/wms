# Goods Receiving ASN API

Dokumen ini menjelaskan konsep API **Goods Receiving ASN** untuk mobile apps.
Flow ini mengikuti flow web `GoodsReceivingAsnController`, tetapi payload dibuat
lebih cocok untuk Flutter.

## Goal

Goods Receiving ASN dipakai saat barang diterima berdasarkan
`Advance Shipping Notice` atau ASN, bukan langsung memilih PO manual.

Tujuan mobile:

- Staff gudang pilih ASN/PO dari list outstanding yang sesuai akses warehouse user
  login.
- Warehouse tujuan utama diambil dari ASN, lalu user memilih receiving warehouse
  dari gudang utama tersebut atau child/raknya.
- Staff scan barcode part/batch yang datang atau pilih detail part manual.
- Sistem validasi barang tersebut ada di detail ASN dan qty receive tidak melebihi
  outstanding ASN.
- Saat submit, sistem membuat Goods Receiving, QC receiving, dan pergerakan
  `Buku_Stock`.

## Prinsip Utama

ASN flow berbeda dari Goods Receiving PO biasa.

Aturan intinya:

- ASN adalah sumber dokumen utama di mobile.
- PO tetap dipakai sebagai referensi karena ASN menyimpan `PONumber`.
- Mobile tidak perlu scan warehouse. Warehouse tujuan utama otomatis dari
  `Trans_AdvanceShippingNoticeHD.DestinationWarehouseID`, tetapi user memilih
  receiving warehouse untuk lokasi stock real.
- List ASN harus mengikuti warehouse yang bisa diakses user login, sama seperti
  pola get warehouse module lain.
- Warehouse yang masuk ke Goods Receiving adalah selected receiving warehouse:
  `ASN.DestinationWarehouseID` atau child/rak dari destination tersebut.
- Stok ASN dianggap berada di warehouse virtual/in-transit
  `Trans_AdvanceShippingNoticeHD.WarehouseID`.
- Submit Goods Receiving ASN membuat:
    - stok plus ke selected receiving warehouse dengan
      `TransactionType = GOODS_RECEIVING`
    - stok minus dari warehouse virtual ASN dengan
      `TransactionType = ASN_GOODS_RECEIVING`
- Detail GR menyimpan referensi detail ASN lewat kolom `GR_ASNDetailID` jika kolom
  tersedia di `Trans_GoodsReceivingDT`.

## Stock Identity

Mengikuti flow batch-no-only yang sedang dipakai:

```text
PartID + WarehouseID + BatchNo
```

Untuk Goods Receiving ASN mobile, identitas scan cukup:

- `PartID`
- `BatchNo`
- `QtyReceive`
- `SourceDetailKey` / `ASNDetailID`

Kolom berikut tidak dipakai untuk identitas stok batch-only:

- `SerialNo`
- `ExpDate`
- `BIN`
- `LOC`

Untuk API mobile, kolom di atas tidak perlu dikirim di request dan tidak boleh
dipakai untuk validasi stok. Backend API harus mengisi `SerialNo`, `ExpDate`,
`BIN`, dan `LOC` dengan `null` saat insert `Trans_GoodsReceivingDT`,
`Trans_QualityControlReceivingDT`, dan `Buku_Stock`.

Catatan: ini sengaja dibuat khusus API mobile. Web existing bisa tetap punya
struktur kolom lama, tetapi implementasi API GR ASN mengikuti batch-no-only.

## Backend Storage

Table yang dipakai:

- Header GR: `Trans_GoodsReceivingHD`
- Detail GR: `Trans_GoodsReceivingDT`
- Header QC: `Trans_QualityControlReceivingHD`
- Detail QC: `Trans_QualityControlReceivingDT`
- Ledger: `Buku_Stock`
- Source ASN: `Trans_AdvanceShippingNoticeHD`
- Source ASN detail: `Trans_AdvanceShippingNoticeDT`
- Source PO: `Trans_PurchaseOrderHD`
- Source PO detail: `Trans_PurchaseOrderDT`

Header Goods Receiving:

- `TransactionNo`: auto/manual
- `TransactionDate`: tanggal receive
- `QCNumber`: nomor QC auto random yang dibuat backend
- `ASNNumber`: nomor ASN yang diterima
- `WarehouseID`: selected receiving warehouse dari request `warehouse_id`
- `CurrencyID`: dari ASN
- `Rate`: dari ASN
- `Notes`: notes user
- `RevCount`: dari ASN

Detail Goods Receiving:

- `TransactionNo`
- `PartID`
- `Sequence`
- `UnitID`
- `Qty`: qty receive
- `BatchNo`
- `SerialNo = null`
- `ExpDate = null`
- `BIN = null`
- `LOC = null`
- `GR_ASNDetailID`: `SourceDetailKey` dari detail ASN, jika kolom tersedia
- `ItemRevDT01..15`: mengikuti revision value dari ASN/detail

QC Receiving:

- Dibuat otomatis saat submit GR.
- Semua detail receive dianggap `Passed = 1`.
- Qty QC sama dengan `QtyReceive`.

`Buku_Stock`:

1. Row plus ke selected receiving warehouse:

```text
WarehouseID = request.warehouse_id
Qty = QtyReceive
TransactionType = GOODS_RECEIVING
```

2. Row minus dari warehouse virtual ASN:

```text
WarehouseID = ASN.WarehouseID
Qty = QtyReceive * -1
TransactionType = ASN_GOODS_RECEIVING
```

## Flow Flutter

### 1. Load ASN Outstanding

User masuk menu `Goods Receiving ASN`, lalu mobile langsung load list ASN/PO
outstanding sesuai akses warehouse user login.

Tidak ada step scan warehouse.

Backend menentukan allowed warehouse dari user login, lalu filter:

```text
ASN.Outstanding = 1
ASN.DestinationWarehouseID IN allowed_warehouse_ids
```

Jika user admin atau mapping warehouse tidak aktif, backend boleh menampilkan ASN
sesuai rule authorization existing.

List di mobile sebaiknya menampilkan ASN dan PO dalam satu item, mengikuti flow
web yang memilih ASN tetapi labelnya membawa nomor PO.

Optional search bisa by `ASN.TransactionNo` atau `ASN.PONumber`.

Response minimal:

```json
{
    "asns": [
        {
            "asn_no": "ASN/2026/08/0001",
            "po_no": "PO/2026/08/0009",
            "transaction_date": "2026-08-06",
            "warehouse_id": "WH-B",
            "currency_id": "IDR",
            "rate": 1
        }
    ]
}
```

### 2. Pilih ASN/PO dan Pilih Receiving Warehouse

Saat user memilih ASN, backend mengembalikan warehouse utama dan pilihan
receiving warehouse.

Field yang otomatis diset dari ASN:

- `destination_warehouse_id = ASN.DestinationWarehouseID`
- `virtual_warehouse_id = ASN.WarehouseID`
- `po_no = ASN.PONumber`
- `currency_id = ASN.CurrencyID`
- `rate = ASN.Rate`
- `rev_count = ASN.RevCount`

Mobile lalu memilih `warehouse_id` untuk receiving:

- boleh `ASN.DestinationWarehouseID`
- boleh child/rak dengan `ParentID = ASN.DestinationWarehouseID`

### 3. Load Detail ASN

Setelah ASN dipilih, mobile load detail ASN.

Backend menghitung `qty_remaining` dari:

```text
ASN detail qty - total qty yang sudah pernah diterima di GR untuk ASN tersebut
```

Jika `qty_remaining <= 0`, detail tidak perlu dikirim kecuali sedang edit GR
existing.

Response detail perlu membawa `source_detail_key` karena ini marker detail ASN.

```json
{
    "asn": {
        "asn_no": "ASN/2026/08/0001",
        "po_no": "PO/2026/08/0009",
        "warehouse_id": "WH-B",
        "virtual_warehouse_id": "ASN-WH",
        "currency_id": "IDR",
        "rate": 1,
        "rev_count": 0,
        "receiving_warehouses": [
            {
                "WarehouseID": "WH-B",
                "WarehouseName": "Warehouse B",
                "ParentID": null
            },
            {
                "WarehouseID": "RAK-A",
                "WarehouseName": "Rak A",
                "ParentID": "WH-B"
            }
        ]
    },
    "details": [
        {
            "source_detail_key": "12345",
            "part_id": "PART-001",
            "part_name": "Part 001",
            "with_serial_no": 0,
            "sequence": 1,
            "unit_id": "PCS",
            "unit_id_base": "PCS",
            "qty_asn": 10,
            "qty_remaining": 7,
            "batch_no": "BATCH-001",
            "rev": []
        }
    ]
}
```

### 4. Scan Barcode Part

Barcode part sebaiknya menghasilkan minimal:

```json
{
    "part_id": "PART-001",
    "batch_no": "BATCH-001"
}
```

Saat scan, Flutter compare ke list detail ASN:

```text
PartID + BatchNo
```

Jika ketemu lebih dari satu row ASN dengan part/batch sama, Flutter harus minta
user pilih line ASN. Setelah line dipilih, simpan `source_detail_key`.

Jika tidak ketemu:

- jangan auto tambah detail
- tampilkan error bahwa part/batch tidak ada di ASN terpilih

Selain scan barcode, mobile juga boleh pilih part manual satu per satu dari list
detail ASN yang `qty_remaining > 0`.

### 5. Locked Qty Receive & Partial Receiving per Line Item

Kuantitas penerimaan per baris barang dikunci (*locked*) secara otomatis sebesar `qty_remaining` dari baris ASN tersebut. User mobile tidak menginput kuantitas pecahan manual per baris.

Rule per line:

```text
qty_receive = qty_remaining
```

Pencicilan penerimaan (*partial receiving*) bekerja **per baris barang (*per line item*) berdasarkan sisa outstanding masing-masing baris**.

Contoh:

- ASN `ASN-001` membawa Part A qty 3 dan Part B qty 3.
- GR pertama menerima Part A qty 3 (locked).
- Setelah GR pertama submit, ASN masih berstatus *Outstanding* karena Part B (qty 3) belum diterima.
- GR kedua menerima Part B qty 3 (locked).
- Setelah Part B diterima penuh, ASN otomatis menjadi *Not Outstanding / Closed*.
- User juga bisa memilih/men-scan Part A (qty 3) dan Part B (qty 3) sekaligus dalam satu transaksi GR.

### 6. Submit Goods Receiving ASN

Submit hanya membawa detail yang diterima.

Payload rekomendasi:

```json
{
    "transaction_no": null,
    "is_auto": true,
    "transaction_date": "2026-08-06",
    "asn_no": "ASN/2026/08/0001",
    "warehouse_id": "RAK-A",
    "notes": "Receive dari mobile",
    "details": [
        {
            "source_detail_key": "12345",
            "part_id": "PART-001",
            "sequence": 1,
            "unit_id": "PCS",
            "qty_asn": 10,
            "qty_remaining": 7,
            "qty_receive": 3,
            "batch_no": "BATCH-001",
            "rev": []
        }
    ]
}
```

Payload API tidak membawa `serial_no`, `exp_date`, `bin`, atau `loc`. Kalau
Flutter punya data itu dari barcode lama, abaikan untuk submit GR ASN.

Backend perlu resolve source data dari ASN, tetapi `warehouse_id` request dipakai
sebagai selected receiving warehouse setelah divalidasi:

- `PONumber` ambil dari ASN.
- `warehouse_id` request harus sama dengan `ASN.DestinationWarehouseID` atau
  child dari `ASN.DestinationWarehouseID`.
- `WarehouseID` GR/QC dan row plus `Buku_Stock` memakai selected
  `warehouse_id`.
- `CurrencyID` dan `Rate` ambil dari ASN.
- `RevCount` ambil dari ASN.
- `source_detail_key` harus ada di ASN detail.

## Validasi Backend

Validasi utama:

- ASN harus exist.
- ASN harus outstanding.
- ASN harus berada di warehouse yang boleh diakses user login.
- `warehouse_id` harus aktif.
- `warehouse_id` harus sama dengan ASN destination warehouse atau child/raknya.
- `warehouse_id` harus masuk authorization user, termasuk rule parent membawa
  child warehouse.
- Transaction date tidak boleh lebih kecil dari tanggal PO.
- Minimal ada satu detail dengan `qty_receive > 0`.
- Setiap detail wajib punya `source_detail_key`.
- `source_detail_key` harus milik ASN yang dipilih.
- `qty_receive` tidak boleh melebihi `qty_remaining`.
- Total receive untuk `source_detail_key` yang sama tidak boleh melebihi
  `qty_remaining`.
- Stok virtual ASN harus cukup sebelum dikurangi:

```text
BukuStockHelper::calculateCurrentStockByBatchNo(
  part_id,
  ASN.WarehouseID,
  batch_no,
  transaction_date
) >= qty_receive
```

Validasi stok API tidak memakai `SerialNo`, `ExpDate`, `BIN`, atau `LOC`.
Filter `transaction_date` hanya dipakai saat store/update. Reverse/delete tetap
cek stok current di warehouse penerima supaya tidak membatalkan GR jika stoknya
sudah dipakai transaksi lain.

## Auto Number

Auto number mengikuti web Goods Receiving ASN:

```text
Ms_AutoNumber.Purchase11/YYYY/MM/0001
```

Digit terakhir dicari dari `Trans_GoodsReceivingHD` yang `IsAuto = 1` pada bulan
dan tahun transaksi.

Jika `is_auto = false`, mobile wajib kirim `transaction_no`, dan backend harus
validasi unique ke `Trans_GoodsReceivingHD.TransactionNo`.

## Endpoint Yang Dibutuhkan

Status implementasi API:

- Controller: `app/Http/Controllers/Api/Transaction/GoodsReceivingAsnController.php`
- Requests: `app/Http/Requests/Transaction/GoodsReceivingAsn/*`
- Route prefix: `/api/goods-receiving-asn`
- Storage: reuse `Trans_GoodsReceivingHD`, `Trans_GoodsReceivingDT`,
  `Trans_QualityControlReceivingHD`, `Trans_QualityControlReceivingDT`, dan
  `Buku_Stock`
- Source document: `Trans_AdvanceShippingNoticeHD/DT`
- Stock detail identity API: batch-no-only
- `SerialNo`, `ExpDate`, `BIN`, dan `LOC` diisi `null` oleh backend API

Web existing tetap memakai route web `/gr/asn/*` dan controller
`Purchase\GoodsReceivingAsnController`.

Rekomendasi route API:

```text
GET    /api/goods-receiving-asn
GET    /api/goods-receiving-asn/details
GET    /api/goods-receiving-asn/asn-options
GET    /api/goods-receiving-asn/asn-details
POST   /api/goods-receiving-asn
PUT    /api/goods-receiving-asn
DELETE /api/goods-receiving-asn
```

### List Goods Receiving ASN

```text
GET /api/goods-receiving-asn
```

Query:

| Param          | Required | Notes                                                     |
| -------------- | -------- | --------------------------------------------------------- |
| `term`         | no       | search transaction no                                     |
| `warehouse_id` | no       | optional filter; tetap harus masuk allowed warehouse user |
| `date_from`    | no       | format `YYYY-MM-DD`                                       |
| `date_to`      | no       | format `YYYY-MM-DD`                                       |
| `outstanding`  | no       | filter outstanding                                        |
| `page`         | no       | default 1                                                 |
| `per_page`     | no       | default 10                                                |

Filter wajib:

```text
Trans_GoodsReceivingHD.ASNNumber IS NOT NULL
```

### Detail Goods Receiving ASN

```text
GET /api/goods-receiving-asn/details?transaction_no=...
```

Response:

- header GR
- detail GR
- `source_detail_key` dari kolom `GR_ASNDetailID` jika tersedia
- batch no
- qty receive
- ASN number
- PO number dari QC/header ASN

### ASN Options

```text
GET /api/goods-receiving-asn/asn-options
```

Query:

| Param            | Required | Notes                                          |
| ---------------- | -------- | ---------------------------------------------- |
| `search`         | no       | search ASN number atau PO number               |
| `include_asn_no` | no       | untuk edit agar ASN existing tetap bisa muncul |

Filter warehouse tidak wajib dari Flutter. Backend mengambil allowed warehouse
dari user login.

### ASN Details

```text
GET /api/goods-receiving-asn/asn-details?asn_no=...
```

Query optional:

| Param   | Required | Notes                                                            |
| ------- | -------- | ---------------------------------------------------------------- |
| `gr_no` | no       | untuk edit; qty existing GR tidak dihitung sebagai received lain |

Response harus sama dengan kebutuhan scan Flutter:

- `source_detail_key`
- `part_id`
- `part_name`
- `sequence`
- `unit_id`
- `unit_id_base`
- `qty_asn`
- `qty_remaining`
- `batch_no`
- `rev`

### Store

```text
POST /api/goods-receiving-asn
```

Backend steps:

1. Begin transaction.
2. Validate ASN, warehouse, date, qty.
3. Resolve auto/manual transaction no.
4. Create QC receiving header/detail.
5. Create GR header.
6. Insert GR detail.
7. Insert `Buku_Stock` plus `GOODS_RECEIVING`.
8. Insert `Buku_Stock` minus `ASN_GOODS_RECEIVING`.
9. Recalculate PO outstanding.
10. Recalculate ASN outstanding.
11. Rebuild journal `sp_jurnal_goodsreceiving`.
12. Commit.

### Update

```text
PUT /api/goods-receiving-asn
```

Backend steps:

1. Find GR by `transaction_no` and ensure `ASNNumber` exists.
2. Reverse old rows:
    - cek stok warehouse tujuan cukup untuk dikurangi
    - kurangi `Trans_PurchaseOrderDT.QtyReceived`
    - delete old `Buku_Stock`
    - delete old GR detail
    - rebuild QC detail
3. Validate new payload.
4. Insert ulang GR detail dan `Buku_Stock`.
5. Recalculate PO/ASN outstanding.
6. Rebuild journal.

### Delete

```text
DELETE /api/goods-receiving-asn
```

Backend steps:

1. Find GR by `transaction_no` or id.
2. Ensure GR exists dan `ASNNumber` tidak null.
3. Reverse old rows.
4. Delete journal.
5. Delete `Buku_Stock`.
6. Delete GR detail/header.
7. Delete QC detail/header.
8. Recalculate PO/ASN outstanding.

## Mapping Web Existing

Flow web saat ini:

- List/menu: `route('gr')`
- ASN add: `route('gr.asn.add')`
- Load ASN: `route('gr.asn.options')`
- Load ASN detail: `route('gr.asn.detail')`
- Store: `route('gr.asn.store')`
- Update: `route('gr.asn.update')`
- Delete: `route('gr.asn.delete')`

Controller web:

```text
app/Http/Controllers/Purchase/GoodsReceivingAsnController.php
```

View web:

```text
resources/views/purchase/gr_asn/*
```

## Open Decisions

- Apakah mobile perlu endpoint scan barcode khusus, atau cukup Flutter compare
  barcode dengan data `asn-details` yang sudah diload.
- Apakah API boleh edit/delete GR ASN dari mobile, atau mobile hanya create.
- Apakah `TransactionDate` mobile dikunci ke tanggal hari ini.
- Apakah response detail perlu membawa nama warehouse/currency lengkap atau cukup ID.

## Decided

- Mobile Goods Receiving memakai flow ASN, bukan flow PO manual.
- Source utama mobile adalah `Trans_AdvanceShippingNoticeHD/DT`.
- Mobile memilih receiving warehouse dari `ASN.DestinationWarehouseID` atau
  child/rak destination tersebut.
- List ASN mengikuti warehouse yang boleh diakses user login.
- Pilihan ASN/PO dibuat satu list seperti web: ASN sebagai value, PO sebagai
  informasi label.
- Detail bisa ditambahkan lewat scan barcode atau pilih manual satu per satu.
- Receive boleh partial/cicil antar GR selama tidak melebihi `qty_remaining`.
- Goods Receiving header menyimpan `ASNNumber`.
- Goods Receiving warehouse memakai selected receiving warehouse dari
  `warehouse_id`.
- `Buku_Stock` membuat dua arah:
    - plus ke selected receiving warehouse dengan `GOODS_RECEIVING`
    - minus dari virtual ASN warehouse dengan `ASN_GOODS_RECEIVING`
- Stock identity mengikuti batch-no-only: `PartID + WarehouseID + BatchNo`.
- API GR ASN tidak memakai `SerialNo`, `ExpDate`, `BIN`, atau `LOC`; semua kolom
  itu diisi `null` oleh backend.
