# Delivery Order Execute — Implementation Guide

## Status

Dokumen ini adalah spesifikasi dan panduan implementasi lengkap modul **Delivery Order Execute (Alur Verifikasi, Scan Barcode, & Handling Multi-Rak)** pada aplikasi Mobile WMS.

- Backend Web Controller: `ikbl-wms/app/Http/Controllers/Sales/DeliveryOrderExecuteController.php`
- Backend API Controller: `ikbl-wms/app/Http/Controllers/Api/Transaction/DeliveryOrderExecuteController.php`
- API Route: `ikbl-wms/routes/api.php` (`Route::prefix('delivery-order-execute')`)
- Mobile Page Code:
  - Step 1 Detail: `ikbl_wms_app/lib/pages/delivery_order/delivery_order_execute_detail_page.dart`
  - Step 2 Scan: `ikbl_wms_app/lib/pages/delivery_order/delivery_order_execute_scan_page.dart`
  - Step 3 Summary: `ikbl_wms_app/lib/pages/delivery_order/delivery_order_execute_summary_page.dart`

---

## Ringkasan Alur Bisnis (Business Flow)

Modul **Delivery Order Execute** pada mobile **tidak membuat Delivery Order baru**. Delivery Order dibuat dan diinstruksikan dari sistem web oleh bagian Office. Office tidak wajib mengetahui kendaraan dan driver saat membuat DO Instruction. Data kendaraan dan driver diisi oleh staf gudang/WMS pada proses DO Execute di mobile sebelum stok dipotong. Mobile digunakan khusus oleh staf gudang untuk verifikasi fisik barang, pemilihan rak/sub-warehouse tempat fisik stok diambil, input kendaraan/driver aktual, dan eksekusi pemotongan stok WMS.

```text
Office buat DO Instruction di Web
  -> Detail DO memakai Gudang Utama (Instruksi) & Batch No dari Web
  -> Vehicle & Driver boleh kosong pada DO Instruction baru
  -> Belum memotong stok WMS & belum membuat Jurnal
  -> Staf Gudang membuka DO Execute di Mobile
  -> Step 1 (Detail): Input Kendaraan (Vehicle) & Driver, review Header DO (Termasuk Alamat Pengiriman), Gudang Utama, & opsi kandidat Rak Fisik
  -> Step 2 (Scan Part): Verifikasi fisik per barang (Scan Hardware Zebra/Kamera atau Input Manual)
      * Progress verifikasi target vs discan (0% -> 100%)
      * Handling Multi-Rak: Staf memilih rak/gudang fisik tempat barang diambil per line item
      * Pop-up / Bottom Sheet "+ Tambah Baris" untuk input manual & fetch API batch stok
  -> Step 3 (Ringkasan): Review rekapitulasi akhir terverifikasi 100% Sesuai & data Kendaraan/Driver
  -> Eksekusi DO (POST /api/delivery-order-execute/execute)
  -> Backend update sub-warehouse/rak detail DO, update VehicleID & DriverID di Trans_DeliveryOrderHD, insert Buku_Stock minus, & rebuild journal
```

### Catatan Status SO Closed

Logika bisnis bawaan Sales Order tetap dipertahankan:
- Pembuatan DO Instruction tetap mengikuti mekanisme lama update `QtySent` dan status `Closed` SO.
- DO Execute tidak mengubah aturan closing bawaan tersebut.
- Jika SO sudah berstatus `Closed`, DO Execute web/API harus menolak eksekusi dan mobile tidak boleh menampilkan tombol submit execute untuk transaksi tersebut.

---

## Standar UI & Alur Layar Wizard (3-Step Wizard Flow)

Alur verifikasi dan eksekusi DO pada mobile WMS menggunakan 3-Langkah Wizard yang selaras dengan modul **Part Usage**:

