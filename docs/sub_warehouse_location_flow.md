# Sub Warehouse Location Flow

Dokumen ini mencatat konsep **sub warehouse** atau rak/lokasi gudang untuk API
mobile WMS, dengan fokus awal ke flow **ASN -> Goods Receiving**.

Tujuannya adalah supaya gudang utama dari ASN tetap menjadi referensi receiving,
tetapi saat GR mobile user bisa memilih lokasi fisik penerimaan: gudang utama
itu sendiri atau rak/sub warehouse di bawah gudang utama tersebut.

## Existing Base

Backend sudah punya basis untuk konsep ini:

- Table tetap memakai `Ms_Warehouse`.
- Kolom relasi parent sudah ada: `Ms_Warehouse.ParentID`.
- Model `MsWarehouse` sudah punya relation:

```php
public function parent()
{
    return $this->belongsTo(MsWarehouse::class, 'ParentID');
}
```

Contoh data:

```text
WH-CGK      = Gudang Cengkareng
RAK-A       = Rak A, ParentID = WH-CGK
RAK-B       = Rak B, ParentID = WH-CGK
```

Dengan konsep ini:

- `WH-CGK` adalah warehouse utama.
- `RAK-A` dan `RAK-B` adalah sub warehouse / lokasi / rak.
- Semua masih disimpan di `Ms_Warehouse`.

## Prinsip Utama

- Warehouse utama tetap jadi referensi dokumen parent seperti ASN dari web.
- Sub warehouse dipakai saat transaksi fisik GR menentukan lokasi real stock.
- `Buku_Stock.WarehouseID` untuk row plus GR harus memakai selected receiving
  warehouse.
- Selected receiving warehouse boleh gudang utama atau child/rak dari gudang
  utama.
- Kalau barang diterima ke `RAK-A`, maka stock plus masuk ke `RAK-A`.
- Kalau barang diterima langsung ke `WH-CGK`, maka stock plus masuk ke `WH-CGK`.
- Stock identity tetap mengikuti batch-no-only:

```text
PartID + WarehouseID + BatchNo
```

Karena `WarehouseID` bagian dari identity stock, pemilihan rak akan langsung
mempengaruhi saldo stock.

## Authorization Warehouse

Mapping warehouse user perlu bisa membawa parent-child.

Aturan yang disarankan:

```text
Jika user dimapping ke warehouse utama, user otomatis boleh akses semua child
warehouse dari parent tersebut.
```

Contoh:

```text
User A mapped to:
- WH-CGK

Maka user A boleh akses:
- WH-CGK
- RAK-A, karena ParentID = WH-CGK
- RAK-B, karena ParentID = WH-CGK
```

Jika user hanya dimapping ke sub warehouse:

```text
User B mapped to:
- RAK-A

Maka user B minimal boleh akses RAK-A.
```

Open decision:

- Apakah user yang mapped ke `RAK-A` juga boleh melihat parent `WH-CGK`.
- Saran: boleh untuk kebutuhan display/context, tetapi transaksi stock tetap
  dibatasi ke `RAK-A`.

## Helper Authorization Yang Dibutuhkan

`WarehouseAccessCriteria::allowedIds()` saat ini mengembalikan warehouse yang
langsung dimapping ke user.

Untuk flow sub warehouse, perlu helper tambahan atau penyesuaian:

```php
WarehouseAccessCriteria::allowedIdsWithChildren()
```

Konsepnya:

1. Ambil warehouse yang langsung dimapping ke user.
2. Ambil semua `Ms_Warehouse.WarehouseID` yang `ParentID` ada di list mapping.
3. Merge parent + child.
4. Pakai hasil merge untuk list warehouse dan validasi transaksi API.

Pseudo:

```text
direct_allowed = Trans_UserWarehouseDT.WarehouseID by user
child_allowed = Ms_Warehouse.WarehouseID where ParentID in direct_allowed
allowed = unique(direct_allowed + child_allowed)
```

Kalau control panel `implement_user_warehouse_mapping` tidak aktif, behavior tetap
seperti sekarang: semua warehouse boleh diakses.

## API Master Warehouse

Perlu endpoint/filter untuk mengambil sub warehouse berdasarkan parent.

Pilihan 1, tambah filter di endpoint existing:

```text
GET /api/warehouse?is_active=1&parent_id=WH-CGK
GET /api/warehouse/paginated?is_active=1&parent_id=WH-CGK
```

Pilihan 2, endpoint khusus:

```text
GET /api/warehouse/sub-warehouses?parent_id=WH-CGK
```

Saran:

