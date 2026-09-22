# Flutter API Consume Guide

Panduan konsumsi **7 API transaction controllers** dari sisi mobile
(`ikbl_wms_app`). Doc ini companion untuk `api-transaction-consistency.md` —
BE-side rules ada di sana, dokumen ini fokus ke sisi FE.

## Scope

7 modul API transaction yang di-consume mobile:

| Module | Base Endpoint | Existing per-module Doc |
|---|---|---|
| Part Usage | `/api/part-usage` | — |
| Stock Adjustment | `/api/stock-adjustment` | — |
| Item Transfer | `/api/direct-item-transfer` | — |
| Location Adjustment | `/api/location-adjustment` | `docs/location_adjustment.md` |
| Purchase Return Execute | `/api/purchase-return-execute` | `docs/purchase_return_execute_api.md` |
| Goods Receiving ASN | `/api/goods-receiving-asn` | `docs/goods_receiving_asn_api.md` |
| Delivery Order Execute | `/api/delivery-order-execute` | `docs/delivery_order_execute.md` |

**Doc ini tidak mengulang** payload/response per-endpoint yang sudah ada di doc
per-module. Fokus ke **cross-cutting concerns** dan **pattern reusable** yang
Flutter dev harus pegang.

---

## Base Convention

### HTTP

- Base URL: `AppConfig.baseUrl` (env config, contoh `https://wms.ikbl.local`).
- Auth: `Authorization: Bearer <token>` (Sanctum/JWT sesuai backend).
- Content-Type: `application/json`.
- Semua endpoint transaction pakai **`prefix: /api/`**.

### Response Envelope

Semua endpoint mengikuti format `ResponseFormatter`:

**Success (2xx):**

```json
{
  "meta": {
    "code": 200,
    "status": "success",
    "message": "<action> fetched/created/updated/deleted successfully"
  },
  "data": { ... }
}
```

**Error (4xx/5xx):**

```json
{
  "meta": {
    "code": 400,
    "status": "error",
    "message": "<human readable error>"
  }
}
```

Flutter parser rekomendasi:

```dart
class ApiResponse<T> {
  final int code;
  final String status;
  final String message;
  final T? data;

  bool get isSuccess => status == 'success' && code >= 200 && code < 300;
}
```

### Pagination Object

Semua list endpoint pakai shape:

```json
{
  "current_page": 1,
  "per_page": 10,
  "total": 42,
  "last_page": 5,
  "has_more": true
}
```

Query param: `?page=1&per_page=10`.

---

## Cross-Cutting Rules

### 1. Baseline Assumption: Dual Quantity Selalu Ada

Semua part yang masuk transaksi WMS **pasti punya `Qty2` dan `UnitID2`**.

FE **wajib**:

- Render `qty2`, `unit_id2`, dan `weight_per_piece` di list & detail card.
- Format: `"100 KG / 10 PCS"` atau `"100 KG (10 PCS @ 10 KG/PCS)"`.
- **Tidak boleh** conditional hide field kalau `qty2 == null` — tampilkan `-`
  supaya data anomaly ke-detect user.

### 2. Weight Per Piece

Dihitung backend, FE cukup render:

```dart
final wpp = detail.weightPerPiece; // sudah di response
if (wpp != null) {
  Text('${wpp.toStringAsFixed(2)} ${unitId1}/${unitId2}');
}
```

Jangan hitung sendiri di FE. Backend sudah pakai `DualQuantityHelper::weightPerPiece()`.

### 3. Coil No

Optional metadata batch. Kalau `coil_no == null`, tampilkan `-` atau hide sesuai
UX. **Jangan block form** kalau coil kosong.

### 4. Batch No & `__NULL__` Sentinel

Batch adalah bagian **identitas stok** (batch-no-only concept).

Tiga state:

| FE Kirim | Arti Backend |
|---|---|
| tidak kirim field | tidak filter batch (all batches) |
| `"BATCH-001"` | filter batch tersebut |
| `"__NULL__"` | filter `WHERE BatchNo IS NULL` (batch kosong disengaja) |

Untuk **payload transaction detail**:

- Kalau user select batch → kirim value batch-nya.
- Kalau part memang tidak punya batch (bisnis mengizinkan) → kirim `"__NULL__"`.
- Jangan kirim `null` — backend akan treat sebagai "tidak select".

