### Tighten Slack Helpers

Note: this is an internal project. Don't try to set it up on your own. If you're interested in using it on your workspace, reach out to me on Twitter [@jakebathman](https://twitter.com/jakebathman) and I'd be happy to help you out.

## IsInBot

This bot provides three services: 
- slash command `/IsIn @person` to return the status of that person in Slack
- slash command `/staff` to return all Tighten staff with their current status
- an API endpoint at `/api/staff-in` to return a JSON encoded list of all staff currently @in

## Authentication

The bot uses Slack's app integrations when installed to a workspace, and manages its own tokens in the `tokens` table.

The API endpoints use an implicit token

## Slash commands

The slash commands are tied to the bot **IsInBot**, which can be installed to the workspace:

<a href="https://slack.com/oauth/authorize?scope=commands,bot&client_id=423337616818.437363470321"><img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>
