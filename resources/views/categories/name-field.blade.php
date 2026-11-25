@php($categoryName = old('name', $category->name ?? ''))
<label for="name" class="font-medium">Nome <x-required-mark /></label>
<input id="name" name="name" type="text" required maxlength="255"
    value="{{ is_scalar($categoryName) ? $categoryName : '' }}"
    @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
    class="field">
@error('name')
    <p id="name-error" class="text-sm text-red-800">{{ $message }}</p>
@enderror
