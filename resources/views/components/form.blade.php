@extends('layouts.app')
@section('title', $component->exists ? 'Edit component' : 'Add component')
@section('content')
<div class="page-heading"><div><p class="eyebrow">COMPONENTS</p><h1>{{ $component->exists ? 'Edit component' : 'Add component' }}</h1></div><a class="button ghost" href="{{ $component->exists ? route('components.show', $component) : route('components.index') }}">← Back</a></div>
<form class="panel form-panel" method="post" enctype="multipart/form-data" action="{{ $component->exists ? route('components.update', $component) : route('components.store') }}">
    @csrf @if($component->exists)@method('put')@endif
    <div class="form-grid">
        <label>Name *<input name="name" value="{{ old('name', $component->name) }}" required maxlength="255"></label>
        <label>SKU / code<input name="sku" value="{{ old('sku', $component->sku) }}" maxlength="255"></label>
        <label>Category<select name="category_id"><option value="">Uncategorised</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $component->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label>Manufacturer<input name="manufacturer" value="{{ old('manufacturer', $component->manufacturer) }}"></label>
        <label>Model<input name="model" value="{{ old('model', $component->model) }}"></label>
        <label>Reorder threshold *<input type="number" name="reorder_threshold" min="0" value="{{ old('reorder_threshold', $component->reorder_threshold ?? 0) }}" required></label>
        <label class="span-two">Datasheet URL<input type="url" name="datasheet_url" value="{{ old('datasheet_url', $component->datasheet_url) }}" placeholder="https://…"></label>
        <label class="span-two">Description<textarea name="description" rows="4">{{ old('description', $component->description) }}</textarea></label>
        <label class="span-two">Component image (JPG, PNG or WebP; max 2 MB)<input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-input>@if($component->image_path)<img class="image-preview" data-image-preview src="{{ \Illuminate\Support\Facades\Storage::url($component->image_path) }}" alt="Current component image">@else<img class="image-preview hidden" data-image-preview alt="Selected image preview">@endif</label>
        <fieldset class="span-two spec-fieldset"><legend>Technical specifications</legend><div data-spec-list>
            @foreach(old('attributes', $component->attributes ?? []) as $index => $attribute)
            <div class="spec-row"><input name="attributes[{{ $index }}][name]" value="{{ $attribute['name'] ?? '' }}" placeholder="Specification name"><input name="attributes[{{ $index }}][value]" value="{{ $attribute['value'] ?? '' }}" placeholder="Value"><button type="button" class="button ghost" data-remove-row>Remove</button></div>
            @endforeach
        </div><button type="button" class="button secondary" data-add-spec>Add specification</button></fieldset>
    </div>
    <div class="form-actions"><a class="button ghost" href="{{ $component->exists ? route('components.show', $component) : route('components.index') }}">Cancel</a><button class="button primary" type="submit">Save component</button></div>
</form>
@endsection
