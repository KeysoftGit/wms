# Purchase Return Execute API

API mobile untuk execute Purchase Return mengikuti flow web `PurchaseReturnExecuteController`.

## Routes

```text
GET  /api/purchase-return-execute
GET  /api/purchase-return-execute/details
POST /api/purchase-return-execute/execute
DELETE /api/purchase-return-execute/execute
```

## List

```text
GET /api/purchase-return-execute
```

Query optional:

| Param       | Notes                         |
| ----------- | ----------------------------- |
| `term`      | search transaction/supplier   |
| `status`    | `all`, `pending`, `executed`  |
| `date_from` | format date                   |
| `date_to`   | format date                   |
| `page`      | default 1                     |
| `per_page`  | default 10, max 100           |

Response utama:

```text
transactions[]
pagination
```

Setiap transaction membawa `executed` dan `status`.

## Details

```text
GET /api/purchase-return-execute/details?transaction_no=...
```

Response:

```text
transaction
details[]
pagination
```

Detail membawa:

```text
part_id
warehouse_id
unit_id
qty
qty2
unit_id2
coil_no
gross_weight
weight_per_piece
conversion
stock_qty
stock_unit_id
available_stock_qty
available_stock_qty2
available_unit_id2
total_stock_qty
total_stock_qty2
batch_no
executed_qty
executed_qty2
executed
```

Warehouse mengikuti detail Purchase Return. Tidak ada pemilihan warehouse di mobile.
`coil_no` diambil dari mapping `PartBatchCoil` via `CoilNoHelper` berdasarkan
`PartID + BatchNo`. `qty2` dihitung proporsional dari total current stock di
`Buku_Stock` jika total stok tersedia. Nilai `Qty2` dari detail Purchase Return
hanya menjadi fallback jika total stok sekunder tidak bisa dihitung:

```text
qty2 = return_stock_qty / available_stock_qty * available_stock_qty2
```

Contoh: current stock batch = `10000 KG` dan `1 PCS`, return = `200 KG`, maka
`qty2 = 200 / 10000 * 1 = 0.02 PCS`.
Hasil `qty2` dibulatkan 2 angka desimal sebelum dikirim ke mobile dan sebelum
diinsert ke `Buku_Stock`.

`available_stock_qty`/`total_stock_qty` dan
`available_stock_qty2`/`total_stock_qty2` berisi total current stock berdasarkan
`PartID + WarehouseID + BatchNo` sampai tanggal transaksi Purchase Return.

## Execute

```text
POST /api/purchase-return-execute/execute
```

Payload:

```json
{
  "transaction_no": "PR/2026/08/0001"
}
```

Backend:

1. Cek permission `pr_execute.add`.
2. Cek Purchase Return authorized.
3. Tolak jika sudah executed.
4. Hitung stok berdasarkan `PartID + WarehouseID + BatchNo + TransactionDate`.
5. Insert `Buku_Stock` minus dengan `TransactionType = PURCHASE_RETURN`,
   termasuk `Qty2/UnitID2` jika kolom tersedia.
6. Jika bukan based on GR, buat/update `Buku_Hutang`.
7. Recalculate reference GR/Direct Purchase outstanding/editable.
8. Rebuild journal `sp_jurnal_purchase_return`.

## Delete Execute

```text
DELETE /api/purchase-return-execute/execute
```

Payload:

```json
{
  "transaction_no": "PR/2026/08/0001"
}
```

Backend:

1. Cek permission `pr_execute.add`.
2. Cek Purchase Return authorized dan sudah executed.
3. Hapus row `Buku_Stock` dengan `TransactionType = PURCHASE_RETURN`.
4. Hapus journal header/detail.
5. Hapus `Buku_Hutang` jika execute sebelumnya membuat hutang return.
6. Recalculate reference GR/Direct Purchase outstanding/editable.
