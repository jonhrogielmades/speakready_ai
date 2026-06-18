@extends('layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="color:var(--tx);font-weight:700">Performance Reports</h4>
            <p style="color:var(--tx3)">Export your interview analytics to share with mentors or coaches.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:32px;text-align:center;height:100%">
                <div style="width:64px;height:64px;background:rgba(139,92,246,.15);color:#a78bfa;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.8rem">
                    <i class="fa-solid fa-file-pdf"></i>
                </div>
                <h5 style="color:var(--tx)">Comprehensive Readiness Report</h5>
                <p style="color:var(--tx3);font-size:.9rem;margin-bottom:24px">A full PDF breakdown of your strengths, weaknesses, and skill improvements across all your practice sessions.</p>
                <button class="btn bgrd px-4 py-2" onclick="alert('PDF Generation will be implemented in a future update.')"><i class="fa-solid fa-download me-2"></i> Download PDF</button>
            </div>
        </div>
        
        <div class="col-md-6">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:32px;text-align:center;height:100%">
                <div style="width:64px;height:64px;background:rgba(52,211,153,.15);color:#34d399;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.8rem">
                    <i class="fa-solid fa-file-excel"></i>
                </div>
                <h5 style="color:var(--tx)">Raw Session Data</h5>
                <p style="color:var(--tx3);font-size:.9rem;margin-bottom:24px">Export an Excel spreadsheet containing the raw scores and metrics for every single mock interview question you've answered.</p>
                <button class="btn btn-outline-primary px-4 py-2" onclick="alert('CSV Export will be implemented in a future update.')"><i class="fa-solid fa-download me-2"></i> Export CSV</button>
            </div>
        </div>
    </div>
</div>
@endsection