Untuk **stock check / filter**:

- Kalau user belum select batch → **omit** field batch dari query.
- Kalau user pilih `(Empty)` di dropdown → kirim `"__NULL__"`.

### 5. Sign Convention

Data ledger (`Buku_Stock`) pakai sign:

- **Positif** = inbound (GR, transfer in, stock adjustment +).
- **Negatif** = outbound (Part Usage, DO, transfer out, stock adjustment -).

FE **selalu tampilkan absolute value** untuk display. Sign hanya untuk internal.

### 6. Warehouse Authorization

Backend enforce `WarehouseAccessCriteria::allowedIdsWithChildren()`:

- Mapping parent warehouse → user otomatis dapat akses semua child/rak.
- Sub-warehouse (rak) parent lookup via `Ms_Warehouse.ParentID`.
- Kalau `control_panel.implement_user_warehouse_mapping` off → semua warehouse
  bebas akses.

FE tidak perlu apply filter sendiri — trust response backend.

### 7. Modal Input Standar Transaksi Stok

Semua modul stok pakai urutan field:

```text
Part → Warehouse → Batch No → Coil No → Input Unit → Input Qty → Qty 1 → Qty 2 → Notes
```

Rules:

- `Coil No` = readonly display, ambil dari `PartID + BatchNo` mapping.
- `Input Unit` = dropdown pilih primary (`UnitID`) atau secondary (`UnitID2`).
- `Input Qty` = user input di unit tersebut.
- `Qty 1` dan `Qty 2` = readonly, di-compute FE berdasarkan rasio current stock:
  - Input primary → `Qty 2 = input_qty_1 / current_qty_1 * current_qty_2`
  - Input secondary → `Qty 1 = input_qty_2 / current_qty_2 * current_qty_1`
- `Notes` = textarea di paling akhir.

Rasio **dari current stock** (bukan dari master `MsPartUnit.Conversion`).

---

## Consume Archetype vs Source-Doc Copy

Per doc `api-transaction-consistency.md`, backend group 7 modul jadi 2 archetype.
FE bisa treat archetype yang sama dengan pola serupa.

### Archetype 1: Consume (6 modul)

**PartUsage, StockAdjustment, ItemTransfer, LocationAdjustment,
PurchaseReturnExecute, DeliveryOrderExecute.**

Pattern FE:

1. Load current stock (`getStockDetail` / `stock-location-check` / detail
   endpoint) untuk dapat `qty1_stock`, `qty2_stock`, `unit_id2`, `coil_no`.
2. User input Qty via modal (pattern di atas).
3. FE display preview Qty1 & Qty2 setelah user pilih input unit.
4. Submit ke API — payload cukup bawa Qty1 (primary), backend hitung Qty2
   proporsional sendiri.
5. Response detail selalu bawa Qty2 dari `Buku_Stock` (bukan dari payload).

### Archetype 2: Source-Doc Copy (1 modul: GR ASN)

Pattern FE:

1. Load ASN outstanding (`asn-options`).
2. User pilih ASN → load detail (`asn-details`) yang sudah bawa
   `qty2_asn/unit_id2/coil_no/gross_weight` dari `Trans_AdvanceShippingNoticeDT`.
3. User pilih receiving warehouse (bisa parent atau child/rak).
4. Qty receive per line **locked** ke `qty_remaining` (partial receiving per
   line item, bukan per qty).
5. Submit — backend copy Qty2/UnitID2 dari ASN detail langsung ke `Buku_Stock`.

---

## Testing Round Rabu — Perubahan yang FE Perlu Adjust

Backend sudah fix. FE perlu update untuk consume perubahan ini.

### 1. GR ASN List Detail (Item 3)

**Sebelum:** kalau GR punya 2 part, list detail cuma tampil 1.

**Sekarang:** backend fix dengan `orderBy('Sequence')`. FE tidak perlu ubah kode —
tinggal re-test dan pastikan list render semua row dari `data.details[]`.

### 2. GR ASN Edit Load ASN (Item 4)

**Sebelum:** edit gagal kalau warehouse mapping user berubah setelah receiving.

