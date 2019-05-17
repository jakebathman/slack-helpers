workflow "Deploy on Forge" {
  on = "push"
  resolves = ["Trigger Forge Deployment"]
}

action "Filter for master" {
  uses = "actions/bin/filter@master"
  args = "branch master"
}

action "Trigger Forge Deployment" {
  uses = "swinton/httpie.action@master"
  needs = ["Filter for master"]
  args = ["GET", "FORGE_DEPLOY_URL"]
  secrets = ["FORGE_DEPLOY_URL"]
}