```text
[DeliveryOrderExecuteListPage]
       │
       ▼ (Pilih DO Pending)
[1. DeliveryOrderExecuteDetailPage] ──> Header DO, Alamat Pengiriman, Picker Kendaraan & Driver (Tombol "Mulai Verifikasi / Scan Barang")
       │
       ▼ (Tekan "Mulai Verifikasi")
[2. DeliveryOrderExecuteScanPage]   ──> Progres Scan (0-100%), Hardware Wedge Scanner, Handling Multi-Rak, & Modal "+ Tambah Baris"
       │
       ▼ Jika Seluruh Barang 100% Sesuai (Tekan "Lanjut ke Ringkasan")
[3. DeliveryOrderExecuteSummaryPage]──> Rekap Akhir (Barang & Kendaraan/Driver) & Tombol "Simpan & Eksekusi DO"
       │
       ▼ (Submit API Execute)
[Backend Stock Deduct, Vehicle/Driver Update & Journal Rebuild]
```

### Detail Tampilan per Layar (Screen Specifications)

#### 1. Step 1: Detail DO (`DeliveryOrderExecuteDetailPage`)
- **Header Info Card**: No. DO, Tgl Transaksi, Ref. SO, Customer, Alamat Pengiriman (`ShipmentAddress`), Catatan.
- **Input Wajib Sebelum Verifikasi**:
  - `Kendaraan / Vehicle *`: Wajib dipilih oleh staf gudang/WMS menggunakan `SearchableSelectionField` (Pencarian terpaginasi dari Master `MsVehicle`).
  - `Pengemudi / Driver *`: Wajib dipilih oleh staf gudang/WMS menggunakan `SearchableSelectionField` (Pencarian terpaginasi dari Master `MsEmployee`).
  - Apabila Kendaraan atau Driver belum dipilih saat menekan tombol "Mulai Verifikasi", sistem menampilkan validasi error.
- **Labeling & Formatting**:
  - `Gudang Utama`: Menampilkan gudang instruksi dari web.
  - `Lokasi Fisik / Rak`: Menampilkan pilihan kandidat rak/sub-warehouse tempat fisik stok berada di `Buku_Stock` (Hanya ditampilkan 1x pada pemilih lokasi interaktif di bagian bawah card).
  - `Unit`: Menampilkan kode `UnitID` langsung (misal: `500 KG`).
  - `No. Batch`: Ditampilkan mencolok menggunakan badge khusus `_buildBatchBadge` lengkap dengan ikon QR/barcode (`Icons.qr_code_2_rounded`).
  - `Coil No`: Ditampilkan dari response `coil_no`. Backend mengambilnya dari mapping `PartBatchCoil` lewat `CoilNoHelper` berdasarkan `PartID + BatchNo`.
  - `Qty2` dan `UnitID2`: Ditampilkan dari response `qty2` dan `unit_id2`. Backend mengambil nilainya langsung dari `Trans_DeliveryOrderDT`.
- **Aturan Tombol Bottom Bar**:
  - Hanya ada **satu tombol utama**: **`Mulai Verifikasi / Scan Barang`** (Navigasi ke Step 2 setelah validasi Kendaraan & Driver).

#### 2. Step 2: Scan & Verifikasi Part (`DeliveryOrderExecuteScanPage`)
Diselaraskan 100% dengan alur & komponen UI pada modul **Part Usage**:
- **Section 1: Progres Verifikasi**:
  - Menampilkan total target Qty vs total discan Qty.
  - Linear Progress Indicator & Persentase (`0%` hingga `100% Sesuai`).
  - Status Badge (`Semua Sesuai` - Hijau, `Belum Lengkap` - Kuning, `Ada Berlebih!` - Merah).
- **Section 3: List Kebutuhan Part & Handling Multi-Rak**:
  - Per baris `Trans_DeliveryOrderDT` ditampilkan terpisah (tanpa grouping/merging) berdasarkan komposit key `(Sequence, PartID, BatchNo)`.
  - **Fitur Multi-Rak / Sub-Warehouse**: Staf gudang dapat memilih/mengubah lokasi rak fisik (`real_locations`) tempat barang diambil untuk tiap item baris.
  - **Fitur "+ Tambah Baris" (Input Manual Modal)**:
    - Membuka Bottom Sheet `_openManualLineModal` saat tombol `+ Tambah Baris` atau card item di-tap.
  - **Tombol Navigasi**: Tombol **`Lanjut ke Ringkasan`** baru aktif apabila seluruh item barang berstatus 100% `Sesuai (Lengkap)`.

