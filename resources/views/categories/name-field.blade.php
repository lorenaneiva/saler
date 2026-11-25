<label for="name" class="font-medium">Nome</label>
<input id="name" name="name" type="text" required maxlength="255"
    value="{{ old('name', $category->name ?? '') }}"
    @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
    class="w-full rounded-lg border border-stone-400 bg-white px-3 py-2">
@error('name')
    <p id="name-error" class="text-sm text-red-800">{{ $message }}</p>
@enderror
