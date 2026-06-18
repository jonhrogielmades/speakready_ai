@extends('layouts.admin')
@section('content')
<div class="db-section active" id="sec-admin-questions">
                  <div class="mb-4 d-flex justify-content-between align-items-center">
                     <div>
                        <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Question Bank</h4>
                        <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage interview questions.</p>
                     </div>
                     <button class="bgrd btn px-3 py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#addQuestionModal"><i class="fa-solid fa-plus me-1"></i> Add Question</button>
                  </div>
                  <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;overflow-x:auto;">
                     <table class="table table-dark table-hover mb-0" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--tx)">
                        <thead>
                           <tr>
                              <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">ID</th>
                              <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Category ID</th>
                              <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Question Text</th>
                              <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Actions</th>
                           </tr>
                        </thead>
                        <tbody>
                           @foreach($questions as $q)
                           <tr>
                              <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ $q->id }}</td>
                              <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ $q->category_id }}</td>
                              <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ Str::limit($q->question_text, 50) }}</td>
                              <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                                 <button class="btn btn-sm btn-outline-primary" style="font-size:.7rem">Edit</button>
                                 <button class="btn btn-sm btn-outline-danger" style="font-size:.7rem">Delete</button>
                              </td>
                           </tr>
                           @endforeach
                        </tbody>
                     </table>
                  </div>
               </div>
<div class="modal fade" id="addQuestionModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
            <div class="modal-dialog">
               <div class="modal-content" style="border:1px solid var(--bd)">
                  <form action="{{ route('admin.questions.store') }}" method="POST">
                     @csrf
                     <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                        <h5 class="modal-title" style="color:var(--tx)">Add Question</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                     </div>
                     <div class="modal-body">
                        <label class="olbl">Category ID</label>
                        <select class="oinp mb-3" name="category_id" required>
                           @foreach($categories as $c)
                              <option value="{{ $c->id }}">{{ $c->title }}</option>
                           @endforeach
                        </select>
                        <label class="olbl">Question Text</label>
                        <textarea class="oinp mb-3" name="question_text" rows="3" required></textarea>
                     </div>
                     <div class="modal-footer" style="border-top:1px solid var(--bd)">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="bgrd btn px-4">Save</button>
                     </div>
                  </form>
               </div>
            </div>
         </div>

         
@endsection