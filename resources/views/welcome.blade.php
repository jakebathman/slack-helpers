@extends('layouts.app')

@section('body')
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
        <a href="https://slack.com/oauth/authorize?scope=commands,bot&client_id=423337616818.437363470321"><img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>
    </div>

</div>
@endsection
