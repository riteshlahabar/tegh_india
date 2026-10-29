@php $invoice = $invoice ?? null; @endphp
@if($invoice)
<div class="row-actions" onclick="event.stopPropagation();">
  <button type="button" class="act-btn call" title="Call" data-invoice-id="{{ $invoice->id }}" onclick="openCallModal({{ $invoice->id }})"><i class="fa-solid fa-phone"></i></button>
  <button type="button" class="act-btn wa" title="WhatsApp" data-invoice-id="{{ $invoice->id }}" onclick="openWaModal({{ $invoice->id }})"><i class="fa-brands fa-whatsapp"></i></button>
  <button type="button" class="act-btn mail" title="Email" data-invoice-id="{{ $invoice->id }}" onclick="openEmailModal({{ $invoice->id }})"><i class="fa-regular fa-envelope"></i></button>
  <button type="button" class="act-btn view" title="View" onclick="openPartyDrawer({{ $invoice->party_id }})"><i class="fa-regular fa-eye"></i></button>
</div>
@endif