**Sekarang:** kalau FE kirim `?gr_no=<transaction_no>` di query
`GET /api/goods-receiving-asn/asn-details`, backend auto-detect edit context dan
bypass warehouse filter.

FE **wajib** kirim `gr_no` saat edit flow:

```dart
// Edit context
final res = await http.get(Uri.parse(
  '$baseUrl/api/goods-receiving-asn/asn-details?asn_no=$asnNo&gr_no=$grNo'
));

// Create context (tidak perlu gr_no)
final res = await http.get(Uri.parse(
  '$baseUrl/api/goods-receiving-asn/asn-details?asn_no=$asnNo'
));
```

### 3. GR ASN Update Payload (Item 5)

**Sebelum:** update sering bad request karena validation strict.

**Sekarang:** validation dilonggarkan. Field berikut jadi **optional** di
payload update/create:

| Field | Status |
|---|---|
| `details.*.source_detail_key` | optional (backend fallback lookup by PartID+Sequence) |
| `details.*.unit_id` | optional (backend derive dari ASN `unit_id_base`) |
| `details.*.qty_remaining` | optional (backend recompute sendiri) |

FE boleh tetap kirim untuk backward compat, tapi kalau kosong tidak error.

`warehouse_id` sekarang tidak divalidasi via `exists:` — backend cek via
`resolveReceivingWarehouseId()` dengan error message lebih spesifik.

### 4. Location Adjustment Input Qty2 (Item 8)

**Sebelum:** cuma bisa input Qty1 (KG).

**Sekarang:** response `checkStockLocation` extended:

```json
{
  "part_id": "PART-001",
  "batch_no": "A05",
  "physical_warehouse_id": "WH-B",
  "system_locations": [
    {
      "warehouse_id": "WH-A",
      "qty": 100.0,
      "qty2": 10.0,
      "unit_id2": "PCS",
      "weight_per_piece": 10.0
    }
  ],
  "matched": false,
  "suggested_from_warehouse_id": "WH-A",
  "suggested_to_warehouse_id": "WH-B",
  "max_adjustable_qty": 100.0,
  "max_adjustable_qty2": 10.0,
  "suggested_unit_id2": "PCS",
  "requires_manual_review": false
}
```

**FE strategy** — kalau user input pakai Qty2 (PCS/COIL):

```dart
// Convert Qty2 → Qty1 di FE pakai rasio suggested
final qty1 = (inputQty2 / suggested.qty2) * suggested.qty;

// Submit ke API tetap kirim Qty1
final payload = {
  'physical_warehouse_id': ...,
  'staff_in_charge_id': ...,
  'details': [
    {
      'warehouse_id_from': suggested.warehouseId,
      'part_id': partId,
      'unit_id': unitId1,  // tetap primary unit
      'conversion': 1,
      'qty': qty1,         // hasil convert dari Qty2
      'batch_no': batchNo,
    }
  ]
};
```

Preview di modal:

```text
Input Unit: [PCS ▼]
Input Qty: [2]
─────────────
Qty 1: 20 KG    (readonly, hasil convert)
Qty 2: 2 PCS    (input user)
```

### 5. Location Adjustment Batch Persistence (Item 9)

**Sebelum:** batch cuma tersimpan di `Buku_Stock`. Kalau ledger row dihapus,
batch info hilang dari detail.

**Sekarang:** `Trans_DirectItemTransferDT.BatchNo` juga di-insert (kalau kolom
ada). Detail response tetap bawa `BatchNo` seperti sebelumnya — no FE change.

### 6. DO List Qty2 (Item 12)

**Sebelum:** list DO cuma bawa header info, tidak ada aggregate qty.

**Sekarang:** response `GET /api/delivery-order-execute` per transaction bawa:

```json
{
  "transaction_no": "DO/2026/08/0001",
  ...
  "total_qty": 100.0,
  "total_qty2": 10.0
}
```

FE render di list card:

```text
DO/2026/08/0001
Customer: ABC Corp
Total: 100 KG / 10 PCS   ← baru
Status: Pending
```

`total_qty` dan `total_qty2` bisa `null` kalau semua detail Qty2-nya null (edge
case anomaly).

### 7. Fixes Sudah di Round Sebelumnya (Verify Only)

