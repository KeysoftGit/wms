# Checklist Perubahan Frontend Mobile (Flutter - `ikbl_wms_app`)

Dokumen ini berisi daftar lengkap file, model, controller, repository, dan tampilan UI di repositori `ikbl_wms_app` yang perlu disesuaikan berdasarkan **Flutter API Consume Guide** dan audit konsistensi API transaksi WMS.

---

## 🛑 Status Backend API (`ikbl-wms`)
- **Backend API**: Sesuai instruksi, **TIDAK ADA KODE BACKEND YANG DIUBAH**.
- Semua penyesuaian dilakukan 100% pada repositori mobile Flutter `ikbl_wms_app`.

---

## 📑 Daftar Perubahan per Modul & Komponen Flutter

### 1. 🚚 Delivery Order Execute (`DO List Total Qty & Qty2`)

#### 📄 [delivery_order_hd.dart](file:///Users/wahyudwiutomo/KERJAAN/Keysoft/ikbl/ikbl_wms_app/lib/core/models/delivery_order/delivery_order_hd.dart)
- Tambahkan field `totalQty` (`double?`) dan `totalQty2` (`double?`) pada model `DeliveryOrderHD`.
- Update `factory DeliveryOrderHD.fromJson` untuk membaca `json['total_qty']` dan `json['total_qty2']`.

#### 📄 [delivery_order_execute_list_page.dart](file:///Users/wahyudwiutomo/KERJAAN/Keysoft/ikbl/ikbl_wms_app/lib/pages/delivery_order_execute/delivery_order_execute_list_page.dart)
- Update render card transaksi DO untuk menampilkan total kuantitas gabungan:
  ```text
  DO/2026/08/0001
  Customer: PT ABC
  Total: 100 KG / 10 PCS
  Status: Pending
  ```
- Jika `totalQty2 == null`, tampilkan `Total: 100 KG` atau `-`.

---

### 2. 📍 Location Adjustment (`Input Qty2, Dual Unit & Conversion`)

#### 📄 [stock_location_check.dart](file:///Users/wahyudwiutomo/KERJAAN/Keysoft/ikbl/ikbl_wms_app/lib/core/models/location_adjustment/stock_location_check.dart)
- Update sub-model `SystemLocation`:
  - Tambahkan properti `qty2` (`double?`), `unitId2` (`String?`), dan `weightPerPiece` (`double?`).
- Update model `StockLocationCheck`:
  - Tambahkan properti `maxAdjustableQty2` (`double?`) dan `suggestedUnitId2` (`String?`).

#### 📄 [location_adjustment_form_controller.dart](file:///Users/wahyudwiutomo/KERJAAN/Keysoft/ikbl/ikbl_wms_app/lib/core/controllers/location_adjustment/location_adjustment_form_controller.dart)
- Update logika pencocokan dan penyesuaian stok lokasi:
  - Dukung pilihan **Input Unit** (Primary `UnitID` vs Secondary `UnitID2`).
  - Jika user menginput kuantitas dalam unit secondary (misal PCS / COIL):
    $$\text{Qty1} = \frac{\text{InputQty2}}{\text{maxAdjustableQty2}} \times \text{maxAdjustableQty}$$
  - Simpan `qty2`, `unitId2`, dan `weightPerPiece` pada draft line item.

#### 📄 Modal Dialog / Bottom Sheet Penyesuaian Lokasi
- Update tampilan modal penyesuaian lokasi agar user dapat memilih unit input (dropdown) dan melihat preview konversi `Qty 1` & `Qty 2` secara real-time.

---

### 3. 📦 Goods Receiving (GR ASN)

#### 📄 [goods_receiving_asn_repository.dart](file:///Users/wahyudwiutomo/KERJAAN/Keysoft/ikbl/ikbl_wms_app/lib/core/repositories/goods_receiving/goods_receiving_asn_repository.dart)
- Pastikan pada flow Edit, pemanggilan API `getAsnDetails` selalu menyertakan query parameter `gr_no`:
  ```dart
  GET /api/goods-receiving-asn/asn-details?asn_no=$asnNo&gr_no=$grNo
  ```

#### 📄 Verifikasi Payload Create / Update:
- Field `source_detail_key`, `unit_id`, dan `qty_remaining` bersifat opsional di backend (tetap dikirim dari FE untuk kompatibilitas).

---

### 4. 🏷️ Batch No & Sentinel `__NULL__` Standard

- **Aturan Filter Batch pada Form & Filter Dialog**:
  - User memilih Batch spesifik $\rightarrow$ Kirim string Batch (contoh: `"BATCH-001"`).
  - Part sengaja tidak memiliki Batch (kosong) $\rightarrow$ Kirim sentinel string `"__NULL__"`.
  - Filter Batch untouched / all $\rightarrow$ Omit parameter `batch_no` dari query (jangan kirim `null` string).

---

### 5. ⚖️ Baseline Display Standard: Dual Quantity & Anomali Data

- **Komponen Target**: Card transaksi & rincian detail pada ketujuh modul:
  1. Part Usage
  2. Stock Adjustment
  3. Item Transfer
  4. Location Adjustment
  5. Purchase Return Execute
  6. Goods Receiving ASN
  7. Delivery Order Execute
- **Aturan Render UI**:
  - Tampilkan `qty2` dan `unit_id2` pada list & detail card.
  - Jika `qty2 == null`, **jangan sembunyikan field**, melainkan tampilkan tanda `-` agar anomali data dapat dideteksi oleh user.

---

## 📌 Rangkuman File yang Akan Diubah di `ikbl_wms_app`:

| No | Modul | File Target | Jenis Perubahan |
|---|---|---|---|
| 1 | DO Execute | `lib/core/models/delivery_order/delivery_order_hd.dart` | Tambah `totalQty` & `totalQty2` |
| 2 | DO Execute | `lib/pages/delivery_order_execute/delivery_order_execute_list_page.dart` | Render Total Qty / Qty2 di List Card |
| 3 | Location Adj | `lib/core/models/location_adjustment/stock_location_check.dart` | Tambah field `qty2`, `unitId2`, `maxAdjustableQty2`, `suggestedUnitId2` |
| 4 | Location Adj | `lib/core/controllers/location_adjustment/location_adjustment_form_controller.dart` | Logika konversi Input Qty2 $\rightarrow$ Qty1 |
| 5 | Location Adj | `lib/pages/location_adjustment/` UI sheets | Modal input unit dropdown & dual qty preview |
| 6 | GR ASN | `lib/core/repositories/goods_receiving/goods_receiving_asn_repository.dart` | Query param `gr_no` saat edit flow |
| 7 | All 7 Modules | Model & UI List/Detail Cards | Render `qty2` / `unit_id2` atau `-` jika null |
