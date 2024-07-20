@component('mail::message')
<div>
    <p>ERROR A LA WEB</p>
    <br>
    <br>
    <p>{{ var_dump($content) }}</p>
    <br><br>
</div>
@component('mail::button', ['url' => ''])
    Button Text
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