#### 3. Step 3: Ringkasan & Eksekusi (`DeliveryOrderExecuteSummaryPage`)
- Menampilkan rekapitulasi lengkap dokumen DO, Alamat Pengiriman, Kendaraan & Driver terpih, serta daftar item terverifikasi 100%.
- Tombol **`Simpan & Eksekusi DO`**.
- Membuka dialog konfirmasi sebelum mengirimkan request eksekusi ke backend.

---

## API & Payload Request

### Endpoint Eksekusi:
- **HTTP Method**: `POST`
- **URL**: `/api/delivery-order-execute/execute`
- **Headers**: `Authorization: Bearer {token}`, `Content-Type: application/json`

Endpoint tambahan:

```text
PUT    /api/delivery-order-execute/execute
DELETE /api/delivery-order-execute/execute
```

`PUT` dipakai untuk revisi eksekusi DO yang sudah executed. Backend menghapus
row `Buku_Stock` dan jurnal execute lama, validasi stok ulang sampai tanggal DO,
lalu insert ulang row execute.

`DELETE` dipakai untuk batal eksekusi DO. Backend menghapus row `Buku_Stock`
dan jurnal execute, lalu mengembalikan header DO menjadi editable.

### Payload Request Body:
```json
{
  "transaction_no": "DO/2026/08/0001",
  "vehicle_id": "VH-001",
  "driver_id": "EMP-DRV-001",
  "details": [
    {
      "sequence": 1,
      "warehouse_id": "RAK-A"
    },
    {
      "sequence": 2,
      "warehouse_id": "RAK-B"
    }
  ]
}
```

### Validasi Backend Execute
- `transaction_no`: wajib, harus DO yang bisa diakses user.
- `vehicle_id`: wajib, harus ada di `Ms_Vehicle.VehicleID`.
- `driver_id`: wajib, harus ada di `Ms_Employee.EmployeeID`.
- `details`: wajib minimal 1 baris.
- `details.*.sequence`: wajib, unique.
- `details.*.warehouse_id`: wajib, harus gudang/rak real yang valid untuk detail DO dan mapping warehouse user.
- Backend menolak execute jika DO sudah pernah membentuk `Buku_Stock` dengan `TransactionType = DELIVERY_ORDER`.
- Backend denolak execute jika SO referensi sudah `Closed`.
- Validasi stok memakai `BukuStockHelper::calculateCurrentStockByBatchNo()`
  berdasarkan `PartID + selected WarehouseID + BatchNo + TransactionDate DO`.
- Response detail DO membawa `coil_no`, `qty2`, dan `unit_id2`.
  `coil_no` berasal dari mapping batch-coil, sedangkan `qty2/unit_id2`
  berasal dari `Trans_DeliveryOrderDT`.

---

## Hak Akses (Permissions)

- `do_execute.view`: Digunakan untuk membuka list & detail halaman Delivery Order Execute.
- `do_execute.add`: Digunakan untuk mengeksekusi Delivery Order (tombol eksekusi & submit API).

---

## Status Pengujian & Integrasi
- ✅ Penyesuaian alur 4 tahap: Pilih DO -> Detail DO (Input Vehicle & Driver) -> Scan Barang -> Ringkasan & Submit.
- ✅ Penambahan Alamat Pengiriman (`shipment_address`) pada card Header DO.
- ✅ Pemilih Kendaraan & Driver terpaginasi dengan validasi wajib sebelum scan.
- ✅ Tampilan No. Batch ber-badge QR ikon & Kode Unit (`UnitID`).
- ✅ Eliminasi error *RenderFlex overflowed by 60 pixels on the right* pada card detail.
- ✅ Pengujian pembacaan hardware scanner Zebra (HID Wedge stream & `ScannerCaptureField`).
- ✅ Pengujian penolakan eksekusi jika scan barang belum 100% Sesuai.
