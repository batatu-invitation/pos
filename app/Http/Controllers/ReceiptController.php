<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function print($saleId)
    {
        $sale = Sale::with(['items', 'customer', 'user'])->findOrFail($saleId);

        $pdf = Pdf::loadView('pdf.receipt', compact('sale'));

        // Set paper size to 80mm width (approx 226pt) and auto height
        // Standard thermal receipt width is usually 80mm or 58mm.
        // DomPDF custom paper size: array(0, 0, width_in_points, height_in_points)
        // 1mm = 2.83465 points
        // 80mm = 226.77 points
        $pdf->setPaper([0, 0, 226.77, 800], 'portrait');

        $pdf->render();
        $canvas = $pdf->getCanvas();
        $script = 'this.print({bUI: true, bSilent: false, bShrinkToFit: true});';
        $canvas->get_cpdf()->addJavascript($script);

        return $pdf->stream('receipt-' . $sale->invoice_number . '.pdf');
    }
}