- Tambah filter `parent_id` di endpoint existing supaya reusable.
- Tambah option `include_self=true` supaya app bisa menampilkan gudang utama juga
  jika tidak ada rak.

Contoh request:

```text
GET /api/warehouse?is_active=1&parent_id=WH-CGK&include_self=1
```

Contoh response:

```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "Warehouses fetched successfully"
  },
  "data": {
    "warehouses": [
      {
        "WarehouseID": "WH-CGK",
        "WarehouseName": "Gudang Cengkareng",
        "ParentID": null,
        "Active": 1
      },
      {
        "WarehouseID": "RAK-A",
        "WarehouseName": "Rak A",
        "ParentID": "WH-CGK",
        "Active": 1
      },
      {
        "WarehouseID": "RAK-B",
        "WarehouseName": "Rak B",
        "ParentID": "WH-CGK",
        "Active": 1
      }
    ]
  }
}
```

## Fokus Flow ASN -> GR

Flow sekarang:

```text
PO -> ASN -> GR
```

ASN menyimpan warehouse tujuan utama:

```text
Trans_AdvanceShippingNoticeHD.DestinationWarehouseID = WH-CGK
```

Stock ASN sendiri berada di gudang virtual/in-transit ASN:

```text
Trans_AdvanceShippingNoticeHD.WarehouseID = ASN virtual warehouse
```

Di flow existing API, `virtual_warehouse_id` di response ASN options/details
mengarah ke `Trans_AdvanceShippingNoticeHD.WarehouseID`.

Flow baru di mobile:

```text
1. User pilih ASN.
2. Backend return destination warehouse utama dari ASN: WH-CGK.
3. Backend/app menampilkan pilihan receiving location:
   - WH-CGK sebagai gudang utama
   - semua child dengan ParentID = WH-CGK
4. User pilih lokasi tujuan, misalnya RAK-A atau WH-CGK.
5. Submit GR memakai selected receiving warehouse.
6. Buku_Stock plus GR masuk ke selected receiving warehouse.
7. Buku_Stock minus ASN tetap dari virtual/in-transit warehouse ASN.
```

Request GR ASN perlu field tambahan:

```json
{
  "asn_no": "ASN/2026/08/0001",
  "transaction_date": "2026-08-07",
  "warehouse_id": "RAK-A",
  "details": [
    {
      "source_detail_key": 1,
      "part_id": "PART-001",
      "unit_id": "PCS",
      "qty_receive": 5,
      "batch_no": "BATCH-001"
    }
  ]
}
```

Validasi backend:

- `warehouse_id` required untuk flow sub warehouse.
- `warehouse_id` harus aktif.
- `warehouse_id` boleh sama dengan `ASN.DestinationWarehouseID`.
- `warehouse_id` boleh child dari `ASN.DestinationWarehouseID`.
- `warehouse_id` harus masuk allowed warehouse user setelah parent-child mapping
  dihitung.
- Qty receive tetap tidak boleh melebihi outstanding ASN.
- Stock identity tetap `PartID + warehouse_id + BatchNo`.

Storage:

- `Trans_GoodsReceivingHD.WarehouseID = selected warehouse_id`.
- `Trans_GoodsReceivingDT` tetap detail item/batch.
- `Buku_Stock` plus `GOODS_RECEIVING` memakai selected warehouse.
- `Buku_Stock` minus `ASN_GOODS_RECEIVING` tetap memakai virtual ASN warehouse.

Contoh jika ASN destination `WH-CGK` dan user pilih `RAK-A`:

```text
ASN destination = WH-CGK
ASN virtual warehouse = ASN/2026/08/0001
User pilih rak = RAK-A

Buku_Stock plus:
PartID = PART-001
WarehouseID = RAK-A
BatchNo = BATCH-001
Qty = +5
TransactionType = GOODS_RECEIVING

Buku_Stock minus:
PartID = PART-001
WarehouseID = ASN/2026/08/0001
BatchNo = BATCH-001
Qty = -5
TransactionType = ASN_GOODS_RECEIVING
```

Contoh jika ASN destination `WH-CGK` dan user pilih gudang utama langsung:

```text
ASN destination = WH-CGK
ASN virtual warehouse = ASN/2026/08/0001
User pilih receiving warehouse = WH-CGK

Buku_Stock plus:
PartID = PART-001
WarehouseID = WH-CGK
BatchNo = BATCH-001
Qty = +5
TransactionType = GOODS_RECEIVING

Buku_Stock minus:
PartID = PART-001
WarehouseID = ASN/2026/08/0001
BatchNo = BATCH-001
Qty = -5
TransactionType = ASN_GOODS_RECEIVING
```

