<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
@foreach($permissions->groupBy(fn($p)=>explode('.',$p->name)[0]) as $group=>$entries)
<fieldset class="rounded-xl border p-4"><legend class="px-1 font-semibold">{{ ucwords(str_replace('_',' ',$group)) }}</legend>
@foreach($entries as $permission)<label class="my-2 flex items-center gap-2 text-sm"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id,$selected))> {{ str_replace('_',' ',implode(' ',array_slice(explode('.',$permission->name),1))) }}</label>@endforeach
</fieldset>@endforeach
</div>