- **Item 1** — GR delete: sudah support, tinggal panggil `DELETE /api/goods-receiving-asn`.
- **Item 2 & 10** — Qty2 di `Buku_Stock`: sudah insert via
  `bukuStockSecondaryColumns()`. Tinggal re-test.
- **Item 6** — Cicil GR: sudah support via `qty_remaining` logic. FE tinggal
  refresh detail per submit.

### 8. FE-Only Items

Tidak butuh API change:

- **Item 7** — Hapus draft line Location Adjustment → FE state management (remove
  dari `List<AdjustmentDraft>` sebelum submit).
- **Item 11** — Format qty di DO list → FE display (jangan pakai `NumberFormat`
  yang round, pakai raw value).

---

## Model Layer Pattern (Dart)

Rekomendasi struktur untuk consistency antar module.

### DTO Base

```dart
abstract class TransactionDetailDTO {
  final String partId;
  final String warehouseId;
  final String? batchNo;
  final String? coilNo;
  final String unitId;
  final double qty;
  final double? qty2;
  final String? unitId2;
  final double? weightPerPiece;

  const TransactionDetailDTO({
    required this.partId,
    required this.warehouseId,
    this.batchNo,
    this.coilNo,
    required this.unitId,
    required this.qty,
    this.qty2,
    this.unitId2,
    this.weightPerPiece,
  });
}
```

### JSON Mapping

Backend kadang kirim double key (snake_case + PascalCase) untuk compatibility.
Contoh: `qty2` DAN `Qty2`, `coil_no` DAN `CoilNo`.

FE parser: prefer snake_case, fallback ke PascalCase:

```dart
factory PartUsageDetail.fromJson(Map<String, dynamic> json) {
  return PartUsageDetail(
    partId: json['part_id'] ?? json['PartID'],
    batchNo: json['batch_no'] ?? json['BatchNo'],
    coilNo: json['coil_no'] ?? json['CoilNo'],
    qty2: _toDouble(json['qty2'] ?? json['Qty2']),
    unitId2: json['unit_id2'] ?? json['UnitID2'],
    // ...
  );
}

double? _toDouble(dynamic v) => v == null ? null : (v as num).toDouble();
```

### Nullable Handling

`Qty2 == null` per baseline assumption = data anomaly. Log dan tampilkan `-`:

```dart
Widget qty2Display(double? qty2, String? unitId2) {
  if (qty2 == null || unitId2 == null) {
    return Text('-', style: TextStyle(color: Colors.grey));
  }
  return Text('${qty2.toStringAsFixed(2)} $unitId2');
}
```

---

## State Management Pattern

### Form Wizard (Multi-Step)

Pakai untuk modul dengan flow scan/verifikasi. Contoh: **GR ASN**, **DO Execute**,
**Location Adjustment**.

```text
Step 1: Select header (ASN / DO / Warehouse)
Step 2: Scan items / pick manual → build draft
Step 3: Review & submit
```

State per step:

```dart
class FormWizardState {
  final int currentStep;
  final Map<String, dynamic> headerState;
  final List<DraftLine> draftLines;
  final bool isSubmitting;
  final String? error;
}
```

### Simple Form (Single-Step)

Untuk **Part Usage**, **Stock Adjustment**. User isi header + detail dalam 1 page.

```dart
class SimpleFormState {
  final Map<String, dynamic> header;
  final List<DetailRow> details;
}
```

### Pagination List

Lazy-load pattern:

```dart
Future<void> loadNextPage() async {
  if (!hasMore || isLoading) return;
  isLoading = true;
  final res = await api.getTransaction(page: currentPage + 1);
  transactions.addAll(res.transactions);
  currentPage = res.pagination.currentPage;
  hasMore = res.pagination.hasMore;
  isLoading = false;
}
```

---

## Error Handling

### Kategori Error

| HTTP | Meaning | FE Action |
|---|---|---|
| 200/201 | Success | Show success message from `meta.message` |
| 400 | Validation / Business error | Show `meta.message` di snackbar/dialog |
| 401 | Unauthorized | Trigger re-login flow |
| 403 | Forbidden (warehouse/permission) | Show `meta.message`, redirect back |
| 404 | Not found | Show "Data not found" |
| 500 | Server error | Show generic "Terjadi kesalahan, coba lagi." |
| Timeout | Network | Retry pattern (max 3x dengan backoff) |