## List Receiving Warehouse

Saat user memilih ASN, app perlu menampilkan pilihan receiving warehouse dari
destination ASN.

Jika:

```text
ASN.DestinationWarehouseID = WH-CGK
```

Maka list receiving warehouse:

```text
WH-CGK
RAK-A, ParentID = WH-CGK
RAK-B, ParentID = WH-CGK
```

Gudang utama wajib tetap masuk list, walaupun punya child. Alasannya, real case
barang bisa saja memang ditaruh langsung di area gudang utama, bukan ke rak.

Endpoint yang disarankan:

```text
GET /api/warehouse?is_active=1&parent_id=WH-CGK&include_self=1
```

Atau kalau dibuat endpoint khusus:

```text
GET /api/warehouse/receiving-locations?warehouse_id=WH-CGK
```

Response harus sudah terfilter authorization user.

## Stock Monitoring

Dengan sub warehouse, stock monitoring perlu punya dua mode:

1. Mode exact warehouse:
   - filter `WarehouseID = RAK-A`
   - hasil hanya stock di Rak A

2. Mode include children:
   - filter `WarehouseID = WH-CGK`
   - hasil stock di `WH-CGK + semua child`

Saran API:

```text
GET /api/stock-monitor?warehouse_id=WH-CGK&include_children=1
```

Kalau `include_children=0`, behavior tetap existing.

## Impact Ke Buku Stock

Tidak perlu table baru untuk stock.

Untuk ASN -> GR, selalu ada dua sisi stock:

```text
1. Stock ASN / in-transit:
   PartID + ASN virtual warehouse + BatchNo

2. Stock GR / receiving:
   PartID + selected receiving warehouse + BatchNo
```

`selected receiving warehouse` bisa gudang utama atau rak.

Contoh ASN destination `WH-CGK`, virtual warehouse `ASN/2026/08/0001`.

Jika user pilih gudang utama:

```text
Minus ASN: PartID + ASN/2026/08/0001 + BatchNo
Plus GR:   PartID + WH-CGK + BatchNo
```

Jika user pilih rak:

```text
Minus ASN: PartID + ASN/2026/08/0001 + BatchNo
Plus GR:   PartID + RAK-A + BatchNo
```

Konsekuensi:

- Stock gudang utama tidak otomatis sama dengan total semua rak.
- Jika butuh total gudang utama, query harus aggregate parent + child.
- Jika transaksi memilih gudang utama langsung, stock tetap tercatat di gudang
  utama.
- Jika transaksi memilih rak, stock tercatat di rak.

## API Adjustments Summary

Master Warehouse:

- Tambah request filter `parent_id`.
- Tambah request option `include_self`.
- Response include `ParentID`.
- Authorization memakai allowed warehouse parent-child.

Goods Receiving ASN:

- Store/update menerima `warehouse_id`.
- `warehouse_id` adalah selected receiving warehouse.
- Selected receiving warehouse boleh gudang utama ASN destination atau child/rak.
- Validasi selected warehouse terhadap `ASN.DestinationWarehouseID`.
- Insert GR HD dan `Buku_Stock` plus ke selected receiving warehouse.
- Insert `Buku_Stock` minus tetap dari ASN virtual warehouse.

Warehouse Authorization:

- Tambah helper allowed warehouse yang include child.
- Mapping parent otomatis membawa child.
- Mapping child minimal bisa pakai child itu sendiri.

## Open Decisions

- Apakah field `warehouse_id` di GR ASN wajib langsung sekarang, atau optional
  fallback ke `ASN.DestinationWarehouseID`.
- Apakah user mapped ke child boleh melihat parent untuk display.
- Apakah stock monitoring default perlu include children saat user memilih
  warehouse utama.

## Decided

- Sub warehouse memakai table yang sama: `Ms_Warehouse`.
- Relasi parent-child memakai `Ms_Warehouse.ParentID`.
- Mapping warehouse user ke parent harus bisa membawa child.
- Stock real harus dicatat di lokasi fisik selected warehouse/rak.
- Stock identity tetap batch-no-only: `PartID + WarehouseID + BatchNo`.
- GR ASN bisa memilih receiving warehouse dari ASN destination:
  - gudang utama `ASN.DestinationWarehouseID`
  - child/rak dengan `ParentID = ASN.DestinationWarehouseID`
- Stock ASN tetap dikurangi dari virtual warehouse ASN.
- Stock GR masuk ke selected receiving warehouse.
