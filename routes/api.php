<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ERP Integration API Endpoints
Route::prefix('erp')->name('erp.')->group(function () {
    // Invoice sync from ERP
    Route::post('/invoices/sync', function (Request $request) {
        // Log incoming request for debugging
        \Log::info('ERP Invoice Sync Request Received', [
            'data' => $request->all(),
            'ip' => $request->ip(),
        ]);

        try {
            // This endpoint receives invoice data from ERP
            $request->validate([
                'erp_invoice_id' => 'required|string',
                'student_id' => 'required|string',
                'invoice_number' => 'required|string',
                'total_amount' => 'required|numeric',
                'academic_year' => 'required|string',
                'semester' => 'nullable|string',
            ]);

            $student = \App\Models\Student::where('student_id', $request->student_id)->first();
            
            if (!$student) {
                \Log::warning('ERP Invoice Sync Failed - Student Not Found', [
                    'student_id' => $request->student_id,
                    'erp_invoice_id' => $request->erp_invoice_id,
                ]);
                return response()->json(['error' => 'Student not found'], 404);
            }

            $invoice = \App\Models\Invoice::updateOrCreate(
                ['erp_invoice_id' => $request->erp_invoice_id],
                [
                    'student_id' => $student->id,
                    'invoice_number' => $request->invoice_number,
                    'invoice_type' => $request->invoice_type ?? 'tuition',
                    'academic_year' => $request->academic_year,
                    'semester' => $request->semester,
                    'total_amount' => $request->total_amount,
                    'paid_amount' => $request->paid_amount ?? 0,
                    'balance' => $request->balance ?? $request->total_amount,
                    'status' => $request->status ?? 'pending',
                    'due_date' => $request->due_date ?? now()->addDays(30),
                    'issued_date' => $request->issued_date ?? now(),
                    'line_items' => $request->line_items ?? [],
                    'synced_from_erp' => true,
                    'synced_at' => now(),
                ]
            );

            \Log::info('ERP Invoice Synced Successfully', [
                'invoice_id' => $invoice->id,
                'erp_invoice_id' => $request->erp_invoice_id,
                'student_id' => $request->student_id,
            ]);

            return response()->json([
                'success' => true,
                'invoice_id' => $invoice->id,
                'message' => 'Invoice synced successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('ERP Invoice Sync Validation Error', [
                'errors' => $e->errors(),
                'data' => $request->all(),
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('ERP Invoice Sync Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    });

    // Payment confirmation from ERP
    Route::post('/payments/confirm', function (Request $request) {
        $request->validate([
            'payment_reference' => 'required|string',
            'erp_payment_id' => 'required|string',
            'status' => 'required|in:completed,failed',
        ]);

        $payment = \App\Models\Payment::where('payment_reference', $request->payment_reference)->first();
        
        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        $payment->update([
            'erp_payment_id' => $request->erp_payment_id,
            'status' => $request->status,
            'erp_status' => 'synced',
            'erp_synced_at' => now(),
            'erp_response' => json_encode($request->all()),
        ]);

        if ($payment->invoice) {
            $payment->invoice->updateBalance();
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed',
        ]);
    });

    // Get student balance (for ERP to check)
    Route::get('/students/{student_id}/balance', function ($studentId) {
        $student = \App\Models\Student::where('student_id', $studentId)->first();
        
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        return response()->json([
            'student_id' => $student->student_id,
            'total_balance' => $student->getTotalBalance(),
            'total_paid' => $student->getTotalPaid(),
            'payment_percentage' => round($student->getPaymentPercentage(), 2),
        ]);
    });
});
