@component('mail::message')
# Introduction

The body of your message.<br>
{{ $testdata }}

@component('mail::button', ['url' => ''])
Button Text
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