### Pattern

```dart
try {
  final res = await api.storeTransaction(payload);
  if (!res.isSuccess) {
    showErrorSnackbar(res.message);
    return;
  }
  Navigator.pop(context, res.data);
} on TimeoutException {
  showErrorSnackbar('Koneksi timeout. Coba lagi.');
} on SocketException {
  showErrorSnackbar('Tidak ada koneksi internet.');
} catch (e) {
  showErrorSnackbar('Terjadi kesalahan: ${e.toString()}');
}
```

### Stock Not Enough Error

Backend format: `"Stock is not enough for selected stock details. Part: PART-001, Warehouse: WH-A, Batch No: BATCH-001."`

FE bisa parse untuk highlight field yang bermasalah, atau tampilkan raw.

---

## Testing Checklist per Module

Untuk setiap modul yang di-implement/update:

### List Page

- [ ] Load pertama tampil dengan pagination correct
- [ ] Pull-to-refresh reset page ke 1
- [ ] Lazy load next page saat scroll bottom
- [ ] Search/filter kirim query param yang benar
- [ ] Empty state kalau `total == 0`
- [ ] Loading indicator saat fetch

### Detail Page

- [ ] Load detail sukses tampil semua field
- [ ] `qty2/unit_id2/coil_no/weight_per_piece` di-render sesuai baseline
- [ ] Kalau `Qty2 == null` tampil `-` (jangan hide)
- [ ] Pagination detail (kalau ada) berfungsi
- [ ] Refresh setelah edit menampilkan data terbaru

### Form Page

- [ ] Modal input pakai urutan field standar
- [ ] Input Unit dropdown menampilkan primary + secondary (kalau stock ada Qty2)
- [ ] Qty 1 / Qty 2 preview auto-update saat Input Qty berubah
- [ ] Validation client-side: qty > 0, required fields
- [ ] Submit tampil loading, disable button sampai response
- [ ] Success → back ke list + snackbar
- [ ] Error → snackbar dengan message backend

### Edit Flow (khusus GR ASN)

- [ ] Kirim `gr_no` query saat load `asn-details`
- [ ] Existing detail lines pre-filled dari response
- [ ] Update sukses tanpa bad request

### Delete Flow

- [ ] Confirmation dialog sebelum delete
- [ ] Success → remove dari list
- [ ] Error handling (misal transaction sudah dipakai → backend reject)

---

## Anti-Pattern (Jangan Dilakukan di FE)

```dart
// ✗ Hitung weight_per_piece manual (sudah ada di response)
final wpp = detail.qty > 0 && detail.qty2 > 0
    ? detail.qty / detail.qty2
    : null;

// ✗ Hardcode unit condition
if (detail.unitId == 'KG') { ... }

// ✗ Hide qty2 kalau null (harus tampil `-`)
if (detail.qty2 != null) Text('${detail.qty2} PCS')

// ✗ Kirim `null` string untuk batch kosong
'batch_no': 'null'   // salah — pakai '__NULL__' atau omit field

// ✗ Convert Qty2 ↔ Qty1 pakai MsPartUnit.Conversion (bukan rasio stock)
final qty1 = qty2 * masterConversion;   // salah — pakai rasio current stock

// ✗ Kirim payload tanpa Qty2 karena "gak wajib"
// backend auto-hitung dari current stock, tapi FE tetap harus display preview

// ✗ Ignore `total_qty2` di list DO (round Rabu fix)
Text('Total: ${transaction.totalQty} KG')   // seharusnya include qty2
```

---

## Referensi

- **BE-side rules**: `docs/api-transaction-consistency.md`
- **Concept dual quantity**: `docs/dual-quantity-unit-stock-concept.md`
- **Concept batch-no + coil-no**: `docs/batch-no-coil-no-concept.md`
- **Concept sub-warehouse**: `docs/sub_warehouse_location_flow.md`
- **Per-module spec (existing):**
  - `docs/goods_receiving_asn_api.md`
  - `docs/location_adjustment.md`
  - `docs/delivery_order_execute.md`
  - `docs/purchase_return_execute_api.md`
