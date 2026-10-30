@props(['name'])
<span {{ $attributes->merge(['class' => 'ui-icon', 'style' => "--icon-url: url('".asset('icons/'.$name.'.svg')."')"]) }} aria-hidden="true"></span>
