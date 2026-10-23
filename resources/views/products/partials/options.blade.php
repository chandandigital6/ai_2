@php
    $optionInput = $optionInput ?? 'values';
    $chosenOptions = session()->hasOldInput($optionInput === 'attribute_values' ? 'attribute_values_present' : '_token') ? old($optionInput, []) : $selectedValues;
    $chosenOptions = array_map('intval', is_array($chosenOptions) ? $chosenOptions : []);
@endphp
<div data-option-picker>
    <div class="pc-options-top"><p class="pc-help">Choose all values available for this product. Example: Colour → Red, Blue; Size → M, L.</p><span class="pc-badge"><span data-option-count>{{ count($chosenOptions) }}</span> selected</span></div>
    @if($attributes->isNotEmpty())<label class="pc-field" style="margin-top:16px">Find an attribute<input type="search" data-option-search placeholder="Search colour, size, material…"></label>@endif
    @forelse($attributes as $attribute)
    <div class="pc-options-group" data-option-group data-option-name="{{ mb_strtolower($attribute->name.' '.$attribute->values->pluck('value')->join(' ')) }}"><h3>{{ $attribute->name }} <span class="pc-muted">{{ $attribute->business_id ? 'Your attribute' : 'Shared attribute' }}</span></h3><div class="pc-chips">@foreach($attribute->values as $value)<label class="pc-chip"><input type="checkbox" name="{{ $optionInput }}[]" value="{{ $value->id }}" @checked(in_array((int)$value->id,$chosenOptions,true)) @disabled(!($canEdit ?? true))><span>{{ $value->value }}</span></label>@endforeach</div></div>
    @empty<div class="pc-alert">No attributes with values are available yet. A simple product can be saved without attributes.</div>@endforelse
    <p class="pc-help" data-no-options hidden>No attributes match your search. Clear the search to show all.</p>
</div>
