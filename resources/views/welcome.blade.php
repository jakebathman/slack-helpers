@extends('layouts.app')

@section('body')
@if (session('status'))
<div class="flex justify-center items-center p-3 mt-3">
<div class="bg-teal-lightest border-t-4 border-teal rounded-b text-teal-darkest px-4 py-3 shadow-md" role="alert">
    <div class="flex">
        <div class="py-1"><svg class="fill-current h-6 w-6 text-teal mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
        <div>
            <p class="font-bold">Nice! 🎉</p>
            <p class="text-sm">
                {{ session('status') }}
            </p>
        </div>
    </div>
</div>
</div>
@endif

<div class="h-100 p-10 flex items-center justify-center">
    <div class="block">
        <h1 class="text-5xl text-purple font-sans">
            <img class="h-16 w-16" src="img/slack.png" alt="Slack logo">
            Slack Things
            <img class="h-16 w-16" src="svg/tighten.svg" alt="Tighten logo">
        </h1>
    </div>
</div>
<div class="justify-center p-10">
    <h2 class="text-3xl text-teal font-sans text-center">Install @IsInBot</h2>
    <div class="text-center p-3">
        <a href="https://slack.com/oauth/authorize?client_id=423337616818.437363470321&scope=chat:write:bot,channels:history,commands,users:read&redirect_uri={{ route('oauth.redirect') }}"><img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>
    </div>

</div>
@endsection
