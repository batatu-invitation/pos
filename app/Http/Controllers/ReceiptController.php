<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\CapabilityProfile;

class ReceiptController extends Controller
{
    public function print($saleId)
    {
        $sale = Sale::with(['items', 'customer', 'user'])->findOrFail($saleId);

        try {
            // Setup the printer
            if (app()->runningUnitTests()) {
                // Use Dummy connector for testing
                $connector = new DummyPrintConnector();
            } else {
                $printerListOutput = shell_exec("lpstat -a | awk '{print $1}'");
                $installedPrinters = explode("\n", trim($printerListOutput));

                // 3. Daftar komprehensif merk & tipe printer (Urutan Prioritas)
                $searchTerms = [
                    // --- EPSON SERIES ---
                    'EPSON_L3250',
                    'EPSON_L3110',
                    'EPSON_L121',
                    'EPSON_LX-310',
                    'TM-T82',
                    'TM-T81',
                    'TM-T88',
                    'TM-U220',
                    'TM-P20',
                    'TM-T20',
                    'TM-T70',
                    'TM-U295',
                    'TM-U590',
                    'EU-T332C',
                    'FX-890',

                    // --- XPRINTER SERIES ---
                    'XP-N160',
                    'XP-58',
                    'XP-Q800',
                    'XPRINTER',
                    'XP-365B',
                    'XP-80C',
                    'XP-90',
                    'XP-Q20011',
                    'F-900',

                    // --- ZJIANG & EXCELVAN ---
                    'ZJ-58',
                    'ZJ-5870',
                    'ZJ-5890',
                    'ZJ-8220',
                    'ZJ-8250',
                    'NT-58H',
                    'HOP-E200',
                    'HOP-E58',
                    'HOP-E801',
                    'EXCELVAN',

                    // --- BIXOLON & STAR ---
                    'BIXOLON',
                    'SRP-350',
                    'STAR_MCP',
                    'BSC10',
                    'TSP100',
                    'TSP-650',
                    'TUP-592',

                    // --- RONGTA & GAINSCHA ---
                    'RP326US',
                    'RP58-U',
                    'RP80USE',
                    'GP-2120',
                    'GP-5890',
                    'GP-U80300',
                    'GP-U80160',

                    // --- LIST TAMBAHAN (BRAND LAIN) ---
                    '3NSTAR_RPT-008',
                    'APPPOS80AM',
                    'AURES_ODP',
                    'BEMATECH',
                    'BIRCH_PRP',
                    'BLACK_COPPER',
                    'CHD_TH-305N',
                    'CITIZEN_CBM',
                    'CITIZEN_CT-S310',
                    'DAPPER_GEYI',
                    'DARUMA_DR800',
                    'DR-MP200',
                    'EPOS_TEP',
                    'ELGIN_I9',
                    'EVERYCOM_EC-58',
                    'HOIN_HOP',
                    'ITHACA_ITHERM',
                    'HASAR_HTP',
                    'METAPACE',
                    'NEXA_PX700',
                    'NYEAR_NP100',
                    'OKI_RT322',
                    'ORIENT_BTP',
                    'PARTNER_TECH',
                    'POSLIGNE_ODP',
                    'QPOS_Q58M',
                    'SAM4S_GIANT',
                    'SENOR_TP-100',
                    'SEWOO_SLK',
                    'SEYPOS_PRP',
                    'SNBC_BTP',
                    'SOLUX_SX',
                    'SICAR_POS-80',
                    'SILICON_SP',
                    'SPRT_SP-POS',
                    'TVS_RP45',
                    'VENUS_V248T',
                    'XEUMIOR_SM-8330',

                    // --- COMMON & FALLBACK ---
                    'CANON',
                    'THERMAL',
                    'RECEIPT',
                    'USB_PRINTER',
                    'POS-80',
                    'POS-58'
                ];

                $targetPrinter = null;

                // 4. Logika Pencarian Otomatis
                foreach ($searchTerms as $term) {
                    foreach ($installedPrinters as $p) {
                        if (!empty($p) && stripos($p, $term) !== false) {
                            $targetPrinter = $p;
                            break 2; // Keluar dari kedua loop jika ketemu
                        }
                    }
                }

                // 5. Gunakan printer pertama jika tidak ada yang cocok di list, atau error jika kosong
                if (!$targetPrinter) {
                    $targetPrinter = !empty($installedPrinters[0]) ? $installedPrinters[0] : null;
                }

                if (!$targetPrinter) {
                    return response()->json(['status' => 'error', 'message' => 'Printer tidak terdeteksi di sistem.']);
                }

                // dd($targetPrinter);

                $connector = new CupsPrintConnector($targetPrinter);
            }

            $profile = CapabilityProfile::load("default");
            $printer = new Printer($connector, $profile);

            // Header
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("Modern POS\n");
            $printer->setEmphasis(false);
            $printer->text("Jl. Contoh No. 123, Jakarta\n");
            $printer->text("Telp: 021-555-1234\n");
            $printer->feed();

            // Information
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(__('Invoice No') . " : " . $sale->invoice_number . "\n");
            $printer->text(__('Date') . "     : " . $sale->created_at->format('d-m-Y H:i') . "\n");
            $printer->text(__('Cashier') . "       : " . ($sale->user->name ?? 'Admin') . "\n");
            $printer->text(__('Customer') . "   : " . ($sale->customer->name ?? 'Guest') . "\n");
            $printer->text("--------------------------------\n");

            // Items
            foreach ($sale->items as $item) {
                $printer->text($item->product_name . "\n");
                $qtyPrice = sprintf("%sx %s", $item->quantity, number_format($item->price, 0, ',', '.'));
                $subtotal = number_format($item->quantity * $item->price, 0, ',', '.');
                $this->printRow($printer, $qtyPrice, $subtotal);
            }

            $printer->text("--------------------------------\n");

            // Totals
            $this->printRow($printer, __('Subtotal'), number_format($sale->subtotal, 0, ',', '.'));

            if ($sale->tax > 0) {
                $this->printRow($printer, __('Tax'), number_format($sale->tax, 0, ',', '.'));
            }

            if ($sale->discount > 0) {
                $this->printRow($printer, __('Discount'), "-" . number_format($sale->discount, 0, ',', '.'));
            }

            $printer->setEmphasis(true);
            $this->printRow($printer, __('TOTAL'), number_format($sale->total_amount, 0, ',', '.'));
            $printer->setEmphasis(false);

            $printer->text("--------------------------------\n");

            $this->printRow($printer, __('Pay'), number_format($sale->cash_received, 0, ',', '.'));
            $this->printRow($printer, __('Change'), number_format($sale->change_amount, 0, ',', '.'));

            // Footer
            $printer->feed(2);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text(__('Thank You') . "\n");
            $printer->text(__('Items purchased cannot be exchanged or returned.') . "\n");
            $printer->feed(3);
            $printer->cut();

            $printer->close();

            // Return success response (JSON for AJAX or Redirect for standard request)
            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Receipt sent to printer']);
            }
            return redirect()->back()->with('success', 'Receipt sent to printer');
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Receipt Printing Error: " . $e->getMessage());

            if (request()->wantsJson()) {
                return response()->json(['error' => 'Printing failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Printing failed: ' . $e->getMessage());
        }
    }

    private function printRow($printer, $label, $value)
    {
        // 32 columns for standard thermal paper (sometimes 42 or 48)
        // Adjust width based on your printer settings
        $width = 32;
        $len = $width - strlen($label) - strlen($value);
        $len = $len < 0 ? 0 : $len;
        $printer->text($label . str_repeat(" ", $len) . $value . "\n");
    }
}
