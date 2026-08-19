@use('Core\Branding\BrandingResolver')
@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logo = app(BrandingResolver::class)->url('branding.logo'))
<img src="{{ $logo }}" class="logo" alt="{{ $slot }}">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
