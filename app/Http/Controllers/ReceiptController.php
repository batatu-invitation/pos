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
                    // EPSON Series
                    'EPSON_L3250',
                    'EPSON_L3110',
                    'EPSON_L121',
                    'EPSON_LX-310',
                    'TM-T82',
                    'TM-T81',
                    'TM-T88',
                    'TM-U220',
                    'TM-P20',
                    // XPRINTER & China Brands
                    'XP-N160',
                    'XP-58',
                    'XP-Q800',
                    'XPRINTER',
                    'POS-80',
                    'POS-58',
                    'ZJ-58',
                    'RPP02N',
                    'GP-58',
                    'GP-80',
                    'RP-80',
                    // Other Brands
                    'CANON',
                    'BIXOLON',
                    'STAR_MCP',
                    'CITIZEN',
                    // Fallback keywords
                    'THERMAL',
                    'RECEIPT',
                    'USB_PRINTER'
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
