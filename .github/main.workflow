workflow "Deploy on Forge" {
  on = "push"
  resolves = ["Trigger Forge Deployment"]
}

action "Filter for master" {
  uses = "actions/bin/filter@master"
  args = "branch master"
}

action "Trigger Forge Deployment" {
  needs = ["Filter for master"]
  uses = "swinton/httpie.action@master"
  args = ["GET", "$FORGE_DEPLOY_URL"]
  secrets = ["FORGE_DEPLOY_URL"]
}
